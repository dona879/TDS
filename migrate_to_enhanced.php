<?php
/**
 * YellowCloaker Enhanced Migration Script
 * 
 * This script helps migrate from original YellowCloaker to Enhanced version
 * Run this script once after installing enhanced version
 */

echo "🚀 YellowCloaker Enhanced Migration Script\n";
echo "==========================================\n\n";

// Check PHP version
if (version_compare(phpversion(), '7.2.0', '<')) {
    die("❌ PHP version should be 7.2 or higher! Current version: " . phpversion() . "\n");
}
echo "✅ PHP Version: " . phpversion() . " (Compatible)\n";

// Check if composer is available
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Composer dependencies not found. Please run: composer install\n";
    exit(1);
}
echo "✅ Composer dependencies found\n";

// Create necessary directories
$directories = [
    'cache',
    'cache/device_detector',
    'cache/fingerprint',
    'logs'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Check directory permissions
foreach ($directories as $dir) {
    if (is_writable($dir)) {
        echo "✅ Directory writable: $dir\n";
    } else {
        echo "⚠️  Directory not writable: $dir (Please run: chmod 755 $dir)\n";
    }
}

// Backup original files
$backupFiles = [
    'index.php' => 'index_original_backup.php',
    'settings.json' => 'settings_original_backup.json'
];

foreach ($backupFiles as $original => $backup) {
    if (file_exists($original) && !file_exists($backup)) {
        if (copy($original, $backup)) {
            echo "✅ Backed up: $original → $backup\n";
        } else {
            echo "❌ Failed to backup: $original\n";
        }
    } else {
        echo "ℹ️  Backup exists or original not found: $original\n";
    }
}

// Check settings.json for enhanced configuration
if (file_exists('settings.json')) {
    $settings = json_decode(file_get_contents('settings.json'), true);
    
    if (!isset($settings['tds']['enhanced'])) {
        echo "⚠️  Enhanced configuration not found in settings.json\n";
        echo "   Please add enhanced configuration manually or update settings.json\n";
        
        // Show example configuration
        echo "\n📝 Example enhanced configuration to add:\n";
        echo json_encode([
            'enhanced' => [
                'enabled' => true,
                'device_detection' => [
                    'enabled' => true,
                    'cache_duration' => 86400
                ],
                'fingerprinting' => [
                    'enabled' => false,
                    'fpjs_api_key' => '',
                    'fpjs_public_key' => '',
                    'max_visits_72h' => 5
                ],
                'rules_engine' => [
                    'enabled' => true
                ]
            ]
        ], JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "✅ Enhanced configuration found in settings.json\n";
        
        $enhanced = $settings['tds']['enhanced'];
        echo "   - Enhanced enabled: " . ($enhanced['enabled'] ? 'Yes' : 'No') . "\n";
        echo "   - Device detection: " . ($enhanced['device_detection']['enabled'] ? 'Yes' : 'No') . "\n";
        echo "   - Fingerprinting: " . ($enhanced['fingerprinting']['enabled'] ? 'Yes' : 'No') . "\n";
        echo "   - Rules engine: " . ($enhanced['rules_engine']['enabled'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "❌ settings.json not found\n";
}

// Test enhanced core loading
try {
    require_once 'vendor/autoload.php';
    require_once 'enhanced_core.php';
    echo "✅ Enhanced core loads successfully\n";
} catch (Exception $e) {
    echo "❌ Enhanced core failed to load: " . $e->getMessage() . "\n";
}

// Create sample rules
echo "\n🔧 Creating sample filter rules...\n";

try {
    require_once 'vendor/autoload.php';
    
    $rulesEngine = new \YellowCloaker\Rules\RulesEngine();
    
    // Sample rule 1: Block high bot probability
    $rule1 = [
        'name' => 'High Bot Probability Block',
        'description' => 'Block visitors with high bot probability from FingerprintJS',
        'conditions' => [
            [
                'field' => 'fingerprint.bot_probability',
                'operator' => 'greater_than',
                'value' => '0.7'
            ]
        ],
        'operator' => 'AND',
        'priority' => 'high',
        'status' => 'active'
    ];
    
    // Sample rule 2: Block VPN users
    $rule2 = [
        'name' => 'VPN/Proxy Block',
        'description' => 'Block visitors using VPN or proxy services',
        'conditions' => [
            [
                'field' => 'fingerprint.vpn',
                'operator' => 'equals',
                'value' => 'true'
            ],
            [
                'field' => 'fingerprint.proxy',
                'operator' => 'equals',
                'value' => 'true'
            ]
        ],
        'operator' => 'OR',
        'priority' => 'medium',
        'status' => 'active'
    ];
    
    // Sample rule 3: Block specific device model
    $rule3 = [
        'name' => 'Suspicious Device Model',
        'description' => 'Block unknown or suspicious device models',
        'conditions' => [
            [
                'field' => 'device.device_brand',
                'operator' => 'equals',
                'value' => 'Unknown'
            ],
            [
                'field' => 'device.detection_confidence',
                'operator' => 'less_than',
                'value' => '50'
            ]
        ],
        'operator' => 'AND',
        'priority' => 'low',
        'status' => 'inactive'
    ];
    
    $sampleRules = [$rule1, $rule2, $rule3];
    
    foreach ($sampleRules as $rule) {
        try {
            $rulesEngine->createRule($rule);
            echo "✅ Created sample rule: " . $rule['name'] . "\n";
        } catch (Exception $e) {
            echo "⚠️  Rule may already exist: " . $rule['name'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Failed to create sample rules: " . $e->getMessage() . "\n";
}

// Migration summary
echo "\n📊 Migration Summary\n";
echo "===================\n";

$checks = [
    'PHP Version Compatible' => version_compare(phpversion(), '7.2.0', '>='),
    'Composer Dependencies' => file_exists('vendor/autoload.php'),
    'Cache Directory' => is_dir('cache') && is_writable('cache'),
    'Logs Directory' => is_dir('logs') && is_writable('logs'),
    'Settings Backup' => file_exists('settings_original_backup.json'),
    'Enhanced Core' => file_exists('enhanced_core.php'),
    'Rules Engine' => file_exists('src/Rules/RulesEngine.php'),
    'Device Detector' => file_exists('src/DeviceDetector/EnhancedDeviceDetector.php'),
    'Admin Rules Page' => file_exists('admin/rules.php'),
    'Admin Analytics Page' => file_exists('admin/analytics.php')
];

$passed = 0;
$total = count($checks);

foreach ($checks as $check => $status) {
    if ($status) {
        echo "✅ $check\n";
        $passed++;
    } else {
        echo "❌ $check\n";
    }
}

echo "\n📈 Migration Status: $passed/$total checks passed\n";

if ($passed === $total) {
    echo "\n🎉 Migration completed successfully!\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Update your index.php to use enhanced_index.php\n";
    echo "2. Configure FingerprintJS Pro (optional)\n";
    echo "3. Access admin panel: /admin/rules.php?password=YOUR_PASSWORD\n";
    echo "4. View analytics: /admin/analytics.php?password=YOUR_PASSWORD\n";
    echo "5. Test enhanced features with sample traffic\n";
} else {
    echo "\n⚠️  Migration completed with issues. Please resolve the failed checks above.\n";
}

echo "\n📚 Documentation: See ENHANCED_DEPLOYMENT.md for detailed instructions\n";
echo "\n🔗 Admin URLs:\n";
echo "   - Rules Management: /admin/rules.php?password=YOUR_PASSWORD\n";
echo "   - Enhanced Analytics: /admin/analytics.php?password=YOUR_PASSWORD\n";
echo "   - Original Stats: /admin/statistics.php?password=YOUR_PASSWORD\n";

echo "\n✨ Enhanced YellowCloaker is ready to use!\n";
?>