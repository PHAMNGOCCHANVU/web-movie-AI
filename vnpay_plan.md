# Kế hoạch tích hợp thanh toán VNPay Sandbox - Web Movie AI

## 1. Tổng quan

Tích hợp Cổng thanh toán VNPAY vào hệ thống đăng ký gói cước (Subscription) phía người dùng. Sử dụng môi trường Sandbox để phát triển và kiểm thử.

### Thông tin cấu hình Sandbox

| Thông số | Giá trị |
|:---------|:--------|
| **vnp_TmnCode** | `RJXNTQ8K` |
| **vnp_HashSecret** | `INHN2HPMJXBZAK8M7CEEQECJZACTO2G7` |
| **VNPay Sandbox URL** | `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` |
| **API Truy vấn** | `https://sandbox.vnpayment.vn/merchant_webapi/api/transaction` |
| **Thuật toán mã hóa** | HMAC-SHA512 |
| **Phiên bản API** | 2.1.0 |

### Thẻ test Sandbox

| Thông tin | Giá trị |
|:----------|:--------|
| Ngân hàng | NCB |
| Số thẻ | `9704198526191432198` |
| Tên chủ thẻ | `NGUYEN VAN A` |
| Ngày phát hành | `07/15` |
| Mã OTP | `123456` |

---

## 2. Kiến trúc hệ thống

```
[User Browser] 
    │
    ▼
[Frontend SPA] ──POST──▶ [Laravel API /payment/vnpay/create]
    │                           │
    │                    Tạo Transaction (pending)
    │                    Build URL + HMAC-SHA512
    │                           │
    │                    ◀── payment_url ──┘
    │
    ▼ (redirect to VNPay)
[VNPay Sandbox] 
    │
    ├── IPN Callback (GET) ──▶ [Ngrok] ──▶ [Laravel /payment/vnpay/ipn]
    │                                            │
    │                                     Verify checksum
    │                                     Check order, amount
    │                                     Activate subscription
    │                                     Return {RspCode, Message}
    │
    └── Return URL (GET) ──▶ [Frontend /payment/return]
                                    │
                                    Hiển thị kết quả cho user
```

---

## 3. Luồng thanh toán chi tiết

### Bước 1: Tạo yêu cầu thanh toán
- User chọn gói cước và bấm "Thanh toán"
- Frontend gọi `POST /api/payment/vnpay/create` với Bearer Token
- Request body:
```json
{
    "plan_code": "standard_monthly",
    "billing_cycle": "monthly", 
    "transaction_type": "purchase"
}
```

### Bước 2: Backend xử lý
- Tạo bản ghi Transaction (status = `pending`)
- Build URL thanh toán VNPay với các tham số:
  - `vnp_Version`: 2.1.0
  - `vnp_Command`: pay
  - `vnp_TmnCode`: Mã merchant
  - `vnp_Amount`: Số tiền × 100
  - `vnp_TxnRef`: Mã giao dịch duy nhất
  - `vnp_OrderInfo`: Mô tả đơn hàng (không dấu)
  - `vnp_ReturnUrl`: URL redirect sau thanh toán
  - `vnp_CreateDate`: Thời gian tạo (GMT+7)
  - `vnp_ExpireDate`: Thời hạn thanh toán (+15 phút)
  - `vnp_SecureHash`: Chữ ký HMAC-SHA512

### Bước 3: User thanh toán tại VNPay
- Frontend redirect user đến `payment_url`
- User chọn phương thức thanh toán và hoàn tất

### Bước 4: VNPay gửi kết quả
- **IPN URL** (server-to-server): VNPay gọi GET đến backend
  - Kiểm tra checksum
  - Tìm giao dịch, kiểm tra số tiền
  - Cập nhật trạng thái (success/failed)
  - Kích hoạt gói cước nếu thành công
  - Trả về `{RspCode: "00", Message: "Confirm Success"}`
  
- **Return URL** (browser redirect): Redirect user về frontend
  - Hiển thị kết quả thanh toán

---

## 4. Các file code liên quan

| File | Vai trò |
|:-----|:--------|
| `config/vnpay.php` | Cấu hình VNPay (TMN Code, Hash Secret, URL) |
| `.env` | Biến môi trường VNPay |
| `app/Services/VNPayService.php` | Logic chính: tạo URL, xử lý IPN, verify return |
| `app/Http/Controllers/Subscription/VNPayController.php` | Controller endpoint |
| `app/Http/Requests/Payment/CreatePaymentRequest.php` | Validation request |
| `app/Models/Transaction.php` | Model giao dịch |
| `app/Models/SubscriptionPlan.php` | Model gói cước |
| `routes/api.php` | Định nghĩa API routes |

---

## 5. Các thay đổi đã thực hiện

### 5.1. VNPayService.php
- ✅ Thêm `vnp_ExpireDate` (bắt buộc theo tài liệu VNPay)
- ✅ Sửa hash data build theo đúng source code mẫu VNPay (dùng `urlencode()` riêng lẻ)
- ✅ Thêm kiểm tra số tiền (`vnp_Amount / 100` == `transaction->amount`) trong IPN
- ✅ Thêm kiểm tra `vnp_TransactionStatus` ngoài `vnp_ResponseCode`
- ✅ Thêm logging chi tiết cho debug
- ✅ Sửa `OrderInfo` không dấu (quy định VNPay)

### 5.2. VNPayController.php
- ✅ IPN dùng `$request->query()` (GET params) thay vì `$request->all()`
- ✅ Return dùng `$request->query()` 
- ✅ Thêm exception handling bao quát cho IPN

### 5.3. routes/api.php
- ✅ IPN route: `Route::match(['get', 'post'])` (VNPay chính thức dùng GET)

---

## 6. Hướng dẫn kiểm thử

### 6.1. Chuẩn bị
```bash
# Khởi động server
php artisan serve --port=8000

# Khởi động Ngrok (terminal khác)
ngrok http 8000
```

### 6.2. Cập nhật .env
```
VNP_RETURN_URL=https://<ngrok-url>/api/payment/vnpay/return
```

### 6.3. Test tạo thanh toán
```bash
# Đăng nhập lấy token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "Password123!"}'

# Tạo thanh toán
curl -X POST http://localhost:8000/api/payment/vnpay/create \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"plan_code": "standard_monthly", "billing_cycle": "monthly", "transaction_type": "purchase"}'
```

### 6.4. Thanh toán test
- Copy `payment_url` từ response
- Mở trên trình duyệt
- Chọn NCB, nhập thông tin thẻ test
- Hoàn tất thanh toán

### 6.5. Xác nhận
- Kiểm tra `transactions` table: status = `success`
- Kiểm tra `users` table: `subscription_status` = `active`
- Kiểm tra `subscription_history` table: có bản ghi mới

---

## 7. Mã lỗi VNPay IPN Response

| RspCode | Ý nghĩa |
|:--------|:---------|
| `00` | Xác nhận thành công |
| `01` | Không tìm thấy đơn hàng |
| `02` | Đơn hàng đã được xác nhận trước đó |
| `04` | Số tiền không hợp lệ |
| `97` | Chữ ký không hợp lệ |
| `99` | Lỗi không xác định |
