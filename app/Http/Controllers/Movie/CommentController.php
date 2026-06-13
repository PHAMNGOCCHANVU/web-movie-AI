<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Movie;
use App\Services\CommentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        protected CommentModerationService $moderationService
    ) {}

    public function index($movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        $comments = Comment::where('movie_id', $movie->id)
            ->where('is_hidden', false)
            ->where('moderation_status', 'approved')
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $comments]);
    }

    public function store(StoreCommentRequest $request, $movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);
        $moderation = $this->moderationService->moderate($request->content);
        $isHidden = $moderation['status'] !== 'approved';

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'movie_id' => $movie->id,
            'content' => $request->content,
            'moderation_status' => $moderation['status'],
            'moderation_model' => $moderation['model'],
            'moderation_score' => $moderation['score'],
            'moderation_categories' => [
                'scores' => $moderation['categories'],
                'matched' => $moderation['matched_categories'],
                'flagged' => $moderation['flagged'],
                'error' => $moderation['error'],
            ],
            'moderation_reason' => $moderation['reason'],
            'moderated_at' => now(),
            'is_hidden' => $isHidden,
            'hidden_at' => null,
        ]);

        return response()->json([
            'message' => match ($moderation['status']) {
                'approved' => 'Bình luận đã được đăng.',
                default => 'Bình luận có dấu hiệu tiêu cực và đang chờ quản trị viên kiểm tra.',
            },
            'moderation_status' => $moderation['status'],
            'data' => $comment->load('user:id,name'),
        ], 201);
    }

    public function destroy(Request $request, $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Bạn không có quyền xóa bình luận này.',
            ], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Bình luận đã được xóa.']);
    }
}
