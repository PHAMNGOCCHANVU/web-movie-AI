<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Movie;
use App\Models\Transaction;
use App\Models\Comment;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // API 1: Thống kê tổng quan (Dashboard Stats)
    public function getStats()
    {
        $totalUsers = User::count();
        $totalMovies = Movie::count();
        $totalRevenue = Transaction::where('status', 'success')->sum('amount');
        $totalComments = Comment::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'total_movies' => $totalMovies,
                'total_revenue' => $totalRevenue,
                'total_comments' => $totalComments
            ]
        ], 200);
    }

    // API 2: Thống kê doanh thu theo thời gian (Revenue Stats)
    public function getRevenue(Request $request)
    {
        $from = $request->input('from', now()->startOfYear());
        $to = $request->input('to', now()->endOfYear());
        $period = $request->input('period', 'monthly'); // daily, monthly, yearly

        // Lấy tất cả giao dịch thành công trong khoảng thời gian
        $transactions = Transaction::where('status', 'success')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->get();

        // Sử dụng Laravel Collection để nhóm dữ liệu an toàn mà không sợ lỗi SQL
        $revenueData = $transactions->groupBy(function ($item) use ($period) {
            if ($period === 'daily') return $item->created_at->format('Y-m-d');
            if ($period === 'yearly') return $item->created_at->format('Y');
            return $item->created_at->format('Y-m'); // Mặc định là monthly
        })->map(function ($row) {
            return $row->sum('amount');
        });

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'data' => $revenueData
        ], 200);
    }

    // API 3: Thống kê tổng quan AI Sentiment
    public function getSentimentOverview()
    {
        // Đếm số lượng bình luận theo từng mức độ Toxic (từ AI)
        $positive = Comment::where('toxic_score', '<', 0.4)->count();
        $neutral = Comment::whereBetween('toxic_score', [0.4, 0.7])->count();
        $toxic = Comment::where('toxic_score', '>', 0.7)->count();
        $unprocessed = Comment::whereNull('toxic_score')->count(); // Chưa được AI quét

        return response()->json([
            'status' => 'success',
            'data' => [
                'positive_safe' => $positive,
                'neutral_warning' => $neutral,
                'toxic_danger' => $toxic,
                'unprocessed' => $unprocessed
            ]
        ], 200);
    }

    // API 4: Bảng xếp hạng phim Hot (Nhiều bình luận nhất)
    public function getTopMovies(Request $request)
    {
        $limit = $request->input('limit', 10);

        // withCount('comments') sẽ tự động đếm số lượng bình luận của mỗi phim
        $topMovies = Movie::withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $topMovies
        ], 200);
    }
}