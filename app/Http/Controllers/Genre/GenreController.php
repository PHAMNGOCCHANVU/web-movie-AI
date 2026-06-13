<?php

namespace App\Http\Controllers\Genre;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(): JsonResponse
    {
        $genres = Genre::orderBy('name')->get();
        return response()->json(['data' => $genres]);
    }

    public function movies(Request $request, $genreId): JsonResponse
    {
        $genre = Genre::findOrFail($genreId);

        $movies = $genre->movies()
            ->where('status', 'approved')
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => [
                'genre' => $genre,
                'movies' => $movies,
            ],
        ]);
    }
}
