<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Genre;
use App\Models\Movie;

class TMDBService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('TMDB_BASE_URL');
        $this->apiKey = env('TMDB_API_KEY');
    }

    // Hàm cào dữ liệu Thể loại từ TMDB
    public function syncGenres()
    {
        // 1. Gọi API đến máy chủ TMDB (Kèm khóa bảo mật và yêu cầu trả về tiếng Việt)
        $response = Http::get("{$this->baseUrl}/genre/movie/list", [
            'api_key' => $this->apiKey,
            'language' => 'vi'
        ]);

        // 2. Nếu gọi thành công (HTTP 200)
        if ($response->successful()) {
            $genres = $response->json('genres'); // Lấy mảng 'genres' từ JSON trả về
            
            $count = 0;
            // 3. Vòng lặp duyệt qua từng thể loại và lưu vào Database
            foreach ($genres as $genre) {
                // updateOrCreate: Nếu tmdb_id đã có thì cập nhật tên, nếu chưa có thì tạo mới
                Genre::updateOrCreate(
                    ['tmdb_id' => $genre['id']], 
                    ['name' => $genre['name']]
                );
                $count++;
            }
            return $count; // Trả về số lượng đã đồng bộ
        }

        return false; // Báo lỗi nếu TMDB sập hoặc sai API Key
    }

    // Đồng bộ danh sách Phim nổi bật từ TMDB
    public function syncMovies()
    {
        // 1. Gọi API lấy danh sách phim phổ biến (popular)
        $response = Http::get("{$this->baseUrl}/movie/popular", [
            'api_key' => $this->apiKey,
            'language' => 'vi', // Lấy tiếng Việt
            'page' => 1         // Lấy trang đầu tiên (20 phim hot nhất)
        ]);

        if ($response->successful()) {
            $movies = $response->json('results');
            $count = 0;

            foreach ($movies as $item) {
                // 2. Tạo hoặc Cập nhật phim (dùng tmdb_id làm điều kiện gốc để dò tìm)
                $movie = Movie::updateOrCreate(
                    ['tmdb_id' => $item['id']], 
                    [
                        'name' => $item['title'],
                        'description' => $item['overview'],
                        'release_date' => $item['release_date'] ?? null,
                        'poster_url' => $item['poster_path'] ? "https://image.tmdb.org/t/p/w500{$item['poster_path']}" : null,
                        'is_premium' => false 
                    ]
                );

                // 3. Xử lý gắn Thể loại (Mối quan hệ Nhiều - Nhiều)
                if (isset($item['genre_ids']) && count($item['genre_ids']) > 0) {
                    // TMDB trả về mảng ID thể loại của họ. Ta phải tìm ID tương ứng trong bảng genres của mình
                    $localGenreIds = Genre::whereIn('tmdb_id', $item['genre_ids'])->pluck('id');
                    
                    // Gắn vào bảng trung gian genre_movie
                    $movie->genres()->sync($localGenreIds);
                }
                
                $count++;
            }
            return $count;
        }

        return false;
    }
}