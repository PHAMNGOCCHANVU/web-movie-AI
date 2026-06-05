<?php
// Test multiple hash algorithms

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_TmnCode = "RJXNTQ8K";
$vnp_HashSecret = "INHN2HPMJXBZAK8M7CEEQECJZACTO2G7";
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://localhost:5173/payment/return";

$startTime = date("YmdHis");
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

$vnp_TxnRef = rand(1,10000);

$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => 49000 * 100,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => "127.0.0.1",
    "vnp_Locale" => "vn",
    "vnp_OrderInfo" => "Thanh toan GD:" . $vnp_TxnRef,
    "vnp_OrderType" => "other",
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
    "vnp_ExpireDate" => $expire
);

ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

// Try SHA512
$hash512 = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
$url512 = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $hash512;

// Try SHA256
$hash256 = hash_hmac('sha256', $hashdata, $vnp_HashSecret);
$url256 = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $hash256;

// Try MD5
$hashMd5 = hash_hmac('md5', $hashdata, $vnp_HashSecret);
$urlMd5 = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $hashMd5;

echo "=== HMAC-SHA512 URL ===\n$url512\n\n";
echo "=== HMAC-SHA256 URL ===\n$url256\n\n";
echo "=== HMAC-MD5 URL ===\n$urlMd5\n\n";
echo "TxnRef: $vnp_TxnRef\n";
echo "CreateDate: " . date('YmdHis') . "\n";
echo "ExpireDate: $expire\n";
echo "HashData: $hashdata\n";
