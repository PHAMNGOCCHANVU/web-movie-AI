<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Movie;
use App\Models\Rating;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_email_otp_before_creating_user(): void
    {
        $this->seedRoles();
        Notification::fake();

        $payload = [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(202)
            ->assertJsonPath('data.email', $payload['email']);

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->assertDatabaseHas('registration_otps', ['email' => $payload['email']]);

        $otp = null;
        Notification::assertSentOnDemand(
            RegistrationOtpNotification::class,
            function (RegistrationOtpNotification $notification) use (&$otp) {
                $otp = $notification->otp;

                return true;
            }
        );

        $this->postJson('/api/auth/verify-registration-otp', [
            'email' => $payload['email'],
            'otp' => '000000',
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'invalid_otp');

        $this->postJson('/api/auth/verify-registration-otp', [
            'email' => $payload['email'],
            'otp' => $otp,
        ])->assertCreated()
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.token', fn ($token) => filled($token));

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('registration_otps', ['email' => $payload['email']]);
    }

    public function test_locked_user_cannot_login(): void
    {
        $this->seedRoles();

        User::factory()->create([
            'role_id' => 2,
            'email' => 'locked@example.com',
            'password' => Hash::make('password123'),
            'is_locked' => true,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'locked@example.com',
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('error', 'account_locked');
    }

    public function test_password_can_be_reset_with_email_otp(): void
    {
        $this->seedRoles();
        Notification::fake();

        $user = User::factory()->create([
            'role_id' => 2,
            'email' => 'reset@example.com',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk()
            ->assertJsonPath(
                'message',
                'Nếu email tồn tại, mã OTP 6 số đã được gửi và có hiệu lực trong 10 phút.'
            );

        $otp = null;
        Notification::assertSentOnDemand(
            PasswordResetOtpNotification::class,
            function (PasswordResetOtpNotification $notification) use (&$otp) {
                $otp = $notification->otp;

                return true;
            }
        );

        $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '000000',
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'invalid_otp');

        $verifyResponse = $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertOk()
            ->assertJsonPath('message', 'Xác thực OTP thành công.');

        $resetToken = $verifyResponse->json('data.reset_token');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk()
            ->assertJsonPath('message', 'Mật khẩu đã được đặt lại thành công.');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
    }

    public function test_movie_list_accepts_limit_and_ignores_type_all(): void
    {
        $this->createMovie(['slug' => 'movie-1']);
        $this->createMovie(['slug' => 'movie-2']);

        $this->getJson('/api/movies?limit=1&type=all')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.per_page', 1);
    }

    public function test_watch_progress_rejects_episode_from_another_movie(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie(['slug' => 'movie-1']);
        $otherMovie = $this->createMovie(['slug' => 'movie-2']);
        $episode = $this->createEpisode($otherMovie);

        $this->postJson('/api/user/watch-history', [
            'movie_id' => $movie->id,
            'episode_id' => $episode->id,
            'progress_seconds' => 120,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('episode_id');

        $this->assertDatabaseMissing('movie_user', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_deleting_watch_history_preserves_watchlist_entry(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();
        $episode = $this->createEpisode($movie);

        $user->movies()->attach($movie->id, [
            'is_favorite' => true,
            'watch_progress_seconds' => 120,
            'episode_id' => $episode->id,
        ]);

        $this->deleteJson("/api/user/watch-history/{$movie->id}")
            ->assertOk();

        $this->assertDatabaseHas('movie_user', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'is_favorite' => true,
            'watch_progress_seconds' => 0,
            'episode_id' => null,
        ]);
    }

    public function test_watch_history_returns_current_episode(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();
        $episode = $this->createEpisode($movie);

        $user->movies()->attach($movie->id, [
            'watch_progress_seconds' => 120,
            'episode_id' => $episode->id,
        ]);

        $this->getJson('/api/user/watch-history')
            ->assertOk()
            ->assertJsonPath('data.0.current_episode.id', $episode->id)
            ->assertJsonPath('data.0.pivot.watch_progress_seconds', 120);
    }

    public function test_watch_history_stores_and_returns_duration(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();
        $episode = $this->createEpisode($movie);

        $this->postJson('/api/user/watch-history', [
            'movie_id' => $movie->id,
            'episode_id' => $episode->id,
            'progress_seconds' => 120,
            'duration_seconds' => 5400,
        ])->assertOk();

        $this->assertDatabaseHas('movie_user', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'watch_progress_seconds' => 120,
            'duration_seconds' => 5400,
        ]);

        $this->getJson('/api/user/watch-history')
            ->assertJsonPath('data.0.pivot.duration_seconds', 5400);
    }

    public function test_profile_can_update_phone(): void
    {
        $user = $this->authenticatedUser();

        $this->putJson('/api/user/profile', [
            'name' => 'Updated User',
            'phone' => '0901234567',
        ])->assertOk()
            ->assertJsonPath('data.phone', '0901234567');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'phone' => '0901234567',
        ]);
    }

    public function test_rating_summary_contains_current_user_score(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();

        Rating::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'score' => 8,
        ]);

        $this->getJson("/api/movies/{$movie->id}/ratings")
            ->assertOk()
            ->assertJsonPath('data.user_score', 8);
    }

    public function test_movie_list_contains_average_rating(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();

        Rating::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'score' => 8,
        ]);

        $this->getJson('/api/movies')
            ->assertOk()
            ->assertJsonPath('data.data.0.ratings_avg_score', 8);
    }

    public function test_payment_request_rejects_mismatched_billing_cycle(): void
    {
        $this->authenticatedUser();

        $this->postJson('/api/payment/vnpay/create', [
            'plan_code' => 'standard_monthly',
            'billing_cycle' => 'yearly',
            'transaction_type' => 'purchase',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('billing_cycle');
    }

    public function test_vnpay_create_payment_uses_sandbox_config(): void
    {
        config([
            'vnpay.tmn_code' => 'RJXNTQ8K',
            'vnpay.hash_secret' => 'sandbox-secret',
            'vnpay.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'vnpay.return_url' => 'http://localhost:8000/#/payment/return',
        ]);
        $user = $this->authenticatedUser();
        $plan = $this->createSubscriptionPlan();

        $this->postJson('/api/payment/vnpay/create', [
            'plan_code' => $plan->plan_code,
            'billing_cycle' => 'monthly',
            'transaction_type' => 'purchase',
        ])->assertOk()
            ->assertJsonPath('data.transaction_id', fn ($id) => filled($id))
            ->assertJsonPath('data.payment_url', fn ($url) => str_contains($url, 'sandbox.vnpayment.vn'))
            ->assertJsonPath('data.payment_url', fn ($url) => str_contains($url, 'vnp_TmnCode=RJXNTQ8K'))
            ->assertJsonPath('data.payment_url', fn ($url) => str_contains($url, 'vnp_SecureHash='));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => 49000,
        ]);
    }

    public function test_vnpay_return_activates_subscription_when_ipn_is_unavailable(): void
    {
        config(['vnpay.hash_secret' => 'sandbox-secret']);
        $user = $this->authenticatedUser();
        $plan = $this->createSubscriptionPlan();
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'transaction_type' => 'purchase',
            'vnp_txn_ref' => 'TXNTEST123',
            'amount' => 49000,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'is_auto_renewal' => false,
        ]);
        $params = $this->signedVnPayParams([
            'vnp_Amount' => 49000 * 100,
            'vnp_BankCode' => 'NCB',
            'vnp_PayDate' => now()->format('YmdHis'),
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => 'RJXNTQ8K',
            'vnp_TransactionNo' => '14123456',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $transaction->vnp_txn_ref,
        ], 'sandbox-secret');

        $this->getJson('/api/payment/vnpay/return?'.http_build_query($params))
            ->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.is_success', true)
            ->assertJsonPath('data.transaction_id', $transaction->id)
            ->assertJsonPath('data.status', 'success');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'success',
            'vnp_transaction_no' => '14123456',
            'vnp_bank_code' => 'NCB',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
        ]);
        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'action' => 'purchased',
        ]);
    }

    public function test_ai_chat_returns_clickable_movie_recommendations(): void
    {
        $this->authenticatedUser();
        $genreId = DB::table('genres')->insertGetId([
            'name' => 'Tình Cảm',
            'slug' => 'tinh-cam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $movie = $this->createMovie(['name' => 'Chuyện Tình Buồn']);
        DB::table('genre_movie')->insert([
            'genre_id' => $genreId,
            'movie_id' => $movie->id,
        ]);
        $this->fakeGemini([
            'response' => 'Mình đã chọn một phim tình cảm phù hợp cho bạn.',
            'intent' => 'movie_recommendation',
            'recommendations' => [
                ['movie_id' => $movie->id, 'reason' => 'Đúng thể loại tình cảm buồn.'],
            ],
            'suggestions' => ['Phim nhẹ nhàng hơn'],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Gợi ý phim tình cảm buồn',
        ])->assertOk()
            ->assertJsonPath('data.source', 'gemini')
            ->assertJsonPath('data.movies.0.id', $movie->id)
            ->assertJsonPath('data.movies.0.name', 'Chuyện Tình Buồn');

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], "ID:{$movie->id}")
            && data_get($request->data(), 'tools.0.google_search') !== null
            && $request['contents'][count($request['contents']) - 1]['parts'][0]['text']
                === 'Gợi ý phim tình cảm buồn');

        $this->getJson('/api/ai/chat/history')
            ->assertOk()
            ->assertJsonPath('data.0.recommended_movies.0.id', $movie->id);
    }

    public function test_ai_chat_responds_to_sadness_before_recommending_movies(): void
    {
        $this->authenticatedUser();
        $this->fakeGemini([
            'response' => 'Mình nghe bạn. Bạn muốn kể thêm một chút hay để mình chọn một bộ phim giúp bạn thư giãn?',
            'intent' => 'emotional_support',
            'recommendations' => [],
            'suggestions' => [
                'Gợi ý phim vui cho mình',
                'Mình muốn phim nhẹ nhàng',
                'Cho mình phim cảm động',
            ],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Chào bạn, hôm nay mình buồn quá',
        ])->assertOk()
            ->assertJsonPath('data.source', 'gemini')
            ->assertJsonCount(0, 'data.movies')
            ->assertJsonCount(3, 'data.suggestions')
            ->assertJsonPath(
                'data.response',
                fn ($response) => str_contains($response, 'Mình nghe bạn')
            );
    }

    public function test_ai_chat_prefers_uplifting_movies_after_sadness_follow_up(): void
    {
        $this->authenticatedUser();
        $comedyGenreId = DB::table('genres')->insertGetId([
            'name' => 'Hài Hước',
            'slug' => 'hai-huoc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $horrorGenreId = DB::table('genres')->insertGetId([
            'name' => 'Kinh Dị',
            'slug' => 'kinh-di',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $comedyMovie = $this->createMovie(['name' => 'Ngày Vui Trở Lại']);
        $horrorMovie = $this->createMovie(['name' => 'Đêm U Ám']);
        DB::table('genre_movie')->insert([
            ['genre_id' => $comedyGenreId, 'movie_id' => $comedyMovie->id],
            ['genre_id' => $horrorGenreId, 'movie_id' => $horrorMovie->id],
        ]);
        Http::fakeSequence()
            ->push($this->geminiPayload([
                'response' => 'Mình hiểu bạn đang buồn. Bạn muốn một bộ vui hay nhẹ nhàng?',
                'intent' => 'emotional_support',
                'recommendations' => [],
                'suggestions' => ['Gợi ý phim vui cho mình'],
            ]))
            ->push($this->geminiPayload([
                'response' => 'Mình chọn một bộ hài để giúp bạn đổi tâm trạng nhé.',
                'intent' => 'movie_recommendation',
                'recommendations' => [
                    ['movie_id' => $comedyMovie->id, 'reason' => 'Không khí vui vẻ và tích cực.'],
                ],
                'suggestions' => ['Thêm phim vui khác'],
            ]));

        $this->postJson('/api/ai/chat', [
            'message' => 'Hôm nay mình buồn quá',
        ])->assertOk();

        $this->postJson('/api/ai/chat', [
            'message' => 'Gợi ý phim vui cho mình',
        ])->assertOk()
            ->assertJsonPath('data.movies.0.id', $comedyMovie->id)
            ->assertJsonPath(
                'data.movies.0.recommendation_reason',
                fn ($reason) => str_contains($reason, 'vui vẻ')
            );
    }

    public function test_ai_chat_handles_happy_small_talk_without_forcing_movies(): void
    {
        $this->authenticatedUser();
        $this->fakeGemini([
            'response' => 'Nghe vui lây luôn đó. Có chuyện gì hay vậy?',
            'intent' => 'happy_small_talk',
            'recommendations' => [],
            'suggestions' => ['Gợi ý phim vui'],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Hôm nay vui quá ta ơi',
        ])->assertOk()
            ->assertJsonCount(0, 'data.movies')
            ->assertJsonPath(
                'data.response',
                fn ($response) => str_contains($response, 'Nghe vui lây')
            );
    }

    public function test_ai_chat_handles_insult_without_misreading_it_as_horror(): void
    {
        $this->authenticatedUser();
        $this->fakeGemini([
            'response' => 'Mình vẫn muốn giúp, nhưng chúng ta nói chuyện nhẹ nhàng hơn nhé.',
            'intent' => 'boundary_setting',
            'recommendations' => [],
            'suggestions' => ['Tìm phim hành động'],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'chó mày',
        ])->assertOk()
            ->assertJsonCount(0, 'data.movies')
            ->assertJsonPath(
                'data.response',
                fn ($response) => str_contains($response, 'nói chuyện nhẹ nhàng hơn')
            );
    }

    public function test_ai_chat_explains_its_capabilities(): void
    {
        $this->authenticatedUser();
        $this->fakeGemini([
            'response' => 'Mình là trợ lý điện ảnh của CineON, có thể trò chuyện và chọn phim từ kho hiện có.',
            'intent' => 'capability_question',
            'recommendations' => [],
            'suggestions' => ['Gợi ý phim cho mình'],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Bạn là ai và làm được gì?',
        ])->assertOk()
            ->assertJsonCount(0, 'data.movies')
            ->assertJsonPath(
                'data.response',
                fn ($response) => str_contains($response, 'trợ lý điện ảnh của CineON')
            );
    }

    public function test_ai_chat_marks_fallback_only_when_gemini_fails(): void
    {
        $this->authenticatedUser();
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Unavailable'],
            ], 503),
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Gợi ý phim hành động',
        ])->assertOk()
            ->assertJsonPath('data.source', 'fallback')
            ->assertJsonPath(
                'data.response',
                'Trợ lý Gemini tạm thời không khả dụng. Bạn vui lòng thử lại sau.'
            );

        Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent'));
    }

    public function test_ai_chat_uses_a_second_gemini_model_when_primary_model_fails(): void
    {
        $this->authenticatedUser();
        config([
            'services.gemini.model' => 'gemini-3.5-flash',
            'services.gemini.fallback_model' => 'gemini-2.5-flash',
            'services.gemini.reserve_model' => null,
        ]);
        $this->app->forgetInstance(\App\Services\GeminiAiService::class);

        Http::fakeSequence()
            ->push(['error' => ['message' => 'Primary unavailable']], 503)
            ->push($this->geminiPayload([
                'response' => 'Mình vẫn đang hỗ trợ bạn bằng Gemini.',
                'intent' => 'general_conversation',
                'recommendations' => [],
                'suggestions' => ['Gợi ý phim cho mình'],
            ]));

        $this->postJson('/api/ai/chat', [
            'message' => 'Xin chào',
        ])->assertOk()
            ->assertJsonPath('data.source', 'gemini')
            ->assertJsonPath('data.model', 'gemini-2.5-flash')
            ->assertJsonPath(
                'data.response',
                'Mình vẫn đang hỗ trợ bạn bằng Gemini.'
            );

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/models/gemini-3.5-flash:generateContent'
        ) && data_get($request->data(), 'generationConfig.thinkingConfig.thinkingLevel') === 'medium');
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/models/gemini-2.5-flash:generateContent'
        ) && data_get($request->data(), 'generationConfig.thinkingConfig') === null);
    }

    public function test_ai_chat_continues_with_gemini_when_search_quota_is_exhausted(): void
    {
        $this->authenticatedUser();
        config([
            'services.gemini.model' => 'gemini-3.1-flash-lite',
            'services.gemini.reserve_model' => null,
            'services.gemini.fallback_model' => null,
        ]);
        $this->app->forgetInstance(\App\Services\GeminiAiService::class);

        Http::fakeSequence()
            ->push(['error' => ['message' => 'Search quota exhausted']], 429)
            ->push($this->geminiPayload([
                'response' => 'Mình vẫn có thể tư vấn từ dữ liệu CineON.',
                'intent' => 'movie_recommendation',
                'recommendations' => [],
                'suggestions' => [],
            ]));

        $this->postJson('/api/ai/chat', [
            'message' => 'Gợi ý phim vui cho mình',
        ])->assertOk()
            ->assertJsonPath('data.source', 'gemini')
            ->assertJsonPath('data.model', 'gemini-3.1-flash-lite')
            ->assertJsonPath('data.sources', []);

        Http::assertSentCount(2);
        $requests = Http::recorded();
        $this->assertNotNull(data_get($requests[0][0]->data(), 'tools.0.google_search'));
        $this->assertNull(data_get($requests[1][0]->data(), 'tools'));
    }

    public function test_ai_chat_ranks_by_intent_and_keeps_recommendation_reason(): void
    {
        $this->authenticatedUser();
        $romanceGenreId = DB::table('genres')->insertGetId([
            'name' => 'Romance',
            'slug' => 'tinh-cam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actionGenreId = DB::table('genres')->insertGetId([
            'name' => 'Action',
            'slug' => 'hanh-dong',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $romanceMovie = $this->createMovie([
            'name' => 'Sad Love Story',
            'content' => 'A moving romantic drama.',
        ]);
        $actionMovie = $this->createMovie([
            'name' => 'Final Battle',
            'content' => 'An intense action battle.',
        ]);
        DB::table('genre_movie')->insert([
            ['genre_id' => $romanceGenreId, 'movie_id' => $romanceMovie->id],
            ['genre_id' => $actionGenreId, 'movie_id' => $actionMovie->id],
        ]);
        $this->fakeGemini([
            'response' => 'Mình ưu tiên lựa chọn tình cảm có màu sắc buồn và cảm động.',
            'intent' => 'movie_recommendation',
            'recommendations' => [
                ['movie_id' => $romanceMovie->id, 'reason' => 'Tình cảm buồn và cảm động.'],
            ],
            'suggestions' => ['Phim chữa lành hơn'],
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'phim tinh cam buon va cam dong',
        ])->assertOk()
            ->assertJsonPath('data.movies.0.id', $romanceMovie->id)
            ->assertJsonPath('data.movies.0.match_score', fn ($score) => $score > 0)
            ->assertJsonPath(
                'data.movies.0.recommendation_reason',
                fn ($reason) => str_contains($reason, 'cảm động')
            );

        $this->getJson('/api/ai/chat/history')
            ->assertOk()
            ->assertJsonPath('data.0.recommended_movies.0.id', $romanceMovie->id)
            ->assertJsonPath(
                'data.0.recommended_movies.0.recommendation_reason',
                fn ($reason) => filled($reason)
            );
    }

    public function test_safe_comment_is_published_after_moderation_api(): void
    {
        $this->authenticatedUser();
        $movie = $this->createMovie();
        Http::fake([
            '*/moderate' => Http::response(
                $this->localModerationPayload('CLEAN', [
                    'CLEAN' => 0.98,
                    'OFFENSIVE' => 0.015,
                    'HATE' => 0.005,
                ])
            ),
        ]);

        $this->postJson("/api/movies/{$movie->id}/comments", [
            'content' => 'Phim rất hay và diễn viên diễn tốt.',
        ])->assertCreated()
            ->assertJsonPath('moderation_status', 'approved')
            ->assertJsonPath('data.is_hidden', false);

        $this->getJson("/api/movies/{$movie->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_negative_comment_waits_for_admin_but_is_not_deleted(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();
        Http::fake([
            '*/moderate' => Http::response(
                $this->localModerationPayload('HATE', [
                    'CLEAN' => 0.01,
                    'OFFENSIVE' => 0.08,
                    'HATE' => 0.91,
                ])
            ),
        ]);

        $response = $this->postJson("/api/movies/{$movie->id}/comments", [
            'content' => 'Nội dung đe dọa dùng để kiểm thử.',
        ])->assertCreated()
            ->assertJsonPath('moderation_status', 'pending_review')
            ->assertJsonPath('data.is_hidden', true);

        $this->assertDatabaseHas('comments', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'moderation_status' => 'pending_review',
            'is_hidden' => true,
        ]);
        $this->getJson("/api/movies/{$movie->id}/comments")
            ->assertJsonCount(0, 'data.data');
    }

    public function test_comment_waits_for_admin_when_moderation_api_is_unavailable(): void
    {
        $this->authenticatedUser();
        $movie = $this->createMovie();
        Http::fake([
            '*/moderate' => Http::response([
                'error' => ['message' => 'Unavailable'],
            ], 503),
        ]);

        $this->postJson("/api/movies/{$movie->id}/comments", [
            'content' => 'Phim khá hay.',
        ])->assertCreated()
            ->assertJsonPath('moderation_status', 'pending_review')
            ->assertJsonPath('data.is_hidden', true)
            ->assertJsonPath(
                'data.moderation_categories.error',
                'http_503'
            );
    }

    public function test_only_admin_can_review_moderated_comments(): void
    {
        $user = $this->authenticatedUser();
        $movie = $this->createMovie();
        $commentId = DB::table('comments')->insertGetId([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'content' => 'Bình luận đang chờ duyệt.',
            'moderation_status' => 'pending_review',
            'is_hidden' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/admin/comment-moderation')->assertForbidden();

        $admin = User::factory()->create(['role_id' => 1]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/comment-moderation')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $commentId);

        $this->patchJson("/api/admin/comment-moderation/{$commentId}", [
            'action' => 'approve',
            'reason' => 'Đã kiểm tra thủ công.',
        ])->assertOk()
            ->assertJsonPath('data.moderation_status', 'approved')
            ->assertJsonPath('data.is_hidden', false);

        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'moderation_status' => 'approved',
            'is_hidden' => false,
            'reviewed_by' => $admin->id,
        ]);
    }

    private function authenticatedUser(): User
    {
        $this->seedRoles();

        $user = User::factory()->create(['role_id' => 2]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function fakeGemini(array $result): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiPayload($result)
            ),
        ]);
    }

    private function geminiPayload(array $result): array
    {
        return [
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ]],
                ],
            ]],
        ];
    }

    private function localModerationPayload(string $label, array $scores): array
    {
        return [
            'model' => 'visolex/phobert-v2-hsd',
            'label' => $label,
            'confidence' => $scores[$label],
            'scores' => $scores,
        ];
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function createMovie(array $attributes = []): Movie
    {
        return Movie::create(array_merge([
            'tmdb_id' => fake()->unique()->numberBetween(1, 1000000),
            'ophim_id' => fake()->unique()->uuid(),
            'slug' => fake()->unique()->slug(),
            'name' => fake()->sentence(3),
            'status' => 'approved',
        ], $attributes));
    }

    private function createSubscriptionPlan(array $attributes = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'plan_code' => 'standard_monthly',
            'name' => 'Standard Monthly',
            'price' => 49000,
            'price_monthly' => 49000,
            'price_yearly' => null,
            'duration_days' => 30,
            'billing_cycle_type' => 'monthly',
            'yearly_discount_percent' => 0,
            'is_active' => true,
        ], $attributes));
    }

    private function signedVnPayParams(array $params, string $secret): array
    {
        ksort($params);
        $hashData = '';
        $index = 0;

        foreach ($params as $key => $value) {
            if (! str_starts_with($key, 'vnp_')) {
                continue;
            }

            $hashData .= ($index++ > 0 ? '&' : '').urlencode($key).'='.urlencode($value);
        }

        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

        return $params;
    }

    private function createEpisode(Movie $movie): Episode
    {
        return Episode::create([
            'movie_id' => $movie->id,
            'server_name' => 'Server 1',
            'name' => 'Episode 1',
            'slug' => 'episode-1',
            'link_m3u8' => 'https://example.com/episode.m3u8',
        ]);
    }
}
