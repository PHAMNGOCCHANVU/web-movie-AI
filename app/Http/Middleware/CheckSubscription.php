<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'require_login',
                'message' => 'Vui lòng đăng nhập để xem phim.',
            ], 401);
        }

        // Tự động đánh expired nếu hết hạn
        if ($user->subscription_status === 'active' 
            && $user->subscription_expires_at 
            && $user->subscription_expires_at->isPast()) {
            $user->update(['subscription_status' => 'expired']);
        }

        if ($user->subscription_status !== 'active') {
            return response()->json([
                'error' => 'require_subscription',
                'message' => 'Bạn cần đăng ký gói cước để xem phim.',
            ], 403);
        }

        return $next($request);
    }
}