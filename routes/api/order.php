<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/', [OrderController::class, 'index']);           // Danh sách đơn
    Route::get('/{orderNumber}', [OrderController::class, 'show']); // Chi tiết đơn
});

