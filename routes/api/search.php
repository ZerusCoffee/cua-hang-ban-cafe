<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::get('/suggest', [SearchController::class, 'suggest']);
