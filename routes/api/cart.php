<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/', [CartController::class, 'index']);       // Xem giỏ
    Route::post('/items', [CartController::class, 'addItem']);     // Thêm item
    Route::patch('/items/{itemKey}', [CartController::class, 'updateItem']);  // Cập nhật SL
    Route::delete('/items/{itemKey}', [CartController::class, 'removeItem']);  // Xóa 1 item
    Route::delete('/', [CartController::class, 'clear']);       // Xóa cả giỏ
});
