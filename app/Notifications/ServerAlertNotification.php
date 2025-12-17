<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ServerAlertNotification extends Notification
{
    use Queueable;

    protected $alerts;

    public function __construct(array $alerts)
    {
        $this->alerts = $alerts;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('⚠️ Server Resource Alert')
            ->greeting('Server Alert Notification');

        if ($this->alerts['cpu_exceeded']) {
            $message->line("CPU usage exceeded: {$this->alerts['cpu_value']}% (Threshold: {$this->alerts['cpu_threshold']}%)");
        }
        
        if ($this->alerts['ram_exceeded']) {
            $message->line("RAM usage exceeded: {$this->alerts['ram_value']}% (Threshold: {$this->alerts['ram_threshold']}%)");
        }
        
        if ($this->alerts['disk_exceeded']) {
            $message->line("Disk usage exceeded: {$this->alerts['disk_value']}% (Threshold: {$this->alerts['disk_threshold']}%)");
        }

        $message->action('View Dashboard', url('/dashboard'))
                ->line('Please check your server immediately!');

        return $message;
    }
}