<?php

namespace App\Services;

use App\Models\SystemMetric;

class SystemMonitorService
{
    public function getCpuUsage(): float
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = [];
                exec('wmic cpu get loadpercentage', $output);
                return isset($output[1]) ? (float) trim($output[1]) : 0;
            } else {
                // Method 1: Use mpstat if available (most accurate)
                $mpstat = @shell_exec('mpstat 1 1 | grep -i "average" | tail -1');
                if ($mpstat) {
                    $parts = preg_split('/\s+/', trim($mpstat));
                    $idle = (float) end($parts);
                    return round(100 - $idle, 2);
                }
                
                // Method 2: Use /proc/stat calculation
                $stat1 = file('/proc/stat');
                $stats1 = explode(' ', preg_replace('!cpu +!', '', $stat1[0]));
                sleep(1);
                $stat2 = file('/proc/stat');
                $stats2 = explode(' ', preg_replace('!cpu +!', '', $stat2[0]));
                
                $total1 = array_sum($stats1);
                $total2 = array_sum($stats2);
                
                $total = $total2 - $total1;
                $idle = $stats2[3] - $stats1[3];
                
                return $total > 0 ? round((($total - $idle) / $total) * 100, 2) : 0;
            }
        } catch (\Exception $e) {
            \Log::error('CPU usage error: ' . $e->getMessage());
            return 0;
        }
    }
    public function getRamUsage(): array
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                $output = [];
                exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory', $output);
                
                if (isset($output[1])) {
                    $memory = preg_split('/\s+/', trim($output[1]));
                    $total = $memory[0] ?? 0;
                    $free = $memory[1] ?? 0;
                    $used = $total - $free;
                    
                    $usage_percent = $total > 0 ? ($used / $total) * 100 : 0;
                    
                    return [
                        'usage_percent' => round($usage_percent, 2),
                        'total' => $this->formatBytes($total * 1024),
                        'used' => $this->formatBytes($used * 1024),
                        'free' => $this->formatBytes($free * 1024)
                    ];
                }
            } else {
                // Linux/Unix
                $free = shell_exec('free -b');
                $free = trim($free);
                $free_arr = explode("\n", $free);
                
                if (isset($free_arr[1])) {
                    $mem = preg_split('/\s+/', $free_arr[1]);
                    $total = $mem[1] ?? 0;
                    $used = $mem[2] ?? 0;
                    $free = $mem[3] ?? 0;
                    
                    $usage_percent = $total > 0 ? ($used / $total) * 100 : 0;
                    
                    return [
                        'usage_percent' => round($usage_percent, 2),
                        'total' => $this->formatBytes($total),
                        'used' => $this->formatBytes($used),
                        'free' => $this->formatBytes($free)
                    ];
                }
            }
            
            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
            
        } catch (\Exception $e) {
            \Log::error('RAM usage error: ' . $e->getMessage());
            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
        }
    }

    public function getDiskUsage(): array
    {
        try {
            $disk_total = disk_total_space('/');
            $disk_free = disk_free_space('/');
            
            if ($disk_total === false) {
                // Try current directory
                $disk_total = disk_total_space('.');
                $disk_free = disk_free_space('.');
            }
            
            if ($disk_total !== false && $disk_free !== false) {
                $disk_used = $disk_total - $disk_free;
                $usage_percent = ($disk_used / $disk_total) * 100;

                return [
                    'usage_percent' => round($usage_percent, 2),
                    'total' => $this->formatBytes($disk_total),
                    'used' => $this->formatBytes($disk_used),
                    'free' => $this->formatBytes($disk_free)
                ];
            }
            
            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Disk usage error: ' . $e->getMessage());
            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
        }
    }

    public function collectMetrics(): SystemMetric
    {
        $ram = $this->getRamUsage();
        $disk = $this->getDiskUsage();

        return SystemMetric::create([
            'cpu_usage' => $this->getCpuUsage(),
            'ram_usage' => $ram['usage_percent'],
            'disk_usage' => $disk['usage_percent'],
            'ram_details' => $ram,
            'disk_details' => $disk,
        ]);
    }

    public function checkThresholds(): array
    {
        $cpu = $this->getCpuUsage();
        $ram = $this->getRamUsage();
        $disk = $this->getDiskUsage();

        $cpuThreshold = env('CPU_THRESHOLD', 80);
        $ramThreshold = env('RAM_THRESHOLD', 85);
        $diskThreshold = env('DISK_THRESHOLD', 90);

        return [
            'cpu_exceeded' => $cpu > $cpuThreshold,
            'ram_exceeded' => $ram['usage_percent'] > $ramThreshold,
            'disk_exceeded' => $disk['usage_percent'] > $diskThreshold,
            'cpu_value' => $cpu,
            'ram_value' => $ram['usage_percent'],
            'disk_value' => $disk['usage_percent'],
            'cpu_threshold' => $cpuThreshold,
            'ram_threshold' => $ramThreshold,
            'disk_threshold' => $diskThreshold,
        ];
    }

    /**
     * Format bytes to human-readable format
     */
    public function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        // Calculate the formatted value
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'server_os' => php_uname('s') . ' ' . php_uname('r'),
            'cpu_count' => $this->getCpuCount(),
            'uptime' => $this->getUptime(),
            'hostname' => gethostname(),
            'ip_address' => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
        ];
    }

    /**
     * Get CPU core count
     */
    private function getCpuCount(): int
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $process = @popen('wmic cpu get NumberOfCores', 'rb');
                if ($process !== false) {
                    fgets($process);
                    $cores = (int) fgets($process);
                    pclose($process);
                    return $cores ?: 1;
                }
            } else {
                $cores = @shell_exec('nproc');
                if ($cores !== null) {
                    return (int) trim($cores) ?: 1;
                }
                
                // Alternative method
                if (is_readable('/proc/cpuinfo')) {
                    $cpuinfo = file_get_contents('/proc/cpuinfo');
                    preg_match_all('/^processor/m', $cpuinfo, $matches);
                    return count($matches[0]) ?: 1;
                }
            }
        } catch (\Exception $e) {
            \Log::error('CPU count error: ' . $e->getMessage());
        }
        
        return 1;
    }

    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $uptime = shell_exec('net stats server | find "Statistics since"');
                if ($uptime) {
                    return trim($uptime);
                }
            } else {
                $uptime = @shell_exec('uptime -p');
                if ($uptime) {
                    return trim($uptime);
                }
                
                // Alternative method
                $uptime = @file_get_contents('/proc/uptime');
                if ($uptime !== false) {
                    $uptime = explode(' ', $uptime)[0];
                    $days = floor($uptime / 86400);
                    $hours = floor(($uptime % 86400) / 3600);
                    $minutes = floor(($uptime % 3600) / 60);
                    
                    if ($days > 0) {
                        return sprintf('%d days, %d hours, %d minutes', $days, $hours, $minutes);
                    } elseif ($hours > 0) {
                        return sprintf('%d hours, %d minutes', $hours, $minutes);
                    } else {
                        return sprintf('%d minutes', $minutes);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Uptime error: ' . $e->getMessage());
        }
        
        return 'N/A';
    }

    /**
     * Get all metrics in one call
     */
    public function getAllMetrics(): array
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'ram' => $this->getRamUsage(),
            'disk' => $this->getDiskUsage(),
            'thresholds' => $this->checkThresholds(),
            'server_info' => $this->getServerInfo(),
            'timestamp' => now()->toDateTimeString()
        ];
    }
}