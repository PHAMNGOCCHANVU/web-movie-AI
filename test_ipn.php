<?php
// Mô phỏng VNPay IPN callback

$vnp_HashSecret = 'INHN2HPMJXBZAK8M7CEEQECJZACTO2G7';

$vnpTxnRef = $argv[1] ?? 'TXN1780660491ZQzqu2';
$vnpAmount = $argv[2] ?? '4900000'; // 49000 VND * 100

$inputData = [
    'vnp_Amount' => $vnpAmount,
    'vnp_BankCode' => 'NCB',
    'vnp_BankTranNo' => 'VNP' . rand(10000000, 99999999),
    'vnp_CardType' => 'ATM',
    'vnp_OrderInfo' => 'Thanh toan goi Standard - Hang thang',
    'vnp_PayDate' => date('YmdHis'),
    'vnp_ResponseCode' => '00',
    'vnp_TmnCode' => 'RJXNTQ8K',
    'vnp_TransactionNo' => rand(10000000, 99999999),
    'vnp_TransactionStatus' => '00',
    'vnp_TxnRef' => $vnpTxnRef,
];

ksort($inputData);

// Build hash data (same as VNPay sample)
$i = 0;
$hashData = '';
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
    } else {
        $hashData .= urlencode($key) . '=' . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$inputData['vnp_SecureHash'] = $secureHash;

// Build IPN URL
$ipnUrl = 'http://localhost:8000/api/payment/vnpay/ipn?' . http_build_query($inputData);

echo "IPN URL:\n$ipnUrl\n\n";

// Call the IPN endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ipnUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
