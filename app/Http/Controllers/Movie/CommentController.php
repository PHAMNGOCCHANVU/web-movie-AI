<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($movieId): JsonResponse
    {
        $movie = Movie::findOrFail($movieId);

        $comments = Comment::where('movie_id', $movie->id)
            ->where('is_hidden', false)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['data' => $comments]);
    }

    public function store(StoreCommentRequest $request, $movieId): JsonResponse
    {
        $movie = Movie::findOrFail($movieId);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'movie_id' => $movie->id,
            'content' => $request->content,
            'is_hidden' => false,
        ]);

        return response()->json([
            'message' => 'Bình luận đã được đăng.',
            'data' => $comment->load('user:id,name'),
        ], 201);
    }

    public function destroy(Request $request, $commentId): JsonResponse
    {
        $comment = Comment::findOrFail($commentId);

        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa bình luận này.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Bình luận đã được xóa.']);
    }
}