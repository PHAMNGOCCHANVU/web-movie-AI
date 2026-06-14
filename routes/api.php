<?php

use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminHomepageBlockController;
use App\Http\Controllers\Admin\AdminMovieController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CommentModerationController;
use App\Http\Controllers\Admin\OphimSyncController;
use App\Http\Controllers\AI\AiChatController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum', 'check.locked'])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
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
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/dashboard/revenue', [AdminDashboardController::class, 'revenue']);
        Route::get('/dashboard/sentiment', [AdminDashboardController::class, 'sentiment']);
        Route::get('/dashboard/top-movies', [AdminDashboardController::class, 'topMovies']);
        Route::get('/movies/top-views', [AdminDashboardController::class, 'topViewedMovies']);

        Route::get('/movies', [AdminMovieController::class, 'index']);
        Route::post('/movies', [AdminMovieController::class, 'store']);
        Route::get('/movies/{movie}', [AdminMovieController::class, 'show']);
        Route::put('/movies/{movie}', [AdminMovieController::class, 'update']);
        Route::delete('/movies/{movie}', [AdminMovieController::class, 'destroy']);
        Route::patch('/movies/{movie}/approve', [AdminMovieController::class, 'approve']);
        Route::patch('/movies/{movie}/reject', [AdminMovieController::class, 'reject']);
        Route::patch('/movies/{movie}/premium', [AdminMovieController::class, 'togglePremium']);
        Route::patch('/movies/{movie}/pin', [AdminMovieController::class, 'togglePin']);
        Route::get('/categories', [AdminMovieController::class, 'categories']);
        Route::put('/categories/{genre}', [AdminMovieController::class, 'updateCategory']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::patch('/users/{user}/subscription', [AdminUserController::class, 'updateSubscription']);

        Route::get('/comments', [AdminCommentController::class, 'index']);
        Route::patch('/comments/{comment}/approve', [AdminCommentController::class, 'approve']);
        Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide']);
        Route::patch('/comments/{comment}/restore', [AdminCommentController::class, 'restore']);
        Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);
        Route::get('/comments/{comment}/sentiment', [AdminCommentController::class, 'sentiment']);

        Route::get('/transactions', [AdminTransactionController::class, 'index']);
        Route::get('/transactions/{id}', [AdminTransactionController::class, 'show']);

        Route::get('/homepage-blocks', [AdminHomepageBlockController::class, 'index']);
        Route::post('/homepage-blocks', [AdminHomepageBlockController::class, 'store']);
        Route::put('/homepage-blocks/{id}', [AdminHomepageBlockController::class, 'update']);
        Route::delete('/homepage-blocks/{id}', [AdminHomepageBlockController::class, 'destroy']);

        Route::post('/sync/ophim/movies', [OphimSyncController::class, 'movies']);
        Route::post('/sync/ophim/genres', [OphimSyncController::class, 'genres']);

        Route::get('/comment-moderation', [CommentModerationController::class, 'index']);
        Route::patch('/comment-moderation/{comment}', [CommentModerationController::class, 'review']);
    });
});
