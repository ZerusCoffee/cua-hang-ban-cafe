<?php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


Route::get('/', [ProductController::class, 'index']);
Route::get('/featured', [ProductController::class, 'getAllFeatured']);
Route::get('/{product}', [ProductController::class, 'show']);
