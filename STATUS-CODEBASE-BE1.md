Dựa trên phân tích các migrations, context.txt, README.md (ERD), và Postman APIs, tôi sẽ đánh giá chi tiết:

## ✅ CÁC BẢNG HỢP LỆ

| Bảng | Cấu trúc | Mục đích |
|------|---------|---------|
| **roles** | id, name | Quản lý vai trò (User, VIP, Admin) ✓ |
| **users** | id, name, email, password, role_id, vip_package_id, vip_expires_at | Lưu người dùng và quyền VIP ✓ |
| **vip_packages** | id, name, price, duration_days | Định nghĩa các gói VIP ✓ |
| **transactions** | id, user_id, vnp_txn_ref, amount, status, vnp_transaction_no | Theo dõi giao dịch VNPay ✓ |
| **movies** | id, tmdb_id, is_premium, is_pinned | Lưu phim từ TMDB ✓ |
| **genres** | id, tmdb_id, name | Danh sách thể loại ✓ |
| **genre_movie** | id, movie_id, genre_id | Liên kết many-to-many ✓ |
| **comments** | id, user_id, movie_id, content, toxic_score, is_hidden | Bình luận với AI toxicity detection ✓ |
| **movie_user** | id, user_id, movie_id, is_favorite, watch_progress_seconds | Watchlist + watch history ✓ |

---

## ⚠️ CÁC VẤN ĐỀ CẦN KHẮC PHỤC

### 1. **THIẾU BẢNG `ratings` (CRITICAL)**
   - **Vấn đề**: API `POST /movies/{{movie_id}}/ratings` (Rate Movie) cần lưu điểm đánh giá, nhưng migrations không có bảng này
   - **Hiện trạng**: Migrations chỉ có `toxic_score` trong `comments`, không phải điểm rating
   - **Recommendation**: Thêm migration tạo bảng `ratings`
   ```php
   Schema::create('ratings', function (Blueprint $table) {
       $table->id();
       $table->foreignId('user_id')->constrained()->cascadeOnDelete();
       $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
       $table->integer('score'); // 1-10 hoặc 1-5
       $table->timestamps();
       $table->unique(['user_id', 'movie_id']); // Mỗi user chỉ rate 1 lần
   });
   ```

### 2. **THIẾU TRƯỜNG `episode_id` TRONG `movie_user` (MODERATE)**
   - **Vấn đề**: API `POST /user/watch-history` gửi `episode_id`, nhưng table `movie_user` không có trường này
   - **Hiện trạng**: Chỉ có `watch_progress_seconds`, không hỗ trợ theo dõi episodes
   - **Recommendation**: Thêm `episode_id` nếu dự án hỗ trợ series/episodes
   ```php
   $table->integer('episode_id')->nullable();
   $table->integer('season_id')->nullable();
   ```

### 3. **THIẾU BẢNG `ai_chat_conversations` (OPTIONAL)**
   - **Vấn đề**: API `POST /ai/chat` với `history` field, có thể cần lưu lịch sử chat
   - **Hiện trạng**: Không có table lưu trữ conversation history
   - **Recommendation** (tuỳ chọn): Nếu cần lưu chat history dài hạn
   ```php
   Schema::create('ai_chat_conversations', function (Blueprint $table) {
       $table->id();
       $table->foreignId('user_id')->constrained()->cascadeOnDelete();
       $table->text('message');
       $table->text('response');
       $table->timestamps();
   });
   ```

### 4. **THIẾU BẢNG `admin_activity_logs` (NICE-TO-HAVE)**
   - **Vấn đề**: Admin APIs (delete, update, lock user, hide comment) không có audit trail
   - **Hiện trạng**: Không theo dõi hành động admin
   - **Recommendation** (tùy chọn):
   ```php
   Schema::create('admin_activity_logs', function (Blueprint $table) {
       $table->id();
       $table->foreignId('admin_id')->constrained('users');
       $table->string('action'); // delete_comment, lock_user, etc
       $table->text('details');
       $table->timestamps();
   });
   ```

### 5. **THIẾU TRƯỜNG `is_locked` TRONG `users`**
   - **Vấn đề**: API `PUT /admin/users/{id}/lock` cần flag để khóa tài khoản, nhưng `users` table không có
   - **Hiện trạng**: Không có cách khóa user
   - **Recommendation**:
   ```php
   $table->boolean('is_locked')->default(false);
   ```

---

## 📊 PHÂN TÍCH TỔNG HỢP

```
✅ Đã có:        9 bảng
⚠️  Thiếu:       1 bảng quan trọng (ratings)
❌ Cần bổ sung:  4 trường (episode_id, season_id, is_locked)
```

### **KẾT LUẬN**: 
- Migrations **cơ bản có đủ** nhưng **chưa hoàn chỉnh**
- **CRITICAL**: Phải thêm bảng `ratings` để hỗ trợ API Rate Movie
- **IMPORTANT**: Thêm `is_locked` column để lock users
- **OPTIONAL**: Xem xét thêm `episode_id`, `season_id` nếu hỗ trợ series

