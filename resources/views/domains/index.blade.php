<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Manager - Server Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .domain-card {
            transition: all 0.3s ease;
        }
        .domain-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .ssl-valid { border-left-color: #10b981; }
        .ssl-warning { border-left-color: #f59e0b; }
        .ssl-expired { border-left-color: #ef4444; }
        .ssl-unknown { border-left-color: #6b7280; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="container mx-auto px-4 py-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-globe mr-2"></i>Domain Manager
                        </h1>
                        <p class="text-gray-600 text-sm mt-1">Monitor all domains and subdomains on your server</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="/dashboard" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold flex items-center">
                            <i class="fas fa-server mr-2"></i>Back to Dashboard
                        </a>
                        <div class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded">
                            <i class="fas fa-clock mr-1"></i>
                            <span id="current-time">{{ now()->format('H:i:s') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="container mx-auto px-4 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-globe text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Domains</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $totalDomains }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-sitemap text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Subdomains</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $totalSubdomains }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-lock text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">SSL Status</p>
                            <p class="text-2xl font-bold text-gray-800">
                                @php
                                    $validSSL = 0;
                                    foreach ($sslInfo as $info) {
                                        if ($info['status'] === 'valid') $validSSL++;
                                    }
                                @endphp
                                {{ $validSSL }}/{{ count($sslInfo) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            @if(isset($error))
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-red-400 mt-1 mr-3"></i>
                    <div>
                        <p class="text-red-700 font-semibold">Warning</p>
                        <p class="text-red-600 text-sm">{{ $error }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Domains Panel -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-globe mr-2"></i>Domains ({{ $totalDomains }})
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            @forelse($domains['domains'] as $domain)
                            <div class="domain-card border rounded-lg p-4 border-l-4 
                                @if(isset($sslInfo[$domain]['status'])) 
                                    ssl-{{ $sslInfo[$domain]['status'] }}
                                @else 
                                    ssl-unknown 
                                @endif">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 flex items-center">
                                            <i class="fas fa-globe mr-2 text-blue-500"></i>
                                            {{ $domain }}
                                        </h3>
                                        @if(isset($sslInfo[$domain]))
                                        <div class="mt-2 space-y-1">
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-600 w-24">SSL Status:</span>
                                                <span class="font-semibold 
                                                    @if($sslInfo[$domain]['status'] == 'valid') text-green-600
                                                    @elseif($sslInfo[$domain]['status'] == 'warning') text-yellow-600
                                                    @elseif($sslInfo[$domain]['status'] == 'expired') text-red-600
                                                    @else text-gray-600 @endif">
                                                    {{ ucfirst($sslInfo[$domain]['status']) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-600 w-24">Expires:</span>
                                                <span>{{ $sslInfo[$domain]['expiry_date'] }}</span>
                                            </div>
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-600 w-24">Days Left:</span>
                                                <span class="{{ $sslInfo[$domain]['days_remaining'] < 30 ? 'text-yellow-600 font-bold' : 'text-green-600' }}">
                                                    {{ $sslInfo[$domain]['days_remaining'] }} days
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="checkDNS('{{ $domain }}')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded text-sm" title="Check DNS">
                                            <i class="fas fa-network-wired"></i>
                                        </button>
                                        <button onclick="pingDomain('{{ $domain }}')" class="bg-green-100 hover:bg-green-200 text-green-700 p-2 rounded text-sm" title="Ping Domain">
                                            <i class="fas fa-satellite-dish"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-globe text-4xl mb-3"></i>
                                <p>No domains detected</p>
                                <p class="text-sm mt-1">Check your web server configuration</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Subdomains Panel -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-sitemap mr-2"></i>Subdomains ({{ $totalSubdomains }})
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            @forelse($domains['subdomains'] as $subdomain)
                            <div class="domain-card border rounded-lg p-4 border-l-4 
                                @if(isset($sslInfo[$subdomain]['status'])) 
                                    ssl-{{ $sslInfo[$subdomain]['status'] }}
                                @else 
                                    ssl-unknown 
                                @endif">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 flex items-center">
                                            <i class="fas fa-sitemap mr-2 text-green-500"></i>
                                            {{ $subdomain }}
                                        </h3>
                                        @if(isset($sslInfo[$subdomain]))
                                        <div class="mt-2 space-y-1">
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-600 w-24">SSL Status:</span>
                                                <span class="font-semibold 
                                                    @if($sslInfo[$subdomain]['status'] == 'valid') text-green-600
                                                    @elseif($sslInfo[$subdomain]['status'] == 'warning') text-yellow-600
                                                    @elseif($sslInfo[$subdomain]['status'] == 'expired') text-red-600
                                                    @else text-gray-600 @endif">
                                                    {{ ucfirst($sslInfo[$subdomain]['status']) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center text-sm">
                                                <span class="text-gray-600 w-24">Days Left:</span>
                                                <span class="{{ $sslInfo[$subdomain]['days_remaining'] < 30 ? 'text-yellow-600 font-bold' : 'text-green-600' }}">
                                                    {{ $sslInfo[$subdomain]['days_remaining'] }} days
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="checkDNS('{{ $subdomain }}')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded text-sm" title="Check DNS">
                                            <i class="fas fa-network-wired"></i>
                                        </button>
                                        <button onclick="pingDomain('{{ $subdomain }}')" class="bg-green-100 hover:bg-green-200 text-green-700 p-2 rounded text-sm" title="Ping Domain">
                                            <i class="fas fa-satellite-dish"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-sitemap text-4xl mb-3"></i>
                                <p>No subdomains detected</p>
                                <p class="text-sm mt-1">Subdomains will appear here automatically</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">
                    <i class="fas fa-cogs mr-2"></i>Domain Tools
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-search mr-2"></i>DNS Lookup
                        </h3>
                        <div class="flex mt-2">
                            <input type="text" id="dnsLookupInput" placeholder="Enter domain" class="flex-1 border rounded-l px-3 py-2 text-sm">
                            <button onclick="customDNSLookup()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 rounded-r text-sm font-semibold">
                                Lookup
                            </button>
                        </div>
                    </div>
                    
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-bolt mr-2"></i>Ping Test
                        </h3>
                        <div class="flex mt-2">
                            <input type="text" id="pingInput" placeholder="Enter domain/IP" class="flex-1 border rounded-l px-3 py-2 text-sm">
                            <button onclick="customPing()" class="bg-green-500 hover:bg-green-600 text-white px-4 rounded-r text-sm font-semibold">
                                Ping
                            </button>
                        </div>
                    </div>
                    
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh Data
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Manually refresh domain list</p>
                        <button onclick="refreshDomains()" class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 rounded text-sm font-semibold">
                            <i class="fas fa-redo mr-2"></i>Refresh All Domains
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-gray-500 text-sm">
                <p>
                    <i class="fas fa-info-circle mr-1"></i>
                    Domain detection works by scanning web server configurations. Not all domains may be detected.
                </p>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div id="resultsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="py-4">
                <div id="modalContent" class="text-gray-700"></div>
            </div>
            <div class="border-t pt-3 flex justify-end">
                <button onclick="closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Update time
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
        }
        setInterval(updateCurrentTime, 1000);

        // Modal functions
        function showModal(title, content) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('resultsModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('resultsModal').classList.add('hidden');
        }

        // Domain functions
        async function checkDNS(domain) {
            showModal(`DNS Lookup: ${domain}`, '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i><p class="mt-2">Checking DNS records...</p></div>');
            
            try {
                const response = await fetch(`/domains/dns/${encodeURIComponent(domain)}`);
                const data = await response.json();
                
                let content = `<div class="space-y-3">`;
                content += `<div class="bg-gray-50 p-3 rounded"><strong>Domain:</strong> ${data.domain}</div>`;
                
                if (data.dns_records && Object.keys(data.dns_records).length > 0) {
                    for (const [type, records] of Object.entries(data.dns_records)) {
                        content += `<div class="border rounded p-3">`;
                        content += `<div class="font-semibold text-blue-600 mb-1">${type} Records:</div>`;
                        content += `<div class="pl-4">`;
                        records.forEach(record => {
                            content += `<div class="text-sm py-1 border-b last:border-0">${record}</div>`;
                        });
                        content += `</div></div>`;
                    }
                } else {
                    content += `<div class="text-yellow-600 p-3 bg-yellow-50 rounded">No DNS records found</div>`;
                }
                
                content += `<div class="text-xs text-gray-500 mt-3">Checked: ${data.timestamp}</div>`;
                content += `</div>`;
                
                showModal(`DNS Results: ${domain}`, content);
            } catch (error) {
                showModal('Error', `<div class="text-red-600 p-4 bg-red-50 rounded">Failed to check DNS: ${error.message}</div>`);
            }
        }

        async function pingDomain(domain) {
            showModal(`Ping Test: ${domain}`, '<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-2xl text-green-500"></i><p class="mt-2">Pinging domain...</p></div>');
            
            try {
                const response = await fetch(`/domains/ping/${encodeURIComponent(domain)}`);
                const data = await response.json();
                
                let content = `<div class="space-y-3">`;
                content += `<div class="grid grid-cols-2 gap-3">`;
                content += `<div class="bg-blue-50 p-3 rounded text-center">`;
                content += `<div class="text-sm text-gray-600">Packet Loss</div>`;
                content += `<div class="text-2xl font-bold ${data.packet_loss < 10 ? 'text-green-600' : data.packet_loss < 50 ? 'text-yellow-600' : 'text-red-600'}">`;
                content += `${data.packet_loss}%</div></div>`;
                
                content += `<div class="bg-green-50 p-3 rounded text-center">`;
                content += `<div class="text-sm text-gray-600">Avg Response</div>`;
                content += `<div class="text-2xl font-bold ${data.avg_time_ms < 50 ? 'text-green-600' : data.avg_time_ms < 200 ? 'text-yellow-600' : 'text-red-600'}">`;
                content += `${data.avg_time_ms}ms</div></div></div>`;
                
                content += `<div class="bg-gray-50 p-3 rounded">`;
                content += `<div class="font-semibold mb-1">Status:</div>`;
                content += `<span class="${data.reachable ? 'text-green-600' : 'text-red-600'} font-bold">`;
                content += `${data.reachable ? '✓ Reachable' : '✗ Unreachable'}</span></div>`;
                
                if (data.output) {
                    content += `<div class="border rounded p-3">`;
                    content += `<div class="font-semibold mb-1">Raw Output:</div>`;
                    content += `<pre class="text-xs bg-gray-900 text-gray-100 p-2 rounded overflow-x-auto">${data.output}</pre>`;
                    content += `</div>`;
                }
                
                content += `<div class="text-xs text-gray-500">Checked: ${data.timestamp}</div>`;
                content += `</div>`;
                
                showModal(`Ping Results: ${domain}`, content);
            } catch (error) {
                showModal('Error', `<div class="text-red-600 p-4 bg-red-50 rounded">Failed to ping domain: ${error.message}</div>`);
            }
        }

        // Custom lookup functions
        function customDNSLookup() {
            const domain = document.getElementById('dnsLookupInput').value.trim();
            if (domain) {
                checkDNS(domain);
                document.getElementById('dnsLookupInput').value = '';
            }
        }

        function customPing() {
            const target = document.getElementById('pingInput').value.trim();
            if (target) {
                pingDomain(target);
                document.getElementById('pingInput').value = '';
            }
        }

        function refreshDomains() {
            window.location.reload();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Initialize
        updateCurrentTime();
    </script>
</body>
</html>