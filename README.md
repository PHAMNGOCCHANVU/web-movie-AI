# Hướng dẫn cài đặt và chạy CineON

Tài liệu này dùng cho máy Windows chạy dự án bằng XAMPP.

## 1. Phần mềm cần cài

- XAMPP có PHP 8.2 trở lên và MySQL.
- Composer 2.
- Node.js 22 LTS và npm.
- Git.

Kiểm tra sau khi cài:

```powershell
php -v
composer --version
node -v
npm -v
```

Không cần cài Python, PyTorch hoặc model PhoBERT trên máy. Chức năng kiểm
duyệt bình luận đang gọi API model được triển khai riêng trên VPS.

## 2. Tải mã nguồn

```powershell
git clone https://github.com/PHAMNGOCCHANVU/web-movie-AI.git
cd web-movie-AI
git checkout backend-1
```

Nếu đã có mã nguồn thì chỉ cần mở terminal tại thư mục dự án.

## 3. Cài thư viện

```powershell
composer install
npm ci
```

`composer install` cài Laravel và thư viện PHP.
`npm ci` cài React, Vite, Tailwind CSS, Axios, HLS.js và các thư viện giao diện.

Nếu `npm ci` báo file lock không đồng bộ, dùng:

```powershell
npm install
```

## 4. Tạo file môi trường

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Không đưa file `.env` hoặc các API key thật lên GitHub.

## 5. Tạo database

1. Bật `Apache` và `MySQL` trong XAMPP.
2. Mở `http://localhost/phpmyadmin`.
3. Tạo database tên `web_movie_ai`, collation `utf8mb4_general_ci`.
4. Kiểm tra phần database trong `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_movie_ai
DB_USERNAME=root
DB_PASSWORD=
```

Tạo bảng và dữ liệu nền:

```powershell
php artisan migrate --seed
```

Muốn xóa toàn bộ dữ liệu cũ và tạo lại từ đầu:

```powershell
php artisan migrate:fresh --seed
```

Lưu ý: `migrate:fresh` xóa toàn bộ bảng và dữ liệu hiện có.

## 6. Cấu hình dịch vụ

### Gemini AI

Điền API key Gemini vào `.env`:

```env
GEMINI_API_KEY=YOUR_GEMINI_API_KEY
```

Không có key thì chức năng Chat AI không hoạt động đầy đủ.

### Kiểm duyệt bình luận

Cấu hình hiện tại:

```env
MODERATION_URL=http://34.203.227.23:8765
MODERATION_MODEL=visolex/phobert-v2-hsd
MODERATION_CONNECT_TIMEOUT=2
MODERATION_TIMEOUT=30
```

Máy chạy website phải có Internet và VPS moderation phải đang hoạt động.
Không cần chạy model local.

Kiểm tra VPS:

```powershell
Invoke-RestMethod http://34.203.227.23:8765/health
```

Kết quả hợp lệ có `status` bằng `ok`.

### Email OTP thật bằng Gmail

Tạo App Password của tài khoản Gmail rồi cấu hình:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=YOUR_GMAIL_APP_PASSWORD
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Nếu chỉ thử ứng dụng mà không cần gửi email thật, giữ:

```env
MAIL_MAILER=log
```

Khi đó OTP được ghi trong `storage/logs/laravel.log`.

### VNPAY Sandbox

Điền tài khoản Sandbox của nhóm:

```env
VNP_TMN_CODE=YOUR_TMN_CODE
VNP_HASH_SECRET=YOUR_HASH_SECRET
VNP_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNP_RETURN_URL=http://localhost:8000/#/payment/return
```

Thanh toán trên local vẫn chuyển hướng về được bằng `VNP_RETURN_URL`.
Riêng IPN từ VNPAY cần URL HTTPS công khai như ngrok hoặc server đã deploy.

Sau khi sửa `.env`, chạy:

```powershell
php artisan optimize:clear
```

## 7. Chạy dự án

Cách gọn nhất, chạy một lệnh:

```powershell
composer run dev
```

Sau đó truy cập:

```text
http://127.0.0.1:8000
```

Hoặc chạy bằng hai terminal:

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

Không đóng terminal trong thời gian sử dụng website.

## 8. Đồng bộ phim từ OPhim

Đồng bộ khoảng 30 phim:

```powershell
php artisan ophim:sync --limit=30
```

Đồng bộ theo một số thể loại:

```powershell
php artisan ophim:sync-genres hanh-dong tinh-cam hoat-hinh kinh-di --limit=12
```

Các lệnh này cần kết nối Internet.

## 9. Chạy kiểm thử

```powershell
php artisan test
```

Hiện bộ kiểm thử bao phủ đăng ký/OTP, phim, lịch sử xem, Gemini AI, VNPAY và
kiểm duyệt bình luận.

## 10. Quy trình mở dự án ở những lần sau

Không cần cài lại thư viện mỗi lần. Chỉ cần:

1. Bật Apache và MySQL trong XAMPP.
2. Mở terminal tại thư mục dự án.
3. Chạy `composer run dev`.
4. Truy cập `http://127.0.0.1:8000`.

Chỉ chạy lại `composer install` hoặc `npm ci` khi vừa tải code mới và các file
`composer.lock` hoặc `package-lock.json` có thay đổi.

## 11. Lỗi thường gặp

### Không kết nối được MySQL

Kiểm tra MySQL trong XAMPP đã bật và database `web_movie_ai` đã tồn tại.

### Thiếu APP_KEY

```powershell
php artisan key:generate
```

### Giao diện không tải hoặc quay liên tục

Đảm bảo `npm run dev` đang chạy. Sau đó chạy:

```powershell
php artisan optimize:clear
```

### Chat AI không trả lời

Kiểm tra `GEMINI_API_KEY`, kết nối Internet và quota của Gemini.

### OTP không gửi về email

Kiểm tra cấu hình SMTP, Gmail App Password và file:

```text
storage/logs/laravel.log
```

### Bình luận không được kiểm duyệt

Kiểm tra:

```powershell
Invoke-RestMethod http://34.203.227.23:8765/health
```

Nếu VPS không phản hồi, cần bật lại service moderation trên VPS.

## 12. Tài khoản thử nghiệm mặc định

Sau khi chạy lệnh migrate seed (`php artisan migrate --seed` hoặc `php artisan migrate:fresh --seed`), các tài khoản sau sẽ được tạo mặc định để đăng nhập và thử nghiệm trên Client:

### Tài khoản Admin (Quản trị viên)
* **Email:** `admin@cineon.com`
* **Mật khẩu (Password):** `admin123`
* **Vai trò (Role):** `admin` (Có quyền truy cập trang quản trị Admin Dashboard tại `/admin`)

### Tài khoản User (Người dùng thường)
* **Email:** `test@example.com`
* **Mật khẩu (Password):** `password`
* **Vai trò (Role):** `user` (Dùng để kiểm tra các tính năng của người dùng thường như xem phim, bình luận, đánh giá, quản lý watchlist...)

