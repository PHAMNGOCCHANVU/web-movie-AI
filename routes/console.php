<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Movie;
use App\Services\OphimService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ophim:sync {--limit=30}', function (OphimService $ophim) {
    $limit = max(1, min(100, (int) $this->option('limit')));
    $startedAt = now();

    $this->info('Đang đồng bộ thể loại từ OPhim...');
    $genreResult = $ophim->syncGenres();
    if (! ($genreResult['success'] ?? false)) {
        $this->error($genreResult['message'] ?? 'Đồng bộ thể loại thất bại.');
        return self::FAILURE;
    }

    $this->info("Đang đồng bộ tối thiểu {$limit} phim...");
    $synced = 0;
    $page = 1;
    while ($synced < $limit && $page <= 10) {
        $movieResult = $ophim->syncMovies($page, min(24, $limit - $synced));
        if (! ($movieResult['success'] ?? false)) {
            if ($page === 1) {
                $this->error($movieResult['message'] ?? 'Đồng bộ phim thất bại.');
                return self::FAILURE;
            }
            break;
        }

        $synced += (int) ($movieResult['count'] ?? 0);
        if (! ($movieResult['has_more'] ?? false)) {
            break;
        }
        $page++;
    }

    $movies = Movie::whereNotNull('slug')
        ->where('last_synced_at', '>=', $startedAt)
        ->orderBy('last_synced_at')
        ->get();

    $bar = $this->output->createProgressBar($movies->count());
    foreach ($movies as $movie) {
        $ophim->syncMovieDetail($movie->slug);
        $bar->advance();
    }
    $bar->finish();
    $this->newLine(2);
    $this->info("Đã đồng bộ chi tiết và tập phim cho {$movies->count()} phim.");

    return self::SUCCESS;
})->purpose('Đồng bộ thể loại, phim và tập phim từ OPhim');

Artisan::command('ophim:sync-genres {genres*} {--limit=12}', function (OphimService $ophim) {
    $genres = $this->argument('genres');
    $limit = max(1, min(24, (int) $this->option('limit')));
    $slugs = collect();

    $ophim->syncGenres();

    foreach ($genres as $genre) {
        $this->info("Đang tải phim thể loại {$genre}...");
        $result = $ophim->syncMoviesByGenre($genre, 1, $limit);
        if (! ($result['success'] ?? false)) {
            $this->warn($result['message'] ?? "Không tải được thể loại {$genre}.");
            continue;
        }
        $slugs = $slugs->merge($result['slugs'] ?? []);
    }

    $slugs = $slugs->unique()->values();
    $bar = $this->output->createProgressBar($slugs->count());
    foreach ($slugs as $slug) {
        $ophim->syncMovieDetail($slug);
        $bar->advance();
    }
    $bar->finish();
    $this->newLine(2);
    $this->info("Đã đồng bộ chi tiết cho {$slugs->count()} phim thuộc ".count($genres).' thể loại.');

    return self::SUCCESS;
})->purpose('Đồng bộ phim OPhim theo danh sách thể loại');
