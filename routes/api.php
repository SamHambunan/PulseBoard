<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);


    Route::post('/ingest', [\App\Http\Controllers\IngestController::class, 'store'])
        ->middleware('auth.apikey');

    // Authenticated & Tenant-Scoped routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/snapshots/{metric}', [\App\Http\Controllers\MetricController::class, 'snapshot']);
        Route::get('/metrics/{metric}/history', [\App\Http\Controllers\MetricController::class, 'history']);
        // API Resource Routes for Dashboards
        Route::apiResource('dashboards', \App\Http\Controllers\DashboardController::class);
        // Custom Broadcast Auth Endpoint
        Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastAuthController::class, 'authenticate']);
    });
});
