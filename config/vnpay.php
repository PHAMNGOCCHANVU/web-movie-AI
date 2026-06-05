<?php

return [
    'tmn_code' => env('VNP_TMN_CODE', ''),
    'hash_secret' => env('VNP_HASH_SECRET', ''),
    'url' => env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url' => env('VNP_RETURN_URL', 'http://localhost:5173/payment/return'),
    'api_url' => env('VNP_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
];