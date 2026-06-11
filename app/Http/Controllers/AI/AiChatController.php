<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Services\GeminiAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        protected GeminiAiService $aiService
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        $user = $request->user();

        // Keep the limit configurable so local testing and production can use different quotas.
        $hourlyLimit = max(1, (int) config('services.gemini.chat_limit_per_hour', 100));
        $recentCount = AiChatConversation::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= $hourlyLimit) {
            return response()->json([
                'message' => "Bạn đã đạt giới hạn {$hourlyLimit} tin nhắn mỗi giờ. Vui lòng thử lại sau.",
                'error' => 'rate_limit_exceeded',
            ], 429);
        }

        // Get recent history (last 10 messages for context)
        $history = AiChatConversation::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn($conv) => [
                'user' => $conv->message,
                'assistant' => $conv->response,
            ])
            ->toArray();

        try {
            $result = $this->aiService->chat($user, $request->message, $history);
            $recommendedMovies = $result['movies'];

            // Save conversation
            AiChatConversation::create([
                'user_id' => $user->id,
                'message' => $request->message,
                'response' => $result['response'],
                'context' => [
                    'history_count' => count($history),
                    'recommended_movie_ids' => $recommendedMovies->pluck('id')->all(),
                    'recommendation_reasons' => $recommendedMovies
                        ->mapWithKeys(fn ($movie) => [
                            $movie->id => $movie->recommendation_reason,
                        ])
                        ->all(),
                    'suggested_replies' => $result['suggestions'] ?? [],
                    'grounding_sources' => $result['sources'] ?? [],
                    'intent' => $result['intent'] ?? [],
                    'response_source' => $result['source'] ?? 'fallback',
                    'ai_model' => $result['model'] ?? null,
                    'fallback_reason' => $result['fallback_reason'] ?? null,
                ],
            ]);

            return response()->json([
                'data' => [
                    'response' => $result['response'],
                    'movies' => $recommendedMovies,
                    'suggestions' => $result['suggestions'] ?? [],
                    'sources' => $result['sources'] ?? [],
                    'source' => $result['source'] ?? 'fallback',
                    'model' => $result['model'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI chat controller error: '.$e->getMessage());

            return response()->json([
                'data' => [
                    'response' => 'Trợ lý AI tạm thời không khả dụng, vui lòng thử lại sau hoặc dùng tìm kiếm thủ công.',
                    'movies' => [],
                    'suggestions' => [],
                    'sources' => [],
                    'source' => 'fallback',
                    'model' => null,
                ],
            ]);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $conversations = AiChatConversation::where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        $movieIds = $conversations
            ->flatMap(fn ($conversation) => $conversation->context['recommended_movie_ids'] ?? [])
            ->unique();
        $movies = \App\Models\Movie::whereIn('id', $movieIds)
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->get()
            ->keyBy('id');

        $conversations->each(function ($conversation) use ($movies) {
            $reasons = $conversation->context['recommendation_reasons'] ?? [];

            $conversation->setAttribute(
                'recommended_movies',
                collect($conversation->context['recommended_movie_ids'] ?? [])
                    ->map(function ($id) use ($movies, $reasons) {
                        $movie = $movies->get($id);

                        if ($movie) {
                            $movie = clone $movie;
                            $movie->setAttribute('recommendation_reason', $reasons[$id] ?? null);
                        }

                        return $movie;
                    })
                    ->filter()
                    ->values()
            );
            $conversation->setAttribute(
                'suggested_replies',
                $conversation->context['suggested_replies'] ?? []
            );
            $conversation->setAttribute(
                'grounding_sources',
                $conversation->context['grounding_sources'] ?? []
            );
            $conversation->setAttribute(
                'response_source',
                $conversation->context['response_source'] ?? null
            );
            $conversation->setAttribute(
                'ai_model',
                $conversation->context['ai_model'] ?? null
            );
        });

        return response()->json(['data' => $conversations]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        AiChatConversation::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Đã xóa lịch sử trò chuyện.']);
    }
}
