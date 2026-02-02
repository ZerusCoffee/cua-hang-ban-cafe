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
        $token = $request->cookie('access_token');

        if ($token && !$request->header('Authorization')) {

            $token = urldecode($token);

            if (str_starts_with($token, 'Bearer ')) {
                $token = trim(substr($token, 7));
            }

            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
