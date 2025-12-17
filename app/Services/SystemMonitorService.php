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
                $mpstat = @shell_exec('mpstat 1 1 2>/dev/null | grep -i "average" | tail -1');
                if ($mpstat) {
                    $parts = preg_split('/\s+/', trim($mpstat));
                    $idle = (float) end($parts);
                    return round(100 - $idle, 2);
                }

                // Method 2: Use /proc/stat calculation
                $stat1 = @file('/proc/stat');
                if (!$stat1) {
                    return 0;
                }
                
                $stats1 = explode(' ', preg_replace('!cpu +!', '', $stat1[0]));
                sleep(1);
                
                $stat2 = @file('/proc/stat');
                if (!$stat2) {
                    return 0;
                }
                
                $stats2 = explode(' ', preg_replace('!cpu +!', '', $stat2[0]));

                $total1 = array_sum($stats1);
                $total2 = array_sum($stats2);

                $total = $total2 - $total1;
                $idle = $stats2[3] - $stats1[3];

                return $total > 0 ? round((($total - $idle) / $total) * 100, 2) : 0;
            }
        } catch (\Exception $e) {
            // Don't log to avoid permission issues
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
                $free = @shell_exec('free -b 2>/dev/null');
                if ($free) {
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
            }

            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
        } catch (\Exception $e) {
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
            $disk_total = @disk_total_space('/');
            $disk_free = @disk_free_space('/');

            if ($disk_total === false) {
                // Try current directory
                $disk_total = @disk_total_space('.');
                $disk_free = @disk_free_space('.');
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
            return [
                'usage_percent' => 0,
                'total' => 'N/A',
                'used' => 'N/A',
                'free' => 'N/A'
            ];
        }
    }

    /**
     * Get Swap Memory Usage
     */
    public function getSwapUsage(): array
    {
        try {
            $output = [];
            @exec("free -b 2>/dev/null | grep Swap", $output);
            if (!empty($output)) {
                $parts = preg_split('/\s+/', $output[0]);
                $total = $parts[1] ?? 0;
                $used = $parts[2] ?? 0;
                $free = $parts[3] ?? 0;
                
                $usagePercent = $total > 0 ? ($used / $total) * 100 : 0;
                
                return [
                    'usage_percent' => round($usagePercent, 2),
                    'total' => $this->formatBytes($total),
                    'used' => $this->formatBytes($used),
                    'free' => $this->formatBytes($free),
                ];
            }
            
            return [
                'usage_percent' => 0,
                'total' => '0 B',
                'used' => '0 B',
                'free' => '0 B'
            ];
            
        } catch (\Exception $e) {
            return [
                'usage_percent' => 0,
                'total' => '0 B',
                'used' => '0 B',
                'free' => '0 B'
            ];
        }
    }

    public function collectMetrics(): SystemMetric
    {
        try {
            $ram = $this->getRamUsage();
            $disk = $this->getDiskUsage();

            return SystemMetric::create([
                'cpu_usage' => $this->getCpuUsage(),
                'ram_usage' => $ram['usage_percent'],
                'disk_usage' => $disk['usage_percent'],
                'ram_details' => $ram,
                'disk_details' => $disk,
            ]);
        } catch (\Exception $e) {
            // Create a basic metric record even if collection fails
            return SystemMetric::create([
                'cpu_usage' => 0,
                'ram_usage' => 0,
                'disk_usage' => 0,
                'ram_details' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                'disk_details' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
            ]);
        }
    }

    public function checkThresholds(): array
    {
        try {
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
        } catch (\Exception $e) {
            return [
                'cpu_exceeded' => false,
                'ram_exceeded' => false,
                'disk_exceeded' => false,
                'cpu_value' => 0,
                'ram_value' => 0,
                'disk_value' => 0,
                'cpu_threshold' => env('CPU_THRESHOLD', 80),
                'ram_threshold' => env('RAM_THRESHOLD', 85),
                'disk_threshold' => env('DISK_THRESHOLD', 90),
            ];
        }
    }

    /**
     * Format bytes to human-readable format
     */
    public function formatBytes($bytes, $precision = 2): string
    {
        try {
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
        } catch (\Exception $e) {
            return '0 B';
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        try {
            return [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'server_os' => php_uname('s') . ' ' . php_uname('r'),
                'cpu_count' => $this->getCpuCount(),
                'uptime' => $this->getUptime(),
                'hostname' => gethostname(),
                'ip_address' => $_SERVER['SERVER_ADDR'] ?? @gethostbyname(gethostname()),
            ];
        } catch (\Exception $e) {
            return [
                'php_version' => 'N/A',
                'laravel_version' => 'N/A',
                'server_software' => 'N/A',
                'server_os' => 'N/A',
                'cpu_count' => 1,
                'uptime' => 'N/A',
                'hostname' => 'N/A',
                'ip_address' => 'N/A',
            ];
        }
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
                $cores = @shell_exec('nproc 2>/dev/null');
                if ($cores !== null) {
                    return (int) trim($cores) ?: 1;
                }

                // Alternative method
                if (@is_readable('/proc/cpuinfo')) {
                    $cpuinfo = @file_get_contents('/proc/cpuinfo');
                    preg_match_all('/^processor/m', $cpuinfo, $matches);
                    return count($matches[0]) ?: 1;
                }
            }
        } catch (\Exception $e) {
            // Silent fail
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
                $uptime = @shell_exec('net stats server | find "Statistics since"');
                if ($uptime) {
                    return trim($uptime);
                }
            } else {
                $uptime = @shell_exec('uptime -p 2>/dev/null');
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
            // Silent fail
        }

        return 'N/A';
    }

    /**
     * Get all metrics in one call
     */
    public function getAllMetrics(): array
    {
        try {
            return [
                'cpu' => $this->getCpuUsage(),
                'ram' => $this->getRamUsage(),
                'disk' => $this->getDiskUsage(),
                'thresholds' => $this->checkThresholds(),
                'server_info' => $this->getServerInfo(),
                'timestamp' => now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            return [
                'cpu' => 0,
                'ram' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                'disk' => ['usage_percent' => 0, 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A'],
                'thresholds' => [
                    'cpu_exceeded' => false,
                    'ram_exceeded' => false,
                    'disk_exceeded' => false,
                    'cpu_threshold' => 80,
                    'ram_threshold' => 85,
                    'disk_threshold' => 90,
                ],
                'server_info' => [],
                'timestamp' => now()->toDateTimeString()
            ];
        }
    }

    public function getNetworkStats(): array
    {
        try {
            // Get total connections
            $totalConnections = (int) @exec("ss -tun 2>/dev/null | wc -l") - 1;
            $establishedConnections = (int) @exec("ss -tun state established 2>/dev/null | wc -l") - 1;
            
            return [
                'total_connections' => max(0, $totalConnections),
                'established_connections' => max(0, $establishedConnections),
            ];
        } catch (\Exception $e) {
            return [
                'total_connections' => 0,
                'established_connections' => 0,
            ];
        }
    }

    public function getDatabaseStatus(): array
    {
        try {
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '3306');
            $dbUser = env('DB_USERNAME', 'root');
            $dbPass = env('DB_PASSWORD', '');
            
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new \PDO($dsn, $dbUser, $dbPass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            
            // Get basic info
            $stmt = $pdo->query("SELECT VERSION() as version");
            $version = $stmt ? $stmt->fetchColumn() : 'Unknown';
            
            // Get connection count
            $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
            $connections = $stmt ? $stmt->fetchColumn(1) : 0;
            
            // Get max connections
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
            $maxConnections = $stmt ? $stmt->fetchColumn(1) : 0;
            
            // Get uptime
            $stmt = $pdo->query("SHOW STATUS LIKE 'Uptime'");
            $uptimeSeconds = $stmt ? $stmt->fetchColumn(1) : 0;
            
            return [
                'version' => $version,
                'connections' => [
                    'current' => (int) $connections,
                    'max' => (int) $maxConnections,
                    'usage_percent' => $maxConnections > 0 ? round(($connections / $maxConnections) * 100, 2) : 0,
                ],
                'active_processes' => 0, // Simplified
                'uptime' => $this->formatSeconds($uptimeSeconds),
            ];
        } catch (\Exception $e) {
            return [
                'version' => 'Unknown',
                'connections' => [
                    'current' => 0,
                    'max' => 0,
                    'usage_percent' => 0,
                ],
                'active_processes' => 0,
                'uptime' => 'N/A',
            ];
        }
    }

    public function getPHPFPMStatus(): array
    {
        try {
            $status = ['active' => false];

            // Try to get from socket/status page
            $ctx = stream_context_create(['http' => ['timeout' => 1]]);
            $fpmStatus = @file_get_contents('http://localhost/status?json', false, $ctx);

            if ($fpmStatus) {
                $data = @json_decode($fpmStatus, true);
                if ($data) {
                    $status = [
                        'active' => true,
                        'pool' => $data['pool'] ?? 'unknown',
                        'total_processes' => $data['total processes'] ?? 0,
                        'active_processes' => $data['active processes'] ?? 0,
                        'idle_processes' => $data['idle processes'] ?? 0,
                        'max_children_reached' => $data['max children reached'] ?? 0,
                        'slow_requests' => $data['slow requests'] ?? 0,
                    ];
                }
            }
            
            return $status;
        } catch (\Exception $e) {
            return ['active' => false];
        }
    }

    public function getSSLCertInfo($domain = null): array
    {
        try {
            if (!$domain) {
                $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            }

            $command = "echo | timeout 2 openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -dates 2>/dev/null";
            exec($command, $output);

            $certInfo = ['valid' => false, 'days_remaining' => 0, 'status' => 'unknown'];

            foreach ($output as $line) {
                if (strpos($line, 'notAfter=') !== false) {
                    $expiryDate = str_replace('notAfter=', '', $line);
                    $expiryTimestamp = strtotime($expiryDate);
                    $daysRemaining = floor(($expiryTimestamp - time()) / (60 * 60 * 24));

                    $certInfo = [
                        'valid' => $daysRemaining > 0,
                        'expiry_date' => date('Y-m-d', $expiryTimestamp),
                        'days_remaining' => $daysRemaining,
                        'status' => $daysRemaining > 30 ? 'valid' : ($daysRemaining > 0 ? 'warning' : 'expired'),
                    ];
                    break;
                }
            }
            
            return $certInfo;
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'expiry_date' => 'N/A',
                'days_remaining' => 0,
                'status' => 'unknown'
            ];
        }
    }

    public function getLogStats(): array
    {
        try {
            $logFiles = [
                storage_path('logs/laravel.log') => 'Laravel Log',
                '/var/log/syslog' => 'System Log',
                '/var/log/auth.log' => 'Auth Log',
            ];

            $stats = [];
            foreach ($logFiles as $path => $name) {
                if (@file_exists($path)) {
                    $size = @filesize($path);
                    $lastModified = @filemtime($path);

                    $stats[$name] = [
                        'size' => $this->formatBytes($size ?: 0),
                        'last_modified' => $lastModified ? date('Y-m-d H:i:s', $lastModified) : 'N/A',
                        'recent_errors' => 0, // Simplified for now
                        'path' => $path,
                    ];
                }
            }
            
            return $stats;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getInodeUsage(): array
    {
        try {
            exec("df -i 2>/dev/null", $output);
            $inodeData = [];

            foreach (array_slice($output, 1) as $line) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 6) {
                    $inodeData[] = [
                        'filesystem' => $parts[0],
                        'inodes' => $parts[1],
                        'used' => $parts[2],
                        'available' => $parts[3],
                        'use_percent' => $parts[4],
                        'mounted_on' => $parts[5],
                    ];
                }
            }
            
            return $inodeData;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getDiskIO(): array
    {
        try {
            exec("iostat -d -x 1 2 2>/dev/null | grep -A 10 'Device' | tail -n +3", $output);

            $ioStats = [];
            foreach ($output as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 7) {
                    $ioStats[$parts[0]] = [
                        'read_kb_per_sec' => $parts[5] ?? 0,
                        'write_kb_per_sec' => $parts[6] ?? 0,
                        'utilization_percent' => $parts[13] ?? 0,
                    ];
                }
            }
            
            return $ioStats;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getServiceStatus(): array
    {
        try {
            $services = [
                'nginx' => 'systemctl is-active nginx',
                'apache2' => 'systemctl is-active apache2',
                'mysql' => 'systemctl is-active mysql',
                'mariadb' => 'systemctl is-active mariadb',
                'php8.3-fpm' => 'systemctl is-active php8.3-fpm',
                'redis' => 'systemctl is-active redis-server',
                'supervisor' => 'systemctl is-active supervisor',
                'cron' => 'systemctl is-active cron',
            ];

            $status = [];
            foreach ($services as $name => $command) {
                $result = @shell_exec($command . " 2>/dev/null");
                $status[$name] = trim($result) === 'active' ? 'running' : 
                               (trim($result) === 'inactive' ? 'stopped' : 'not found');
            }
            
            return $status;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTopProcesses(): array
    {
        try {
            exec("ps aux --sort=-%cpu 2>/dev/null | head -6", $cpuProcesses);
            exec("ps aux --sort=-%mem 2>/dev/null | head -6", $memProcesses);

            return [
                'by_cpu' => array_slice($cpuProcesses, 1, 5),
                'by_memory' => array_slice($memProcesses, 1, 5),
            ];
        } catch (\Exception $e) {
            return [
                'by_cpu' => [],
                'by_memory' => []
            ];
        }
    }

    /**
     * Format seconds to human readable time
     */
    private function formatSeconds($seconds): string
    {
        try {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            if ($days > 0) {
                return sprintf('%d days, %d hours', $days, $hours);
            } elseif ($hours > 0) {
                return sprintf('%d hours, %d minutes', $hours, $minutes);
            } elseif ($minutes > 0) {
                return sprintf('%d minutes', $minutes);
            } else {
                return sprintf('%d seconds', $seconds);
            }
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get connections per second (stub method)
     */
    private function getConnectionsPerSecond(): int
    {
        return 0; // Simplified for now
    }
}