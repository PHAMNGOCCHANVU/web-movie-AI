<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        // Seed Genre
        $genreId = \Illuminate\Support\Facades\DB::table('genres')->insertGetId([
            'name' => 'Hành động',
            'slug' => 'hanh-dong',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed Movies
        $movie1Id = \Illuminate\Support\Facades\DB::table('movies')->insertGetId([
            'ophim_id' => 'movie1',
            'slug' => 'phim-standard',
            'name' => 'Phim Standard',
            'is_premium' => false,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movie2Id = \Illuminate\Support\Facades\DB::table('movies')->insertGetId([
            'ophim_id' => 'movie2',
            'slug' => 'phim-premium',
            'name' => 'Phim VIP',
            'is_premium' => true,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Associate Genre to Movies
        \Illuminate\Support\Facades\DB::table('genre_movie')->insert([
            ['genre_id' => $genreId, 'movie_id' => $movie1Id],
            ['genre_id' => $genreId, 'movie_id' => $movie2Id],
        ]);

        // Seed Episodes
        \Illuminate\Support\Facades\DB::table('episodes')->insert([
            [
                'movie_id' => $movie1Id,
                'server_name' => 'Vietsub #1',
                'name' => '1',
                'slug' => '1',
                'link_m3u8' => 'https://example.com/movie1/ep1.m3u8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'movie_id' => $movie2Id,
                'server_name' => 'Vietsub #1',
                'name' => '1',
                'slug' => '1',
                'link_m3u8' => 'https://example.com/movie2/ep1.m3u8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create Default Test Admin/Users
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
