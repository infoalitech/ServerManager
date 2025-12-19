<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    public function index()
    {
        try {
            $domains = $this->getDomainsAndSubdomains();
            $sslInfo = $this->getAllSSLCertificates($domains);
            
            return view('domains.index', [
                'domains' => $domains,
                'sslInfo' => $sslInfo,
                'totalDomains' => count($domains['domains'] ?? []),
                'totalSubdomains' => count($domains['subdomains'] ?? [])
            ]);
        } catch (\Exception $e) {
            return view('domains.index', [
                'error' => 'Unable to fetch domain information',
                'domains' => ['domains' => [], 'subdomains' => []],
                'sslInfo' => [],
                'totalDomains' => 0,
                'totalSubdomains' => 0
            ]);
        }
    }
    
    private function getDomainsAndSubdomains(): array
    {
        $domains = [];
        $subdomains = [];
        
        try {
            // Method 1: Check Apache/nginx configs
            if (file_exists('/etc/apache2/sites-available/')) {
                $apacheConfigs = glob('/etc/apache2/sites-available/*.conf');
                foreach ($apacheConfigs as $config) {
                    $content = file_get_contents($config);
                    preg_match_all('/ServerName\s+([^\s]+)/', $content, $matches);
                    foreach ($matches[1] ?? [] as $domain) {
                        if (strpos($domain, '.') !== false) {
                            $parts = explode('.', $domain);
                            if (count($parts) > 2) {
                                $subdomains[] = $domain;
                            } else {
                                $domains[] = $domain;
                            }
                        }
                    }
                }
            }
            
            // Method 2: Check nginx configs
            if (file_exists('/etc/nginx/sites-available/')) {
                $nginxConfigs = glob('/etc/nginx/sites-available/*');
                foreach ($nginxConfigs as $config) {
                    $content = file_get_contents($config);
                    preg_match_all('/server_name\s+([^;]+)/', $content, $matches);
                    foreach ($matches[1] ?? [] as $serverNames) {
                        $names = explode(' ', $serverNames);
                        foreach ($names as $name) {
                            $name = trim($name, ';');
                            if (strpos($name, '.') !== false && $name !== '_') {
                                $parts = explode('.', $name);
                                if (count($parts) > 2) {
                                    $subdomains[] = $name;
                                } else {
                                    $domains[] = $name;
                                }
                            }
                        }
                    }
                }
            }
            
            // Method 3: Check web root directories
            $webRoots = ['/var/www/', '/home/*/public_html/', '/home/*/www/'];
            foreach ($webRoots as $pattern) {
                $dirs = glob($pattern);
                foreach ($dirs as $dir) {
                    if (is_dir($dir)) {
                        $domain = basename($dir);
                        if (strpos($domain, '.') !== false) {
                            $domains[] = $domain;
                        }
                    }
                }
            }
            
            // Remove duplicates and sort
            $domains = array_unique($domains);
            $subdomains = array_unique($subdomains);
            sort($domains);
            sort($subdomains);
            
            return [
                'domains' => $domains,
                'subdomains' => $subdomains
            ];
            
        } catch (\Exception $e) {
            Log::error('Domain detection error: ' . $e->getMessage());
            return ['domains' => [], 'subdomains' => []];
        }
    }
    
    private function getAllSSLCertificates(array $domains): array
    {
        $sslInfo = [];
        
        // Check main domains
        foreach ($domains['domains'] as $domain) {
            $sslInfo[$domain] = $this->getSSLCertInfo($domain);
        }
        
        // Check subdomains
        foreach ($domains['subdomains'] as $subdomain) {
            $sslInfo[$subdomain] = $this->getSSLCertInfo($subdomain);
        }
        
        return $sslInfo;
    }
    
    private function getSSLCertInfo(string $domain): array
    {
        try {
            $command = "echo | timeout 2 openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -dates -subject 2>/dev/null";
            exec($command, $output);
            
            $certInfo = [
                'valid' => false,
                'days_remaining' => 0,
                'status' => 'unknown',
                'expiry_date' => 'N/A',
                'subject' => 'N/A'
            ];
            
            $expiryDate = null;
            $subject = null;
            
            foreach ($output as $line) {
                if (strpos($line, 'notAfter=') !== false) {
                    $expiryDate = str_replace('notAfter=', '', $line);
                }
                if (strpos($line, 'subject=') !== false) {
                    $subject = str_replace('subject=', '', $line);
                }
            }
            
            if ($expiryDate) {
                $expiryTimestamp = strtotime($expiryDate);
                $daysRemaining = floor(($expiryTimestamp - time()) / (60 * 60 * 24));
                
                $certInfo = [
                    'valid' => $daysRemaining > 0,
                    'days_remaining' => $daysRemaining,
                    'status' => $daysRemaining > 30 ? 'valid' : ($daysRemaining > 0 ? 'warning' : 'expired'),
                    'expiry_date' => date('Y-m-d', $expiryTimestamp),
                    'subject' => $subject ?: 'N/A'
                ];
            }
            
            return $certInfo;
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'days_remaining' => 0,
                'status' => 'unknown',
                'expiry_date' => 'N/A',
                'subject' => 'N/A'
            ];
        }
    }
    
    public function checkDNS(string $domain)
    {
        try {
            $dnsRecords = [];
            
            // Check A records
            exec("dig +short A {$domain} 2>/dev/null", $aRecords);
            if (!empty($aRecords)) {
                $dnsRecords['A'] = $aRecords;
            }
            
            // Check CNAME records
            exec("dig +short CNAME {$domain} 2>/dev/null", $cnameRecords);
            if (!empty($cnameRecords)) {
                $dnsRecords['CNAME'] = $cnameRecords;
            }
            
            // Check MX records
            exec("dig +short MX {$domain} 2>/dev/null", $mxRecords);
            if (!empty($mxRecords)) {
                $dnsRecords['MX'] = $mxRecords;
            }
            
            // Check TXT records
            exec("dig +short TXT {$domain} 2>/dev/null", $txtRecords);
            if (!empty($txtRecords)) {
                $dnsRecords['TXT'] = $txtRecords;
            }
            
            return response()->json([
                'success' => true,
                'domain' => $domain,
                'dns_records' => $dnsRecords,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unable to check DNS records'
            ], 500);
        }
    }
    
    public function pingDomain(string $domain)
    {
        try {
            exec("ping -c 4 -W 2 {$domain} 2>/dev/null", $output);
            $response = implode("\n", $output);
            
            // Extract packet loss and average time
            $packetLoss = 100;
            $avgTime = 0;
            
            foreach ($output as $line) {
                if (strpos($line, 'packet loss') !== false) {
                    preg_match('/(\d+)% packet loss/', $line, $matches);
                    $packetLoss = $matches[1] ?? 100;
                }
                if (strpos($line, 'rtt min/avg/max/mdev') !== false) {
                    preg_match('/= ([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+) ms/', $line, $matches);
                    $avgTime = $matches[2] ?? 0;
                }
            }
            
            return response()->json([
                'success' => true,
                'domain' => $domain,
                'packet_loss' => (float) $packetLoss,
                'avg_time_ms' => (float) $avgTime,
                'reachable' => $packetLoss < 100,
                'output' => $response,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unable to ping domain'
            ], 500);
        }
    }
}