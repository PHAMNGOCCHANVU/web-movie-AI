<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminMovieController;
use App\Http\Controllers\Admin\TMDBSyncController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminDashboardController;

/* API ROUTES CHO USER (Xác thực bằng Sanctum)*/
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/*ADMIN API ROUTES*/
Route::prefix('admin')->group(function () {
    
    // --- 9. TMDB Sync (Đồng bộ dữ liệu) ---
    Route::post('/sync/genres', [TMDBSyncController::class, 'syncGenres']);
    Route::post('/sync/movies', [TMDBSyncController::class, 'syncMovies']);


    // --- 11. Admin - Movie Management (Quản lý Phim) ---
    Route::get('/movies', [AdminMovieController::class, 'getAllMovies']);        // Lấy danh sách phim
    Route::post('/movies', [AdminMovieController::class, 'createMovie']);        // Thêm phim mới
    Route::put('/movies/{id}', [AdminMovieController::class, 'updateMovie']);    // Sửa thông tin phim
    Route::delete('/movies/{id}', [AdminMovieController::class, 'deleteMovie']); // Xóa phim

    // Thao tác nhanh với phim (Bật/tắt trạng thái)
    Route::patch('/movies/{id}/premium', [AdminMovieController::class, 'togglePremium']); // Bật/tắt trả phí
    Route::patch('/movies/{id}/pin', [AdminMovieController::class, 'togglePin']);         // Ghim/Gỡ ghim trang chủ

    // --- 11. Admin - Category Management (Quản lý Thể loại) ---
    Route::get('/categories', [AdminMovieController::class, 'getCategories']);       // Lấy danh sách thể loại
    Route::put('/categories/{id}', [AdminMovieController::class, 'updateCategory']); // Sửa tên thể loại

    // --- 12. Admin - User Management (Quản lý Người dùng) ---
    Route::get('/users', [AdminUserController::class, 'index']);               // 1. Get All Users
    Route::get('/users/{id}', [AdminUserController::class, 'show']);           // 2. Get User Detail
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole']);   // 3. Change User Role
    Route::patch('/users/{id}/status', [AdminUserController::class, 'updateStatus']); // 4. Lock/Unlock User

    // --- 13. Admin - Comment Moderation (Duyệt bình luận) ---
    Route::get('/comments', [AdminCommentController::class, 'index']);                        // Lấy danh sách
    Route::patch('/comments/{id}/approve', [AdminCommentController::class, 'approve']);       // Duyệt
    Route::patch('/comments/{id}/hide', [AdminCommentController::class, 'hide']);             // Ẩn
    Route::patch('/comments/{id}/restore', [AdminCommentController::class, 'restore']);       // Khôi phục
    Route::delete('/comments/{id}', [AdminCommentController::class, 'destroy']);              // Xóa
    Route::get('/comments/{id}/sentiment', [AdminCommentController::class, 'sentimentAnalysis']); // AI Sentiment

    // --- 14. Admin - Transaction Management (Lịch sử Giao dịch) ---
    Route::get('/transactions', [AdminTransactionController::class, 'index']);      // Lấy danh sách
    Route::get('/transactions/{id}', [AdminTransactionController::class, 'show']);  // Xem chi tiết

    // --- 10. Admin - Dashboard (Thống kê Tổng quan) ---
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats']);
    Route::get('/dashboard/revenue', [AdminDashboardController::class, 'getRevenue']);
    Route::get('/dashboard/sentiment', [AdminDashboardController::class, 'getSentimentOverview']);
    Route::get('/dashboard/top-movies', [AdminDashboardController::class, 'getTopMovies']);

});