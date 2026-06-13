<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected string $apiKey;

    protected string $baseUrl;

    /** @var array<int, string> */
    protected array $models;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        $this->baseUrl = rtrim(
            (string) config(
                'services.gemini.base_url',
                'https://generativelanguage.googleapis.com/v1beta'
            ),
            '/'
        );
        $this->models = collect([
            config('services.gemini.model', 'gemini-3.5-flash'),
            config('services.gemini.reserve_model', 'gemini-3.1-flash-lite'),
            config('services.gemini.fallback_model', 'gemini-2.5-flash'),
        ])->filter()
            ->map(fn ($model) => trim((string) $model))
            ->unique()
            ->values()
            ->all();
    }

    public function chat(User $user, string $message, array $history = []): array
    {
        $catalog = $this->movieCatalog();

        if ($this->apiKey === '') {
            return $this->fallbackResult('missing_api_key');
        }

        $contents = $this->conversationContents(
            $user,
            $message,
            $history,
            $catalog
        );
        $lastFailure = 'gemini_unavailable';

        foreach ($this->models as $model) {
            try {
                $response = $this->request($model, $contents, true);

                if (
                    config('services.gemini.google_search', true)
                    && in_array($response->status(), [400, 429], true)
                ) {
                    Log::notice('Gemini Search grounding unavailable; retrying without web search', [
                        'model' => $model,
                        'status' => $response->status(),
                    ]);
                    $response = $this->request($model, $contents, false);
                }

                if (! $response->successful()) {
                    $lastFailure = "gemini_http_{$response->status()}";
                    Log::warning('Gemini API error', [
                        'model' => $model,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    continue;
                }

                $payload = $this->structuredPayload($response);

                if ($payload === null) {
                    $lastFailure = 'invalid_ai_response';
                    Log::warning('Gemini returned invalid structured output', [
                        'model' => $model,
                    ]);

                    continue;
                }

                $movies = $this->hydrateRecommendations(
                    $catalog,
                    $payload['recommendations'] ?? []
                );

                return [
                    'response' => $this->cleanResponse($payload['response']),
                    'movies' => $movies,
                    'sources' => $this->groundingSources($response),
                    'intent' => [
                        'label' => $payload['intent'] ?? 'general_conversation',
                        'needs_movies' => $movies->isNotEmpty(),
                    ],
                    'suggestions' => collect($payload['suggestions'] ?? [])
                        ->filter(fn ($item) => is_string($item) && filled(trim($item)))
                        ->map(fn ($item) => trim($item))
                        ->take(3)
                        ->values()
                        ->all(),
                    'source' => 'gemini',
                    'model' => $model,
                ];
            } catch (\Throwable $exception) {
                $lastFailure = 'gemini_exception';
                Log::error('Gemini API exception', [
                    'model' => $model,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->fallbackResult($lastFailure);
    }

    protected function request(
        string $model,
        array $contents,
        bool $withGoogleSearch
    ): Response
    {
        $generationConfig = [
            'temperature' => 0.7,
            'maxOutputTokens' => 1000,
            'responseMimeType' => 'application/json',
            'responseSchema' => $this->responseSchema(),
        ];

        if (preg_match('/^gemini-3(?:\.|[-])/', $model) === 1) {
            $generationConfig['thinkingConfig'] = [
                'thinkingLevel' => config(
                    'services.gemini.thinking_level',
                    'medium'
                ),
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => $generationConfig,
        ];

        if (
            $withGoogleSearch
            && config('services.gemini.google_search', true)
        ) {
            $payload['tools'] = [['google_search' => (object) []]];
        }

        return Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
        ])->connectTimeout((int) config('services.gemini.connect_timeout', 5))
            ->timeout($this->modelTimeout($model))
            ->post(
                "{$this->baseUrl}/models/{$model}:generateContent",
                $payload
            );
    }

    protected function modelTimeout(string $model): int
    {
        $primaryModel = (string) config(
            'services.gemini.model',
            'gemini-3.5-flash'
        );

        return $model === $primaryModel
            ? (int) config('services.gemini.primary_timeout', 12)
            : (int) config('services.gemini.fallback_timeout', 15);
    }

    protected function structuredPayload(Response $response): ?array
    {
        $parts = $response->json('candidates.0.content.parts', []);
        $raw = collect(is_array($parts) ? $parts : [])
            ->reject(fn ($part) => (bool) ($part['thought'] ?? false))
            ->pluck('text')
            ->filter(fn ($text) => is_string($text))
            ->implode('');
        $payload = $raw !== '' ? json_decode($raw, true) : null;

        return is_array($payload) && filled($payload['response'] ?? null)
            ? $payload
            : null;
    }

    protected function groundingSources(Response $response): array
    {
        return collect(
            $response->json('candidates.0.groundingMetadata.groundingChunks', [])
        )->map(fn ($chunk) => [
            'title' => data_get($chunk, 'web.title'),
            'url' => data_get($chunk, 'web.uri'),
        ])->filter(fn ($source) => filled($source['url'])
            && filter_var($source['url'], FILTER_VALIDATE_URL))
            ->unique('url')
            ->take(5)
            ->values()
            ->all();
    }

    protected function movieCatalog(): EloquentCollection
    {
        return Movie::query()
            ->where('status', 'approved')
            ->with('genres:id,name,slug')
            ->withAvg('ratings', 'score')
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->limit((int) config('services.gemini.catalog_limit', 120))
            ->get();
    }

    protected function conversationContents(
        User $user,
        string $message,
        array $history,
        EloquentCollection $catalog
    ): array {
        $systemPrompt = implode("\n", [
            'Bạn là CineON AI, trợ lý điện ảnh dùng Gemini của nền tảng CineON.',
            'Mục tiêu: trò chuyện tự nhiên bằng tiếng Việt và tư vấn phim chính xác từ kho phim được cung cấp.',
            '',
            'NGUYÊN TẮC HỘI THOẠI:',
            '- Tự phân tích ý định, cảm xúc, ngữ cảnh và câu trả lời cho mọi tin nhắn.',
            '- Chào hỏi, tâm sự, vui, buồn, cảm ơn hoặc hỏi khả năng thì phản hồi như một trợ lý thân thiện; không ép thành yêu cầu tìm phim.',
            '- Khi người dùng buồn hoặc căng thẳng, phản hồi đồng cảm trước rồi mới hỏi họ có muốn nhận gợi ý phim hay không.',
            '- Nếu người dùng có dấu hiệu nguy hiểm hoặc tự làm hại bản thân, ưu tiên khuyên tìm hỗ trợ trực tiếp và dịch vụ khẩn cấp tại nơi họ sống.',
            '- Khi bị xúc phạm, giữ bình tĩnh, đặt giới hạn lịch sự và vẫn đề nghị hỗ trợ.',
            '- Không tự nhận là con người và không bịa khả năng ngoài CineON.',
            '',
            'NGUYÊN TẮC GỢI Ý PHIM:',
            '- Chỉ gợi ý khi người dùng muốn tìm phim, xin tư vấn, hoặc đồng ý nhận gợi ý.',
            '- Chỉ chọn movie_id có trong CATALOG. Tuyệt đối không bịa ID, tên phim hoặc dữ kiện.',
            '- Chọn tối đa 3 phim phù hợp nhất, đa dạng nếu có nhiều lựa chọn tương đương.',
            '- Dựa trên tâm trạng, thể loại, quốc gia, năm, mô tả, đánh giá, lịch sử xem và phim đã lưu.',
            '- Khi gợi ý phim, hãy dùng Google Search nếu cần để kiểm chứng nội dung, sắc thái, đánh giá hoặc mức độ phù hợp thay vì suy đoán từ tên phim.',
            '- Ưu tiên nguồn chính thức, trang dữ liệu điện ảnh uy tín và bài đánh giá có nội dung rõ ràng.',
            '- Lý do gợi ý phải nêu đặc điểm cụ thể của phim phù hợp với yêu cầu, không dùng lý do chung chung.',
            '- Không gợi ý lại phim đã xem gần đây trừ khi người dùng yêu cầu.',
            '- Nếu tài khoản không có VIP, vẫn có thể giới thiệu phim VIP nhưng phải nói rõ cần gói VIP.',
            '- Nếu catalog không có phim đủ phù hợp, nói thật và để recommendations rỗng.',
            '',
            'YÊU CẦU ĐẦU RA:',
            '- response tự nhiên, súc tích 1-5 câu, không Markdown và không liệt kê lại tên phim đã có trên card.',
            '- recommendations là mảng tối đa 3 phần tử gồm movie_id và lý do riêng cho từng phim.',
            '- suggestions là tối đa 3 câu trả lời nhanh, ngắn và đúng ngữ cảnh.',
            '- intent là nhãn snake_case ngắn mô tả đúng ý định chính.',
            '',
            'HỒ SƠ NGƯỜI DÙNG:',
            $this->userContext($user),
            '',
            'CATALOG PHIM ĐƯỢC PHÉP GỢI Ý:',
            $this->catalogText($catalog),
        ]);

        $contents = [
            ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
            ['role' => 'model', 'parts' => [[
                'text' => 'Đã hiểu. Tôi sẽ tự xử lý hội thoại và chỉ chọn phim từ catalog CineON.',
            ]]],
        ];

        foreach (array_slice($history, -12) as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (filled($item['user'] ?? null)) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => (string) $item['user']]],
                ];
            }

            if (filled($item['assistant'] ?? null)) {
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => (string) $item['assistant']]],
                ];
            }
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $contents;
    }

    protected function userContext(User $user): string
    {
        $user->loadMissing('subscriptionPlan');
        $recentMovies = $user->movies()
            ->with('genres:id,name')
            ->orderByPivot('updated_at', 'desc')
            ->limit(12)
            ->get();
        $favoriteNames = $recentMovies
            ->filter(fn ($movie) => (bool) $movie->pivot->is_favorite)
            ->pluck('name')
            ->take(6)
            ->join(', ');
        $watchedNames = $recentMovies
            ->filter(fn ($movie) => (int) $movie->pivot->watch_progress_seconds > 0)
            ->pluck('name')
            ->take(8)
            ->join(', ');
        $preferredGenres = $recentMovies
            ->flatMap(fn ($movie) => $movie->genres->pluck('name'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(5)
            ->join(', ');

        return implode("\n", [
            'Tên hiển thị: '.$user->name,
            'Quyền xem: '.($user->isVip() ? 'VIP' : ($user->hasActiveSubscription() ? 'Standard' : 'Chưa có gói')),
            'Thể loại thường xem: '.($preferredGenres ?: 'Chưa đủ dữ liệu'),
            'Phim đã lưu: '.($favoriteNames ?: 'Chưa có'),
            'Phim xem gần đây: '.($watchedNames ?: 'Chưa có'),
        ]);
    }

    protected function catalogText(EloquentCollection $catalog): string
    {
        if ($catalog->isEmpty()) {
            return '(Catalog hiện chưa có phim.)';
        }

        return $catalog->map(function (Movie $movie) {
            $countries = collect($movie->country)->pluck('name')->filter()->join(', ');
            $actors = collect($movie->actor)->filter()->take(5)->join(', ');
            $directors = collect($movie->director)->filter()->take(3)->join(', ');
            $content = trim(strip_tags((string) $movie->content));

            return implode(' | ', array_filter([
                "ID:{$movie->id}",
                "Tên:{$movie->name}",
                $movie->origin_name ? "Tên gốc:{$movie->origin_name}" : null,
                $movie->year ? "Năm:{$movie->year}" : null,
                'Thể loại:'.$movie->genres->pluck('name')->join(', '),
                $countries ? "Quốc gia:{$countries}" : null,
                $actors ? "Diễn viên:{$actors}" : null,
                $directors ? "Đạo diễn:{$directors}" : null,
                $movie->type ? "Loại:{$movie->type}" : null,
                $movie->quality ? "Chất lượng:{$movie->quality}" : null,
                $movie->is_premium ? 'Gói:VIP' : 'Gói:Standard',
                $movie->ratings_avg_score !== null
                    ? 'Điểm:'.round((float) $movie->ratings_avg_score, 1)
                    : null,
                $content ? 'Mô tả:'.mb_strimwidth($content, 0, 260, '...') : null,
            ]));
        })->implode("\n");
    }

    protected function hydrateRecommendations(
        EloquentCollection $catalog,
        array $recommendations
    ): EloquentCollection {
        $catalogById = $catalog->keyBy('id');
        $movies = collect($recommendations)
            ->filter(fn ($item) => is_array($item) && isset($item['movie_id']))
            ->take(3)
            ->map(function (array $item, int $index) use ($catalogById) {
                $movie = $catalogById->get((int) $item['movie_id']);

                if (! $movie) {
                    return null;
                }

                $movie = clone $movie;
                $movie->setAttribute(
                    'recommendation_reason',
                    $this->cleanResponse(
                        (string) ($item['reason'] ?? 'Phù hợp với yêu cầu của bạn.')
                    )
                );
                $movie->setAttribute('match_score', 100 - ($index * 5));

                return $movie;
            })
            ->filter()
            ->unique('id')
            ->values();

        return new EloquentCollection($movies->all());
    }

    protected function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'response' => [
                    'type' => 'STRING',
                    'description' => 'Câu trả lời tự nhiên bằng tiếng Việt.',
                ],
                'intent' => [
                    'type' => 'STRING',
                    'description' => 'Nhãn ý định ngắn bằng snake_case.',
                ],
                'recommendations' => [
                    'type' => 'ARRAY',
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'movie_id' => ['type' => 'INTEGER'],
                            'reason' => ['type' => 'STRING'],
                        ],
                        'required' => ['movie_id', 'reason'],
                    ],
                ],
                'suggestions' => [
                    'type' => 'ARRAY',
                    'maxItems' => 3,
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['response', 'intent', 'recommendations', 'suggestions'],
        ];
    }

    protected function fallbackResult(string $reason): array
    {
        return [
            'response' => 'Trợ lý Gemini tạm thời không khả dụng. Bạn vui lòng thử lại sau.',
            'movies' => new EloquentCollection(),
            'intent' => ['label' => 'ai_unavailable', 'needs_movies' => false],
            'suggestions' => [],
            'sources' => [],
            'source' => 'fallback',
            'model' => $this->models[0] ?? null,
            'fallback_reason' => $reason,
        ];
    }

    protected function cleanResponse(string $response): string
    {
        $response = preg_replace('/\*\*(.*?)\*\*/u', '$1', $response);
        $response = preg_replace('/^\s*[-*#]+\s*/mu', '', $response);
        $response = preg_replace("/\n{3,}/", "\n\n", $response);

        return trim((string) $response);
    }
}
