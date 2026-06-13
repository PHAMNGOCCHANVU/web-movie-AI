<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WatchlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movies = $request->user()->movies()
            ->wherePivot('is_favorite', true)
            ->where('movies.status', 'approved')
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('movie_user.updated_at', 'desc')
            ->get();

        return response()->json(['data' => $movies]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->where('status', 'approved'),
            ],
        ]);

        $request->user()->movies()->syncWithoutDetaching([
            $request->movie_id => ['is_favorite' => true],
        ]);

        return response()->json(['message' => 'Đã thêm vào danh sách xem sau.']);
    }

    public function destroy(Request $request, $movieId): JsonResponse
    {
        Movie::where('status', 'approved')->findOrFail($movieId);

        $movie = $request->user()->movies()
            ->where('movies.id', $movieId)
            ->firstOrFail();

        if ($movie->pivot->watch_progress_seconds > 0) {
            $request->user()->movies()->updateExistingPivot($movieId, ['is_favorite' => false]);
        } else {
            $request->user()->movies()->detach($movieId);
        }

        return response()->json(['message' => 'Đã bỏ khỏi danh sách xem sau.']);
    }
}
