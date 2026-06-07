<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; 
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // API 1: LẤY DANH SÁCH NGƯỜI DÙNG (Có bộ lọc & phân trang)
    public function index(Request $request)
    {
        // Khởi tạo truy vấn, tự động nối bảng Role và SubscriptionPlan (Eager Loading)
        $query = User::with(['role', 'subscriptionPlan'])->orderBy('created_at', 'desc');

        // Lọc theo Role (nếu Postman có gửi tham số ?role=...)
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        // Lọc theo Trạng thái (nếu Postman có gửi tham số ?status=locked/active)
        if ($request->filled('status')) {
            $isLocked = $request->input('status') === 'locked' ? true : false;
            $query->where('is_locked', $isLocked);
        }

        // Cắt trang theo số limit (Mặc định 20 nếu Postman không gửi)
        $limit = $request->input('limit', 20);
        $users = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Lấy danh sách người dùng thành công',
            'data' => $users
        ], 200);
    }

    // API 2: XEM CHI TIẾT 1 NGƯỜI DÙNG
    public function show(string $id)
    {
        $user = User::with(['role', 'subscriptionPlan'])->find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy người dùng'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    // API 3: THAY ĐỔI QUYỀN (Role)
    public function updateRole(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy người dùng'], 404);
        }

        // Lấy tên quyền Postman gửi lên (VD: "vip") và dò tìm ID trong database
        $roleName = $request->input('role'); 
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => "Không tìm thấy quyền: $roleName"], 400);
        }

        // Cập nhật và lưu lại
        $user->role_id = $role->id;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật phân quyền thành công',
            'data' => User::with('role')->find($id)
        ], 200);
    }

    // API 4: KHÓA / MỞ KHÓA TÀI KHOẢN (Status)
    public function updateStatus(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy người dùng'], 404);
        }

        // Kiểm tra biến 'status' Postman gửi lên
        $status = $request->input('status'); 

        if ($status === 'locked') {
            $user->is_locked = true;
            $message = 'Đã khóa tài khoản thành công';
        } else {
            $user->is_locked = false;
            $message = 'Đã mở khóa tài khoản thành công';
        }

        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => $message
        ], 200);
    }

    // API 5: CẬP NHẬT GÓI CƯỚC THỦ CÔNG (Admin Manual Override)
    public function updateSubscription(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy người dùng'], 404);
        }

        $validated = $request->validate([
            'plan_code' => 'required|string|exists:subscription_plans,plan_code',
            'subscription_status' => 'required|string|in:active,expired,cancelled,suspended',
            'subscription_starts_at' => 'nullable|date',
            'subscription_expires_at' => 'nullable|date',
            'auto_renew' => 'boolean',
            'cancelled_at' => 'nullable|date',
            'cancellation_reason' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::where('plan_code', $validated['plan_code'])->firstOrFail();

        // Áp dụng logic xác định action phù hợp cho enum của subscription_history
        $action = 'purchased';
        if ($validated['subscription_status'] === 'expired') {
            $action = 'expired';
        } elseif ($validated['subscription_status'] === 'cancelled') {
            $action = 'cancelled';
        } elseif ($user->subscription_plan_id) {
            if ($user->subscription_plan_id !== $plan->id) {
                $action = 'upgraded';
            } else {
                $action = 'renewed';
            }
        }

        // Ghi log vào subscription_history trước khi đổi
        SubscriptionHistory::create([
            'user_id' => $user->id,
            'action' => $action,
            'subscription_plan_id' => $plan->id,
            'previous_subscription_plan_id' => $user->subscription_plan_id,
            'amount' => 0.00,
            'billing_cycle' => $plan->billing_cycle_type === 'yearly' ? 'yearly' : 'monthly',
            'reason' => 'Admin manual override: ' . ($validated['cancellation_reason'] ?? 'No reason provided'),
            'subscription_starts_at' => $validated['subscription_starts_at'],
            'subscription_expires_at' => $validated['subscription_expires_at'],
        ]);

        // Cập nhật thông tin User
        $user->update([
            'subscription_plan_id' => $plan->id,
            'subscription_status' => $validated['subscription_status'],
            'subscription_starts_at' => $validated['subscription_starts_at'],
            'subscription_expires_at' => $validated['subscription_expires_at'],
            'auto_renew' => $validated['auto_renew'] ?? false,
            'cancelled_at' => $validated['cancelled_at'],
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật gói cước người dùng thành công',
            'data' => User::with(['role', 'subscriptionPlan'])->find($id)
        ], 200);
    }
}