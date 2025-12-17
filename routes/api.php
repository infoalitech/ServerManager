<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/monitor-run', function () {
        try {
            Artisan::call('monitor:server');
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Monitor command executed successfully',
                'output' => trim($output),
                'alert_sent' => strpos($output, 'Alert email sent') !== false,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute monitor command',
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    });

    // You can add more API routes here
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toDateTimeString()
        ]);
    });
});