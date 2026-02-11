<?php

use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::apiResource('/', CouponController::class)->parameters([
        '' => 'coupon'
    ]);
});
