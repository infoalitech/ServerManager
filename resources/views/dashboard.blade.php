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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-800">
            <i class="fas fa-server mr-2"></i>Server Monitor Dashboard
        </h1>

        <!-- Current Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- CPU Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{cpu: {{ $currentStats['cpu'] }}, threshold: {{ $currentStats['thresholds']['cpu_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-microchip mr-2"></i>CPU Usage
                    </h2>
                    <span class="text-2xl font-bold" :class="cpu > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="cpu.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-4 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(cpu, 100) + '%'" 
                             :class="cpu > threshold ? 'bg-red-500' : 'bg-blue-500'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"></div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Threshold: {{ $currentStats['thresholds']['cpu_threshold'] }}%</span>
                        <span :class="cpu > threshold ? 'text-red-600 font-bold' : 'text-green-600'">
                            <i class="fas" :class="cpu > threshold ? 'fa-exclamation-triangle' : 'fa-check-circle'"></i>
                            <span x-text="cpu > threshold ? ' Alert!' : ' Normal'"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- RAM Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{ram: {{ $currentStats['ram']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['ram_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-memory mr-2"></i>RAM Usage
                    </h2>
                    <span class="text-2xl font-bold" :class="ram > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="ram.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-4 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(ram, 100) + '%'" 
                             :class="ram > threshold ? 'bg-red-500' : 'bg-blue-500'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <div>Total: {{ $currentStats['ram']['total'] }}</div>
                        <div>Used: {{ $currentStats['ram']['used'] }}</div>
                        <div>Free: {{ $currentStats['ram']['free'] }}</div>
                        <div>Threshold: {{ $currentStats['thresholds']['ram_threshold'] }}%</div>
                    </div>
                </div>
            </div>

            <!-- Disk Card -->
            <div class="bg-white rounded-lg shadow-lg p-6" x-data="{disk: {{ $currentStats['disk']['usage_percent'] }}, threshold: {{ $currentStats['thresholds']['disk_threshold'] }}}">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-700">
                        <i class="fas fa-hard-drive mr-2"></i>Disk Usage
                    </h2>
                    <span class="text-2xl font-bold" :class="disk > threshold ? 'text-red-600' : 'text-green-600'">
                        <span x-text="disk.toFixed(1)"></span>%
                    </span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-4 mb-4 text-xs flex rounded bg-gray-200">
                        <div :style="'width: ' + Math.min(disk, 100) + '%'" 
                             :class="disk > threshold ? 'bg-red-500' : 'bg-blue-500'"
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <div>Total: {{ $currentStats['disk']['total'] }}</div>
                        <div>Used: {{ $currentStats['disk']['used'] }}</div>
                        <div>Free: {{ $currentStats['disk']['free'] }}</div>
                        <div>Threshold: {{ $currentStats['thresholds']['disk_threshold'] }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">
                <i class="fas fa-cogs mr-2"></i>Control Panel
            </h2>
            <div class="flex space-x-4">
                <button onclick="refreshStats()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh Stats
                </button>
                <button onclick="collectMetrics()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded flex items-center">
                    <i class="fas fa-database mr-2"></i> Collect Metrics
                </button>
                <button onclick="runMonitor()" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded flex items-center">
                    <i class="fas fa-bell mr-2"></i> Run Monitor & Check Alerts
                </button>
            </div>
        </div>

        <!-- Historical Charts -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">
                <i class="fas fa-chart-line mr-2"></i>Historical Usage (Last 24 Hours)
            </h2>
            <canvas id="historyChart" height="100"></canvas>
        </div>

        <!-- Recent Metrics Table -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">
                <i class="fas fa-history mr-2"></i>Recent Metrics
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPU %</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RAM %</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disk %</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($latestMetrics as $metric)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $metric->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="{{ $metric->cpu_usage > $currentStats['thresholds']['cpu_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                    {{ number_format($metric->cpu_usage, 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="{{ $metric->ram_usage > $currentStats['thresholds']['ram_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                    {{ number_format($metric->ram_usage, 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="{{ $metric->disk_usage > $currentStats['thresholds']['disk_threshold'] ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                    {{ number_format($metric->disk_usage, 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($metric->cpu_usage > $currentStats['thresholds']['cpu_threshold'] || 
                                    $metric->ram_usage > $currentStats['thresholds']['ram_threshold'] || 
                                    $metric->disk_usage > $currentStats['thresholds']['disk_threshold'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Alert
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Normal
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize historical chart
        const historyCtx = document.getElementById('historyChart').getContext('2d');
        const historyChart = new Chart(historyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($historicalData->pluck('created_at')->map(function($date) { return $date->format('H:i'); })->toArray()) !!},
                datasets: [
                    {
                        label: 'CPU Usage %',
                        data: {!! json_encode($historicalData->pluck('cpu_usage')->toArray()) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'RAM Usage %',
                        data: {!! json_encode($historicalData->pluck('ram_usage')->toArray()) !!},
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Disk Usage %',
                        data: {!! json_encode($historicalData->pluck('disk_usage')->toArray()) !!},
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Refresh stats function
        async function refreshStats() {
            const response = await fetch('/refresh-stats');
            const data = await response.json();
            
            // Update CPU
            document.querySelector('[x-data*="cpu"]').__x.$data.cpu = data.cpu;
            document.querySelector('[x-data*="cpu"]').__x.$data.threshold = data.thresholds.cpu_threshold;
            
            // Update RAM
            document.querySelector('[x-data*="ram"]').__x.$data.ram = data.ram.usage_percent;
            document.querySelector('[x-data*="ram"]').__x.$data.threshold = data.thresholds.ram_threshold;
            
            // Update Disk
            document.querySelector('[x-data*="disk"]').__x.$data.disk = data.disk.usage_percent;
            document.querySelector('[x-data*="disk"]').__x.$data.threshold = data.thresholds.disk_threshold;
            
            // Update text values
            document.querySelectorAll('.ram-total').forEach(el => el.textContent = data.ram.total);
            document.querySelectorAll('.ram-used').forEach(el => el.textContent = data.ram.used);
            document.querySelectorAll('.ram-free').forEach(el => el.textContent = data.ram.free);
            document.querySelectorAll('.disk-total').forEach(el => el.textContent = data.disk.total);
            document.querySelectorAll('.disk-used').forEach(el => el.textContent = data.disk.used);
            document.querySelectorAll('.disk-free').forEach(el => el.textContent = data.disk.free);
        }

        // Collect metrics function
        async function collectMetrics() {
            const response = await fetch('/collect-metrics');
            const data = await response.json();
            alert('Metrics collected successfully! ID: ' + data.id);
            location.reload();
        }

        // Run monitor function
        async function runMonitor() {
            const response = await fetch('/api/monitor-run');
            const result = await response.json();
            alert(result.message);
            if (result.alert_sent) {
                alert('Alert email was sent!');
            }
            refreshStats();
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshStats, 30000);
    </script>
</body>
</html>