<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Episode;
use Illuminate\Http\Request;

class AdminMovieController extends Controller
{
    // =========================================================
    // NHÓM 1: QUẢN LÝ THỂ LOẠI (CATEGORIES / GENRES)
    // =========================================================

    // API 1: Lấy toàn bộ danh sách Thể loại
    public function getCategories()
    {
        $categories = Genre::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Lấy danh sách thể loại thành công',
            'data' => $categories
        ], 200);
    }

    // API 2: Cập nhật tên Thể loại
    public function updateCategory(Request $request, string $id)
    {
        $category = Genre::find($id);
        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy thể loại này'], 404);
        }

        $category->name = $request->input('name');
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật thể loại thành công',
            'data' => $category
        ], 200);
    }

    // =========================================================
    // NHÓM 2: QUẢN LÝ PHIM (MOVIE CRUD)
    // =========================================================

    // API 3: Lấy danh sách toàn bộ Phim (Có lọc theo status và phân trang)
    public function getAllMovies(Request $request)
    {
        $query = Movie::with('genres')->orderBy('created_at', 'desc');

        // Bổ sung bộ lọc status cho Admin (Ví dụ: lọc phim 'pending' để duyệt)
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $movies = $query->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Lấy danh sách phim thành công',
            'data' => $movies
        ], 200);
    }

    // API 4: Thêm phim mới (Thủ công)
    public function createMovie(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
            'duration' => 'nullable|integer',
            'poster_url' => 'nullable|string',
            'trailer_url' => 'nullable|string',
            'is_premium' => 'boolean',
            'is_pinned' => 'boolean',
            'genre_ids' => 'array',
            'tmdb_id' => 'nullable|integer|unique:movies,tmdb_id'
        ]);

        $movie = Movie::create($validatedData);

        if ($request->has('genre_ids')) {
            $movie->genres()->attach($request->input('genre_ids'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm phim mới thành công',
            'data' => Movie::with('genres')->find($movie->id)
        ], 201);
    }

    // API 5: Cập nhật thông tin Phim
    public function updateMovie(Request $request, string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
            'duration' => 'nullable|integer',
            'poster_url' => 'nullable|string',
            'trailer_url' => 'nullable|string',
            'is_premium' => 'boolean',
            'is_pinned' => 'boolean',
            'genre_ids' => 'array'
        ]);

        $movie->update($validatedData);

        if ($request->has('genre_ids')) {
            $movie->genres()->sync($request->input('genre_ids'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật phim thành công',
            'data' => Movie::with('genres')->find($id)
        ], 200);
    }

    // API 6: Xóa Phim
    public function deleteMovie(string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        $movie->episodes()->delete(); // Xóa các tập phim trước
        $movie->genres()->detach();   // Gỡ liên kết thể loại
        $movie->delete();             // Xóa phim

        return response()->json([
            'status' => 'success',
            'message' => 'Đã xóa phim thành công!'
        ], 200);
    }

    // =========================================================
    // NHÓM 3: THAO TÁC NHANH (QUICK ACTIONS)
    // =========================================================

    // API 7: Bật/Tắt trạng thái Premium
    public function togglePremium(string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        $movie->is_premium = !$movie->is_premium; 
        $movie->save();

        $statusText = $movie->is_premium ? 'Premium (Trả phí)' : 'Free (Miễn phí)';
        return response()->json([
            'status' => 'success',
            'message' => "Đã chuyển phim sang trạng thái: $statusText"
        ], 200);
    }

    // API 8: Ghim/Bỏ ghim phim lên Slider Trang chủ
    public function togglePin(string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        $movie->is_pinned = !$movie->is_pinned; 
        $movie->save();

        $statusText = $movie->is_pinned ? 'Đã ghim phim lên trang chủ' : 'Đã gỡ ghim khỏi trang chủ';
        return response()->json([
            'status' => 'success',
            'message' => $statusText
        ], 200);
    }

    // =========================================================
    // NHÓM 4: KIỂM DUYỆT PHIM (MODERATION WORKFLOW)
    // =========================================================

    // API 9: Duyệt phim (Chuyển status từ pending -> approved)
    public function approveMovie(string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        // Chuyển trạng thái sang đã duyệt
        $movie->status = 'approved';
        $movie->save();

        return response()->json([
            'status' => 'success',
            'message' => "Đã DUYỆT bộ phim: {$movie->name}. Phim đã được hiển thị ra trang chủ."
        ], 200);
    }

    // API 10: Từ chối phim (Xóa phim rác khỏi hệ thống)
    public function rejectMovie(string $id)
    {
        $movie = Movie::find($id);
        if (!$movie) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy phim'], 404);
        }

        // Lưu ý: Phải xóa các tập phim (episodes) liên kết trước để tránh lỗi khóa ngoại
        $movie->episodes()->delete(); 
        
        // Gỡ liên kết thể loại và xóa phim
        $movie->genres()->detach();
        $movie->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Đã TỪ CHỐI và xóa vĩnh viễn phim: {$movie->name}."
        ], 200);
    }

    // API 11: Xóa một luồng phát / server lỗi cụ thể
    public function deleteEpisode(string $id)
    {
        $episode = Episode::find($id);
        if (!$episode) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy luồng phát này'], 404);
        }

        $episode->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Đã xóa server phát lỗi: {$episode->name}."
        ], 200);
    }
}