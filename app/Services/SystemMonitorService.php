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

    public function getNetworkStats(): array
    {
        try {
            // Network interfaces
            exec("ip -s link show", $output);
            $interfaces = [];
            $currentInterface = null;

            foreach ($output as $line) {
                if (preg_match('/^\d+:\s+(\w+):/', $line, $matches)) {
                    $currentInterface = $matches[1];
                    $interfaces[$currentInterface] = ['rx' => 0, 'tx' => 0];
                } elseif ($currentInterface && preg_match('/RX:\s+bytes\s+(\d+)/', $line, $matches)) {
                    $interfaces[$currentInterface]['rx'] = $this->formatBytes($matches[1]);
                } elseif ($currentInterface && preg_match('/TX:\s+bytes\s+(\d+)/', $line, $matches)) {
                    $interfaces[$currentInterface]['tx'] = $this->formatBytes($matches[1]);
                }
            }

            // Active connections
            $tcpConnections = (int) @exec("ss -tun | wc -l") - 1;
            $establishedConnections = (int) @exec("ss -tun state established | wc -l") - 1;

            return [
                'interfaces' => $interfaces,
                'total_connections' => $tcpConnections,
                'established_connections' => $establishedConnections,
                'connections_per_second' => $this->getConnectionsPerSecond(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getDatabaseStatus(): array
    {
        try {
            $pdo = new \PDO(
                'mysql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . env('DB_PORT', '3306'),
                env('DB_USERNAME', 'root'),
                env('DB_PASSWORD', '')
            );

            // Get status variables
            $stmt = $pdo->query("SHOW GLOBAL STATUS");
            $statusVars = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $statusVars[$row['Variable_name']] = $row['Value'];
            }

            // Get process list
            $stmt = $pdo->query("SHOW PROCESSLIST");
            $processes = $stmt->rowCount();

            // Get connections
            $connections = $statusVars['Threads_connected'] ?? 0;
            $maxConnections = $statusVars['max_connections'] ?? 0;

            return [
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'connections' => [
                    'current' => (int) $connections,
                    'max' => (int) $maxConnections,
                    'usage_percent' => $maxConnections > 0 ? round(($connections / $maxConnections) * 100, 2) : 0,
                ],
                'active_processes' => $processes,
                'queries_per_second' => $statusVars['Queries'] ?? 0,
                'slow_queries' => $statusVars['Slow_queries'] ?? 0,
                'uptime' => $this->formatSeconds($statusVars['Uptime'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    public function getPHPFPMStatus(): array
    {
        $status = ['active' => false];

        // Try to get from socket/status page
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $fpmStatus = @file_get_contents('http://localhost/status?json', false, $ctx);

        if ($fpmStatus) {
            $data = json_decode($fpmStatus, true);
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
    }
    public function getSSLCertInfo($domain = null): array
    {
        if (!$domain) {
            $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        $command = "echo | openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -dates";
        exec($command, $output);

        $certInfo = ['valid' => false, 'days_remaining' => 0];

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
            }
        }

        return $certInfo;
    }
    public function getLogStats(): array
    {
        $logFiles = [
            '/var/log/syslog' => 'System Log',
            '/var/log/auth.log' => 'Auth Log',
            '/var/log/nginx/error.log' => 'Nginx Errors',
            '/var/log/apache2/error.log' => 'Apache Errors',
            storage_path('logs/laravel.log') => 'Laravel Log',
        ];

        $stats = [];
        foreach ($logFiles as $path => $name) {
            if (file_exists($path)) {
                $size = filesize($path);
                $lastModified = filemtime($path);
                $errorCount = @exec("grep -i 'error\\|fatal\\|exception' " . escapeshellarg($path) . " | tail -20 | wc -l");

                $stats[$name] = [
                    'size' => $this->formatBytes($size),
                    'last_modified' => date('Y-m-d H:i:s', $lastModified),
                    'recent_errors' => (int) $errorCount,
                    'path' => $path,
                ];
            }
        }

        return $stats;
    }
    public function getInodeUsage(): array
    {
        exec("df -i", $output);
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
    }
    public function getDiskIO(): array
    {
        exec("iostat -d -x 1 2 | grep -A 10 'Device' | tail -n +3", $output);

        $ioStats = [];
        foreach ($output as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 7) {
                $ioStats[$parts[0]] = [
                    'read_kb_per_sec' => $parts[5] ?? 0,
                    'write_kb_per_sec' => $parts[6] ?? 0,
                    'utilization_percent' => $parts[13] ?? 0, // %util
                ];
            }
        }

        return $ioStats;
    }
    public function getServiceStatus(): array
    {
        $services = [
            'nginx' => 'systemctl is-active nginx',
            'apache2' => 'systemctl is-active apache2',
            'mysql' => 'systemctl is-active mysql',
            'mariadb' => 'systemctl is-active mariadb',
            'redis' => 'systemctl is-active redis-server',
            'php-fpm' => 'systemctl is-active php8.2-fpm', // Adjust version
            'supervisor' => 'systemctl is-active supervisor',
            'cron' => 'systemctl is-active cron',
        ];

        $status = [];
        foreach ($services as $name => $command) {
            $result = @shell_exec($command . " 2>/dev/null");
            $status[$name] = trim($result) === 'active' ? 'running' : (trim($result) === 'inactive' ? 'stopped' : 'not found');
        }

        return $status;
    }
    public function getTopProcesses(): array
    {
        exec("ps aux --sort=-%cpu | head -6", $cpuProcesses);
        exec("ps aux --sort=-%mem | head -6", $memProcesses);

        return [
            'by_cpu' => array_slice($cpuProcesses, 1, 5), // Skip header
            'by_memory' => array_slice($memProcesses, 1, 5),
        ];
    }
}
