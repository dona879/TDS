<?php

require_once 'vendor/autoload.php';
require_once 'core.php';
require_once 'db.php';

use YellowCloaker\DeviceDetector\EnhancedDeviceDetector;
use YellowCloaker\Fingerprint\FingerprintJSClient;
use YellowCloaker\Rules\RulesEngine;

/**
 * Enhanced Cloaker with advanced device detection, fingerprinting, and dynamic rules
 */
class EnhancedCloaker extends Cloaker
{
    private $deviceDetector;
    private $fingerprintClient;
    private $rulesEngine;
    private $enhancedDetect = [];
    private $fingerprintData = null;
    
    // Configuration
    private $fpjsApiKey;
    private $fpjsPublicKey;
    private $enableFingerprinting;
    private $maxVisitsIn72h;
    
    public function __construct($os_white, $country_white, $lang_white, $ip_black_filename, $ip_black_cidr, $tokens_black, $url_should_contain, $ua_black, $isp_black, $block_without_referer, $referer_stopwords, $block_vpnandtor, $config = [])
    {
        // Initialize parent
        parent::__construct($os_white, $country_white, $lang_white, $ip_black_filename, $ip_black_cidr, $tokens_black, $url_should_contain, $ua_black, $isp_black, $block_without_referer, $referer_stopwords, $block_vpnandtor);
        
        // Enhanced configuration
        $this->fpjsApiKey = $config['fpjs_api_key'] ?? '';
        $this->fpjsPublicKey = $config['fpjs_public_key'] ?? '';
        $this->enableFingerprinting = $config['enable_fingerprinting'] ?? false;
        $this->maxVisitsIn72h = $config['max_visits_72h'] ?? 5;
        
        // Initialize enhanced components
        $this->deviceDetector = new EnhancedDeviceDetector();
        $this->rulesEngine = new RulesEngine();
        
        if ($this->enableFingerprinting && !empty($this->fpjsApiKey)) {
            $this->fingerprintClient = new FingerprintJSClient($this->fpjsApiKey);
        }
        
        // Perform enhanced detection
        $this->enhancedDetect();
    }
    
    /**
     * Enhanced detection with device fingerprinting
     */
    public function enhancedDetect()
    {
        // Get basic detection from parent
        parent::detect();
        
        // Enhanced device detection
        $deviceInfo = $this->deviceDetector->detect();
        
        // Process fingerprint data if available
        $requestId = $_POST['fpjs_request_id'] ?? $_GET['fpjs_request_id'] ?? null;
        if ($this->fingerprintClient && $requestId) {
            $this->fingerprintData = $this->fingerprintClient->getVisitorData($requestId);
        }
        
        // Merge all detection data
        $this->enhancedDetect = array_merge($this->detect, [
            'enhanced_device' => $deviceInfo,
            'fingerprint' => $this->fingerprintData,
            'detection_timestamp' => time(),
            'client_hints_available' => !empty(array_filter($deviceInfo['client_hints'] ?? [])),
            'fingerprint_available' => !empty($this->fingerprintData)
        ]);
    }
    
    /**
     * Enhanced check with dynamic rules and advanced detection
     */
    public function enhancedCheck()
    {
        $result = 0;
        $blockReasons = [];
        
        // Run original checks first
        $originalResult = parent::check();
        if ($originalResult > 0) {
            $result = 1;
            $blockReasons = array_merge($blockReasons, $this->result);
        }
        
        // Enhanced device-based checks
        $deviceChecks = $this->checkEnhancedDevice();
        if ($deviceChecks['should_block']) {
            $result = 1;
            $blockReasons = array_merge($blockReasons, $deviceChecks['reasons']);
        }
        
        // Fingerprint-based checks
        if ($this->fingerprintData) {
            $fingerprintChecks = $this->checkFingerprint();
            if ($fingerprintChecks['should_block']) {
                $result = 1;
                $blockReasons = array_merge($blockReasons, $fingerprintChecks['reasons']);
            }
        }
        
        // Dynamic rules evaluation
        $rulesResult = $this->rulesEngine->evaluateRules(
            $this->detect,
            $this->enhancedDetect['enhanced_device'],
            $this->fingerprintData
        );
        
        if ($rulesResult['should_block']) {
            $result = 1;
            $blockReasons = array_merge($blockReasons, $rulesResult['block_reasons']);
        }
        
        // Update result with all reasons
        $this->result = array_unique($blockReasons);
        
        return $result;
    }
    
    /**
     * Enhanced device-based checks
     */
    private function checkEnhancedDevice()
    {
        $deviceInfo = $this->enhancedDetect['enhanced_device'];
        $reasons = [];
        
        // Bot detection with higher confidence
        if ($deviceInfo['is_bot'] && $deviceInfo['detection_confidence'] > 80) {
            $reasons[] = 'enhanced_bot_detection';
        }
        
        // Suspicious device patterns
        if ($deviceInfo['device_brand'] === 'Unknown' && 
            $deviceInfo['device_model'] === 'Unknown' && 
            $deviceInfo['detection_confidence'] < 50) {
            $reasons[] = 'suspicious_device_pattern';
        }
        
        // Client Hints inconsistency
        if ($this->detectClientHintsInconsistency($deviceInfo)) {
            $reasons[] = 'client_hints_inconsistency';
        }
        
        return [
            'should_block' => !empty($reasons),
            'reasons' => $reasons
        ];
    }
    
    /**
     * Fingerprint-based checks
     */
    private function checkFingerprint()
    {
        if (!$this->fingerprintClient || !$this->fingerprintData) {
            return ['should_block' => false, 'reasons' => []];
        }
        
        return $this->fingerprintClient->shouldBlockVisitor(
            $this->fingerprintData, 
            $this->maxVisitsIn72h
        );
    }
    
    /**
     * Detect Client Hints inconsistency (potential spoofing)
     */
    private function detectClientHintsInconsistency($deviceInfo)
    {
        $hints = $deviceInfo['client_hints'];
        
        // Check if platform from Client Hints matches detected OS
        if (!empty($hints['sec_ch_ua_platform'])) {
            $chPlatform = strtolower(trim($hints['sec_ch_ua_platform'], '"'));
            $detectedOS = strtolower($deviceInfo['os_name']);
            
            $platformMap = [
                'windows' => 'windows',
                'macos' => 'mac',
                'linux' => 'linux',
                'android' => 'android',
                'ios' => 'ios'
            ];
            
            if (isset($platformMap[$chPlatform]) && 
                strpos($detectedOS, $platformMap[$chPlatform]) === false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get enhanced detection data
     */
    public function getEnhancedDetect()
    {
        return $this->enhancedDetect;
    }
    
    /**
     * Get fingerprint data
     */
    public function getFingerprintData()
    {
        return $this->fingerprintData;
    }
    
    /**
     * Generate FingerprintJS client script
     */
    public function getFingerprintScript()
    {
        if (!$this->fingerprintClient || empty($this->fpjsPublicKey)) {
            return '';
        }
        
        return $this->fingerprintClient->generateClientScript($this->fpjsPublicKey);
    }
    
    /**
     * Get rules engine instance
     */
    public function getRulesEngine()
    {
        return $this->rulesEngine;
    }
    
    /**
     * Add enhanced click logging
     */
    public function logEnhancedClick($type = 'black', $additionalData = [])
    {
        $logData = array_merge($this->enhancedDetect, $additionalData, [
            'click_type' => $type,
            'block_reasons' => $this->result,
            'logged_at' => time()
        ]);
        
        // Use existing logging system but with enhanced data
        if ($type === 'black') {
            $this->logEnhancedBlackClick($logData);
        } else {
            $this->logEnhancedWhiteClick($logData);
        }
    }
    
    /**
     * Enhanced black click logging
     */
    private function logEnhancedBlackClick($data)
    {
        $dataDir = __DIR__ . "/logs";
        $store = new \SleekDB\Store("enhanced_black_clicks", $dataDir);
        
        $click = [
            'subid' => $data['subid'] ?? '',
            'time' => time(),
            'ip' => $data['ip'],
            'country' => $data['country'],
            'os' => $data['os'],
            'isp' => $data['isp'],
            'ua' => $data['ua'],
            'referer' => $data['referer'] ?? '',
            
            // Enhanced device data
            'device_brand' => $data['enhanced_device']['device_brand'] ?? '',
            'device_model' => $data['enhanced_device']['device_model'] ?? '',
            'device_type' => $data['enhanced_device']['device_type'] ?? '',
            'os_version' => $data['enhanced_device']['os_version'] ?? '',
            'browser_name' => $data['enhanced_device']['browser_name'] ?? '',
            'browser_version' => $data['enhanced_device']['browser_version'] ?? '',
            'detection_confidence' => $data['enhanced_device']['detection_confidence'] ?? 0,
            'is_bot_enhanced' => $data['enhanced_device']['is_bot'] ?? false,
            
            // Fingerprint data
            'visitor_id' => $data['fingerprint']['visitor_id'] ?? null,
            'fingerprint_confidence' => $data['fingerprint']['confidence'] ?? 0,
            'bot_probability' => $data['fingerprint']['bot_probability'] ?? 0,
            'vpn' => $data['fingerprint']['vpn'] ?? false,
            'proxy' => $data['fingerprint']['proxy'] ?? false,
            'tor' => $data['fingerprint']['tor'] ?? false,
            'incognito' => $data['fingerprint']['incognito'] ?? false,
            'virtual_machine' => $data['fingerprint']['virtual_machine'] ?? false,
            'visit_frequency_72h' => 0, // Will be updated by background process
            
            // Additional metadata
            'client_hints_available' => $data['client_hints_available'] ?? false,
            'fingerprint_available' => $data['fingerprint_available'] ?? false,
            
            'preland' => $data['preland'] ?? 'unknown',
            'land' => $data['land'] ?? 'unknown',
            'subs' => $data['subs'] ?? []
        ];
        
        return $store->insert($click);
    }
    
    /**
     * Enhanced white click logging
     */
    private function logEnhancedWhiteClick($data)
    {
        $dataDir = __DIR__ . "/logs";
        $store = new \SleekDB\Store("enhanced_white_clicks", $dataDir);
        
        $click = [
            'time' => time(),
            'ip' => $data['ip'],
            'country' => $data['country'],
            'os' => $data['os'],
            'isp' => $data['isp'],
            'ua' => $data['ua'],
            'referer' => $data['referer'] ?? '',
            'reason' => $data['block_reasons'],
            
            // Enhanced device data
            'device_brand' => $data['enhanced_device']['device_brand'] ?? '',
            'device_model' => $data['enhanced_device']['device_model'] ?? '',
            'device_type' => $data['enhanced_device']['device_type'] ?? '',
            'os_version' => $data['enhanced_device']['os_version'] ?? '',
            'browser_name' => $data['enhanced_device']['browser_name'] ?? '',
            'browser_version' => $data['enhanced_device']['browser_version'] ?? '',
            'detection_confidence' => $data['enhanced_device']['detection_confidence'] ?? 0,
            'is_bot_enhanced' => $data['enhanced_device']['is_bot'] ?? false,
            
            // Fingerprint data
            'visitor_id' => $data['fingerprint']['visitor_id'] ?? null,
            'fingerprint_confidence' => $data['fingerprint']['confidence'] ?? 0,
            'bot_probability' => $data['fingerprint']['bot_probability'] ?? 0,
            'vpn' => $data['fingerprint']['vpn'] ?? false,
            'proxy' => $data['fingerprint']['proxy'] ?? false,
            'tor' => $data['fingerprint']['tor'] ?? false,
            'visit_frequency_72h' => 0, // Will be updated by background process
            
            'subs' => $data['subs'] ?? []
        ];
        
        return $store->insert($click);
    }
}