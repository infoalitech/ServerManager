<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $toEmail = $this->argument('email') ?: env('ALERT_EMAIL', env('MAIL_FROM_ADDRESS'));
            
            if (!$toEmail) {
                $this->error('No email address specified. Please provide an email or set ALERT_EMAIL in .env');
                return Command::FAILURE;
            }
            
            $this->info("📧 Testing email configuration...");
            $this->line("From: " . env('MAIL_FROM_ADDRESS'));
            $this->line("To: " . $toEmail);
            $this->line("SMTP: " . env('MAIL_HOST') . ":" . env('MAIL_PORT'));
            
            // Test 1: Basic mail
            $this->info("\nSending test email...");
            
            Mail::raw('Server Monitor Test Email

If you receive this email, your SMTP configuration is working correctly!

Server: ' . gethostname() . '
Time: ' . now()->format('Y-m-d H:i:s') . '
IP: ' . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . '

✅ Email system is operational!

You will receive alerts when:
- CPU usage exceeds ' . env('CPU_THRESHOLD', 80) . '%
- RAM usage exceeds ' . env('RAM_THRESHOLD', 85) . '%
- Disk usage exceeds ' . env('DISK_THRESHOLD', 90) . '%

Thank you for using Server Monitor!', 
            function ($message) use ($toEmail) {
                $message->to($toEmail)
                        ->subject('✅ Server Monitor - SMTP Test Successful')
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'Server Monitor'));
            });
            
            $this->info("✅ Test email sent successfully to: " . $toEmail);
            
            // Test 2: Check if we can use the notification
            $this->info("\nTesting alert notification system...");
            
            $testAlerts = [
                'cpu_exceeded' => true,
                'ram_exceeded' => false,
                'disk_exceeded' => true,
                'cpu_value' => 95,
                'ram_value' => 45,
                'disk_value' => 92,
                'cpu_threshold' => 80,
                'ram_threshold' => 85,
                'disk_threshold' => 90,
            ];
            
            \App\Notifications\ServerAlertNotification::send(
                new \Illuminate\Notifications\AnonymousNotifiable,
                new \App\Notifications\ServerAlertNotification($testAlerts)
            );
            
            $this->info("✅ Alert notification test completed!");
            
            $this->line("\n" . str_repeat("=", 50));
            $this->info("🎉 All email tests completed successfully!");
            $this->info("Check your inbox (and spam folder) for the test email.");
            $this->line(str_repeat("=", 50));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Email test failed!");
            $this->error("Error: " . $e->getMessage());
            $this->line("\nDebug information:");
            $this->line("- MAIL_HOST: " . env('MAIL_HOST'));
            $this->line("- MAIL_PORT: " . env('MAIL_PORT'));
            $this->line("- MAIL_USERNAME: " . env('MAIL_USERNAME'));
            $this->line("- MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION'));
            
            // Show common fixes
            $this->line("\n🔧 Common fixes:");
            $this->line("1. Check if you're using Gmail App Password (not regular password)");
            $this->line("2. Enable 2-Step Verification on Google account");
            $this->line("3. Check firewall: sudo ufw allow out 587/tcp");
            $this->line("4. Visit: https://accounts.google.com/DisplayUnlockCaptcha");
            
            return Command::FAILURE;
        }
    }
}