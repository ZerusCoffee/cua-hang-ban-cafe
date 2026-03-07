<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::post('/checkout', [OrderController::class, 'checkout']); // Tạo order từ giỏ
    Route::get('/', [OrderController::class, 'index']);     // Danh sách đơn của customer
    Route::get('/{orderNumber}', [OrderController::class, 'show']);     // Chi tiết đơn
});

