<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TMDBService;

class TMDBSyncController extends Controller
{
    protected TMDBService $tmdbService;

    // Nhúng TMDBService vào Controller
    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    public function syncGenres()
    {
        // Gọi hàm xử lý từ tầng Service
        $count = $this->tmdbService->syncGenres();

        if ($count !== false) {
            return response()->json([
                'status' => 'success',
                'message' => "Đồng bộ thành công {$count} thể loại từ TMDB."
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Lỗi kết nối đến máy chủ TMDB. Vui lòng kiểm tra API Key.'
        ], 500);
    }

    public function syncMovies()
    {
        $count = $this->tmdbService->syncMovies();

        if ($count !== false) {
            return response()->json([
                'status' => 'success',
                'message' => "Đồng bộ thành công {$count} bộ phim hot nhất từ TMDB."
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Lỗi kết nối khi tải phim từ TMDB.'
        ], 500);
    }
}