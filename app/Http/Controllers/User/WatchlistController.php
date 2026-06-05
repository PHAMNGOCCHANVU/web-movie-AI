<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movies = $request->user()->movies()
            ->wherePivot('is_favorite', true)
            ->with('genres')
            ->orderBy('movie_user.updated_at', 'desc')
            ->get();

        return response()->json(['data' => $movies]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['movie_id' => 'required|exists:movies,id']);

        $request->user()->movies()->syncWithoutDetaching([
            $request->movie_id => ['is_favorite' => true],
        ]);

        return response()->json(['message' => 'Đã thêm vào danh sách xem sau.']);
    }

    public function destroy(Request $request, $movieId): JsonResponse
    {
        $movie = Movie::findOrFail($movieId);
        $request->user()->movies()->updateExistingPivot($movie->id, ['is_favorite' => false]);

        return response()->json(['message' => 'Đã bỏ khỏi danh sách xem sau.']);
    }
}