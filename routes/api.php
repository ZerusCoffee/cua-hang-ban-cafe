<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::prefix("v1")->group(function () {
    Route::prefix("auth")->group(base_path('routes/api/auth.php'));

    Route::prefix("address")->group(base_path('routes/api/address.php'));

    Route::prefix("unit")->group(base_path('routes/api/unit.php'));
});
