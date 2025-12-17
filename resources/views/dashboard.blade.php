<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Monitor Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .glow-alert {
            animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow {
            from { box-shadow: 0 0 5px #f56565; }
            to { box-shadow: 0 0 20px #f56565, 0 0 30px #f56565; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-server mr-2"></i>Server Monitor Dashboard
            </h1>
            <div class="text-sm text-gray-600">
                <i class="fas fa-clock mr-1"></i>
                <span id="current-time">{{ now()->format('Y-m-d H:i:s') }}</span>
                <span class="mx-2">•</span>
                <span id="uptime">{{ $currentStats['server_info']['uptime'] ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Alert Banner (if any alerts) -->
        @php
            $hasAlerts = $currentStats['thresholds']['cpu_exceeded'] || 
                         $currentStats['thresholds']['ram_exceeded'] || 
                         $currentStats['thresholds']['disk_exceeded'] ||
                         ($currentStats['swap']['usage_percent'] ?? 0) > ($additionalThresholds['swap_threshold'] ?? 50) ||
                         ($currentStats['ssl']['days_remaining'] ?? 0) < ($additionalThresholds['ssl_warning_days'] ?? 30);
        @endphp
        
        @if($hasAlerts)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 glow-alert rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-bold">
                        <i class="fas fa-bell mr-1"></i>ALERT: Server resources exceeded thresholds!
                    </p>
                    <div class="mt-1 text-sm text-red-600">
                        @if($currentStats['thresholds']['cpu_exceeded'])
                        <span class="mr-3"><i class="fas fa-microchip mr-1"></i>CPU: {{ $currentStats['cpu'] }}%</span>
                        @endif
                        @if($currentStats['thresholds']['ram_exceeded'])
                        <span class="mr-3"><i class="fas fa-memory mr-1"></i>RAM: {{ $currentStats['ram']['usage_percent'] }}%</span>
                        @endif
                        @if($currentStats['thresholds']['disk_exceeded'])
                        <span class="mr-3"><i class="fas fa-hard-drive mr-1"></i>Disk: {{ $currentStats['disk']['usage_percent'] }}%</span>
                        @endif
                        @if(($currentStats['swap']['usage_percent'] ?? 0) > ($additionalThresholds['swap_threshold'] ?? 50))
                        <span class="mr-3"><i class="fas fa-exchange-alt mr-1"></i>Swap: {{ $currentStats['swap']['usage_percent'] ?? 0 }}%</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Core Resources Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- CPU Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{cpu: {{ $currentStats['cpu'] }}, threshold: {{ $currentStats['thresholds']['cpu_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-microchip mr-2"></i>CPU
                    </h2>
                    <span class="text-2xl font-bold" :class="cpu > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="cpu.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-3 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(cpu, 100) + '%'" 
                             :class="cpu > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Threshold: {{ $currentStats['thresholds']['cpu_threshold'] }}%</span>
                        <span :class="cpu > threshold ? 'text-red-600 font-bold' : 'text-green-600'">
                            <i class="fas" :class="cpu > threshold ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>
                            <span x-text="cpu > threshold ? ' Alert' : ' Normal'"></span>
                        </span>
                    </div>
                </div>
                @if(isset($currentStats['server_info']['cpu_count']))
                <div class="mt-3 text-sm text-gray-500">
                    <i class="fas fa-layer-group mr-1"></i>Cores: {{ $currentStats['server_info']['cpu_count'] ?? 1 }}
                </div>
                @endif
            </div>

            <!-- RAM Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{ram: {{ $currentStats['ram']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['ram_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-memory mr-2"></i>RAM
                    </h2>
                    <span class="text-2xl font-bold" :class="ram > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="ram.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-3 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(ram, 100) + '%'" 
                             :class="ram > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-sm">
                        <div class="text-gray-600">Total:</div><div>{{ $currentStats['ram']['total'] }}</div>
                        <div class="text-gray-600">Used:</div><div>{{ $currentStats['ram']['used'] }}</div>
                        <div class="text-gray-600">Free:</div><div>{{ $currentStats['ram']['free'] }}</div>
                        <div class="text-gray-600">Threshold:</div><div>{{ $currentStats['thresholds']['ram_threshold'] }}%</div>
                    </div>
                </div>
            </div>

            <!-- Disk Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{disk: {{ $currentStats['disk']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['disk_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-hard-drive mr-2"></i>Disk
                    </h2>
                    <span class="text-2xl font-bold" :class="disk > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="disk.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-3 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(disk, 100) + '%'" 
                             :class="disk > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-sm">
                        <div class="text-gray-600">Total:</div><div>{{ $currentStats['disk']['total'] }}</div>
                        <div class="text-gray-600">Used:</div><div>{{ $currentStats['disk']['used'] }}</div>
                        <div class="text-gray-600">Free:</div><div>{{ $currentStats['disk']['free'] }}</div>
                        <div class="text-gray-600">Threshold:</div><div>{{ $currentStats['thresholds']['disk_threshold'] }}%</div>
                    </div>
                </div>
            </div>

            <!-- Swap Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{swap: {{ $currentStats['swap']['usage_percent'] ?? 0 }}, threshold: {{ $additionalThresholds['swap_threshold'] ?? 50 }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-exchange-alt mr-2"></i>Swap
                    </h2>
                    <span class="text-2xl font-bold" :class="swap > threshold ? 'text-yellow-600' : 'text-green-600'">
                        <span x-text="swap.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-3 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(swap, 100) + '%'" 
                             :class="swap > threshold ? 'bg-yellow-500 progress-bar' : 'bg-blue-400 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-sm">
                        <div class="text-gray-600">Total:</div><div>{{ $currentStats['swap']['total'] ?? '0 B' }}</div>
                        <div class="text-gray-600">Used:</div><div>{{ $currentStats['swap']['used'] ?? '0 B' }}</div>
                        <div class="text-gray-600">Free:</div><div>{{ $currentStats['swap']['free'] ?? '0 B' }}</div>
                        <div class="text-gray-600">Threshold:</div><div>{{ $additionalThresholds['swap_threshold'] ?? 50 }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Network & Services -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Network Stats -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-network-wired mr-2"></i>Network
                </h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Connections</div>
                            <div class="text-2xl font-bold">{{ $currentStats['network']['total_connections'] ?? 0 }}</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Established</div>
                            <div class="text-2xl font-bold">{{ $currentStats['network']['established_connections'] ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <!-- SSL Certificate -->
                    @if(isset($currentStats['ssl']['valid']))
                    <div class="border-t pt-4">
                        <h3 class="font-semibold mb-2 text-gray-700">
                            <i class="fas fa-lock mr-2"></i>SSL Certificate
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="{{ $currentStats['ssl']['status'] == 'valid' ? 'text-green-600' : ($currentStats['ssl']['status'] == 'warning' ? 'text-yellow-600' : 'text-red-600') }} font-bold">
                                    {{ $currentStats['ssl']['status'] ?? 'unknown' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Expires:</span>
                                <span>{{ $currentStats['ssl']['expiry_date'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Days Left:</span>
                                <span class="{{ ($currentStats['ssl']['days_remaining'] ?? 0) < 30 ? 'text-yellow-600 font-bold' : 'text-green-600' }}">
                                    {{ $currentStats['ssl']['days_remaining'] ?? 0 }} days
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Services Status -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-cogs mr-2"></i>Services
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($currentStats['services'] as $service => $status)
                    <div class="p-3 rounded text-center status-badge {{ $status == 'running' ? 'bg-green-100 text-green-800 border border-green-200' : ($status == 'stopped' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-gray-100 text-gray-800 border border-gray-200') }}">
                        <div class="font-semibold truncate" title="{{ $service }}">{{ $service }}</div>
                        <div class="text-xs mt-1">
                            @if($status == 'running')
                            <i class="fas fa-check-circle"></i>
                            @elseif($status == 'stopped')
                            <i class="fas fa-times-circle"></i>
                            @else
                            <i class="fas fa-question-circle"></i>
                            @endif
                            {{ $status }}
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Database Status -->
                @if(!empty($currentStats['database']))
                <div class="border-t mt-4 pt-4">
                    <h3 class="font-semibold mb-2 text-gray-700">
                        <i class="fas fa-database mr-2"></i>Database
                    </h3>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="text-center p-2 bg-blue-50 rounded">
                            <div class="text-gray-500">Connections</div>
                            <div class="font-bold">{{ $currentStats['database']['connections']['current'] ?? 0 }}/{{ $currentStats['database']['connections']['max'] ?? 0 }}</div>
                        </div>
                        <div class="text-center p-2 bg-green-50 rounded">
                            <div class="text-gray-500">Processes</div>
                            <div class="font-bold">{{ $currentStats['database']['active_processes'] ?? 0 }}</div>
                        </div>
                        <div class="text-center p-2 bg-purple-50 rounded">
                            <div class="text-gray-500">Uptime</div>
                            <div class="font-bold">{{ $currentStats['database']['uptime'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Top Processes -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-list mr-2"></i>Top Processes
                </h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold mb-2 text-sm text-gray-600">By CPU</h3>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @forelse(array_slice($currentStats['processes']['by_cpu'] ?? [], 0, 5) as $process)
                            <div class="text-xs p-2 bg-gray-50 rounded truncate" title="{{ $process }}">
                                {{ Str::limit($process, 80) }}
                            </div>
                            @empty
                            <div class="text-sm text-gray-500 text-center p-2">No processes found</div>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-2 text-sm text-gray-600">By Memory</h3>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @forelse(array_slice($currentStats['processes']['by_memory'] ?? [], 0, 5) as $process)
                            <div class="text-xs p-2 bg-gray-50 rounded truncate" title="{{ $process }}">
                                {{ Str::limit($process, 80) }}
                            </div>
                            @empty
                            <div class="text-sm text-gray-500 text-center p-2">No processes found</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel & Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Control Panel -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-sliders-h mr-2"></i>Control Panel
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="refreshAllStats()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded flex items-center justify-center transition">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh All
                    </button>
                    <button onclick="collectMetrics()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded flex items-center justify-center transition">
                        <i class="fas fa-database mr-2"></i> Collect Metrics
                    </button>
                    <button onclick="runMonitor()" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-4 rounded flex items-center justify-center transition">
                        <i class="fas fa-bell mr-2"></i> Check Alerts
                    </button>
                    <button onclick="testEmail()" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded flex items-center justify-center transition">
                        <i class="fas fa-envelope mr-2"></i> Test Email
                    </button>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-6 border-t pt-4">
                    <h3 class="font-semibold mb-3 text-gray-700">Quick Actions</h3>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="checkServices()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm py-2 px-3 rounded flex items-center">
                            <i class="fas fa-cog mr-1"></i> Services
                        </button>
                        <button onclick="checkNetwork()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm py-2 px-3 rounded flex items-center">
                            <i class="fas fa-network-wired mr-1"></i> Network
                        </button>
                        <button onclick="checkLogs()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm py-2 px-3 rounded flex items-center">
                            <i class="fas fa-file-alt mr-1"></i> Logs
                        </button>
                        <button onclick="checkDatabase()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm py-2 px-3 rounded flex items-center">
                            <i class="fas fa-database mr-1"></i> Database
                        </button>
                    </div>
                </div>
            </div>

            <!-- Log Summary -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-file-alt mr-2"></i>Log Summary
                </h2>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @forelse($currentStats['logs'] as $logName => $logData)
                    <div class="border-b pb-3 last:border-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-medium text-gray-700">{{ $logName }}</span>
                            <span class="text-sm text-gray-500">{{ $logData['size'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Last modified: {{ $logData['last_modified'] ?? 'N/A' }}</span>
                            @if(($logData['recent_errors'] ?? 0) > 0)
                            <span class="text-red-600 font-bold">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $logData['recent_errors'] }} errors
                            </span>
                            @else
                            <span class="text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Clean
                            </span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-gray-500 py-4">
                        <i class="fas fa-file-alt text-3xl mb-2"></i>
                        <p>No log data available</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Historical Charts -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">
                    <i class="fas fa-chart-line mr-2"></i>Historical Usage (24 Hours)
                </h2>
                <div class="flex space-x-2">
                    <button onclick="updateChart('24h')" class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded">24H</button>
                    <button onclick="updateChart('7d')" class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded">7D</button>
                    <button onclick="updateChart('30d')" class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded">30D</button>
                </div>
            </div>
            <canvas id="historyChart" height="120"></canvas>
        </div>

        <!-- Recent Metrics & Server Info -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Metrics -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-history mr-2"></i>Recent Metrics
                </h2>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">CPU</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">RAM</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Disk</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($latestMetrics as $metric)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $metric->created_at->format('H:i:s') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="{{ $metric->cpu_usage > $currentStats['thresholds']['cpu_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                        {{ number_format($metric->cpu_usage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="{{ $metric->ram_usage > $currentStats['thresholds']['ram_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                        {{ number_format($metric->ram_usage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="{{ $metric->disk_usage > $currentStats['thresholds']['disk_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                        {{ number_format($metric->disk_usage, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Server Information -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">
                    <i class="fas fa-info-circle mr-2"></i>Server Information
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['icon' => 'code', 'label' => 'PHP Version', 'value' => $currentStats['server_info']['php_version'] ?? 'N/A'],
                        ['icon' => 'laravel', 'label' => 'Laravel Version', 'value' => $currentStats['server_info']['laravel_version'] ?? 'N/A'],
                        ['icon' => 'server', 'label' => 'Server OS', 'value' => $currentStats['server_info']['server_os'] ?? 'N/A'],
                        ['icon' => 'layer-group', 'label' => 'CPU Cores', 'value' => $currentStats['server_info']['cpu_count'] ?? 'N/A'],
                        ['icon' => 'clock', 'label' => 'Uptime', 'value' => $currentStats['server_info']['uptime'] ?? 'N/A'],
                        ['icon' => 'desktop', 'label' => 'Hostname', 'value' => $currentStats['server_info']['hostname'] ?? 'N/A'],
                        ['icon' => 'globe', 'label' => 'IP Address', 'value' => $currentStats['server_info']['ip_address'] ?? 'N/A'],
                        ['icon' => 'hdd', 'label' => 'Server Software', 'value' => $currentStats['server_info']['server_software'] ?? 'N/A'],
                    ] as $info)
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="flex items-center text-sm text-gray-500 mb-1">
                            <i class="fas fa-{{ $info['icon'] }} mr-2"></i>
                            {{ $info['label'] }}
                        </div>
                        <div class="font-semibold truncate" title="{{ $info['value'] }}">{{ $info['value'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8 pt-6 border-t">
            <p>
                <i class="fas fa-heart text-red-400 mr-1"></i>
                Server Monitor v1.0 • Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span>
                • Auto-refresh: <span id="refresh-countdown">30</span>s
            </p>
        </div>
    </div>

    <script>
        // Initialize historical chart
        const historyCtx = document.getElementById('historyChart').getContext('2d');
        let historyChart = new Chart(historyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($historicalData->pluck('created_at')->map(function($date) { return $date->format('H:i'); })->toArray()) !!},
                datasets: [
                    {
                        label: 'CPU %',
                        data: {!! json_encode($historicalData->pluck('cpu_usage')->toArray()) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'RAM %',
                        data: {!! json_encode($historicalData->pluck('ram_usage')->toArray()) !!},
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Disk %',
                        data: {!! json_encode($historicalData->pluck('disk_usage')->toArray()) !!},
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Update time every second
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toISOString().replace('T', ' ').substr(0, 19);
        }
        setInterval(updateCurrentTime, 1000);

        // Auto-refresh countdown
        let refreshCountdown = 30;
        function updateCountdown() {
            document.getElementById('refresh-countdown').textContent = refreshCountdown;
            refreshCountdown--;
            
            if (refreshCountdown < 0) {
                refreshCountdown = 30;
                refreshAllStats();
            }
        }
        setInterval(updateCountdown, 1000);

        // Refresh all stats function
        async function refreshAllStats() {
            try {
                document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                
                const response = await fetch('/refresh-stats');
                const data = await response.json();
                
                // Update core stats
                if (document.querySelector('[x-data*="cpu"]')) {
                    document.querySelector('[x-data*="cpu"]').__x.$data.cpu = data.cpu || 0;
                }
                if (document.querySelector('[x-data*="ram"]')) {
                    document.querySelector('[x-data*="ram"]').__x.$data.ram = data.ram?.usage_percent || 0;
                }
                if (document.querySelector('[x-data*="disk"]')) {
                    document.querySelector('[x-data*="disk"]').__x.$data.disk = data.disk?.usage_percent || 0;
                }
                if (document.querySelector('[x-data*="swap"]')) {
                    document.querySelector('[x-data*="swap"]').__x.$data.swap = data.swap?.usage_percent || 0;
                }
                
                // Check for alerts
                checkAlerts();
                
                // Reset countdown
                refreshCountdown = 30;
                
            } catch (error) {
                console.error('Refresh error:', error);
            }
        }

        // Check alerts function
        async function checkAlerts() {
            try {
                const response = await fetch('/trigger-alerts');
                const data = await response.json();
                
                if (data.has_alerts) {
                    // Show alert banner
                    const alertBanner = document.querySelector('.glow-alert');
                    if (!alertBanner) {
                        location.reload(); // Reload to show alert banner
                    }
                }
            } catch (error) {
                console.error('Alert check error:', error);
            }
        }

        // Other functions
        async function collectMetrics() {
            const response = await fetch('/collect-metrics');
            const result = await response.json();
            alert(result.message || 'Metrics collected!');
            location.reload();
        }

        async function runMonitor() {
            const response = await fetch('/api/monitor-run');
            const result = await response.json();
            alert(result.message || 'Monitor run completed!');
            if (result.alert_sent) {
                alert('Alert email was sent!');
            }
            refreshAllStats();
        }

        async function testEmail() {
            const email = prompt('Enter email address for test:', '{{ env("ALERT_EMAIL") }}');
            if (email) {
                const response = await fetch(`/email-test?email=${encodeURIComponent(email)}`);
                const result = await response.json();
                alert(result.message || 'Test email sent!');
            }
        }

        // Quick action functions
        async function checkServices() {
            const response = await fetch('/service-status');
            const data = await response.json();
            alert('Services checked: ' + Object.keys(data.services || {}).length + ' services found');
        }

        async function checkNetwork() {
            const response = await fetch('/network-info');
            const data = await response.json();
            alert('Network checked: ' + (data.network?.total_connections || 0) + ' connections');
        }

        async function checkLogs() {
            const response = await fetch('/log-summary');
            const data = await response.json();
            alert('Logs checked: ' + Object.keys(data.logs || {}).length + ' log files');
        }

        async function checkDatabase() {
            const response = await fetch('/database-info');
            const data = await response.json();
            alert('Database checked: ' + (data.database?.connections?.current || 0) + ' active connections');
        }

        // Chart period switching
        async function updateChart(period) {
            // Implement chart period switching
            alert('Chart period switching to ' + period + ' - implement API endpoint for this');
        }

        // Initialize
        updateCurrentTime();
        setInterval(refreshAllStats, 30000); // Auto-refresh every 30 seconds
    </script>
</body>
</html>