<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use App\Http\Middleware\GetTokenFromCookie;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', [
            GetTokenFromCookie::class,
        ]);

        $middleware->encryptCookies(except: [
            'access_token',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Bắt sự kiện render lỗi
        $exceptions->render(function (Throwable $e, Request $request) {

            // Chỉ can thiệp nếu request gọi vào đường dẫn bắt đầu bằng 'api/'
            if ($request->is('api/*')) {

                // 1. Xử lý lỗi không tìm thấy (404)
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy tài nguyên yêu cầu (404).',
                    ], 404);
                }

                // 2. Xử lý lỗi Validation (422) - Ví dụ: thiếu email, sai định dạng
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Dữ liệu không hợp lệ.',
                        'errors' => $e->errors(), // Trả về chi tiết lỗi
                    ], 422);
                }

                // 3. Các lỗi server khác (500, code sai, v.v.)
                // Lưu ý: Môi trường production nên ẩn $e->getMessage() để bảo mật
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
    })->create();
