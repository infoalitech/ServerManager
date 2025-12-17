<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/refresh-stats', [DashboardController::class, 'refreshStats']);
Route::get('/collect-metrics', [DashboardController::class, 'collectMetrics']);

// New routes for additional stats
Route::get('/service-status', [DashboardController::class, 'getServiceStatus']);
Route::get('/network-info', [DashboardController::class, 'getNetworkInfo']);
Route::get('/database-info', [DashboardController::class, 'getDatabaseInfo']);
Route::get('/log-summary', [DashboardController::class, 'getLogSummary']);
Route::get('/trigger-alerts', [DashboardController::class, 'triggerAlerts']);

// API routes (if you want separate API endpoints)
Route::prefix('api')->group(function () {
    Route::get('/stats', [DashboardController::class, 'refreshStats']);
    Route::get('/services', [DashboardController::class, 'getServiceStatus']);
    Route::get('/alerts', [DashboardController::class, 'triggerAlerts']);
});