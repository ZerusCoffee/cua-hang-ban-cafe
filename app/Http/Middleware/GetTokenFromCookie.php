<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra xem có cookie 'access_token' không
        $token = $request->cookie('access_token');

        // Nếu có cookie và chưa có Header Authorization
        if ($token && !$request->header('Authorization')) {
            // Tự động gán vào Header để Sanctum hiểu
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
