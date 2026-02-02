<?php

use App\Http\Controllers\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::post("/login", [CustomerAuthController::class, "login"]);
Route::post("/register", [CustomerAuthController::class, "register"]);
Route::post("/google", [CustomerAuthController::class, "googleLogin"]);
Route::post("/send-mail-forgot-password", [CustomerAuthController::class, "sendMailForgotPassword"]);
Route::post("/reset-password", [CustomerAuthController::class, "resetPassword"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::post("/logout", [CustomerAuthController::class, "logout"]);
    Route::get("/profile", function (Illuminate\Http\Request $request) {
        return response()->json(["status" => "success", "data" => $request->user()]);
    });
    Route::put("/profile", [CustomerAuthController::class, "updateProfile"]);
    Route::put("/avatar", [CustomerAuthController::class, "updateAvatar"]);
    Route::put("/change-password", [CustomerAuthController::class, "updatePassword"]);
});
