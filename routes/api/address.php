<?php

use App\Http\Controllers\AddressController;
use Illuminate\Support\Facades\Route;

Route::get('/provinces', [AddressController::class, 'getProvinces']);

Route::get('/wards/{provinceCode}', [AddressController::class, 'getWardsByProvinceCode']);

Route::middleware("auth:sanctum")->group(function () {
    Route::get('/default', [AddressController::class, 'getDefault']);
    Route::patch('/{address}/set-default', [AddressController::class, 'setDefault']);

    Route::apiResource('/', AddressController::class)->parameters([
        '' => 'address'
    ]);

});
