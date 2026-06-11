<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => [
                'nullable',
                Rule::in(['approved', 'pending_review', 'hidden']),
            ],
        ]);

        $comments = Comment::query()
            ->when(
                $request->status,
                fn ($query, $status) => $query->where('moderation_status', $status),
                fn ($query) => $query->whereIn(
                    'moderation_status',
                    ['pending_review', 'hidden']
                )
            )
            ->with([
                'user:id,name,email',
                'movie:id,name,slug',
                'reviewedBy:id,name',
            ])
            ->latest()
            ->paginate(30);

        return response()->json(['data' => $comments]);
    }

    public function review(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'hide'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $approved = $validated['action'] === 'approve';

        $comment->update([
            'moderation_status' => $approved ? 'approved' : 'hidden',
            'is_hidden' => ! $approved,
            'hidden_at' => $approved ? null : now(),
            'hidden_by' => $approved ? null : $request->user()->id,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'moderation_reason' => $validated['reason']
                ?? ($approved
                    ? 'Quản trị viên đã duyệt hiển thị.'
                    : 'Quản trị viên đã xác nhận ẩn bình luận.'),
        ]);

        return response()->json([
            'message' => $approved
                ? 'Bình luận đã được duyệt hiển thị.'
                : 'Bình luận đã được ẩn.',
            'data' => $comment->fresh([
                'user:id,name,email',
                'movie:id,name,slug',
                'reviewedBy:id,name',
            ]),
        ]);
    }
}
