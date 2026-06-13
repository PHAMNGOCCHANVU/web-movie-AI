<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHomepageBlockController extends Controller
{
    // API 1: Lấy danh sách tất cả các khối trang chủ (Không lọc is_visible để admin thấy hết)
    public function index(): JsonResponse
    {
        $blocks = HomepageBlock::orderBy('sort_order')->get();

        return response()->json([
            'status' => 'success',
            'data' => $blocks
        ], 200);
    }

    // API 2: Tạo khối trang chủ mới
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'source_type' => 'required|string|in:genre,latest,pinned,custom',
            'source_ref' => 'nullable|string',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $block = HomepageBlock::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tạo khối trang chủ thành công',
            'data' => $block
        ], 201);
    }

    // API 3: Cập nhật khối trang chủ
    public function update(Request $request, string $id): JsonResponse
    {
        $block = HomepageBlock::find($id);
        if (!$block) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy khối trang chủ này'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'source_type' => 'sometimes|string|in:genre,latest,pinned,custom',
            'source_ref' => 'nullable|string',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $block->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật khối trang chủ thành công',
            'data' => $block
        ], 200);
    }

    // API 4: Xóa khối trang chủ
    public function destroy(string $id): JsonResponse
    {
        $block = HomepageBlock::find($id);
        if (!$block) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy khối trang chủ này'], 404);
        }

        $block->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Xóa khối trang chủ thành công'
        ], 200);
    }
}
