<?php

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/', [CheckoutController::class, 'checkout']);
    Route::delete('/cancel/{orderNumber}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
});

// Payment callbacks — không cần auth, cổng thanh toán gọi về
Route::prefix('callback')->group(function () {
    Route::get('/vnpay', [CheckoutController::class, 'vnpayCallback'])->name('vnpay.callback');
    Route::get('/momo', [CheckoutController::class, 'momoCallback'])->name('momo.callback');
    Route::post('/momo/ipn', [CheckoutController::class, 'momoIpn'])->name('momo.ipn');
    Route::get('/paypal', [CheckoutController::class, 'paypalCallback'])->name('paypal.callback');
});
