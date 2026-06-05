<?php

namespace App\Http\Controllers\Homepage;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\HomepageBlock;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;

class HomepageBlockController extends Controller
{
    public function index(): JsonResponse
    {
        $blocks = HomepageBlock::where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($block) {
                $movies = collect();

                switch ($block->source_type) {
                    case 'genre':
                        $genre = Genre::find($block->source_ref);
                        if ($genre) {
                            $movies = $genre->movies()
                                ->where('status', 'approved')
                                ->with('genres')
                                ->orderBy('updated_at', 'desc')
                                ->take(10)
                                ->get();
                        }
                        break;

                    case 'latest':
                        $movies = Movie::where('status', 'approved')
                            ->with('genres')
                            ->orderBy('created_at', 'desc')
                            ->take(10)
                            ->get();
                        break;

                    case 'pinned':
                        $movies = Movie::where('status', 'approved')
                            ->where('is_pinned', true)
                            ->with('genres')
                            ->orderBy('updated_at', 'desc')
                            ->take(10)
                            ->get();
                        break;

                    case 'custom':
                        // Custom logic - có thể mở rộng sau
                        break;
                }

                return [
                    'id' => $block->id,
                    'title' => $block->title,
                    'source_type' => $block->source_type,
                    'movies' => $movies,
                ];
            });

        return response()->json(['data' => $blocks]);
    }
}