<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // API 1: Hiển thị phim ra trang chủ (Chỉ lấy phim ĐÃ DUYỆT)
    public function getHomeMovies()
    {
        // Điều kiện kiên quyết: status phải là 'approved'
        $movies = Movie::where('status', 'approved')
            ->with('genres') // Lấy kèm thông tin thể loại
            ->orderBy('created_at', 'desc')
            ->paginate(12); // Mỗi trang 12 phim cho đẹp giao diện

        return response()->json([
            'status' => 'success',
            'message' => 'Lấy danh sách phim trang chủ thành công',
            'data' => $movies
        ], 200);
    }

    // API 2: Lấy chi tiết phim và danh sách tập phim (kèm link m3u8)
    public function getMovieDetails(string $id)
    {
        // Hướng đi tốt nhất: Dùng Eager Loading (with) để lấy luôn Thể loại và Tập phim trong 1 lần gọi DB.
        // Bắt buộc phải có điều kiện 'approved' để chặn user gõ mò ID phim chưa duyệt.
        $movie = Movie::with(['genres', 'episodes'])
                      ->where('status', 'approved')
                      ->find($id);

        if (!$movie) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Phim không tồn tại, đã bị xóa hoặc chưa được kiểm duyệt.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lấy thông tin chi tiết phim thành công.',
            'data' => $movie
        ], 200);
    }
}