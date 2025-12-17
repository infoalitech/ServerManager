<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/refresh-stats', [DashboardController::class, 'refreshStats']);
Route::get('/collect-metrics', [DashboardController::class, 'collectMetrics']);

// You might want to add authentication for production
// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index']);
// });