<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PenjualanController;
use App\Http\Controllers\Api\KasHarianController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('pelanggan', PelangganController::class)->only(['index', 'show']);
    Route::apiResource('item', ItemController::class)->only(['index', 'show']);

    Route::get('penjualan', [PenjualanController::class, 'index']);
    Route::post('penjualan', [PenjualanController::class, 'store']);

    Route::get('kas-harian', [KasHarianController::class, 'index']);
    Route::post('kas-harian', [KasHarianController::class, 'store']);

    Route::get('dashboard', [DashboardController::class, 'show']);
});
