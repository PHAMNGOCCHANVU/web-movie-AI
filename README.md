# 🎬 HỆ THỐNG BACKEND CORE: WEB MOVIE AI (FREEMIUM)

Dự án nghiên cứu và phát triển cổng giao tiếp dữ liệu (Backend API Core) phục vụ hệ thống nền tảng Xem phim trực tuyến ứng dụng Trợ lý ảo AI kiểm duyệt bình luận và tích hợp Cổng thanh toán quốc gia VNPay.

---

## 🛠️ 1. TỔNG QUAN CÔNG NGHỆ VÀ MÔI TRƯỜNG LÕI
* **Framework hạt nhân:** Laravel 11.x / RESTful API Architecture.
* **Mô hình tổ chức:** Model - View - Controller (MVC) kết hợp **Service Pattern** tách biệt logic nghiệp vụ.
* **Hệ quản trị cơ sở dữ liệu:** MySQL 8.0+.
* **Phân hệ bảo mật cổng API:** Laravel Sanctum (Token-based Authentication).
* **Thư viện tích hợp:** GuzzleHTTP (HTTP Client phục vụ kết nối TMDB API và Google Gemini AI API).

---

## 📊 2. SƠ ĐỒ THỰC THỂ QUAN HỆ (ERD DATABASE SCHEMA)
Sơ đồ mô tả cấu trúc các bảng vật lý, quy tắc ràng buộc toàn vẹn dữ liệu khóa ngoại và cơ chế phân cấp quyền hạn, quản lý gói VIP cùng luồng kiểm duyệt dữ liệu AI.

```mermaid
erDiagram
    ROLES ||--o{ USERS : "has"
    VIP_PACKAGES ||--o{ USERS : "subscribes_to"
    USERS ||--o{ TRANSACTIONS : "makes"
    USERS ||--o{ COMMENTS : "writes"
    MOVIES ||--o{ COMMENTS : "receives"
    USERS }|--|{ MOVIES : "interacts (movie_user)"
    MOVIES }|--|{ GENRES : "belongs_to (genre_movie)"

    ROLES {
        int id PK
        string name
    }
    VIP_PACKAGES {
        int id PK
        string name
        decimal price
        int duration_days
    }
    USERS {
        int id PK
        string name
        string email
        string password
        int role_id FK
        int vip_package_id FK
        timestamp vip_expires_at
    }
    TRANSACTIONS {
        int id PK
        int user_id FK
        string vnp_txn_ref UK
        decimal amount
        string status
        string vnp_transaction_no
    }
    MOVIES {
        int id PK
        int tmdb_id UK
        boolean is_premium
        boolean is_pinned
    }
    GENRES {
        int id PK
        int tmdb_id UK
        string name
    }
    COMMENTS {
        int id PK
        int user_id FK
        int movie_id FK
        text content
        decimal toxic_score
        boolean is_hidden
    }
```

---

## 💸 3. SƠ ĐỒ TUẦN TỰ (SEQUENCE DIAGRAM) - LUỒNG THANH TOÁN VNPAY
Mô tả chi tiết đường đi của dữ liệu từ ứng dụng khách, quá trình băm bảo mật mã hóa tham số tại lớp Service, bắt gói tin bất đồng bộ từ cổng VNPay qua kênh IPN Webhook và đồng bộ hóa quyền lợi người dùng.

```mermaid
sequenceDiagram
    autonumber
    actor U as User (Frontend)
    participant BE as Laravel Backend (API)
    participant VNP as VNPay Gateway
    participant DB as MySQL Database

    U->>BE: Yêu cầu mua gói VIP (POST /api/payment)
    BE->>DB: Khởi tạo Transaction (Status: pending)
    BE->>BE: Xây dựng URL thanh toán & Tạo chữ ký (Secure Hash)
    BE-->>U: Trả về Payment URL (JSON Response)
    U->>VNP: Chuyển hướng sang cổng VNPay & Tiến hành thanh toán
    VNP-->>U: Hoàn thành, redirect về ứng dụng khách (Return URL)
    VNP->>BE: Gọi ngầm URL IPN Webhook báo kết quả xử lý
    BE->>BE: Kiểm tra tính toàn vẹn dữ liệu (Verify Checksum)
    alt Giao dịch thành công (vnp_ResponseCode == 00)
        BE->>DB: Cập nhật Transaction (status: success)
        BE->>DB: Cập nhật quyền VIP & Thời gian hết hạn cho User
    else Giao dịch thất bại
        BE->>DB: Cập nhật Transaction (status: failed)
    end
    BE-->>VNP: Phản hồi kết quả xử lý IPN thành công (HTTP 200 OK)
    U->>BE: Kiểm tra trạng thái đơn hàng thời gian thực
    BE-->>U: Phản hồi kết quả quyền lợi VIP mới (JSON)
```

---

## 📂 4. CẤU TRÚC THƯ MỤC KIẾN TRÚC NÂNG CAO (SERVICE PATTERN)
Hệ thống triển khai phân tách lớp xử lý giúp mã nguồn tại Controllers tối giản, toàn bộ logic lõi tương tác với bên thứ ba được đóng gói tại tầng `app/Services/`:
```text
web-movie-AI/
├── app/
│   ├── Http/
│   │   └── Controllers/     # Tiếp nhận Requests và trả về phản hồi JSON
│   ├── Models/              # Khai báo cấu trúc Model và định nghĩa Relationships
│   └── Services/            # TẦNG XỬ LÝ NGHIỆP VỤ ĐỘC LẬP (CORE LOGIC)
│       ├── TMDBService.php      # Đóng gói logic gọi API lấy dữ liệu phim ngoài
│       ├── GeminiAiService.php  # Xử lý chấm điểm độc hại và kiểm duyệt nội dung
│       └── VNPayService.php     # Xử lý băm mã hash, tạo URL và verify IPN Webhook
├── database/
│   └── migrations/          # Hệ thống quản lý phiên bản cơ sở dữ liệu vật lý
└── routes/
    └── api.php              # Phân hệ quản lý định tuyến cổng API tập trung
```

---

## 🚀 5. HƯỚNG DẪN KHỞI CHẠY DỰ ÁN CHO THÀNH VIÊN (SETUP GUIDE)

**Bước 1: Tải các thư viện phụ thuộc và kích hoạt nhân dự án**
```bash
composer install
```

**Bước 2: Sao chép và cấu hình tệp tin môi trường**
Hệ thống yêu cầu tạo tệp `.env` từ khuôn mẫu có sẵn:
```bash
cp .env.example .env
```
Mở tệp `.env` ra, cấu hình tài khoản kết nối MySQL (`web_movie_ai`) và điền các mã khóa bảo mật được cấp phát tại mục `THIRD-PARTY APIs`.

**Bước 3: Khởi tạo khóa mã hóa dữ liệu hệ thống**
```bash
php artisan key:generate
```

**Bước 4: Đồng bộ hạ tầng bảng vật lý vào MySQL**
```bash
php artisan migrate
```

**Bước 5: Kích hoạt máy chủ ảo nội bộ phục vụ thực nghiệm**
```bash
php artisan serve
```