<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    // API 1: Lấy danh sách bình luận (Có lọc theo status và movie_id)
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'movie'])->orderBy('created_at', 'desc');

        // Lọc theo ID phim
        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        // Lọc theo trạng thái. (Vì DB chỉ có is_hidden, ta quy ước: hidden -> true, pending/approved -> false)
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'hidden') {
                $query->where('is_hidden', true);
            } else {
                $query->where('is_hidden', false);
            }
        }

        $limit = $request->input('limit', 20);
        $comments = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $comments
        ], 200);
    }

    // API 2: Duyệt bình luận (Approve)
    public function approve(string $id)
    {
        $comment = Comment::find($id);
        if (!$comment) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bình luận'], 404);

        $comment->is_hidden = false;
        $comment->save();

        return response()->json(['status' => 'success', 'message' => 'Đã duyệt bình luận'], 200);
    }

    // API 3: Ẩn bình luận (Hide)
    public function hide(string $id)
    {
        $comment = Comment::find($id);
        if (!$comment) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bình luận'], 404);

        $comment->is_hidden = true;
        $comment->save();

        return response()->json(['status' => 'success', 'message' => 'Đã ẩn bình luận'], 200);
    }

    // API 4: Khôi phục bình luận đã ẩn (Restore)
    public function restore(string $id)
    {
        $comment = Comment::find($id);
        if (!$comment) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bình luận'], 404);

        $comment->is_hidden = false;
        $comment->save();

        return response()->json(['status' => 'success', 'message' => 'Đã khôi phục bình luận'], 200);
    }

    // API 5: Xóa vĩnh viễn bình luận (Delete)
    public function destroy(string $id)
    {
        $comment = Comment::find($id);
        if (!$comment) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bình luận'], 404);

        $comment->delete();

        return response()->json(['status' => 'success', 'message' => 'Đã xóa bình luận thành công'], 200);
    }

    // API 6: Phân tích AI Sentiment (Cảm xúc & Độ độc hại)
    public function sentimentAnalysis(string $id)
    {
        $comment = Comment::find($id);
        if (!$comment) return response()->json(['status' => 'error', 'message' => 'Không tìm thấy bình luận'], 404);

        // Giả lập gọi API sang một con AI Model (Ví dụ: Python/OpenAI)
        // Dựa vào các từ khóa nhạy cảm trong content để tính điểm toxic
        $badWords = ['chửi', 'ngu', 'dở', 'tệ', 'rác']; 
        $toxicScore = 0.01; // Mặc định rất an toàn

        foreach ($badWords as $word) {
            // Hàm str_contains kiểm tra nếu bình luận chứa từ cấm
            if (str_contains(mb_strtolower($comment->content), $word)) {
                $toxicScore += 0.25; 
            }
        }

        // Đảm bảo điểm không vượt quá 1.0 (100% Toxic)
        $toxicScore = min($toxicScore, 1.0);

        // Lưu lại kết quả AI vào Database
        $comment->toxic_score = $toxicScore;
        $comment->save();

        // Đánh giá nhãn (Label)
        $label = 'Tích cực / Bình thường';
        if ($toxicScore > 0.7) {
            $label = 'Rất độc hại (Cần ẩn ngay)';
        } elseif ($toxicScore > 0.4) {
            $label = 'Có tính tiêu cực / Gây hấn';
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Phân tích AI hoàn tất',
            'data' => [
                'comment_id' => $comment->id,
                'content' => $comment->content,
                'toxic_score' => $toxicScore,
                'ai_label' => $label
            ]
        ], 200);
    }
}