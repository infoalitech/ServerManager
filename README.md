# 📊 Server Monitor Dashboard - Laravel

A comprehensive server monitoring system built with Laravel that tracks CPU, RAM, Disk, Network usage, services status, and domain management with email alerts.

## ✨ Features

### 🖥️ **Server Monitoring**
- **Real-time CPU, RAM, Disk, and Swap usage** with visual progress bars
- **Network connections** monitoring (total & established)
- **Service status** tracking (Nginx, MySQL, PHP-FPM, Redis, etc.)
- **Top processes** by CPU and Memory usage
- **Disk space usage** for all partitions with alerts
- **SSL certificate** expiry tracking with warnings
- **Historical charts** (24-hour data visualization)

### 📧 **Alert System**
- **Email notifications** via Gmail SMTP when thresholds are exceeded
- **Configurable thresholds** for CPU, RAM, Disk, and Swap
- **Test email functionality** to verify SMTP configuration

### 🌐 **Domain Management**
- **Automatic domain detection** from Apache/Nginx configurations
- **Subdomain tracking** and monitoring
- **SSL certificate checking** for all domains
- **DNS record lookup** (A, CNAME, MX, TXT)
- **Ping testing** with packet loss statistics
- **Separate domain management interface**

### 📊 **Dashboard**
- **Responsive design** with Tailwind CSS
- **Auto-refresh** every 30 seconds
- **Alert banners** with visual indicators
- **Server information** panel (PHP version, Laravel version, OS, uptime, etc.)
- **Historical data** storage and visualization with Chart.js

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/server-monitor.git
cd server-monitor
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=server_monitor
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Run Migrations
```bash
php artisan migrate
```

### 6. Configure Gmail SMTP for Email Alerts
Update your `.env` file with Gmail credentials:
```env
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Use Gmail App Password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-email@gmail.com"
MAIL_FROM_NAME="Server Monitor"

# Alert Configuration
ALERT_EMAIL=admin@example.com
CPU_THRESHOLD=80
RAM_THRESHOLD=85
DISK_THRESHOLD=90
```

**Important:** Use [Gmail App Password](https://myaccount.google.com/apppasswords) not your regular password.

### 7. Set File Permissions
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 8. Schedule Monitoring (Optional)
Add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📁 Project Structure

```
server-monitor/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── MonitorServer.php      # Server monitoring command
│   │       └── TestEmailCommand.php   # Email testing command
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DashboardController.php
│   │       └── DomainController.php   # Domain management
│   ├── Models/
│   │   └── SystemMetric.php           # Metrics storage model
│   ├── Notifications/
│   │   └── ServerAlertNotification.php # Email alerts
│   └── Services/
│       └── SystemMonitorService.php   # Core monitoring logic
├── resources/
│   └── views/
│       ├── dashboard.blade.php        # Main dashboard
│       └── domains/
│           └── index.blade.php        # Domain manager
├── routes/
│   └── web.php                        # Application routes
└── storage/
    └── logs/                          # Application logs
```

## 🔧 Usage

### Access URLs:
- **Dashboard:** `https://yourdomain.com/dashboard`
- **Domain Manager:** `https://yourdomain.com/domains`

### Available Commands:
```bash
# Run server monitoring manually
php artisan monitor:server

# Test email configuration
php artisan email:test

# Collect metrics without alerts
php artisan collect-metrics

# Clear application cache
php artisan config:clear
php artisan cache:clear
```

### Control Panel Functions:
- **Refresh Stats**: Update all statistics
- **Collect Metrics**: Store current metrics in database
- **Check Alerts**: Run monitoring and send email alerts if thresholds exceeded
- **Test Email**: Send test email to verify SMTP configuration
- **Domain Manager**: Navigate to domain management page

## 🛠️ Customization

### Adjust Monitoring Thresholds:
Edit `.env` file:
```env
CPU_THRESHOLD=80      # Alert when CPU > 80%
RAM_THRESHOLD=85      # Alert when RAM > 85%
DISK_THRESHOLD=90     # Alert when Disk > 90%
```

### Add New Services to Monitor:
Edit `SystemMonitorService.php` `getServiceStatus()` method:
```php
'service-name' => 'systemctl is-active service-name',
```

### Extend Monitoring:
Add new methods to `SystemMonitorService.php`:
- Network traffic monitoring
- Database query performance
- Application-specific metrics
- Custom alert conditions

## 🚨 Troubleshooting

### Permission Errors:
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Email Not Working:
1. Verify Gmail App Password is correct
2. Enable 2-Step Verification on Google account
3. Check firewall allows outbound port 587
4. Visit: https://accounts.google.com/DisplayUnlockCaptcha

### No Data Showing:
1. Check if `exec()` function is enabled in PHP
2. Verify web server user has permission to run system commands
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Domain Detection Not Working:
1. Ensure web server configs are in standard locations
2. Check file permissions on `/etc/apache2/` or `/etc/nginx/`
3. Manually add domains in the controller if auto-detection fails

## 📈 Screenshots

### Dashboard
![Dashboard](screenshots/dashboard.png)

### Domain Manager
![Domain Manager](screenshots/domains.png)

### Email Alert
![Email Alert](screenshots/email-alert.png)

## 🔒 Security Considerations

1. **Restrict access** to the dashboard with authentication
2. **Use HTTPS** for all connections
3. **Regularly update** dependencies
4. **Monitor logs** for suspicious activity
5. **Use strong passwords** for database and email
6. **Limit command execution** permissions

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - The PHP Framework
- [Tailwind CSS](https://tailwindcss.com/) - CSS Framework
- [Chart.js](https://www.chartjs.org/) - JavaScript Charts
- [Alpine.js](https://alpinejs.dev/) - Minimal JavaScript Framework

## 📞 Support

For support, please open an issue in the GitHub repository or contact the maintainers.

---

**Made with ❤️ for server administrators and developers**

**⭐ Star this repo if you find it useful!**