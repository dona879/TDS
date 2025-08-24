<?php

namespace YellowCloaker\DeviceDetector;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

/**
 * Enhanced Device Detector with HTTP Client Hints support and caching
 */
class EnhancedDeviceDetector
{
    private $detector;
    private $cacheDir;
    private $clientHints;
    
    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../../cache/device_detector';
        $this->ensureCacheDir();
        $this->collectClientHints();
    }
    
    /**
     * Detect device information with enhanced accuracy
     */
    public function detect($userAgent = null)
    {
        $userAgent = $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check cache first
        $cacheKey = $this->getCacheKey($userAgent);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Initialize device detector
        $this->detector = new DeviceDetector($userAgent);
        $this->detector->parse();
        
        // Build enhanced device info
        $deviceInfo = $this->buildDeviceInfo();
        
        // Cache the result
        $this->saveToCache($cacheKey, $deviceInfo);
        
        return $deviceInfo;
    }
    
    /**
     * Build comprehensive device information
     */
    private function buildDeviceInfo()
    {
        $info = [
            // Basic detection
            'user_agent' => $this->detector->getUserAgent(),
            'is_bot' => $this->detector->isBot(),
            'bot_name' => $this->detector->getBot()['name'] ?? null,
            
            // Device info
            'device_type' => $this->detector->getDeviceName(),
            'device_brand' => $this->detector->getBrandName(),
            'device_model' => $this->detector->getModel(),
            
            // OS info
            'os_name' => $this->detector->getOs('name'),
            'os_version' => $this->detector->getOs('version'),
            'os_platform' => $this->detector->getOs('platform'),
            
            // Browser info
            'browser_name' => $this->detector->getClient('name'),
            'browser_version' => $this->detector->getClient('version'),
            'browser_engine' => $this->detector->getClient('engine'),
            'browser_type' => $this->detector->getClient('type'),
            
            // Enhanced info from Client Hints
            'client_hints' => $this->clientHints,
            
            // Detection confidence
            'detection_confidence' => $this->calculateConfidence(),
            
            // Timestamp
            'detected_at' => time()
        ];
        
        // Enhance with Client Hints data
        $info = $this->enhanceWithClientHints($info);
        
        return $info;
    }
    
    /**
     * Collect HTTP Client Hints for enhanced detection
     */
    private function collectClientHints()
    {
        $this->clientHints = [
            'sec_ch_ua' => $_SERVER['HTTP_SEC_CH_UA'] ?? null,
            'sec_ch_ua_mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? null,
            'sec_ch_ua_platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? null,
            'sec_ch_ua_platform_version' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM_VERSION'] ?? null,
            'sec_ch_ua_arch' => $_SERVER['HTTP_SEC_CH_UA_ARCH'] ?? null,
            'sec_ch_ua_model' => $_SERVER['HTTP_SEC_CH_UA_MODEL'] ?? null,
            'sec_ch_ua_bitness' => $_SERVER['HTTP_SEC_CH_UA_BITNESS'] ?? null,
            'sec_ch_ua_full_version' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION'] ?? null,
            'sec_ch_ua_full_version_list' => $_SERVER['HTTP_SEC_CH_UA_FULL_VERSION_LIST'] ?? null,
        ];
    }
    
    /**
     * Enhance device info with Client Hints data
     */
    private function enhanceWithClientHints($info)
    {
        // Override with more accurate Client Hints data when available
        if (!empty($this->clientHints['sec_ch_ua_platform'])) {
            $platform = trim($this->clientHints['sec_ch_ua_platform'], '"');
            if ($platform) {
                $info['os_name_ch'] = $platform;
            }
        }
        
        if (!empty($this->clientHints['sec_ch_ua_platform_version'])) {
            $version = trim($this->clientHints['sec_ch_ua_platform_version'], '"');
            if ($version) {
                $info['os_version_ch'] = $version;
            }
        }
        
        if (!empty($this->clientHints['sec_ch_ua_model'])) {
            $model = trim($this->clientHints['sec_ch_ua_model'], '"');
            if ($model && $model !== 'Unknown') {
                $info['device_model_ch'] = $model;
            }
        }
        
        if (!empty($this->clientHints['sec_ch_ua_mobile'])) {
            $info['is_mobile_ch'] = $this->clientHints['sec_ch_ua_mobile'] === '?1';
        }
        
        return $info;
    }
    
    /**
     * Calculate detection confidence based on available data
     */
    private function calculateConfidence()
    {
        $confidence = 0;
        
        // Base confidence from user agent parsing
        if ($this->detector->getDeviceName() !== 'desktop') $confidence += 20;
        if ($this->detector->getBrandName()) $confidence += 20;
        if ($this->detector->getModel()) $confidence += 20;
        if ($this->detector->getOs('name')) $confidence += 20;
        if ($this->detector->getClient('name')) $confidence += 20;
        
        // Bonus for Client Hints availability
        $hintsAvailable = array_filter($this->clientHints);
        $confidence += min(count($hintsAvailable) * 2, 20);
        
        return min($confidence, 100);
    }
    
    /**
     * Generate cache key for user agent
     */
    private function getCacheKey($userAgent)
    {
        $hintsKey = md5(serialize($this->clientHints));
        return md5($userAgent . $hintsKey);
    }
    
    /**
     * Get cached detection result
     */
    private function getFromCache($key)
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Check if cache is still valid (24 hours)
        if (filemtime($cacheFile) < time() - 86400) {
            unlink($cacheFile);
            return null;
        }
        
        $data = file_get_contents($cacheFile);
        return json_decode($data, true);
    }
    
    /**
     * Save detection result to cache
     */
    private function saveToCache($key, $data)
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json';
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDir()
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Clear cache (for maintenance)
     */
    public function clearCache()
    {
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}