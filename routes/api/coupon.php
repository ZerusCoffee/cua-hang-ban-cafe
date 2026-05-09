<?php

use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CouponController::class, 'index'])->name('index');

Route::middleware("auth:sanctum")->group(function () {
    Route::post('/preview', [CouponController::class, 'preview']);
});
