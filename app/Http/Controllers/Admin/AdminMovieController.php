<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMovieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movies = Movie::query()
            ->with('genres:id,name,slug')
            ->when($validated['search'] ?? null, function ($query, string $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('origin_name', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['limit'] ?? 10);

        return response()->json(['status' => 'success', 'data' => $movies]);
    }

    public function show(Movie $movie): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $movie->load('genres:id,name,slug')]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateMovie($request);
        $genreIds = $validated['genres'] ?? $validated['genre_ids'] ?? [];
        unset($validated['genres'], $validated['genre_ids']);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? $validated['name']);
        $validated['time'] ??= $validated['duration'] ?? null;
        unset($validated['duration']);

        $movie = DB::transaction(function () use ($validated, $genreIds) {
            $movie = Movie::create($validated);
            $movie->genres()->sync($genreIds);

            return $movie;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm phim thành công.',
            'data' => $movie->load('genres:id,name,slug'),
        ], 201);
    }

    public function update(Request $request, Movie $movie): JsonResponse
    {
        $validated = $this->validateMovie($request, $movie);
        $genreIds = $validated['genres'] ?? $validated['genre_ids'] ?? null;
        unset($validated['genres'], $validated['genre_ids']);
        if (array_key_exists('duration', $validated)) {
            $validated['time'] = $validated['duration'];
            unset($validated['duration']);
        }
        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $movie->id);
        }

        DB::transaction(function () use ($movie, $validated, $genreIds) {
            $movie->update($validated);
            if (is_array($genreIds)) {
                $movie->genres()->sync($genreIds);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật phim thành công.',
            'data' => $movie->fresh()->load('genres:id,name,slug'),
        ]);
    }

    public function destroy(Movie $movie): JsonResponse
    {
        $movie->delete();

        return response()->json(['status' => 'success', 'message' => 'Đã xóa phim.']);
    }

    public function approve(Movie $movie): JsonResponse
    {
        $movie->update(['status' => 'approved']);

        return response()->json(['status' => 'success', 'message' => 'Đã duyệt phim.']);
    }

    public function reject(Movie $movie): JsonResponse
    {
        $movie->update(['status' => 'rejected', 'is_pinned' => false]);

        return response()->json(['status' => 'success', 'message' => 'Đã từ chối phim.']);
    }

    public function togglePremium(Movie $movie): JsonResponse
    {
        $movie->update(['is_premium' => ! $movie->is_premium]);

        return response()->json(['status' => 'success', 'data' => $movie->fresh()]);
    }

    public function togglePin(Movie $movie): JsonResponse
    {
        $movie->update(['is_pinned' => ! $movie->is_pinned]);

        return response()->json(['status' => 'success', 'data' => $movie->fresh()]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => Genre::query()->orderBy('name')->get()]);
    }

    public function updateCategory(Request $request, Genre $genre): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('genres', 'slug')->ignore($genre->id)],
        ]);
        $validated['slug'] ??= Str::slug($validated['name']);
        $genre->update($validated);

        return response()->json(['status' => 'success', 'data' => $genre]);
    }

    private function validateMovie(Request $request, ?Movie $movie = null): array
    {
        return $request->validate([
            'name' => [$movie ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'origin_name' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'thumb_url' => ['nullable', 'string', 'max:2048'],
            'poster_url' => ['nullable', 'string', 'max:2048'],
            'trailer_url' => ['nullable', 'string', 'max:2048'],
            'quality' => ['nullable', 'string', 'max:255'],
            'lang' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1888', 'max:2100'],
            'time' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'episode_current' => ['nullable', 'string', 'max:255'],
            'episode_total' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'is_premium' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Movie::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
