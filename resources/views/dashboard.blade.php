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
        
        /* Fixed height for better scrolling */
        .dashboard-container {
            min-height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
        }
        
        /* Compact chart container */
        .chart-container {
            height: 200px !important;
            max-height: 200px;
            position: relative;
        }
        
        /* Better table scrolling */
        .metrics-table {
            max-height: 250px;
            overflow-y: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-container {
                height: 150px !important;
            }
            .metrics-table {
                max-height: 200px;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="dashboard-container container mx-auto px-4 py-4">
        <!-- Header - More Compact -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-server mr-2"></i>Server Monitor
            </h1>
            <div class="text-xs sm:text-sm text-gray-600 bg-white px-3 py-1 rounded shadow">
                <i class="fas fa-clock mr-1"></i>
                <span id="current-time">{{ now()->format('H:i:s') }}</span>
                <span class="mx-1">•</span>
                <span id="uptime">{{ $currentStats['server_info']['uptime'] ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Alert Banner - More Compact -->
        @php
            $hasAlerts = $currentStats['thresholds']['cpu_exceeded'] || 
                         $currentStats['thresholds']['ram_exceeded'] || 
                         $currentStats['thresholds']['disk_exceeded'] ||
                         ($currentStats['swap']['usage_percent'] ?? 0) > ($additionalThresholds['swap_threshold'] ?? 50) ||
                         ($currentStats['ssl']['days_remaining'] ?? 0) < ($additionalThresholds['ssl_warning_days'] ?? 30);
        @endphp
        
        @if($hasAlerts)
        <div class="bg-red-50 border-l-4 border-red-500 p-3 mb-4 rounded text-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
                <span class="font-bold text-red-700">ALERT:</span>
                <div class="ml-2 flex flex-wrap gap-2">
                    @if($currentStats['thresholds']['cpu_exceeded'])
                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                        <i class="fas fa-microchip mr-1"></i>CPU: {{ $currentStats['cpu'] }}%
                    </span>
                    @endif
                    @if($currentStats['thresholds']['ram_exceeded'])
                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                        <i class="fas fa-memory mr-1"></i>RAM: {{ $currentStats['ram']['usage_percent'] }}%
                    </span>
                    @endif
                    @if($currentStats['thresholds']['disk_exceeded'])
                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                        <i class="fas fa-hard-drive mr-1"></i>Disk: {{ $currentStats['disk']['usage_percent'] }}%
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Core Resources Row - More Compact -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <!-- CPU Card -->
            <div class="bg-white rounded-lg shadow p-4" x-data="{cpu: {{ $currentStats['cpu'] }}, threshold: {{ $currentStats['thresholds']['cpu_threshold'] }}}">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-semibold text-gray-700 text-sm">
                        <i class="fas fa-microchip mr-1"></i>CPU
                    </h2>
                    <span class="text-xl font-bold" :class="cpu > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="cpu.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(cpu, 100) + '%'" 
                             :class="cpu > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span>Thresh: {{ $currentStats['thresholds']['cpu_threshold'] }}%</span>
                        <span :class="cpu > threshold ? 'text-red-600 font-bold' : 'text-green-600'">
                            <i class="fas" :class="cpu > threshold ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>
                            <span x-text="cpu > threshold ? ' Alert' : ' OK'"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- RAM Card -->
            <div class="bg-white rounded-lg shadow p-4" x-data="{ram: {{ $currentStats['ram']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['ram_threshold'] }}}">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-semibold text-gray-700 text-sm">
                        <i class="fas fa-memory mr-1"></i>RAM
                    </h2>
                    <span class="text-xl font-bold" :class="ram > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="ram.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(ram, 100) + '%'" 
                             :class="ram > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-xs">
                        <div class="text-gray-600 truncate">Used:</div><div class="truncate">{{ $currentStats['ram']['used'] }}</div>
                        <div class="text-gray-600 truncate">Free:</div><div class="truncate">{{ $currentStats['ram']['free'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Disk Card -->
            <div class="bg-white rounded-lg shadow p-4" x-data="{disk: {{ $currentStats['disk']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['disk_threshold'] }}}">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-semibold text-gray-700 text-sm">
                        <i class="fas fa-hard-drive mr-1"></i>Disk
                    </h2>
                    <span class="text-xl font-bold" :class="disk > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="disk.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(disk, 100) + '%'" 
                             :class="disk > threshold ? 'bg-red-500 progress-bar' : 'bg-blue-500 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-xs">
                        <div class="text-gray-600 truncate">Used:</div><div class="truncate">{{ $currentStats['disk']['used'] }}</div>
                        <div class="text-gray-600 truncate">Free:</div><div class="truncate">{{ $currentStats['disk']['free'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Swap Card -->
            <div class="bg-white rounded-lg shadow p-4" x-data="{swap: {{ $currentStats['swap']['usage_percent'] ?? 0 }}, threshold: {{ $additionalThresholds['swap_threshold'] ?? 50 }}}">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="font-semibold text-gray-700 text-sm">
                        <i class="fas fa-exchange-alt mr-1"></i>Swap
                    </h2>
                    <span class="text-xl font-bold" :class="swap > threshold ? 'text-yellow-600' : 'text-green-600'">
                        <span x-text="swap.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(swap, 100) + '%'" 
                             :class="swap > threshold ? 'bg-yellow-500 progress-bar' : 'bg-blue-400 progress-bar'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 text-xs">
                        <div class="text-gray-600 truncate">Used:</div><div class="truncate">{{ $currentStats['swap']['used'] ?? '0 B' }}</div>
                        <div class="text-gray-600 truncate">Free:</div><div class="truncate">{{ $currentStats['swap']['free'] ?? '0 B' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Disk Space Section -->
        @if(!empty($currentStats['disk_space']))
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                <i class="fas fa-hdd mr-1"></i>Disk Space
            </h2>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach($currentStats['disk_space'] as $disk)
                <div class="border rounded p-3 {{ $disk['alert'] ? 'border-red-300 bg-red-50' : '' }}">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-medium text-sm truncate" title="{{ $disk['filesystem'] }} on {{ $disk['mounted_on'] }}">
                            {{ $disk['filesystem'] }}
                        </span>
                        <span class="text-sm font-bold {{ $disk['alert'] ? 'text-red-600' : 'text-blue-600' }}">
                            {{ $disk['use_percent'] }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                        <div class="bg-blue-500 h-1.5 rounded-full" 
                            style="width: {{ $disk['use_percent'] }}"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-1 text-xs text-gray-600">
                        <div>Used: {{ $disk['used'] }}</div>
                        <div>Free: {{ $disk['available'] }}</div>
                        <div>Total: {{ $disk['size'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        <!-- Second Row: Network & Services - More Compact -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">
            <!-- Network Stats -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                    <i class="fas fa-network-wired mr-1"></i>Network
                </h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-blue-50 p-3 rounded">
                            <div class="text-xs text-gray-500">Connections</div>
                            <div class="text-lg font-bold">{{ $currentStats['network']['total_connections'] ?? 0 }}</div>
                        </div>
                        <div class="bg-green-50 p-3 rounded">
                            <div class="text-xs text-gray-500">Established</div>
                            <div class="text-lg font-bold">{{ $currentStats['network']['established_connections'] ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <!-- SSL Certificate -->
                    @if(isset($currentStats['ssl']['valid']))
                    <div class="border-t pt-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">
                                <i class="fas fa-lock mr-1"></i>SSL
                            </span>
                            <span class="{{ $currentStats['ssl']['status'] == 'valid' ? 'text-green-600' : ($currentStats['ssl']['status'] == 'warning' ? 'text-yellow-600' : 'text-red-600') }} font-bold">
                                {{ $currentStats['ssl']['days_remaining'] ?? 0 }}d
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Services Status -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                    <i class="fas fa-cogs mr-1"></i>Services
                </h2>
                <div class="grid grid-cols-3 gap-1">
                    @foreach($currentStats['services'] as $service => $status)
                    <div class="p-2 rounded text-center text-xs status-badge {{ $status == 'running' ? 'bg-green-100 text-green-800' : ($status == 'stopped' ? 'bg-red-100 text-red-800' : 'bg-gray-100') }}">
                        <div class="font-semibold truncate" title="{{ $service }}">{{ substr($service, 0, 8) }}</div>
                        <div class="{{ $status == 'running' ? 'text-green-600' : 'text-red-600' }}">
                            @if($status == 'running')
                            <i class="fas fa-check"></i>
                            @else
                            <i class="fas fa-times"></i>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Processes -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                    <i class="fas fa-list mr-1"></i>Top Processes
                </h2>
                <div class="space-y-2">
                    <div>
                        <h3 class="font-semibold mb-1 text-xs text-gray-600">CPU</h3>
                        <div class="space-y-1 max-h-20 overflow-y-auto text-xs">
                            @forelse(array_slice($currentStats['processes']['by_cpu'] ?? [], 0, 3) as $process)
                            <div class="p-1 bg-gray-50 rounded truncate" title="{{ $process }}">
                                {{ Str::limit($process, 60) }}
                            </div>
                            @empty
                            <div class="text-xs text-gray-500 text-center p-1">No processes</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel - More Compact -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                <i class="fas fa-sliders-h mr-1"></i>Control Panel
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button onclick="refreshAllStats()" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-2 px-3 rounded flex items-center justify-center transition">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
                <button onclick="collectMetrics()" class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-2 px-3 rounded flex items-center justify-center transition">
                    <i class="fas fa-database mr-1"></i> Collect
                </button>
                <button onclick="runMonitor()" class="bg-purple-500 hover:bg-purple-600 text-white text-sm font-bold py-2 px-3 rounded flex items-center justify-center transition">
                    <i class="fas fa-bell mr-1"></i> Alerts
                </button>
                <button onclick="testEmail()" class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-bold py-2 px-3 rounded flex items-center justify-center transition">
                    <i class="fas fa-envelope mr-1"></i> Test Email
                </button>
            </div>
        </div>

        <!-- Historical Charts - Fixed Height -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-gray-700 text-sm">
                    <i class="fas fa-chart-line mr-1"></i>History (24h)
                </h2>
                <div class="flex space-x-1">
                    <button onclick="updateChart('24h')" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">24H</button>
                    <button onclick="updateChart('7d')" class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">7D</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <!-- Recent Metrics & Server Info - Side by Side -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
            <!-- Recent Metrics - Compact -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                    <i class="fas fa-history mr-1"></i>Recent Metrics
                </h2>
                <div class="metrics-table">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase">CPU</th>
                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase">RAM</th>
                                <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase">Disk</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($latestMetrics->take(10) as $metric)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 whitespace-nowrap text-xs">{{ $metric->created_at->format('H:i') }}</td>
                                <td class="px-2 py-1 whitespace-nowrap">
                                    <span class="{{ $metric->cpu_usage > $currentStats['thresholds']['cpu_threshold'] ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format($metric->cpu_usage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap">
                                    <span class="{{ $metric->ram_usage > $currentStats['thresholds']['ram_threshold'] ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format($metric->ram_usage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap">
                                    <span class="{{ $metric->disk_usage > $currentStats['thresholds']['disk_threshold'] ? 'text-red-600' : 'text-green-600' }}">
                                        {{ number_format($metric->disk_usage, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Server Information - Compact -->
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold mb-3 text-gray-700 text-sm">
                    <i class="fas fa-info-circle mr-1"></i>Server Info
                </h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach([
                        ['icon' => 'code', 'label' => 'PHP', 'value' => $currentStats['server_info']['php_version'] ?? 'N/A'],
                        ['icon' => 'laravel', 'label' => 'Laravel', 'value' => $currentStats['server_info']['laravel_version'] ?? 'N/A'],
                        ['icon' => 'server', 'label' => 'OS', 'value' => Str::limit($currentStats['server_info']['server_os'] ?? 'N/A', 15)],
                        ['icon' => 'layer-group', 'label' => 'Cores', 'value' => $currentStats['server_info']['cpu_count'] ?? 1],
                        ['icon' => 'clock', 'label' => 'Uptime', 'value' => Str::limit($currentStats['server_info']['uptime'] ?? 'N/A', 12)],
                        ['icon' => 'desktop', 'label' => 'Host', 'value' => Str::limit($currentStats['server_info']['hostname'] ?? 'N/A', 12)],
                    ] as $info)
                    <div class="bg-gray-50 p-2 rounded">
                        <div class="flex items-center text-xs text-gray-500 mb-1">
                            <i class="fas fa-{{ $info['icon'] }} mr-1"></i>
                            {{ $info['label'] }}
                        </div>
                        <div class="font-semibold text-sm truncate" title="{{ $info['value'] }}">{{ $info['value'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Footer - Compact -->
        <div class="text-center text-gray-500 text-xs mt-4 pt-4 border-t">
            <p>
                <i class="fas fa-heart text-red-400 mr-1"></i>
                Server Monitor • Updated: <span id="last-updated">{{ now()->format('H:i') }}</span>
                • Auto-refresh: <span id="refresh-countdown">30</span>s
            </p>
        </div>
    </div>

    <script>
        // Initialize historical chart with fixed height
        const historyCtx = document.getElementById('historyChart').getContext('2d');
        let historyChart = new Chart(historyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($historicalData->pluck('created_at')->map(function($date) { return $date->format('H:i'); })->toArray()) !!},
                datasets: [
                    {
                        label: 'CPU',
                        data: {!! json_encode($historicalData->pluck('cpu_usage')->toArray()) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 1.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0
                    },
                    {
                        label: 'RAM',
                        data: {!! json_encode($historicalData->pluck('ram_usage')->toArray()) !!},
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 1.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0
                    },
                    {
                        label: 'Disk',
                        data: {!! json_encode($historicalData->pluck('disk_usage')->toArray()) !!},
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 1.5,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            padding: 5,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: { size: 11 },
                        bodyFont: { size: 11 },
                        padding: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 9
                            },
                            padding: 3
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            maxRotation: 0,
                            padding: 3
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    line: {
                        tension: 0.4
                    }
                }
            }
        });

        // Update time every second
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
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
                document.getElementById('last-updated').textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
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
                
                // Reset countdown
                refreshCountdown = 30;
                
            } catch (error) {
                console.error('Refresh error:', error);
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

        // Chart period switching
        async function updateChart(period) {
            alert('Chart period: ' + period);
            // Implement API call here for different time ranges
        }

        // Initialize
        updateCurrentTime();
        setInterval(refreshAllStats, 30000); // Auto-refresh every 30 seconds
    </script>
</body>
</html>