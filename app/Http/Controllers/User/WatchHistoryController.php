<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movies = $request->user()->movies()
            ->wherePivot('watch_progress_seconds', '>', 0)
            ->with('genres')
            ->orderBy('movie_user.updated_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['data' => $movies]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'progress_seconds' => 'required|integer|min:0',
            'season_id' => 'nullable|integer',
        ]);

        $request->user()->movies()->syncWithoutDetaching([
            $request->movie_id => [
                'watch_progress_seconds' => $request->progress_seconds,
                'episode_id' => $request->episode_id,
                'season_id' => $request->season_id,
            ],
        ]);

        return response()->json(['message' => 'Đã lưu tiến độ xem.']);
    }

    public function destroy(Request $request, $movieId): JsonResponse
    {
        $request->user()->movies()->detach($movieId);

        return response()->json(['message' => 'Đã xóa lịch sử xem.']);
    }
}