<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->baseUrl = env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta');
    }

    public function chat(User $user, string $message, array $history = []): string
    {
        // Get available movies as context
        $movies = Movie::where('status', 'approved')
            ->select(['name', 'origin_name', 'year', 'type', 'is_premium'])
            ->take(50)
            ->get()
            ->map(fn($m) => "- {$m->name}" . ($m->origin_name ? " ({$m->origin_name})" : '') . " ({$m->year})" . ($m->is_premium ? ' [VIP]' : ''))
            ->implode("\n");

        $systemPrompt = "Bạn là trợ lý AI gợi ý phim thông minh. "
            . "Nhiệm vụ của bạn là gợi ý phim dựa trên danh sách phim khả dụng dưới đây, "
            . "hoặc trả lời các câu hỏi về phim. "
            . "Hãy trả lời bằng tiếng Việt, thân thiện và hữu ích.\n\n"
            . "Danh sách phim khả dụng:\n{$movies}";

        // Build conversation contents
        $contents = [['role' => 'user', 'parts' => [['text' => $systemPrompt]]]];
        $contents[] = ['role' => 'model', 'parts' => [['text' => 'Tôi đã sẵn sàng hỗ trợ bạn tìm phim!']]];

        foreach ($history as $h) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $h['user']]]];
            $contents[] = ['role' => 'model', 'parts' => [['text' => $h['assistant']]]];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        // Call Gemini API
        if (empty($this->apiKey)) {
            // Fallback when no API key
            return $this->fallbackResponse($message, $movies);
        }

        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent", [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->fallbackResponse($message, $movies);
            }

            Log::warning('Gemini API error: ' . $response->body());
            return $this->fallbackResponse($message, $movies);
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return 'Trợ lý AI tạm thời không khả dụng, vui lòng thử lại sau hoặc dùng tìm kiếm thủ công.';
        }
    }

    protected function fallbackResponse(string $message, string $movieList): string
    {
        // Simple keyword-based fallback
        $message = mb_strtolower($message);

        if (str_contains($message, 'hành động') || str_contains($message, 'action')) {
            return 'Bạn có thể tham khảo các phim hành động trong danh sách của chúng tôi. Hãy dùng chức năng tìm kiếm theo thể loại để khám phá thêm!';
        }

        if (str_contains($message, 'hot') || str_contains($message, 'mới') || str_contains($message, 'new')) {
            return 'Hiện tại có nhiều phim mới được cập nhật. Bạn có thể xem mục "Mới cập nhật" trên trang chủ để khám phá!';
        }

        if (str_contains($message, 'vip') || str_contains($message, 'premium')) {
            return 'Các phim VIP được đánh dấu [VIP] trong danh sách. Bạn cần đăng ký gói VIP để xem các phim này!';
        }

        return "Cảm ơn bạn đã quan tâm! Dưới đây là danh sách phim khả dụng:\n{$movieList}\n\nBạn có muốn tôi gợi ý phim theo thể loại hoặc tâm trạng không?";
    }
}