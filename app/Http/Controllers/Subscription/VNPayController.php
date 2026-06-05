<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Services\VNPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VNPayController extends Controller
{
    public function __construct(
        protected VNPayService $vnpayService
    ) {}

    public function create(CreatePaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->vnpayService->createPaymentUrl(
                $request->user(),
                $request->plan_code,
                $request->billing_cycle,
                $request->transaction_type
            );

            return response()->json([
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'invalid_request',
            ], 400);
        }
    }

    public function return(Request $request): JsonResponse
    {
        $result = $this->vnpayService->verifyReturn($request->query());

        return response()->json(['data' => $result]);
    }

    /**
     * IPN URL - VNPay server gọi đến URL này (GET) để thông báo kết quả thanh toán
     * Theo tài liệu VNPay: Phương thức GET, trả về JSON {RspCode, Message}
     */
    public function ipn(Request $request): JsonResponse
    {
        try {
            // Lấy dữ liệu từ query string (VNPay gửi qua GET params)
            $result = $this->vnpayService->handleIPN($request->query());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'RspCode' => '99',
                'Message' => 'Unknown error',
            ]);
        }
    }
}