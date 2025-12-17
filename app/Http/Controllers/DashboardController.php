<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SystemMonitorService;
use App\Models\SystemMetric;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request, SystemMonitorService $monitor)
    {
        try {
            $currentStats = [
                'cpu' => $monitor->getCpuUsage(),
                'ram' => $monitor->getRamUsage(),
                'disk' => $monitor->getDiskUsage(),
                'thresholds' => $monitor->checkThresholds()
            ];

            // Get historical data (last 24 hours)
            $historicalData = SystemMetric::where('created_at', '>=', now()->subDay())
                ->orderBy('created_at')
                ->get();

            // Get latest metrics
            $latestMetrics = SystemMetric::latest()->take(50)->get();

            return view('dashboard', compact('currentStats', 'historicalData', 'latestMetrics'));
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            return view('dashboard', [
                'error' => 'Unable to fetch system metrics',
                'currentStats' => [
                    'cpu' => 0,
                    'ram' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                    'disk' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                    'thresholds' => [
                        'cpu_exceeded' => false,
                        'ram_exceeded' => false,
                        'disk_exceeded' => false,
                        'cpu_threshold' => env('CPU_THRESHOLD', 80),
                        'ram_threshold' => env('RAM_THRESHOLD', 85),
                        'disk_threshold' => env('DISK_THRESHOLD', 90),
                    ]
                ],
                'historicalData' => collect([]),
                'latestMetrics' => collect([])
            ]);
        }
    }

    public function refreshStats(SystemMonitorService $monitor)
    {
        try {
            $currentStats = [
                'cpu' => $monitor->getCpuUsage(),
                'ram' => $monitor->getRamUsage(),
                'disk' => $monitor->getDiskUsage(),
                'thresholds' => $monitor->checkThresholds()
            ];

            return response()->json($currentStats);
        } catch (\Exception $e) {
            Log::error('Refresh stats error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch stats'], 500);
        }
    }

    public function collectMetrics(SystemMonitorService $monitor)
    {
        try {
            $metric = $monitor->collectMetrics();
            return response()->json($metric);
        } catch (\Exception $e) {
            Log::error('Collect metrics error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to collect metrics'], 500);
        }
    }
}