<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IngestController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::post('/ingest', [IngestController::class, 'store'])
        ->middleware('auth.apikey');

    // Authenticated & Tenant-Scoped routes
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/snapshots/{metric}', [MetricController::class, 'snapshot']);
        Route::get('/metrics/{metric}/history', [MetricController::class, 'history']);
        // API Resource Routes for Dashboards
        Route::apiResource('dashboards', DashboardController::class);
        Route::apiResource('dashboards.widgets', WidgetController::class)
            ->shallow()
            ->only(['store', 'update', 'destroy']);
        // Presence Endpoint
        Route::get('dashboards/{dashboard}/presence', [PresenceController::class, 'index']);
        // Custom Broadcast Auth Endpoint
        Route::post('/broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);
    });
});
