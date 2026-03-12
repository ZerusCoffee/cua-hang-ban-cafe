<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/item/{itemKey}', [CartController::class, 'updateItem']);
    Route::delete('/item/{itemKey}', [CartController::class, 'removeItem']);
    Route::delete('/clear', [CartController::class, 'clear']);
});
