<?php

use App\Http\Controllers\AI\AiChatController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Admin\CommentModerationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Genre\GenreController;
use App\Http\Controllers\Homepage\HomepageBlockController;
use App\Http\Controllers\Movie\CommentController;
use App\Http\Controllers\Movie\EpisodeController;
use App\Http\Controllers\Movie\MovieController;
use App\Http\Controllers\Movie\RatingController;
use App\Http\Controllers\Movie\StreamController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\Subscription\VNPayController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\WatchHistoryController;
use App\Http\Controllers\User\WatchlistController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminMovieController;
use App\Http\Controllers\Admin\OphimSyncController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminHomepageBlockController;
use App\Http\Controllers\Client\MovieController as ClientMovieController;
use Illuminate\Http\Request;

// === PUBLIC ROUTES ===

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/verify-password-otp', [AuthController::class, 'verifyPasswordOtp'])->middleware('throttle:10,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
});

// Movies
Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/search', [MovieController::class, 'search']);
Route::get('/movies/featured', [MovieController::class, 'featured']);
Route::get('/movies/new-updated', [MovieController::class, 'newUpdated']);
Route::get('/movies/{movie}', [MovieController::class, 'show']);
Route::get('/movies/{movie}/cast', [MovieController::class, 'cast']);
Route::get('/movies/{movie}/trailer', [MovieController::class, 'trailer']);
Route::get('/movies/{movie}/episodes', [EpisodeController::class, 'index']);
Route::get('/movies/{movie}/comments', [CommentController::class, 'index']);
Route::get('/movies/{movie}/ratings', [RatingController::class, 'summary']);

// Genres
Route::get('/genres', [GenreController::class, 'index']);
Route::get('/genres/{genre}/movies', [GenreController::class, 'movies']);

// Homepage
Route::get('/homepage-blocks', [HomepageBlockController::class, 'index']);

// Subscription plans (public)
Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);

// VNPay callbacks (no auth)
Route::get('/payment/vnpay/return', [VNPayController::class, 'return']);
Route::match(['get', 'post'], '/payment/vnpay/ipn', [VNPayController::class, 'ipn']);

// === AUTHENTICATED ROUTES ===
Route::middleware(['auth:sanctum', 'check.locked'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'data' => $request->user()->load(['role', 'subscriptionPlan'])
        ]);
    });

    // Profile
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/change-password', [ProfileController::class, 'changePassword']);

    // Watchlist
    Route::get('/user/watchlist', [WatchlistController::class, 'index']);
    Route::post('/user/watchlist', [WatchlistController::class, 'store']);
    Route::delete('/user/watchlist/{movie}', [WatchlistController::class, 'destroy']);

    // Watch History
    Route::get('/user/watch-history', [WatchHistoryController::class, 'index']);
    Route::post('/user/watch-history', [WatchHistoryController::class, 'store']);
    Route::delete('/user/watch-history/{movie}', [WatchHistoryController::class, 'destroy']);

    // Comments & Ratings
    Route::post('/movies/{movie}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/movies/{movie}/ratings', [RatingController::class, 'store']);

    // Subscription & Payment
    Route::get('/user/subscription', [SubscriptionController::class, 'show']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/user/payment-history', [SubscriptionController::class, 'paymentHistory']);
    Route::get('/user/payments/{transaction}', [SubscriptionController::class, 'paymentStatus']);
    Route::post('/payment/vnpay/create', [VNPayController::class, 'create']);

    // Stream (subscription check inside controller)
    Route::get('/movies/{movie}/stream', [StreamController::class, 'stream']);

    // AI Chat
    Route::post('/ai/chat', [AiChatController::class, 'chat']);
    Route::get('/ai/chat/history', [AiChatController::class, 'history']);
    Route::delete('/ai/chat/history', [AiChatController::class, 'clearHistory']);
});

/* API ROUTES CHO USER (Xác thực bằng Sanctum)*/
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/* PUBLIC API ROUTES (Dành cho Frontend User từ admin branch) */
Route::prefix('client')->group(function () {
    // 1. Lấy danh sách phim đã duyệt ra trang chủ
    Route::get('/movies', [ClientMovieController::class, 'getHomeMovies']);
    
    // 2. Lấy chi tiết phim và link xem video (m3u8)
    Route::get('/movies/{id}', [ClientMovieController::class, 'getMovieDetails']);
});

/* ADMIN API ROUTES */
Route::middleware(['auth:sanctum', 'check.locked', 'admin'])->prefix('admin')->group(function () {
    
    // =========================================================
    // 9. Đồng bộ dữ liệu phim (ETL - Nguồn Ophim)
    // =========================================================
    Route::match(['get', 'post'], '/sync/ophim', [OphimSyncController::class, 'syncMovies']);
    Route::post('/sync/ophim/movies', [OphimSyncController::class, 'syncMovies']);
    Route::post('/sync/ophim/genres', [OphimSyncController::class, 'syncGenres']);

    // =========================================================
    // 11. Admin - Movie Management (Quản lý Phim & Kiểm duyệt)
    // =========================================================
    Route::get('/movies', [AdminMovieController::class, 'getAllMovies']);        // Lấy danh sách phim (có thể lọc theo status)
    Route::post('/movies', [AdminMovieController::class, 'createMovie']);        // Thêm phim mới
    Route::put('/movies/{id}', [AdminMovieController::class, 'updateMovie']);    // Sửa thông tin phim
    Route::delete('/movies/{id}', [AdminMovieController::class, 'deleteMovie']); // Xóa phim

    // Thao tác nhanh với phim (Bật/tắt trạng thái)
    Route::patch('/movies/{id}/premium', [AdminMovieController::class, 'togglePremium']); // Bật/tắt trả phí
    Route::patch('/movies/{id}/pin', [AdminMovieController::class, 'togglePin']);         // Ghim/Gỡ ghim trang chủ

    // BƯỚC 2 WORKFLOW: KIỂM DUYỆT PHIM
    Route::patch('/movies/{id}/approve', [AdminMovieController::class, 'approveMovie']); // Duyệt phim
    Route::delete('/movies/{id}/reject', [AdminMovieController::class, 'rejectMovie']);  // Từ chối (xóa) phim

    // =========================================================
    // 11b. Admin - Category Management (Quản lý Thể loại)
    // =========================================================
    Route::get('/categories', [AdminMovieController::class, 'getCategories']);       // Lấy danh sách thể loại
    Route::put('/categories/{id}', [AdminMovieController::class, 'updateCategory']); // Sửa tên thể loại

    // =========================================================
    // 11c. Admin - Homepage Block Management (Quản lý Khối trang chủ)
    // =========================================================
    Route::get('/homepage-blocks', [AdminHomepageBlockController::class, 'index']);
    Route::post('/homepage-blocks', [AdminHomepageBlockController::class, 'store']);
    Route::put('/homepage-blocks/{id}', [AdminHomepageBlockController::class, 'update']);
    Route::delete('/homepage-blocks/{id}', [AdminHomepageBlockController::class, 'destroy']);

    // =========================================================
    // 12. Admin - User Management (Quản lý Người dùng)
    // =========================================================
    Route::get('/users', [AdminUserController::class, 'index']);               // 1. Get All Users
    Route::get('/users/{id}', [AdminUserController::class, 'show']);           // 2. Get User Detail
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole']);   // 3. Change User Role
    Route::patch('/users/{id}/status', [AdminUserController::class, 'updateStatus']); // 4. Lock/Unlock User
    Route::patch('/users/{id}/subscription', [AdminUserController::class, 'updateSubscription']); // 5. Update Subscription

    // =========================================================
    // 13. Admin - Comment Moderation (Duyệt bình luận)
    // =========================================================
    Route::get('/comments', [AdminCommentController::class, 'index']);                        // Lấy danh sách
    Route::patch('/comments/{id}/approve', [AdminCommentController::class, 'approve']);       // Duyệt
    Route::patch('/comments/{id}/hide', [AdminCommentController::class, 'hide']);             // Ẩn
    Route::patch('/comments/{id}/restore', [AdminCommentController::class, 'restore']);       // Khôi phục
    Route::delete('/comments/{id}', [AdminCommentController::class, 'destroy']);              // Xóa
    Route::get('/comments/{id}/sentiment', [AdminCommentController::class, 'sentimentAnalysis']); // AI Sentiment

    // Comment Moderation (backend-2)
    Route::get('/comment-moderation', [CommentModerationController::class, 'index']);
    Route::patch('/comment-moderation/{comment}', [CommentModerationController::class, 'review']);

    // =========================================================
    // 14. Admin - Transaction Management (Lịch sử Giao dịch)
    // =========================================================
    Route::get('/transactions', [AdminTransactionController::class, 'index']);      // Lấy danh sách
    Route::get('/transactions/{id}', [AdminTransactionController::class, 'show']);  // Xem chi tiết

    // =========================================================
    // 10. Admin - Dashboard (Thống kê Tổng quan)
    // =========================================================
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats']);
    Route::get('/dashboard/revenue', [AdminDashboardController::class, 'getRevenue']);
    Route::get('/dashboard/sentiment', [AdminDashboardController::class, 'getSentimentOverview']);
    Route::get('/dashboard/top-movies', [AdminDashboardController::class, 'getTopMovies']);
});
