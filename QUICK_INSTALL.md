# 🚀 Quick Installation - Enhanced YellowCloaker TDS

## One-Command Installation

```bash
# Download and install in one command
curl -sL https://raw.githubusercontent.com/dona879/TDS/enhanced-yellowcloaker-features/install_cpanel.sh | bash
```

## Manual Quick Install (5 Commands)

```bash
# 1. Download the system
git clone https://github.com/dona879/TDS.git enhanced-tds && cd enhanced-tds
git checkout enhanced-yellowcloaker-features

# 2. Install dependencies
php composer.phar install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 . && chmod -R 777 cache/ logs/

# 4. Run migration
php migrate_to_enhanced.php

# 5. Test installation
php enhanced_index.php
```

## Access Your System

After installation, access these URLs:

- **Rules Management**: `https://yourdomain.com/admin/rules.php?password=12345`
- **Enhanced Analytics**: `https://yourdomain.com/admin/analytics.php?password=12345`
- **Original Stats**: `https://yourdomain.com/admin/statistics.php?password=12345`

⚠️ **Important**: Change the default password `12345` in `settings.json`!

## What You Get

✅ **Advanced Device Detection** - Samsung Galaxy S21, iPhone 13 Pro, etc.  
✅ **Bot Detection** - AI-powered bot vs human classification  
✅ **Dynamic Rules Engine** - Create rules via web interface  
✅ **Enhanced Analytics** - Interactive charts and insights  
✅ **FingerprintJS Integration** - Persistent visitor tracking  
✅ **Backward Compatibility** - All original features preserved  

## Need Help?

- 📖 **Full Guide**: `CPANEL_INSTALLATION_GUIDE.md`
- 🔧 **Features**: `ENHANCED_README.md`
- 🚀 **Deployment**: `ENHANCED_DEPLOYMENT.md`

---

**Ready in 5 minutes!** 🎉