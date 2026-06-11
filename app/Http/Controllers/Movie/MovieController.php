<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Movie::where('status', 'approved');

        if ($request->filled('genre_id')) {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $request->genre_id));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->type);
        }

        $movies = $query->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('updated_at', 'desc')
            ->paginate($this->perPage($request));

        return response()->json(['data' => $movies]);
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword', '');

        $query = Movie::where('status', 'approved')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('origin_name', 'LIKE', "%{$keyword}%");
            });

        if ($request->filled('genre_id')) {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $request->genre_id));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $movies = $query->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('updated_at', 'desc')
            ->paginate($this->perPage($request));

        return response()->json(['data' => $movies]);
    }

    public function featured(): JsonResponse
    {
        $movies = Movie::where('status', 'approved')
            ->where('is_pinned', true)
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return response()->json(['data' => $movies]);
    }

    public function newUpdated(Request $request): JsonResponse
    {
        $movies = Movie::where('status', 'approved')
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('updated_at', 'desc')
            ->paginate($this->perPage($request));

        return response()->json(['data' => $movies]);
    }

    public function show($movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->findOrFail($movieId);

        return response()->json(['data' => $movie]);
    }

    public function cast($movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);
        $actors = $movie->actor ?? [];
        $directors = $movie->director ?? [];

        return response()->json([
            'data' => [
                'actor' => $actors,
                'director' => $directors,
            ],
        ]);
    }

    public function trailer($movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        return response()->json([
            'data' => [
                'trailer_url' => $movie->trailer_url,
            ],
        ]);
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->input(
            'per_page',
            $request->input('limit', 20)
        )));
    }
}
