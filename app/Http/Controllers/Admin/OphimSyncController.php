<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OphimSyncController extends Controller
{
    // Danh sách từ khóa cấm (Bước 1: Filter)
    private array $bannedKeywords = [
        '18+', 'adult', 'c18', 'gambling', 'sòng bài', 'cá độ', 'sex', 'khiêu dâm', 'lô đề'
    ];

    public function syncMovies(Request $request)
    {
        // CHỐT CHẶN BẢO VỆ DATABASE: Giới hạn tối đa 500 phim theo thiết kế
        $totalMovies = Movie::count();
        if ($totalMovies >= 500) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kho dữ liệu đã đạt giới hạn an toàn (500 phim). Vui lòng xóa bớt phim cũ hoặc phim rác trước khi đồng bộ thêm.'
            ], 400);
        }

        // Nhận tham số page (mặc định là 1). Mỗi page khoảng 20-24 phim giúp tránh Vercel Timeout
        $page = $request->input('page', 1);
        $apiUrl = "https://ophim1.com/danh-sach/phim-moi-cap-nhat?page={$page}";

        $response = Http::get($apiUrl);
        if (!$response->successful()) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi kết nối đến Ophim API'], 500);
        }

        $data = $response->json();
        $items = $data['items'] ?? [];
        $countAdded = 0;
        $countSkipped = 0;

        foreach ($items as $item) {
            $slug = $item['slug'];
            
            // Gọi API lấy chi tiết phim và link m3u8
            $detailUrl = "https://ophim1.com/phim/{$slug}";
            $detailRes = Http::get($detailUrl);

            if (!$detailRes->successful()) continue;

            $movieData = $detailRes->json();
            $info = $movieData['movie'] ?? null;
            $episodesData = $movieData['episodes'] ?? [];

            if (!$info) continue;

            $name = $info['name'] ?? '';
            $description = strip_tags($info['content'] ?? ''); // Bỏ mã HTML rác

            // BƯỚC 1: LÀM SẠCH TỰ ĐỘNG - Quét từ khóa
            if ($this->containsBannedKeywords($name . ' ' . $description)) {
                $countSkipped++;
                continue; // Gặp từ cấm -> Bỏ qua không lưu để tiết kiệm 1GB Database
            }

            // BƯỚC 1: LÀM SẠCH TỰ ĐỘNG - Kiểm tra link poster 404
            $posterUrl = $info['thumb_url'] ?? null;
            if ($posterUrl) {
                try {
                    // Dùng Head request để check nhanh (không tải cả ảnh), timeout 2 giây
                    $imageCheck = Http::timeout(2)->head($posterUrl);
                    if ($imageCheck->status() === 404) {
                        $posterUrl = null; // Gán null nếu ảnh bị chết (404)
                    }
                } catch (\Exception $e) {
                    // Bỏ qua nếu timeout hoặc lỗi mạng để không sập tiến trình
                }
            }

            // Lưu Phim vào DB (Status mặc định là pending theo chuẩn thiết kế)
            $movie = Movie::updateOrCreate(
                ['name' => $name], 
                [
                    'description' => $description,
                    'poster_url' => $posterUrl, // Sử dụng link ảnh đã được kiểm tra
                    'trailer_url' => $info['trailer_url'] ?? null,
                    'release_date' => (isset($info['year']) && is_numeric($info['year'])) ? $info['year'].'-01-01' : null,
                    'duration' => is_numeric($info['time']) ? (int)$info['time'] : null,
                    'status' => 'pending', 
                ]
            );

            // Lưu Tập phim và luồng m3u8 vào bảng episodes
            foreach ($episodesData as $server) {
                $serverName = $server['server_name'] ?? 'Default';
                $serverData = $server['server_data'] ?? [];
                foreach ($serverData as $index => $ep) {
                    $epName = $ep['name'] ?? '';
                    $linkM3u8 = $ep['link_m3u8'] ?? '';
                    $slug = $ep['slug'] ?? (string)($index + 1);

                    if ($linkM3u8) {
                        Episode::updateOrCreate(
                            [
                                'movie_id' => $movie->id,
                                'server_name' => $serverName,
                                'slug' => $slug,
                            ],
                            [
                                'name' => $epName,
                                'filename' => $ep['filename'] ?? null,
                                'link_embed' => $ep['link_embed'] ?? null,
                                'link_m3u8' => $linkM3u8,
                                'stream_url' => $linkM3u8,
                                'sort_order' => $index,
                            ]
                        );
                    }
                }
            }
            $countAdded++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Đồng bộ hoàn tất trang {$page} từ Ophim.",
            'data' => [
                'phim_da_them_vao_kho' => $countAdded,
                'phim_rac_da_chan' => $countSkipped
            ]
        ], 200);
    }

    // Hàm kiểm tra từ khóa độc hại
    private function containsBannedKeywords(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');
        foreach ($this->bannedKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }
}