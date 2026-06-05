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

        // Rate limit: 20 requests/hour/user (simple check)
        $recentCount = AiChatConversation::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= 20) {
            return response()->json([
                'message' => 'Bạn đã đạt giới hạn 20 tin nhắn mỗi giờ. Vui lòng thử lại sau.',
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
            $response = $this->aiService->chat($user, $request->message, $history);

            // Save conversation
            AiChatConversation::create([
                'user_id' => $user->id,
                'message' => $request->message,
                'response' => $response,
                'context' => ['history_count' => count($history)],
            ]);

            return response()->json([
                'data' => [
                    'response' => $response,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'response' => 'Trợ lý AI tạm thời không khả dụng, vui lòng thử lại sau hoặc dùng tìm kiếm thủ công.',
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

        return response()->json(['data' => $conversations]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        AiChatConversation::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Đã xóa lịch sử trò chuyện.']);
    }
}