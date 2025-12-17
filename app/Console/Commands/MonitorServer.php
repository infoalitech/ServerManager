<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SystemMonitorService;
use App\Notifications\ServerAlertNotification;
use Illuminate\Support\Facades\Notification;

class MonitorServer extends Command
{
    protected $signature = 'monitor:server';
    protected $description = 'Monitor server resources and send alerts';

    public function handle(): void
    {
        $monitor = new SystemMonitorService();
        
        // Collect metrics
        $metrics = $monitor->collectMetrics();
        $this->info('Metrics collected: CPU=' . $metrics->cpu_usage . '%, RAM=' . $metrics->ram_usage . '%, Disk=' . $metrics->disk_usage . '%');
        
        // Check thresholds
        $alerts = $monitor->checkThresholds();
        
        // Send alert if any threshold exceeded
        if ($alerts['cpu_exceeded'] || $alerts['ram_exceeded'] || $alerts['disk_exceeded']) {
            $alertEmail = env('ALERT_EMAIL');
            
            if ($alertEmail) {
                Notification::route('mail', $alertEmail)
                    ->notify(new ServerAlertNotification($alerts));
                $this->info('Alert email sent to ' . $alertEmail);
            }
        } else {
            $this->info('All systems normal.');
        }
    }
}