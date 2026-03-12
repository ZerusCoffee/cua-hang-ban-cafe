<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::prefix("v1")->group(function () {
    Route::prefix("auth")->group(base_path('routes/api/auth.php'));

    Route::prefix("address")->group(base_path('routes/api/address.php'));

    Route::prefix("product")->group(base_path('routes/api/product.php'));

    Route::prefix("category")->group(base_path('routes/api/category.php'));

    Route::prefix("cart")->group(base_path('routes/api/cart.php'));

    Route::prefix("order")->group(base_path('routes/api/order.php'));

    Route::prefix('checkout')->group(base_path('routes/api/checkout.php'));
});
