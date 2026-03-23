<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/product/{productId}', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/product/{productId}', [ReviewController::class, 'store']);
    Route::put('/{review}', [ReviewController::class, 'update']);
    Route::delete('/{review}', [ReviewController::class, 'destroy']);
});
