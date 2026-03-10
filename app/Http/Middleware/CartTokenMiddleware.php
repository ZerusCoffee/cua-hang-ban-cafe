<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartTokenMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('cart_token') ?? $request->header('X-Cart-Token');
        $isNew = false;

        if (!$token) {
            // Không có token → tạo mới, KHÔNG init Redis
            // Redis chỉ init khi addItem được gọi
            $token = Str::uuid()->toString();
            $isNew = true;
        }
        // Có token → dùng luôn, kể cả chưa có trong Redis (giỏ trống)

        $request->attributes->set('cart_token', $token);

        $response = $next($request);

        $response->headers->set('X-Cart-Token', $token);

        if ($isNew) {
            $response->cookie('cart_token', $token, 60 * 24 * 7, '/', null, false, true, false, 'lax');
        }

        return $response;
    }
}
