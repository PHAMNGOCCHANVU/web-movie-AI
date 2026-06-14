<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    // API 1: Lấy danh sách giao dịch (Có lọc theo status và thời gian từ/đến)
    public function index(Request $request)
    {
        $query = Transaction::with('user')->orderBy('created_at', 'desc');

        // Lọc theo trạng thái (pending, success, failed)
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Lọc theo ngày bắt đầu (from)
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        // Lọc theo ngày kết thúc (to)
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $limit = $request->input('limit', 20);
        $transactions = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ], 200);
    }

    // API 2: Lấy chi tiết 1 giao dịch
    public function show(string $id)
    {
        $transaction = Transaction::with('user')->find($id);

        if (! $transaction) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy giao dịch'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $transaction,
        ], 200);
    }
}
