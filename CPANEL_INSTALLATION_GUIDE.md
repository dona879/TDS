# 🚀 Enhanced YellowCloaker TDS - cPanel Installation Guide

## Prerequisites

Before starting, ensure you have:
- ✅ cPanel hosting account with SSH/Terminal access
- ✅ PHP 7.4+ (preferably PHP 8.0+)
- ✅ Composer access or ability to install it
- ✅ Git access
- ✅ Write permissions to your domain directory

## Step 1: Access Your Server Terminal

### Option A: SSH Access (Recommended)
```bash
ssh username@your-server-ip
# or
ssh username@yourdomain.com
```

### Option B: cPanel Terminal (if available)
- Login to cPanel
- Go to "Advanced" → "Terminal"
- Click "Open Terminal"

## Step 2: Navigate to Your Domain Directory

```bash
# Navigate to your domain's public_html directory
cd ~/public_html/yourdomain.com
# or for main domain
cd ~/public_html
```

## Step 3: Download the Enhanced TDS System

```bash
# Clone the repository
git clone https://github.com/dona879/TDS.git enhanced-tds

# Navigate to the directory
cd enhanced-tds

# Switch to the enhanced features branch
git checkout enhanced-yellowcloaker-features
```

## Step 4: Install Composer (if not available)

### Check if Composer is installed:
```bash
composer --version
```

### If Composer is not installed:
```bash
# Download Composer
curl -sS https://getcomposer.org/installer | php

# Make it globally accessible (optional)
mv composer.phar composer
chmod +x composer

# Or use the downloaded composer.phar directly
```

## Step 5: Install Dependencies

```bash
# If you have global composer:
composer install --no-dev --optimize-autoloader

# If using composer.phar:
php composer.phar install --no-dev --optimize-autoloader

# If composer fails, use the included composer.phar:
php composer.phar install --no-dev --optimize-autoloader
```

## Step 6: Set Up File Permissions

```bash
# Set proper permissions for directories
chmod 755 admin/
chmod 755 src/
chmod 755 js/
chmod 755 cache/
chmod 755 logs/
chmod 755 vendor/

# Set write permissions for cache and logs
chmod 777 cache/
chmod 777 logs/
chmod -R 777 cache/
chmod -R 777 logs/

# Set execute permissions for PHP files
chmod 644 *.php
chmod 644 admin/*.php
chmod 644 src/*/*.php
```

## Step 7: Configure the System

### Edit Configuration File:
```bash
# Edit settings.json
nano settings.json
# or
vi settings.json
```

### Update these essential settings:
```json
{
  "admin_password": "YOUR_SECURE_PASSWORD",
  "enhanced": {
    "enabled": true,
    "device_detection": {
      "enabled": true,
      "cache_duration": 86400
    },
    "fingerprint": {
      "enabled": false,
      "api_key": "",
      "api_secret": ""
    },
    "rules_engine": {
      "enabled": true
    },
    "analytics": {
      "enabled": true
    }
  }
}
```

## Step 8: Run the Migration Script

```bash
# Run the automated migration
php migrate_to_enhanced.php
```

Expected output:
```
🔄 YellowCloaker Enhanced Migration
==================================
✅ Backup created: settings_original_backup.json
✅ Backup created: index_original_backup.php
✅ Enhanced configuration updated
✅ Enhanced index.php activated
✅ Dependencies verified
✅ Permissions set correctly
✅ Cache directories created
✅ Test rules created successfully
🎉 Migration completed successfully!
```

## Step 9: Test the Installation

```bash
# Test the enhanced features
php -f enhanced_index.php
```

## Step 10: Set Up Web Access

### Update .htaccess (if needed):
```bash
# Create or edit .htaccess
nano .htaccess
```

Add these rules:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
```

## Step 11: Access Admin Panels

### Rules Management:
```
https://yourdomain.com/admin/rules.php?password=YOUR_PASSWORD
```

### Enhanced Analytics:
```
https://yourdomain.com/admin/analytics.php?password=YOUR_PASSWORD
```

### Original Statistics:
```
https://yourdomain.com/admin/statistics.php?password=YOUR_PASSWORD
```

## Step 12: Create Your First Traffic Rule

1. Access the Rules Management panel
2. Click "Add New Rule"
3. Example rule to block bots:
   ```
   Name: Block High Bot Probability
   Field: fingerprint.bot_probability
   Operator: greater_than
   Value: 0.7
   Action: BLOCK
   ```

## Troubleshooting Common Issues

### Issue 1: Composer Not Working
```bash
# Use the included composer.phar
php composer.phar install --no-dev

# Or download fresh composer
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev
```

### Issue 2: Permission Denied Errors
```bash
# Fix permissions
chmod -R 755 .
chmod -R 777 cache/ logs/
chown -R username:username .
```

### Issue 3: PHP Version Issues
```bash
# Check PHP version
php -v

# If using older PHP, update in cPanel:
# cPanel → Software → Select PHP Version → Choose PHP 8.0+
```

### Issue 4: Memory Limit Issues
Add to .htaccess:
```apache
php_value memory_limit 256M
php_value max_execution_time 300
```

### Issue 5: Missing Extensions
```bash
# Check required extensions
php -m | grep -E "(curl|json|mbstring|openssl)"
```

If missing, enable in cPanel → PHP Extensions

## Advanced Configuration

### Enable FingerprintJS Pro (Optional):
1. Sign up at https://fingerprint.com
2. Get API keys
3. Update settings.json:
```json
"fingerprint": {
  "enabled": true,
  "api_key": "your_public_key",
  "api_secret": "your_secret_key"
}
```

### Performance Optimization:
```bash
# Enable OPcache in cPanel
# Set in .htaccess:
```
```apache
php_value opcache.enable 1
php_value opcache.memory_consumption 128
php_value opcache.max_accelerated_files 4000
```

## Monitoring and Maintenance

### Check System Status:
```bash
# View recent logs
tail -f logs/access.log

# Check cache status
ls -la cache/

# Monitor rules performance
php -r "
require 'src/Rules/RulesEngine.php';
\$engine = new \YellowCloaker\Rules\RulesEngine();
print_r(\$engine->getAllRules());
"
```

### Regular Maintenance:
```bash
# Clear cache (monthly)
rm -rf cache/*

# Update dependencies (quarterly)
composer update --no-dev

# Backup settings (before changes)
cp settings.json settings_backup_$(date +%Y%m%d).json
```

## Security Recommendations

1. **Change Default Password**: Update admin_password in settings.json
2. **Restrict Admin Access**: Add IP restrictions in .htaccess
3. **Regular Updates**: Keep the system updated
4. **Monitor Logs**: Check for suspicious activity
5. **Backup Regularly**: Backup settings and rules

## Support and Documentation

- **Enhanced Features**: See `ENHANCED_README.md`
- **Deployment Guide**: See `ENHANCED_DEPLOYMENT.md`
- **Implementation Summary**: See `IMPLEMENTATION_SUMMARY.md`

## Quick Commands Reference

```bash
# Installation
git clone https://github.com/dona879/TDS.git enhanced-tds
cd enhanced-tds
git checkout enhanced-yellowcloaker-features
php composer.phar install --no-dev
php migrate_to_enhanced.php

# Permissions
chmod -R 755 .
chmod -R 777 cache/ logs/

# Testing
php enhanced_index.php

# Monitoring
tail -f logs/access.log
```

---

🎉 **Congratulations!** Your Enhanced YellowCloaker TDS system is now installed and ready to use!

For support or questions, refer to the documentation files included in the installation.