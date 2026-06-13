<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WatchHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movies = $request->user()->movies()
            ->wherePivot('watch_progress_seconds', '>', 0)
            ->where('movies.status', 'approved')
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('movie_user.updated_at', 'desc')
            ->limit(50)
            ->get();

        $episodes = Episode::whereIn(
            'id',
            $movies->pluck('pivot.episode_id')->filter()->unique()
        )->get()->keyBy('id');

        $movies->each(function ($movie) use ($episodes) {
            $movie->setAttribute(
                'current_episode',
                $movie->pivot->episode_id ? $episodes->get($movie->pivot->episode_id) : null
            );
        });

        return response()->json(['data' => $movies]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->where('status', 'approved'),
            ],
            'episode_id' => [
                'nullable',
                Rule::exists('episodes', 'id')
                    ->where(fn ($query) => $query->where('movie_id', $request->input('movie_id'))),
            ],
            'progress_seconds' => 'required|integer|min:0',
            'duration_seconds' => 'nullable|integer|min:0',
            'season_id' => 'nullable|integer',
        ]);

        $request->user()->movies()->syncWithoutDetaching([
            $request->movie_id => [
                'watch_progress_seconds' => $request->progress_seconds,
                'duration_seconds' => $request->input('duration_seconds', 0),
                'episode_id' => $request->episode_id,
                'season_id' => $request->season_id,
            ],
        ]);

        return response()->json(['message' => 'Đã lưu tiến độ xem.']);
    }

    public function destroy(Request $request, $movieId): JsonResponse
    {
        $movie = $request->user()->movies()
            ->where('movies.id', $movieId)
            ->firstOrFail();

        if ($movie->pivot->is_favorite) {
            $request->user()->movies()->updateExistingPivot($movieId, [
                'watch_progress_seconds' => 0,
                'duration_seconds' => 0,
                'episode_id' => null,
                'season_id' => null,
            ]);
        } else {
            $request->user()->movies()->detach($movieId);
        }

        return response()->json(['message' => 'Đã xóa lịch sử xem.']);
    }
}
