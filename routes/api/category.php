<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CategoryController::class, 'index']);
Route::get('/{category}', [CategoryController::class, 'show']);
