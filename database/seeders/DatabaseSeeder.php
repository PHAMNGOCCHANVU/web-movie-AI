<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Chạy các seeder danh mục cấu hình hệ thống trước
        $this->call([
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        // 2. Lấy thông tin ID động từ database để tránh hardcode lỗi khóa ngoại
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $userRoleId = DB::table('roles')->where('name', 'user')->value('id');
        
        $planVipMonthly = DB::table('subscription_plans')->where('plan_code', 'vip_monthly')->first();
        $planVipYearly = DB::table('subscription_plans')->where('plan_code', 'vip_yearly')->first();

        // 3. Khởi tạo tài khoản Quản trị viên (Admin) chuẩn quyền truy cập hệ thống
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'CineOn Admin',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRoleId,
                'subscription_status' => 'none',
                'auto_renew' => false,
                'is_locked' => false,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. Khởi tạo danh sách Người dùng (Users) với các trạng thái khác nhau để test bộ lọc Dashboard
        // Tài khoản User Standard (Thường)
        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Standard Member',
                'password' => Hash::make('user123'),
                'role_id' => $userRoleId,
                'subscription_status' => 'none',
                'auto_renew' => false,
                'is_locked' => false,
                'email_verified_at' => now(),
                'created_at' => now()->subDays(10),
                'updated_at' => now(),
            ]
        );

        // Tài khoản VIP đang hoạt động (Tính toán cho mục thống kê VIP)
        $vipUser = User::updateOrCreate(
            ['email' => 'vipmember@gmail.com'],
            [
                'name' => 'VIP Member Active',
                'password' => Hash::make('user123'),
                'role_id' => $userRoleId,
                'subscription_status' => 'active',
                'subscription_plan_id' => $planVipMonthly ? $planVipMonthly->id : null,
                'subscription_expires_at' => now()->addDays(30),
                'auto_renew' => true,
                'is_locked' => false,
                'email_verified_at' => now(),
                'created_at' => now()->subDays(5),
                'updated_at' => now(),
            ]
        );

        // Tài khoản User đang bị khóa (Phục vụ việc test tính năng Khóa/Mở khóa tài khoản)
        $lockedUser = User::updateOrCreate(
            ['email' => 'lockeduser@gmail.com'],
            [
                'name' => 'Banned Member',
                'password' => Hash::make('user123'),
                'role_id' => $userRoleId,
                'subscription_status' => 'none',
                'auto_renew' => false,
                'is_locked' => true, // Đánh dấu tài khoản bị khóa
                'email_verified_at' => now(),
                'created_at' => now()->subDays(20),
                'updated_at' => now(),
            ]
        );

        // 5. Seed dữ liệu Giao dịch thanh toán tài chính (VNPay Transactions) để hiển thị Doanh thu
        if ($planVipMonthly && $planVipYearly) {
            DB::table('transactions')->insert([
                [
                    'user_id' => $vipUser->id,
                    'subscription_plan_id' => $planVipMonthly->id,
                    'vnp_txn_ref' => 'VNP_' . time() . '_1',
                    'vnp_transaction_no' => '14000000',
                    'amount' => $planVipMonthly->price,
                    'billing_cycle' => 'monthly',
                    'status' => 'success', // Giao dịch thành công -> Dashboard sum() sẽ cộng tiền từ đây
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ],
                [
                    'user_id' => $testUser->id,
                    'subscription_plan_id' => $planVipYearly->id,
                    'vnp_txn_ref' => 'VNP_' . time() . '_2',
                    'vnp_transaction_no' => '14000001',
                    'amount' => $planVipYearly->price,
                    'billing_cycle' => 'yearly',
                    'status' => 'success',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
                [
                    'user_id' => $testUser->id,
                    'subscription_plan_id' => $planVipMonthly->id,
                    'vnp_txn_ref' => 'VNP_' . time() . '_3',
                    'vnp_transaction_no' => '0',
                    'amount' => $planVipMonthly->price,
                    'billing_cycle' => 'monthly',
                    'status' => 'pending', // Trạng thái đang xử lý/thất bại -> Dashboard không cộng dồn tiền
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 6. Seed Thể loại phim (Genres)
        $genreId = DB::table('genres')->insertGetId([
            'name' => 'Hành động Viễn tưởng',
            'slug' => 'hanh-dong-vien-tuong',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Seed Phim với thông số Lượt xem (view_count) phong phú phục vụ Thống kê phim
        $movie1Id = DB::table('movies')->insertGetId([
            'ophim_id' => 'movie_std_01',
            'slug' => 'avengers-endgame',
            'name' => 'Avengers: Endgame',
            'origin_name' => 'Avengers: Endgame',
            'is_premium' => false,
            'status' => 'approved',
            'view_count' => 15420, // Thêm dữ liệu lượt xem
            'created_at' => now()->subMonths(1),
            'updated_at' => now(),
        ]);

        $movie2Id = DB::table('movies')->insertGetId([
            'ophim_id' => 'movie_vip_01',
            'slug' => 'avatar-the-way-of-water',
            'name' => 'Avatar: Dòng Chảy Của Nước',
            'origin_name' => 'Avatar: The Way of Water',
            'is_premium' => true, // Phim bản quyền VIP
            'status' => 'approved',
            'view_count' => 8450,
            'created_at' => now()->subMonths(1),
            'updated_at' => now(),
        ]);

        // Tạo liên kết bảng trung gian Genre & Movie
        DB::table('genre_movie')->insert([
            ['genre_id' => $genreId, 'movie_id' => $movie1Id],
            ['genre_id' => $genreId, 'movie_id' => $movie2Id],
        ]);

        // 8. Seed các Tập phim (Episodes) tương ứng
        DB::table('episodes')->insert([
            [
                'movie_id' => $movie1Id,
                'server_name' => 'CineOn Vietsub',
                'name' => 'Full HD',
                'slug' => 'full-hd',
                'link_m3u8' => 'https://example.com/storage/avengers/playlist.m3u8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'movie_id' => $movie2Id,
                'server_name' => 'CineOn Thuyết Minh',
                'name' => 'Full HD VIP',
                'slug' => 'full-hd-vip',
                'link_m3u8' => 'https://example.com/storage/avatar/playlist.m3u8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 9. Seed Đánh giá phim (Ratings)
        DB::table('ratings')->insert([
            ['movie_id' => $movie1Id, 'user_id' => $testUser->id, 'score' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['movie_id' => $movie2Id, 'user_id' => $vipUser->id, 'score' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 10. Seed dữ liệu Bình luận được kiểm duyệt bởi AI (Gồm cả trạng thái đã duyệt và CHỜ DUYỆT)
        // Điều này giúp bộ đếm "Bình luận chờ duyệt" (Badge) trên Dashboard nhảy số chính xác để Admin click xử lý.
        DB::table('comments')->insert([
            [
                'movie_id' => $movie1Id,
                'user_id' => $testUser->id,
                'content' => 'Phim xem mượt mà sắc nét quá, rất xứng đáng bỏ thời gian xem lại!',
                'moderation_status' => 'approved', // Đã duyệt hiển thị công khai
                'is_hidden' => false,
                'moderation_model' => 'gemini-1.5-flash',
                'moderation_score' => 0.05, // Điểm độc hại cực thấp
                'moderation_reason' => 'Bình luận an toàn.',
                'created_at' => now()->subHours(5),
                'updated_at' => now(),
            ],
            [
                'movie_id' => $movie2Id,
                'user_id' => $testUser->id,
                'content' => 'Ad làm ăn như cái ****, link phim giật lag vcl xem bực cả mình!',
                'moderation_status' => 'pending_review', // Chờ Quản trị viên duyệt (Dashboard sẽ quét giá trị này)
                'is_hidden' => true, // Ẩn tạm thời chờ xử lý
                'moderation_model' => 'gemini-1.5-flash',
                'moderation_score' => 0.92, // Điểm độc hại cao do chứa từ tục tĩu
                'moderation_reason' => 'Phát hiện từ ngữ xúc phạm thô tục, vi phạm quy chuẩn cộng đồng.',
                'created_at' => now()->subHours(2),
                'updated_at' => now(),
            ]
        ]);
    }
}