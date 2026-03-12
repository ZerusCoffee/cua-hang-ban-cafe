<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
class CheckCustomerLocked
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_locked) {
            return $this->errorResponse(
                "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.",
                401
            )->withoutCookie('access_token');
        }

        return $next($request);
    }
}
