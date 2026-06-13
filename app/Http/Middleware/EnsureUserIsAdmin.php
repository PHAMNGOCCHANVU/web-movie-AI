<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền truy cập chức năng quản trị.'
            ], 403);
        }

        return $next($request);
    }
}
