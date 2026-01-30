<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::prefix("v1")->group(function () {
    Route::prefix("auth")->group(function () {
        Route::post("/login", [CustomerAuthController::class, "login"]);

        Route::post("/register", [CustomerAuthController::class, "register"]);

        Route::post("/google", [CustomerAuthController::class, "googleLogin"]);

        Route::post("/send-mail-forgot-password", [
            CustomerAuthController::class,
            "sendMailForgotPassword",
        ]);

        Route::post("/reset-password", [
            CustomerAuthController::class,
            "resetPassword",
        ]);

        // Route private (cần login + token)
        Route::middleware("auth:sanctum")->group(function () {
            Route::post("/logout", [CustomerAuthController::class, "logout"]);

            Route::get("/profile", function (Request $request) {
                return response()->json([
                    "status" => "success",
                    "data" => $request->user(),
                ]);
            });
            Route::put("/profile", [
                CustomerAuthController::class,
                "updateProfile",
            ]);
            Route::put("/avatar", [
                CustomerAuthController::class,
                "updateAvatar",
            ]);
        });
    });
});
