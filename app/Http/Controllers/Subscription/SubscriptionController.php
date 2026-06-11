<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return response()->json(['data' => $plans]);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('subscriptionPlan');

        $daysRemaining = null;
        if ($user->subscription_expires_at) {
            $daysRemaining = max(0, now()->diffInDays($user->subscription_expires_at, false));
        }

        return response()->json([
            'data' => [
                'user' => $user,
                'days_remaining' => $daysRemaining,
                'is_active' => $user->hasActiveSubscription(),
                'is_vip' => $user->isVip(),
            ],
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->subscription_status !== 'active') {
            return response()->json([
                'message' => 'Bạn không có gói cước đang hoạt động.',
                'error' => 'no_active_subscription',
            ], 400);
        }

        $request->validate(['cancellation_reason' => 'nullable|string|max:1000']);

        $user->update([
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        // Ghi subscription_history
        $user->subscriptionHistories()->create([
            'subscription_plan_id' => $user->subscription_plan_id,
            'action' => 'cancelled',
            'reason' => $request->cancellation_reason,
        ]);

        return response()->json([
            'message' => "Gói cước của bạn sẽ kết thúc vào {$user->subscription_expires_at->format('d/m/Y')}. Bạn vẫn có quyền xem phim cho đến ngày đó.",
            'data' => [
                'expires_at' => $user->subscription_expires_at,
                'auto_renew' => false,
            ],
        ]);
    }

    public function paymentHistory(Request $request): JsonResponse
    {
        $transactions = $request->user()->transactions()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['data' => $transactions]);
    }

    public function paymentStatus(Request $request, $transactionId): JsonResponse
    {
        $transaction = Transaction::with('subscriptionPlan')
            ->where('user_id', $request->user()->id)
            ->findOrFail($transactionId);

        return response()->json(['data' => $transaction]);
    }
}
