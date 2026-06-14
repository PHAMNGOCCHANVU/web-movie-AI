<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OphimService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('services.ophim.base_url', 'https://ophim1.com'),
            '/'
        );
    }

    public function syncGenres(): array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/the-loai");

        if (! $response->successful()) {
            Log::error('Ophim API genre sync failed: '.$response->body());

            return ['success' => false, 'message' => 'Không thể kết nối Ophim API'];
        }

        $categories = $response->json();

        if (empty($categories)) {
            return ['success' => false, 'message' => 'Không có dữ liệu thể loại'];
        }

        $count = 0;
        foreach ($categories as $cat) {
            Genre::updateOrCreate(
                ['slug' => $cat['slug'] ?? $cat['name']],
                [
                    'ophim_id' => $cat['_id'] ?? null,
                    'name' => $cat['name'],
                    'slug' => $cat['slug'] ?? $cat['name'],
                ]
            );
            $count++;
        }

        return ['success' => true, 'count' => $count];
    }

    public function syncMovies(int $page = 1, int $limit = 20): array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/danh-sach/phim-moi-cap-nhat", [
            'page' => $page,
            'limit' => $limit,
        ]);

        if (! $response->successful()) {
            Log::error('Ophim API movie sync failed: '.$response->body());

            return ['success' => false, 'message' => 'Không thể kết nối Ophim API'];
        }

        $data = $response->json();
        $items = $data['items'] ?? $data['data'] ?? [];

        if (empty($items)) {
            return ['success' => false, 'message' => 'Không có dữ liệu phim', 'page' => $page];
        }

        $count = 0;
        foreach ($items as $item) {
            $movie = $this->upsertMovie($item);
            if ($movie) {
                $count++;
            }
        }

        return [
            'success' => true,
            'count' => $count,
            'page' => $page,
            'has_more' => count($items) >= $limit,
        ];
    }

    public function syncMoviesByGenre(string $genreSlug, int $page = 1, int $limit = 12): array
    {
        $response = Http::timeout(20)->get("{$this->baseUrl}/v1/api/the-loai/{$genreSlug}", [
            'page' => $page,
        ]);

        if (! $response->successful()) {
            Log::error("Ophim genre movie sync failed for {$genreSlug}: ".$response->body());

            return ['success' => false, 'message' => "Không thể tải phim thể loại {$genreSlug}"];
        }

        $items = collect($response->json('data.items', []))->take($limit);
        if ($items->isEmpty()) {
            return ['success' => false, 'message' => "Không có phim thể loại {$genreSlug}"];
        }

        $genre = Genre::where('slug', $genreSlug)->first();
        $slugs = [];

        foreach ($items as $item) {
            $movie = $this->upsertMovie($item);
            if (! $movie) {
                continue;
            }

            if ($genre) {
                $movie->genres()->syncWithoutDetaching([$genre->id]);
            }
            $slugs[] = $movie->slug;
        }

        return [
            'success' => true,
            'count' => count($slugs),
            'slugs' => $slugs,
            'genre' => $genreSlug,
        ];
    }

    public function syncMovieDetail(string $slug): ?Movie
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/phim/{$slug}");

        if (! $response->successful()) {
            Log::warning("Ophim detail sync failed for slug: {$slug}");

            return null;
        }

        $data = $response->json();
        $movieData = $data['movie'] ?? $data;

        $movie = $this->upsertMovie($movieData);

        // Sync episodes
        if ($movie && isset($data['episodes'])) {
            $this->syncEpisodes($movie, $data['episodes']);
        }

        return $movie;
    }

    protected function upsertMovie(array $data): ?Movie
    {
        $slug = $data['slug'] ?? null;
        if (! $slug) {
            return null;
        }

        try {
            $movie = Movie::updateOrCreate(
                ['slug' => $slug],
                [
                    'ophim_id' => $data['_id'] ?? null,
                    'name' => $data['name'] ?? 'Unknown',
                    'origin_name' => $data['origin_name'] ?? null,
                    'content' => $data['content'] ?? null,
                    'type' => $data['type'] ?? 'single',
                    'thumb_url' => $data['thumb_url'] ?? null,
                    'poster_url' => $data['poster_url'] ?? null,
                    'trailer_url' => $data['trailer_url'] ?? null,
                    'quality' => $data['quality'] ?? null,
                    'lang' => $data['lang'] ?? null,
                    'year' => $data['year'] ?? null,
                    'episode_current' => $data['episode_current'] ?? null,
                    'episode_total' => $data['episode_total'] ?? null,
                    'time' => $data['time'] ?? null,
                    'actor' => isset($data['actor']) ? (is_array($data['actor']) ? $data['actor'] : null) : null,
                    'director' => isset($data['director']) ? (is_array($data['director']) ? $data['director'] : null) : null,
                    'country' => isset($data['country']) ? (is_array($data['country']) ? $data['country'] : null) : null,
                    // Movies imported from OPhim must be reviewed by admin before users can see them.
                    'status' => 'pending',
                    'last_synced_at' => now(),
                ]
            );

            // Sync genres
            if (isset($data['category']) && is_array($data['category'])) {
                foreach ($data['category'] as $cat) {
                    $genre = Genre::where('slug', $cat['slug'] ?? $cat['name'])->first();
                    if (! $genre) {
                        $genre = Genre::create([
                            'ophim_id' => $cat['_id'] ?? null,
                            'name' => $cat['name'],
                            'slug' => $cat['slug'] ?? $cat['name'],
                        ]);
                    }
                    $movie->genres()->syncWithoutDetaching([$genre->id]);
                }
            }

            return $movie;
        } catch (\Exception $e) {
            Log::error("Failed to upsert movie {$slug}: ".$e->getMessage());

            return null;
        }
    }

    protected function syncEpisodes(Movie $movie, array $episodesData): void
    {
        foreach ($episodesData as $server) {
            $serverName = $server['server_name'] ?? 'Unknown';

            if (! isset($server['server_data']) || ! is_array($server['server_data'])) {
                continue;
            }

            foreach ($server['server_data'] as $index => $ep) {
                Episode::updateOrCreate(
                    [
                        'movie_id' => $movie->id,
                        'server_name' => $serverName,
                        'slug' => $ep['slug'] ?? (string) ($index + 1),
                    ],
                    [
                        'name' => $ep['name'] ?? (string) ($index + 1),
                        'filename' => $ep['filename'] ?? null,
                        'link_embed' => $ep['link_embed'] ?? null,
                        'link_m3u8' => $ep['link_m3u8'] ?? null,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
