# OPManager Integration Plugin - Installation Guide

## Prerequisites

Sebelum menginstal plugin, pastikan sistem memenuhi persyaratan berikut:

### System Requirements
- **GLPI**: Version 10.0.0 atau lebih baru
- **PHP**: Version 7.4 atau lebih baru
- **Database**: MySQL 5.7+ atau MariaDB 10.2+
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **OPManager**: Version 12.0+ dengan REST API enabled

### PHP Extensions
```bash
# Required extensions
php-curl
php-json
php-mbstring
php-mysql
php-xml

# Check installed extensions
php -m | grep -E "(curl|json|mbstring|mysql|xml)"
```

### Server Access
- SSH access ke server GLPI
- Database access (MySQL/MariaDB)
- Web server configuration access

## Installation Methods

### Method 1: Manual Installation (Recommended)

#### Step 1: Download Plugin
```bash
# Navigate to GLPI plugins directory
cd /var/www/html/glpi/plugins/

# Clone repository
git clone https://github.com/yourusername/glpi-opmanager.git opmanager

# Or download ZIP and extract
wget https://github.com/yourusername/glpi-opmanager/archive/main.zip
unzip main.zip
mv glpi-opmanager-main opmanager
```

#### Step 2: Set Permissions
```bash
# Set ownership to web server user
chown -R www-data:www-data opmanager/

# Set proper permissions
chmod -R 755 opmanager/
chmod -R 644 opmanager/*.php
chmod -R 644 opmanager/inc/*.php
chmod -R 644 opmanager/front/*.php

# Make sure web server can write to logs
chmod 775 opmanager/logs/ 2>/dev/null || true
```

#### Step 3: Install via GLPI Admin
1. Login ke GLPI sebagai administrator
2. Navigate ke **Setup > Plugins**
3. Cari "OPManager Integration" dalam daftar
4. Klik **Install** button
5. Setelah instalasi selesai, klik **Enable**

#### Step 4: Verify Installation
```bash
# Check if tables were created
mysql -u glpi_user -p glpi_database -e "SHOW TABLES LIKE 'glpi_plugin_opmanager_%';"

# Expected output:
# glpi_plugin_opmanager_alarms
# glpi_plugin_opmanager_config
# glpi_plugin_opmanager_logs
# glpi_plugin_opmanager_webhook_retries
```

### Method 2: Composer Installation

#### Step 1: Add Repository
```bash
# Navigate to GLPI root
cd /var/www/html/glpi/

# Add to composer.json
composer config repositories.opmanager vcs https://github.com/yourusername/glpi-opmanager
```

#### Step 2: Install Plugin
```bash
# Install via composer
composer require yourusername/glpi-opmanager:dev-main

# Or add to composer.json and run update
composer update
```

#### Step 3: Enable Plugin
1. Login ke GLPI admin
2. **Setup > Plugins**
3. Enable "OPManager Integration"

### Method 3: Package Installation

#### Step 1: Download Package
```bash
# Download release package
wget https://github.com/yourusername/glpi-opmanager/releases/download/v1.0.0/opmanager-1.0.0.tar.gz

# Extract to plugins directory
tar -xzf opmanager-1.0.0.tar.gz -C /var/www/html/glpi/plugins/
```

#### Step 2: Set Permissions
```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/opmanager/
chmod -R 755 /var/www/html/glpi/plugins/opmanager/
```

#### Step 3: Install via GLPI
1. **Setup > Plugins**
2. Install dan Enable plugin

## Post-Installation Configuration

### Step 1: Basic Configuration
1. Navigate ke **Setup > General > OPManager Integration**
2. Isi konfigurasi dasar:
   - **OPManager Server**: IP/hostname server OPManager
   - **Port**: Port OPManager (default: 443)
   - **Username**: Username untuk API access
   - **Password**: Password untuk API access

### Step 2: Webhook Configuration
1. **Webhook Secret Key**: Generate secret key yang kuat
   ```bash
   # Generate random secret
   openssl rand -hex 32
   ```
2. **Webhook URL**: URL akan otomatis ter-generate
3. **Test Connection**: Klik button test untuk verifikasi koneksi

### Step 3: Ticket Settings
1. **Default Entity**: Pilih entity default untuk ticket
2. **Default Request Type**: Pilih request type default
3. **Default Category**: Pilih kategori default

### Step 4: Sync Configuration
1. **Enable Bidirectional Sync**: Aktifkan sinkronisasi dua arah
2. **Sync Interval**: Set interval sinkronisasi (default: 5 menit)
3. **Retry Settings**: Konfigurasi retry mechanism

## OPManager Configuration

### Step 1: Enable REST API
1. Login ke OPManager Admin Console
2. Navigate ke **Administration > REST API**
3. Enable REST API
4. Create API user dengan permissions yang sesuai

### Step 2: Configure Webhook
1. **Administration > Webhooks**
2. Click **Add Webhook**
3. Configure webhook settings:
   ```
   Name: GLPI Integration
   URL: https://your-glpi-server/plugins/opmanager/front/webhook.php
   Method: POST
   Events: alarm_raised, alarm_cleared, alarm_acknowledged, alarm_updated
   ```

### Step 3: Webhook Payload Template
```json
{
  "event_type": "{{event_type}}",
  "alarm_id": "{{alarm_id}}",
  "device_name": "{{device_name}}",
  "severity": "{{severity}}",
  "message": "{{message}}",
  "timestamp": "{{timestamp}}",
  "custom_fields": {
    "location": "{{location}}",
    "department": "{{department}}",
    "monitor_name": "{{monitor_name}}"
  }
}
```

### Step 4: Authentication
1. **Headers**: Add custom header
   ```
   X-OPManager-Signature: {{signature}}
   ```
2. **Signature**: Generate HMAC-SHA256 signature
   ```javascript
   const signature = crypto.createHmac('sha256', webhook_secret)
     .update(JSON.stringify(payload))
     .digest('hex');
   ```

## Database Configuration

### Automatic Installation
Plugin akan otomatis membuat tabel yang diperlukan saat instalasi.

### Manual Database Setup
Jika diperlukan setup manual:

```sql
-- Run SQL script manually
mysql -u glpi_user -p glpi_database < /path/to/opmanager/sql/install/install.sql
```

### Verify Database Tables
```sql
-- Check table structure
DESCRIBE glpi_plugin_opmanager_config;
DESCRIBE glpi_plugin_opmanager_alarms;
DESCRIBE glpi_plugin_opmanager_logs;
DESCRIBE glpi_plugin_opmanager_webhook_retries;

-- Check default configuration
SELECT * FROM glpi_plugin_opmanager_config;
```

## Web Server Configuration

### Apache Configuration
```apache
# Ensure mod_rewrite is enabled
a2enmod rewrite

# Add to .htaccess or virtual host
<Directory /var/www/html/glpi/plugins/opmanager>
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx Configuration
```nginx
# Add to server block
location /plugins/opmanager/ {
    try_files $uri $uri/ /index.php?$query_string;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
}
```

### SSL Configuration
```bash
# Ensure HTTPS is enabled
# Webhook endpoint must be accessible via HTTPS
# Generate SSL certificate if needed
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/opmanager.key \
  -out /etc/ssl/certs/opmanager.crt
```

## Cron Job Configuration

### Automatic Setup
Plugin akan otomatis mendaftarkan cron job dengan GLPI.

### Manual Cron Setup
Jika diperlukan setup manual:

```bash
# Edit crontab
crontab -e

# Add cron jobs
*/5 * * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force=PluginOpmanagerCron::syncTickets
*/5 * * * * /usr/bin/php /var/www/html/glpi/front/cron.php --force=PluginOpmanagerCron::retryFailedWebhooks
```

### Verify Cron Jobs
```bash
# Check if cron jobs are registered
mysql -u glpi_user -p glpi_database -e "SELECT * FROM glpi_crontasks WHERE itemtype LIKE '%Opmanager%';"

# Check cron logs
tail -f /var/log/cron
```

## Firewall Configuration

### Allow OPManager Access
```bash
# Allow OPManager server IP
iptables -A INPUT -s <opmanager_ip> -p tcp --dport 443 -j ACCEPT

# Allow webhook access
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# Save rules
iptables-save > /etc/iptables/rules.v4
```

### Network Security
```bash
# Restrict access to webhook endpoint
iptables -A INPUT -s <opmanager_ip> -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j DROP

# Enable logging
iptables -A INPUT -s <opmanager_ip> -p tcp --dport 443 -j LOG --log-prefix "OPManager Webhook: "
```

## Testing Installation

### Step 1: Test Webhook Endpoint
```bash
# Test webhook endpoint
curl -X POST https://your-glpi-server/plugins/opmanager/front/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-OPManager-Signature: test" \
  -d '{"test": "data"}'

# Expected response: 401 Unauthorized (invalid signature)
```

### Step 2: Test Configuration
1. Login ke GLPI admin
2. **Setup > General > OPManager Integration**
3. Test koneksi ke OPManager
4. Verify semua field tersimpan

### Step 3: Test Webhook Processing
1. Send test webhook dari OPManager
2. Check ticket creation di GLPI
3. Verify log entries
4. Check database records

### Step 4: Test Bidirectional Sync
1. Create ticket di GLPI
2. Check sync ke OPManager
3. Update ticket status
4. Verify sync updates

## Troubleshooting

### Common Issues

#### 1. Plugin Not Visible
```bash
# Check plugin directory
ls -la /var/www/html/glpi/plugins/opmanager/

# Check permissions
ls -la /var/www/html/glpi/plugins/opmanager/setup.php

# Check GLPI logs
tail -f /var/log/glpi/glpi.log
```

#### 2. Database Tables Missing
```bash
# Check database connection
mysql -u glpi_user -p glpi_database -e "SELECT 1;"

# Run installation SQL manually
mysql -u glpi_user -p glpi_database < /path/to/opmanager/sql/install/install.sql
```

#### 3. Webhook Not Working
```bash
# Check web server logs
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log

# Check plugin logs
mysql -u glpi_user -p glpi_database -e "SELECT * FROM glpi_plugin_opmanager_logs ORDER BY timestamp DESC LIMIT 10;"
```

#### 4. Permission Denied
```bash
# Fix ownership
chown -R www-data:www-data /var/www/html/glpi/plugins/opmanager/

# Fix permissions
chmod -R 755 /var/www/html/glpi/plugins/opmanager/
chmod -R 644 /var/www/html/glpi/plugins/opmanager/*.php
```

### Debug Mode
```bash
# Enable debug logging
mysql -u glpi_user -p glpi_database -e "UPDATE glpi_plugin_opmanager_config SET value='1' WHERE name='debug_mode';"

# Check debug logs
tail -f /var/log/glpi/glpi.log | grep opmanager
```

## Security Considerations

### 1. Access Control
```bash
# Restrict plugin access
# Only allow specific IPs to access webhook endpoint
iptables -A INPUT -s <allowed_ip> -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j DROP
```

### 2. Secret Management
```bash
# Use strong secret keys
openssl rand -hex 32

# Rotate secrets regularly
# Store secrets securely
```

### 3. SSL/TLS
```bash
# Force HTTPS for webhook
# Use strong cipher suites
# Regular certificate renewal
```

## Maintenance

### Regular Tasks
1. **Monitor logs**: Check plugin logs regularly
2. **Update secrets**: Rotate webhook secrets
3. **Backup data**: Backup plugin configuration
4. **Performance**: Monitor sync performance

### Backup Configuration
```bash
# Backup plugin configuration
mysqldump -u glpi_user -p glpi_database glpi_plugin_opmanager_config > opmanager_config_backup.sql

# Backup plugin files
tar -czf opmanager_files_backup.tar.gz /var/www/html/glpi/plugins/opmanager/
```

### Update Plugin
```bash
# Backup current installation
cp -r /var/www/html/glpi/plugins/opmanager /var/www/html/glpi/plugins/opmanager_backup

# Download new version
cd /var/www/html/glpi/plugins/
git pull origin main

# Or download new release
wget https://github.com/yourusername/glpi-opmanager/releases/download/v1.0.1/opmanager-1.0.1.tar.gz
tar -xzf opmanager-1.0.1.tar.gz

# Set permissions
chown -R www-data:www-data opmanager/
chmod -R 755 opmanager/

# Update database if needed
mysql -u glpi_user -p glpi_database < opmanager/sql/update/update.sql
```

## Support

### Documentation
- [README.md](../README.md)
- [API Reference](API_REFERENCE.md)
- [Configuration Guide](CONFIGURATION.md)

### Community Support
- GitHub Issues: [Repository Issues](https://github.com/yourusername/glpi-opmanager/issues)
- GLPI Forum: [Community Forum](https://forum.glpi-project.org/)
- Documentation: [Plugin Wiki](https://github.com/yourusername/glpi-opmanager/wiki)

### Professional Support
- Email: support@yourcompany.com
- Phone: +1-555-0123
- Hours: Monday-Friday 9AM-6PM EST
