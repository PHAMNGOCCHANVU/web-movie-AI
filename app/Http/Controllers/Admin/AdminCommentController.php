<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['approved', 'pending_review', 'hidden'])],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $comments = Comment::query()
            ->with(['user:id,name,email', 'movie:id,name,slug', 'reviewedBy:id,name'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('moderation_status', $status))
            ->when($validated['movie_id'] ?? null, fn ($query, $movieId) => $query->where('movie_id', $movieId))
            ->latest()
            ->paginate($validated['limit'] ?? 10);

        $comments->getCollection()->transform(fn (Comment $comment) => $this->present($comment));

        return response()->json(['status' => 'success', 'data' => $comments]);
    }

    public function approve(Request $request, Comment $comment): JsonResponse
    {
        return $this->review($request, $comment, true);
    }

    public function hide(Request $request, Comment $comment): JsonResponse
    {
        return $this->review($request, $comment, false);
    }

    public function restore(Request $request, Comment $comment): JsonResponse
    {
        return $this->review($request, $comment, true);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['status' => 'success', 'message' => 'Đã xóa bình luận.']);
    }

    public function sentiment(Comment $comment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'label' => $this->label($comment),
                'toxic_score' => $this->toxicityScore($comment),
                'confidence' => (float) ($comment->moderation_score ?? 0),
                'model' => $comment->moderation_model,
                'scores' => $comment->moderation_categories,
                'reason' => $comment->moderation_reason,
            ],
        ]);
    }

    private function review(Request $request, Comment $comment, bool $approved): JsonResponse
    {
        $comment->update([
            'moderation_status' => $approved ? 'approved' : 'hidden',
            'is_hidden' => ! $approved,
            'hidden_at' => $approved ? null : now(),
            'hidden_by' => $approved ? null : $request->user()->id,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $approved ? 'Đã duyệt bình luận.' : 'Đã ẩn bình luận.',
            'data' => $this->present($comment->fresh(['user', 'movie', 'reviewedBy'])),
        ]);
    }

    private function present(Comment $comment): array
    {
        return [
            ...$comment->toArray(),
            'status' => $comment->moderation_status,
            'toxic_score' => $this->toxicityScore($comment),
            'ai_label' => $this->label($comment),
        ];
    }

    private function label(Comment $comment): string
    {
        $categories = $comment->moderation_categories ?? [];
        $matched = $categories['matched'][0] ?? null;
        $scores = $categories['scores'] ?? [];

        if (! $matched && $scores) {
            arsort($scores);
            $matched = array_key_first($scores);
        }

        return strtoupper((string) ($matched ?? match ($comment->moderation_status) {
            'approved' => 'CLEAN',
            'hidden' => 'HATE',
            default => 'OFFENSIVE',
        }));
    }

    private function toxicityScore(Comment $comment): float
    {
        $scores = $comment->moderation_categories['scores'] ?? [];

        return min(1, round(
            (float) ($scores['OFFENSIVE'] ?? 0) + (float) ($scores['HATE'] ?? 0),
            5
        ));
    }
}
