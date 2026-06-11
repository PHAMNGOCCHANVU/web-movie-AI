<?php

namespace App\Http\Controllers\Movie;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rating\StoreRatingRequest;
use App\Models\Movie;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function summary(Request $request, $movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        $avg = Rating::where('movie_id', $movie->id)->avg('score');
        $count = Rating::where('movie_id', $movie->id)->count();
        $distribution = Rating::where('movie_id', $movie->id)
            ->selectRaw('score, COUNT(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score');

        return response()->json([
            'data' => [
                'average' => $avg ? round($avg, 1) : 0,
                'count' => $count,
                'distribution' => $distribution,
                'user_score' => $request->user('sanctum')
                    ? Rating::where('movie_id', $movie->id)
                        ->where('user_id', $request->user('sanctum')->id)
                        ->value('score')
                    : null,
            ],
        ]);
    }

    public function store(StoreRatingRequest $request, $movieId): JsonResponse
    {
        $movie = Movie::where('status', 'approved')->findOrFail($movieId);

        $rating = Rating::updateOrCreate(
            ['user_id' => $request->user()->id, 'movie_id' => $movie->id],
            ['score' => $request->score]
        );

        return response()->json([
            'message' => 'Đánh giá đã được lưu.',
            'data' => $rating,
        ]);
    }
}
