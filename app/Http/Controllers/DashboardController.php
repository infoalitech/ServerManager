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
            // Get all system metrics
            $currentStats = [
                'cpu' => $monitor->getCpuUsage(),
                'ram' => $monitor->getRamUsage(),
                'disk' => $monitor->getDiskUsage(),
                'swap' => $monitor->getSwapUsage(),
                'network' => $monitor->getNetworkStats(),
                'processes' => $monitor->getTopProcesses(),
                'services' => $monitor->getServiceStatus(),
                'disk_io' => $monitor->getDiskIO(),
                'inodes' => $monitor->getInodeUsage(),
                'logs' => $monitor->getLogStats(),
                'ssl' => $monitor->getSSLCertInfo(),
                'database' => $monitor->getDatabaseStatus(),
                'php_fpm' => $monitor->getPHPFPMStatus(),
                'server_info' => $monitor->getServerInfo(),
                'thresholds' => $monitor->checkThresholds()
            ];

            // Get historical data (last 24 hours)
            $historicalData = SystemMetric::where('created_at', '>=', now()->subDay())
                ->orderBy('created_at')
                ->get();

            // Get latest metrics
            $latestMetrics = SystemMetric::latest()->take(50)->get();

            // Calculate additional thresholds for new metrics
            $additionalThresholds = [
                'swap_threshold' => 50, // Alert if swap > 50%
                'connections_threshold' => 1000, // Alert if connections > 1000
                'inode_threshold' => 80, // Alert if inodes > 80%
                'ssl_warning_days' => 30, // Warn if SSL expires in < 30 days
            ];

            return view('dashboard', compact(
                'currentStats', 
                'historicalData', 
                'latestMetrics',
                'additionalThresholds'
            ));
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            // Fallback with all new stats structure
            return view('dashboard', [
                'error' => 'Unable to fetch system metrics: ' . $e->getMessage(),
                'currentStats' => [
                    'cpu' => 0,
                    'ram' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                    'disk' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                    'swap' => ['usage_percent' => 0, 'total' => '0 B', 'used' => '0 B', 'free' => '0 B'],
                    'network' => ['total_connections' => 0, 'established_connections' => 0],
                    'processes' => ['by_cpu' => [], 'by_memory' => []],
                    'services' => [],
                    'disk_io' => [],
                    'inodes' => [],
                    'logs' => [],
                    'ssl' => ['valid' => false, 'days_remaining' => 0],
                    'database' => [],
                    'php_fpm' => ['active' => false],
                    'server_info' => [],
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
                'latestMetrics' => collect([]),
                'additionalThresholds' => [
                    'swap_threshold' => 50,
                    'connections_threshold' => 1000,
                    'inode_threshold' => 80,
                    'ssl_warning_days' => 30,
                ]
            ]);
        }
    }

    public function refreshStats(SystemMonitorService $monitor)
    {
        try {
            // Get updated stats (lightweight - only essential)
            $currentStats = [
                'cpu' => $monitor->getCpuUsage(),
                'ram' => $monitor->getRamUsage(),
                'disk' => $monitor->getDiskUsage(),
                'swap' => $monitor->getSwapUsage(),
                'network' => $monitor->getNetworkStats(),
                'processes' => $monitor->getTopProcesses(),
                'thresholds' => $monitor->checkThresholds()
            ];

            return response()->json($currentStats);
        } catch (\Exception $e) {
            Log::error('Refresh stats error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to fetch stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function collectMetrics(SystemMonitorService $monitor)
    {
        try {
            $metric = $monitor->collectMetrics();
            return response()->json([
                'success' => true,
                'message' => 'Metrics collected successfully',
                'metric' => $metric,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Collect metrics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to collect metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getServiceStatus(SystemMonitorService $monitor)
    {
        try {
            $services = $monitor->getServiceStatus();
            return response()->json([
                'success' => true,
                'services' => $services,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Service status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch service status'
            ], 500);
        }
    }

    public function getNetworkInfo(SystemMonitorService $monitor)
    {
        try {
            $network = $monitor->getNetworkStats();
            $ssl = $monitor->getSSLCertInfo();
            
            return response()->json([
                'success' => true,
                'network' => $network,
                'ssl' => $ssl,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Network info error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch network info'
            ], 500);
        }
    }

    public function getDatabaseInfo(SystemMonitorService $monitor)
    {
        try {
            $database = $monitor->getDatabaseStatus();
            
            return response()->json([
                'success' => true,
                'database' => $database,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Database info error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch database info'
            ], 500);
        }
    }

    public function getLogSummary(SystemMonitorService $monitor)
    {
        try {
            $logs = $monitor->getLogStats();
            
            return response()->json([
                'success' => true,
                'logs' => $logs,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Log summary error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch log summary'
            ], 500);
        }
    }

    public function triggerAlerts(SystemMonitorService $monitor)
    {
        try {
            // Get current thresholds
            $alerts = $monitor->checkThresholds();
            
            // Check additional thresholds
            $swap = $monitor->getSwapUsage();
            $network = $monitor->getNetworkStats();
            $ssl = $monitor->getSSLCertInfo();
            
            $additionalAlerts = [
                'swap_exceeded' => ($swap['usage_percent'] ?? 0) > 50,
                'high_connections' => ($network['total_connections'] ?? 0) > 1000,
                'ssl_expiring_soon' => ($ssl['days_remaining'] ?? 0) > 0 && ($ssl['days_remaining'] ?? 0) < 30,
                'ssl_expired' => ($ssl['days_remaining'] ?? 0) <= 0,
            ];
            
            // Combine all alerts
            $allAlerts = array_merge($alerts, $additionalAlerts);
            
            // Check if any alert is triggered
            $hasAlerts = in_array(true, $allAlerts, true);
            
            return response()->json([
                'success' => true,
                'has_alerts' => $hasAlerts,
                'alerts' => $allAlerts,
                'swap' => $swap,
                'network' => $network,
                'ssl' => $ssl,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Trigger alerts error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Unable to check alerts'
            ], 500);
        }
    }
}