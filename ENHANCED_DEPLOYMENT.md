# YellowCloaker Enhanced - Deployment Guide

## 🚀 Overview

This enhanced version of YellowCloaker includes:
- **Advanced Device Detection** with Matomo Device Detector + HTTP Client Hints
- **FingerprintJS Pro Integration** for persistent visitor identification
- **Dynamic Rules Engine** for flexible traffic filtering
- **Advanced Analytics Dashboard** with comprehensive reporting
- **Enhanced Logging** with detailed device and fingerprint data

## 📋 Prerequisites

- **PHP 7.2+** (recommended: PHP 8.0+)
- **Composer** for dependency management
- **HTTPS Certificate** (required for FingerprintJS Pro)
- **Write permissions** for cache and logs directories

## 🔧 Installation Steps

### 1. Install Dependencies

```bash
cd /path/to/yellowcloaker
composer install
```

### 2. Set Directory Permissions

```bash
chmod 755 cache/
chmod 755 logs/
chmod 755 vendor/
```

### 3. Configure Enhanced Features

Edit `settings.json` to enable enhanced features:

```json
{
  "tds": {
    "enhanced": {
      "enabled": true,
      "device_detection": {
        "enabled": true,
        "cache_duration": 86400
      },
      "fingerprinting": {
        "enabled": false,
        "fpjs_api_key": "YOUR_FPJS_API_KEY",
        "fpjs_public_key": "YOUR_FPJS_PUBLIC_KEY", 
        "max_visits_72h": 5
      },
      "rules_engine": {
        "enabled": true
      }
    }
  }
}
```

### 4. FingerprintJS Pro Setup (Optional)

1. **Sign up** at [FingerprintJS Pro](https://fingerprintjs.com/)
2. **Get API keys** from your dashboard
3. **Add keys** to `settings.json`:
   - `fpjs_api_key`: Server-side API key
   - `fpjs_public_key`: Client-side public key
4. **Enable fingerprinting**: Set `"enabled": true`

### 5. Update Entry Point

Replace your current `index.php` with `enhanced_index.php`:

```bash
# Backup original
cp index.php index_original.php

# Use enhanced version
cp enhanced_index.php index.php
```

Or modify your existing `index.php` to include enhanced features.

## 🎛️ Configuration Options

### Enhanced Device Detection

```json
"device_detection": {
  "enabled": true,
  "cache_duration": 86400  // Cache duration in seconds
}
```

### FingerprintJS Pro Settings

```json
"fingerprinting": {
  "enabled": true,
  "fpjs_api_key": "sk_prod_...",      // Server-side API key
  "fpjs_public_key": "pk_prod_...",   // Client-side public key
  "max_visits_72h": 5                 // Max visits in 72h before blocking
}
```

### Rules Engine

```json
"rules_engine": {
  "enabled": true
}
```

## 📊 Admin Dashboard Access

### Enhanced Features URLs:

- **Filter Rules Management**: `/admin/rules.php?password=YOUR_PASSWORD`
- **Advanced Analytics**: `/admin/analytics.php?password=YOUR_PASSWORD`
- **Original Statistics**: `/admin/statistics.php?password=YOUR_PASSWORD`

### Default Admin Access:
- **URL**: `/admin/index.php?password=12345`
- **⚠️ Change default password** in settings!

## 🔍 Features Overview

### 1. Enhanced Device Detection

**Capabilities:**
- Precise device brand/model detection (Samsung SM-G991B, iPhone 13 Pro, etc.)
- Operating system version detection (Android 11.0.3, iOS 15.2, etc.)
- Browser engine and version detection
- HTTP Client Hints integration for accuracy
- Detection confidence scoring
- Caching for performance

**Benefits:**
- Block specific device models
- Create granular filtering rules
- Reduce false positives

### 2. FingerprintJS Pro Integration

**Capabilities:**
- Persistent visitor identification across sessions
- Bot probability scoring
- VPN/Proxy/Tor detection
- Device tampering detection
- Visit frequency tracking (72-hour window)
- Fraud signal analysis

**Benefits:**
- Detect repeat visitors even with cleared cookies
- Advanced bot detection
- Fraud prevention
- Visitor behavior analysis

### 3. Dynamic Rules Engine

**Rule Types:**
- Device-based rules (brand, model, OS version)
- Fingerprint-based rules (bot probability, fraud signals)
- Behavioral rules (visit frequency, patterns)
- Combined conditions with AND/OR logic

**Rule Examples:**
```
Block if: device.device_brand = "Samsung" AND device.device_model = "SM-G991B"
Block if: fingerprint.bot_probability > 0.7
Block if: fingerprint.vpn = true OR fingerprint.proxy = true
Block if: visitor frequency in 72h > 5
```

### 4. Advanced Analytics

**Dashboards Include:**
- **Temporal Analysis**: Hourly/daily traffic patterns
- **Device Intelligence**: Brand/model/OS breakdowns
- **Fingerprint Analytics**: Fraud signals, confidence distribution
- **Visitor Behavior**: Repeat visitor analysis
- **Rule Performance**: Most effective blocking rules

## 🛠️ Troubleshooting

### Common Issues:

#### 1. Composer Dependencies Not Found
```bash
# Install dependencies
composer install

# If composer not found, install it first:
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

#### 2. Permission Errors
```bash
# Set correct permissions
chmod -R 755 cache/
chmod -R 755 logs/
chmod -R 755 vendor/
```

#### 3. FingerprintJS Not Working
- Ensure HTTPS is properly configured
- Check API keys are correct
- Verify domain is whitelisted in FingerprintJS dashboard
- Check browser console for JavaScript errors

#### 4. Enhanced Features Not Loading
- Verify `"enhanced": {"enabled": true}` in settings.json
- Check PHP error logs
- Ensure all dependencies are installed

### Debug Mode:

Enable debug mode in `enhanced_index.php`:
```php
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

## 📈 Performance Optimization

### 1. Caching
- Device detection results are cached for 24 hours
- FingerprintJS results are cached for 1 hour
- Clear cache: Delete files in `cache/` directory

### 2. Database Optimization
- SleekDB automatically optimizes JSON storage
- Consider periodic log cleanup for large datasets

### 3. Memory Usage
- Enhanced detection uses ~2-5MB additional memory
- Monitor with `memory_get_usage()` if needed

## 🔒 Security Considerations

### 1. API Keys Protection
- Store FingerprintJS keys securely
- Use environment variables in production
- Rotate keys periodically

### 2. Admin Access
- Change default admin password
- Use strong passwords
- Consider IP whitelisting for admin panel

### 3. Data Privacy
- Enhanced logging stores more detailed visitor data
- Ensure compliance with privacy regulations
- Implement data retention policies

## 🔄 Migration from Original YellowCloaker

### Backward Compatibility:
- All original features remain functional
- Original logging continues alongside enhanced logging
- Settings.json maintains backward compatibility
- Can disable enhanced features anytime

### Migration Steps:
1. **Backup** your current installation
2. **Install** enhanced version alongside original
3. **Test** with enhanced features disabled
4. **Gradually enable** enhanced features
5. **Monitor** performance and accuracy

## 📞 Support

### Resources:
- **Original YellowCloaker**: [GitHub Repository](https://github.com/dvygolov/YellowCloaker)
- **FingerprintJS Pro**: [Documentation](https://dev.fingerprintjs.com/)
- **Matomo Device Detector**: [Documentation](https://github.com/matomo-org/device-detector)

### Common Questions:

**Q: Can I use enhanced features without FingerprintJS Pro?**
A: Yes! Enhanced device detection and rules engine work independently.

**Q: Will this break my existing setup?**
A: No, enhanced features are additive and can be disabled anytime.

**Q: How much does FingerprintJS Pro cost?**
A: Check their pricing page. Free tier available for testing.

**Q: Can I create custom rules?**
A: Yes! The rules engine supports complex conditions and custom logic.

---

## 🎯 Quick Start Checklist

- [ ] Install Composer dependencies
- [ ] Set directory permissions
- [ ] Enable enhanced features in settings.json
- [ ] (Optional) Configure FingerprintJS Pro
- [ ] Update entry point to enhanced version
- [ ] Test admin dashboard access
- [ ] Create first custom rule
- [ ] Monitor analytics dashboard

**🎉 You're ready to use Enhanced YellowCloaker!**