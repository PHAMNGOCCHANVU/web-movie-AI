<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VNPayService
{
    protected string $tmnCode;
    protected string $hashSecret;
    protected string $url;
    protected string $returnUrl;

    public function __construct()
    {
        $this->tmnCode = config('vnpay.tmn_code');
        $this->hashSecret = config('vnpay.hash_secret');
        $this->url = config('vnpay.url');
        $this->returnUrl = config('vnpay.return_url');
    }

    public function createPaymentUrl(User $user, string $planCode, string $billingCycle, string $transactionType): array
    {
        $plan = SubscriptionPlan::where('plan_code', $planCode)->where('is_active', true)->firstOrFail();

        // Validate upgrade
        if ($transactionType === 'upgrade') {
            if (!$user->hasActiveSubscription() || !str_starts_with($user->subscriptionPlan->plan_code, 'standard')) {
                throw new \InvalidArgumentException('Bạn cần có gói Standard đang hoạt động để nâng cấp.');
            }
            if (!str_starts_with($planCode, 'vip')) {
                throw new \InvalidArgumentException('Chỉ có thể nâng cấp lên gói VIP.');
            }
        }

        $amount = $plan->price;
        $vnpTxnRef = 'TXN' . now()->timestamp . Str::random(6);

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'transaction_type' => $transactionType,
            'vnp_txn_ref' => $vnpTxnRef,
            'amount' => $amount,
            'status' => 'pending',
            'billing_cycle' => $billingCycle,
            'is_auto_renewal' => false,
            'previous_subscription_plan_id' => $transactionType === 'upgrade' ? $user->subscription_plan_id : null,
            'ip_address' => request()->ip(),
            'description' => "Thanh toan goi {$plan->name} - " . ($billingCycle === 'monthly' ? 'Hang thang' : 'Hang nam'),
        ]);

        // Build VNPay params theo đúng tài liệu VNPay
        $createDate = now()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis');
        $expireDate = now()->timezone('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis');

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => (int) ($amount * 100), // VNPay uses VND * 100
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $vnpTxnRef,
            'vnp_OrderInfo' => $this->removeVietnameseDiacritics("Thanh toan goi {$plan->name}"),
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_IpAddr' => request()->ip(),
            'vnp_CreateDate' => $createDate,
            'vnp_ExpireDate' => $expireDate, // BẮT BUỘC theo tài liệu VNPay
        ];

        // Sort and generate hash theo đúng source code mẫu VNPay
        ksort($inputData);
        $hashData = $this->buildHashData($inputData);
        $vnpSecureHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        // Build query URL
        $query = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $query .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $query .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }

        $paymentUrl = $this->url . '?' . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        Log::info('VNPay: Created payment URL', [
            'vnp_txn_ref' => $vnpTxnRef,
            'amount' => $amount,
            'user_id' => $user->id,
        ]);

        return [
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction->id,
            'vnp_txn_ref' => $vnpTxnRef,
        ];
    }

    public function handleIPN(array $input): array
    {
        try {
            // Bước 1: Verify hash (checksum)
            $vnpSecureHash = $input['vnp_SecureHash'] ?? '';
            unset($input['vnp_SecureHash']);
            unset($input['vnp_SecureHashType']);

            ksort($input);
            $hashData = $this->buildHashData($input);
            $calculatedHash = hash_hmac('sha512', $hashData, $this->hashSecret);

            if ($vnpSecureHash !== $calculatedHash) {
                Log::warning('VNPay IPN: Invalid signature', ['input' => $input]);
                return ['RspCode' => '97', 'Message' => 'Invalid Signature'];
            }

            // Bước 2: Tìm giao dịch trong database
            $vnpTxnRef = $input['vnp_TxnRef'] ?? '';
            $vnpResponseCode = $input['vnp_ResponseCode'] ?? '';
            $vnpTransactionStatus = $input['vnp_TransactionStatus'] ?? '';
            $vnpTransactionNo = $input['vnp_TransactionNo'] ?? '';
            $vnpPayDate = $input['vnp_PayDate'] ?? '';
            $vnpBankCode = $input['vnp_BankCode'] ?? '';
            $vnpAmount = isset($input['vnp_Amount']) ? (int) $input['vnp_Amount'] / 100 : 0;

            $transaction = Transaction::where('vnp_txn_ref', $vnpTxnRef)->first();

            if (!$transaction) {
                Log::warning('VNPay IPN: Order not found', ['vnp_txn_ref' => $vnpTxnRef]);
                return ['RspCode' => '01', 'Message' => 'Order not found'];
            }

            // Bước 3: Kiểm tra số tiền giữa hai hệ thống
            if ((float) $transaction->amount != (float) $vnpAmount) {
                Log::warning('VNPay IPN: Invalid amount', [
                    'expected' => $transaction->amount,
                    'received' => $vnpAmount,
                    'vnp_txn_ref' => $vnpTxnRef,
                ]);
                return ['RspCode' => '04', 'Message' => 'Invalid amount'];
            }

            // Bước 4: Kiểm tra trạng thái idempotent
            if ($transaction->status === 'success') {
                return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
            }

            // Save VNPay response data
            $transaction->update([
                'vnp_transaction_no' => $vnpTransactionNo,
                'vnp_response_code' => $vnpResponseCode,
                'vnp_bank_code' => $vnpBankCode,
                'vnp_secure_hash' => $vnpSecureHash,
            ]);

            // Bước 5: Kiểm tra cả vnp_ResponseCode và vnp_TransactionStatus
            if ($vnpResponseCode === '00' && $vnpTransactionStatus === '00') {
                // Success - activate subscription
                DB::transaction(function () use ($transaction, $vnpPayDate) {
                    $transaction->update([
                        'status' => 'success',
                        'vnp_pay_date' => $vnpPayDate ?: now(),
                    ]);

                    $user = $transaction->user;
                    $plan = $transaction->subscriptionPlan;

                    $user->update([
                        'subscription_plan_id' => $plan->id,
                        'subscription_starts_at' => now(),
                        'subscription_expires_at' => now()->addDays($plan->duration_days),
                        'subscription_status' => 'active',
                        'auto_renew' => true,
                        'cancelled_at' => null,
                        'cancellation_reason' => null,
                    ]);

                    // Ghi subscription_history
                    $actionMap = [
                        'purchase' => 'purchased',
                        'renewal' => 'renewed',
                        'upgrade' => 'upgraded',
                    ];

                    $user->subscriptionHistories()->create([
                        'subscription_plan_id' => $plan->id,
                        'previous_subscription_plan_id' => $transaction->previous_subscription_plan_id,
                        'action' => $actionMap[$transaction->transaction_type] ?? 'purchased',
                        'amount' => $transaction->amount,
                        'billing_cycle' => $transaction->billing_cycle,
                    ]);
                });

                Log::info('VNPay IPN: Payment success', ['vnp_txn_ref' => $vnpTxnRef]);
                return ['RspCode' => '00', 'Message' => 'Confirm Success'];
            }

            // Failed
            $transaction->update(['status' => 'failed']);
            Log::info('VNPay IPN: Payment failed', [
                'vnp_txn_ref' => $vnpTxnRef,
                'response_code' => $vnpResponseCode,
                'transaction_status' => $vnpTransactionStatus,
            ]);

            return ['RspCode' => '00', 'Message' => 'Confirm Success'];
        } catch (\Exception $e) {
            Log::error('VNPay IPN: Unknown error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['RspCode' => '99', 'Message' => 'Unknown error'];
        }
    }

    public function verifyReturn(array $input): array
    {
        $vnpSecureHash = $input['vnp_SecureHash'] ?? '';
        unset($input['vnp_SecureHash']);
        unset($input['vnp_SecureHashType']);

        ksort($input);
        $hashData = $this->buildHashData($input);
        $calculatedHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        if ($vnpSecureHash !== $calculatedHash) {
            return ['is_valid' => false, 'message' => 'Invalid signature'];
        }

        return [
            'is_valid' => true,
            'vnp_txn_ref' => $input['vnp_TxnRef'] ?? '',
            'vnp_response_code' => $input['vnp_ResponseCode'] ?? '',
            'vnp_transaction_status' => $input['vnp_TransactionStatus'] ?? '',
            'is_success' => ($input['vnp_ResponseCode'] ?? '') === '00' && ($input['vnp_TransactionStatus'] ?? '') === '00',
        ];
    }

    /**
     * Build hash data theo đúng source code mẫu VNPay (vnpay_php)
     * Sử dụng urlencode() riêng lẻ cho từng key/value
     *
     * @param array $input Dữ liệu đầu vào (đã ksort)
     * @return string Hash data string
     */
    /**
     * Loại bỏ dấu tiếng Việt để tuân thủ quy định VNPay:
     * vnp_OrderInfo không chứa ký tự đặc biệt và tiếng Việt không dấu
     */
    protected function removeVietnameseDiacritics(string $str): string
    {
        $vietnamese = [
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'đ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
            'Đ',
        ];
        $ascii = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'd',
            'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
            'E','E','E','E','E','E','E','E','E','E','E',
            'I','I','I','I','I',
            'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
            'U','U','U','U','U','U','U','U','U','U','U',
            'Y','Y','Y','Y','Y',
            'D',
        ];
        return str_replace($vietnamese, $ascii, $str);
    }

    protected function buildHashData(array $input): string
    {
        $i = 0;
        $hashData = '';
        foreach ($input as $key => $value) {
            if (substr($key, 0, 4) === 'vnp_') {
                if ($i == 1) {
                    $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . '=' . urlencode($value);
                    $i = 1;
                }
            }
        }
        return $hashData;
    }
}