<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CommentModerationService
{
    public function moderate(string $content): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.moderation.connect_timeout', 2))
                ->timeout((int) config('services.moderation.timeout', 8))
                ->post(
                    rtrim((string) config('services.moderation.url'), '/').'/moderate',
                    ['text' => $content]
                );

            if (! $response->successful()) {
                Log::warning('Moderation API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->unavailable("http_{$response->status()}");
            }

            $label = strtoupper((string) $response->json('label'));
            $action = strtoupper((string) $response->json('action'));
            $model = (string) $response->json(
                'model',
                config('services.moderation.model', 'visolex/phobert-v2-hsd')
            );
            $scores = collect($response->json('scores', []))
                ->mapWithKeys(fn ($score, $name) => [
                    strtoupper((string) $name) => round((float) $score, 5),
                ])
                ->all();

            if (! in_array($label, ['CLEAN', 'OFFENSIVE', 'HATE'], true)) {
                return $this->unavailable('invalid_label');
            }

            $confidence = (float) ($scores[$label] ?? $response->json('confidence', 0));
            $needsReview = $action === 'REVIEW'
                || in_array($label, ['OFFENSIVE', 'HATE'], true);

            return [
                'available' => true,
                'status' => $needsReview ? 'pending_review' : 'approved',
                'flagged' => $needsReview,
                'score' => $confidence,
                'categories' => $scores,
                'matched_categories' => $needsReview ? [$label] : [],
                'reason' => $needsReview
                    ? "Model phát hiện nội dung {$label}; cần quản trị viên xác nhận."
                    : null,
                'model' => $model,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            Log::error('Moderation API exception', [
                'message' => $exception->getMessage(),
            ]);

            return $this->unavailable('exception');
        }
    }

    protected function unavailable(string $error): array
    {
        return [
            'available' => false,
            'status' => 'pending_review',
            'flagged' => true,
            'score' => null,
            'categories' => [],
            'matched_categories' => [],
            'reason' => 'Không kết nối được API kiểm duyệt; cần quản trị viên kiểm tra.',
            'model' => (string) config(
                'services.moderation.model',
                'visolex/phobert-v2-hsd'
            ),
            'error' => $error,
        ];
    }
}
