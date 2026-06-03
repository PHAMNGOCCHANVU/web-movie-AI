<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; 
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // API 1: LẤY DANH SÁCH NGƯỜI DÙNG (Có bộ lọc & phân trang)
    public function index(Request $request)
    {
        // Khởi tạo truy vấn, tự động nối bảng Role và VipPackage (Eager Loading)
        $query = User::with(['role', 'vipPackage'])->orderBy('created_at', 'desc');

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
        $user = User::with(['role', 'vipPackage'])->find($id);

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
}