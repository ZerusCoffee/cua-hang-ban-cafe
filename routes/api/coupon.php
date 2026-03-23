<?php

use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/', [CouponController::class, 'index'])->name('index');
    Route::post('validate', [CouponController::class, 'validate'])->name('validate');
    Route::post('apply', [CouponController::class, 'apply'])->name('apply');
});
