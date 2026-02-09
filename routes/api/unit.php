<?php

use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::apiResource('', UnitController::class)->parameters([
    '' => 'unit'
]);
