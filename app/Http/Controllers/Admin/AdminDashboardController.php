<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Movie;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => User::count(),
                'total_movies' => Movie::count(),
                'total_revenue' => Transaction::where('status', 'success')->sum('amount'),
                'total_comments' => Comment::count(),
            ],
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,monthly,yearly'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $period = $validated['period'] ?? 'monthly';

        $transactions = Transaction::where('status', 'success')
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->get();

        $data = $transactions->groupBy(fn ($transaction) => match ($period) {
            'daily' => $transaction->created_at->format('Y-m-d'),
            'yearly' => $transaction->created_at->format('Y'),
            default => $transaction->created_at->format('Y-m'),
        })->map->sum('amount');

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function sentiment(): JsonResponse
    {
        $counts = Comment::query()
            ->selectRaw("SUM(moderation_status = 'approved') AS positive_safe")
            ->selectRaw("SUM(moderation_status = 'pending_review') AS neutral_warning")
            ->selectRaw("SUM(moderation_status = 'hidden') AS toxic_danger")
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'positive_safe' => (int) ($counts->positive_safe ?? 0),
                'neutral_warning' => (int) ($counts->neutral_warning ?? 0),
                'toxic_danger' => (int) ($counts->toxic_danger ?? 0),
            ],
        ]);
    }

    public function topMovies(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 5), 1), 20);

        return response()->json([
            'status' => 'success',
            'data' => Movie::withCount('comments')->orderByDesc('comments_count')->limit($limit)->get(),
        ]);
    }

    public function topViewedMovies(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 5), 1), 20);

        return response()->json([
            'status' => 'success',
            'data' => Movie::orderByDesc('view_count')->limit($limit)->get(['id', 'name', 'view_count']),
        ]);
    }
}
