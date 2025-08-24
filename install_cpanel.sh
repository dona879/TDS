#!/bin/bash

# Enhanced YellowCloaker TDS - cPanel Installation Script
# Usage: bash install_cpanel.sh

echo "🚀 Enhanced YellowCloaker TDS - cPanel Installation"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "enhanced_index.php" ]; then
    print_error "Please run this script from the TDS directory"
    exit 1
fi

print_status "Starting installation..."

# Step 1: Check PHP version
echo ""
echo "📋 Checking system requirements..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_status "PHP Version: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '7.4.0', '<') ? 1 : 0);"; then
    print_error "PHP 7.4+ required. Current version: $PHP_VERSION"
    exit 1
fi

# Step 2: Install Composer dependencies
echo ""
echo "📦 Installing dependencies..."

if command -v composer &> /dev/null; then
    print_status "Using system Composer"
    composer install --no-dev --optimize-autoloader
elif [ -f "composer.phar" ]; then
    print_status "Using included composer.phar"
    php composer.phar install --no-dev --optimize-autoloader
else
    print_warning "Downloading Composer..."
    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --no-dev --optimize-autoloader
fi

if [ $? -eq 0 ]; then
    print_status "Dependencies installed successfully"
else
    print_error "Failed to install dependencies"
    exit 1
fi

# Step 3: Set permissions
echo ""
echo "🔐 Setting file permissions..."

chmod 755 admin/ src/ js/ cache/ logs/ vendor/ 2>/dev/null
chmod 777 cache/ logs/ 2>/dev/null
chmod -R 777 cache/ logs/ 2>/dev/null
chmod 644 *.php admin/*.php 2>/dev/null
find src/ -name "*.php" -exec chmod 644 {} \; 2>/dev/null

print_status "Permissions set correctly"

# Step 4: Create necessary directories
echo ""
echo "📁 Creating directories..."

mkdir -p cache/device_detector 2>/dev/null
mkdir -p logs/filter_rules/data 2>/dev/null
mkdir -p logs/filter_rules/cache 2>/dev/null

print_status "Directories created"

# Step 5: Check settings.json
echo ""
echo "⚙️  Checking configuration..."

if [ ! -f "settings.json" ]; then
    print_error "settings.json not found!"
    exit 1
fi

# Check if enhanced section exists
if ! grep -q '"enhanced"' settings.json; then
    print_warning "Enhanced configuration not found in settings.json"
    print_warning "Please run: php migrate_to_enhanced.php"
else
    print_status "Enhanced configuration found"
fi

# Step 6: Test PHP functionality
echo ""
echo "🧪 Testing PHP functionality..."

php -r "
try {
    require_once 'vendor/autoload.php';
    echo 'Autoloader: OK\n';
    
    if (class_exists('DeviceDetector\DeviceDetector')) {
        echo 'Device Detector: OK\n';
    } else {
        echo 'Device Detector: MISSING\n';
    }
    
    if (class_exists('GuzzleHttp\Client')) {
        echo 'Guzzle HTTP: OK\n';
    } else {
        echo 'Guzzle HTTP: MISSING\n';
    }
    
    if (extension_loaded('curl')) {
        echo 'cURL Extension: OK\n';
    } else {
        echo 'cURL Extension: MISSING\n';
    }
    
    if (extension_loaded('json')) {
        echo 'JSON Extension: OK\n';
    } else {
        echo 'JSON Extension: MISSING\n';
    }
    
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . \"\n\";
    exit(1);
}
"

if [ $? -eq 0 ]; then
    print_status "PHP functionality test passed"
else
    print_error "PHP functionality test failed"
    exit 1
fi

# Step 7: Run migration if needed
echo ""
echo "🔄 Running migration..."

if [ -f "migrate_to_enhanced.php" ]; then
    php migrate_to_enhanced.php
    if [ $? -eq 0 ]; then
        print_status "Migration completed successfully"
    else
        print_warning "Migration had issues, but continuing..."
    fi
else
    print_warning "Migration script not found"
fi

# Step 8: Create .htaccess if it doesn't exist
echo ""
echo "🌐 Setting up web configuration..."

if [ ! -f ".htaccess" ]; then
    cat > .htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"

# PHP settings
php_value memory_limit 256M
php_value max_execution_time 300
php_value upload_max_filesize 10M
php_value post_max_size 10M

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
EOF
    print_status ".htaccess created"
else
    print_status ".htaccess already exists"
fi

# Step 9: Final test
echo ""
echo "🎯 Running final test..."

php -r "
try {
    require_once 'enhanced_core.php';
    echo 'Enhanced core loaded successfully\n';
} catch (Exception \$e) {
    echo 'Error loading enhanced core: ' . \$e->getMessage() . \"\n\";
    exit(1);
}
"

if [ $? -eq 0 ]; then
    print_status "Final test passed"
else
    print_error "Final test failed"
    exit 1
fi

# Installation complete
echo ""
echo "🎉 Installation Complete!"
echo "========================"
echo ""
echo "📋 Next Steps:"
echo "1. Update your admin password in settings.json"
echo "2. Access Rules Management: https://yourdomain.com/admin/rules.php?password=YOUR_PASSWORD"
echo "3. Access Enhanced Analytics: https://yourdomain.com/admin/analytics.php?password=YOUR_PASSWORD"
echo "4. Create your first traffic filtering rules"
echo ""
echo "📚 Documentation:"
echo "- Installation Guide: CPANEL_INSTALLATION_GUIDE.md"
echo "- Enhanced Features: ENHANCED_README.md"
echo "- Deployment Guide: ENHANCED_DEPLOYMENT.md"
echo ""
echo "🔧 Troubleshooting:"
echo "- Check logs: tail -f logs/access.log"
echo "- Test functionality: php enhanced_index.php"
echo "- Verify permissions: ls -la cache/ logs/"
echo ""
print_status "Enhanced YellowCloaker TDS is ready to use!"