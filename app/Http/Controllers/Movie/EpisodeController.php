<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;

class EpisodeController extends Controller
{
    public function index($movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        $episodes = Episode::where('movie_id', $movie->id)
            ->orderBy('server_name')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('server_name');

        return response()->json([
            'data' => [
                'movie_id' => $movie->id,
                'movie_name' => $movie->name,
                'episodes' => $episodes,
            ],
        ]);
    }
}
