<?php

namespace YellowCloaker\Fingerprint;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * FingerprintJS Pro Server-Side API Client
 */
class FingerprintJSClient
{
    private $apiKey;
    private $client;
    private $baseUrl = 'https://api.fpjs.io';
    private $cacheDir;
    
    public function __construct($apiKey, $cacheDir = null)
    {
        $this->apiKey = $apiKey;
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../../cache/fingerprint';
        $this->ensureCacheDir();
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 5.0,
            'headers' => [
                'Auth-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    /**
     * Get visitor information by request ID
     */
    public function getVisitorData($requestId)
    {
        if (empty($requestId)) {
            return null;
        }
        
        // Check cache first
        $cached = $this->getFromCache($requestId);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $response = $this->client->get("/visitors/{$requestId}");
            $data = json_decode($response->getBody(), true);
            
            if (isset($data['visits']) && !empty($data['visits'])) {
                $visitorData = $this->processVisitorData($data);
                $this->saveToCache($requestId, $visitorData);
                return $visitorData;
            }
            
        } catch (RequestException $e) {
            // Log error but don't break the flow
            error_log("FingerprintJS API Error: " . $e->getMessage());
            return null;
        }
        
        return null;
    }
    
    /**
     * Process and normalize visitor data
     */
    private function processVisitorData($data)
    {
        $visit = $data['visits'][0] ?? [];
        
        return [
            'visitor_id' => $visit['visitorId'] ?? null,
            'request_id' => $visit['requestId'] ?? null,
            'confidence' => $visit['confidence']['score'] ?? 0,
            'ip' => $visit['ip'] ?? null,
            'ip_location' => $visit['ipLocation'] ?? [],
            'browser_details' => $visit['browserDetails'] ?? [],
            'incognito' => $visit['incognito'] ?? false,
            'timestamp' => $visit['timestamp'] ?? null,
            
            // Bot detection
            'bot_probability' => $visit['botProbability'] ?? 0,
            'is_bot' => ($visit['botProbability'] ?? 0) > 0.5,
            
            // Device info
            'device' => [
                'screen' => $visit['browserDetails']['screen'] ?? null,
                'timezone' => $visit['browserDetails']['timezone'] ?? null,
                'language' => $visit['browserDetails']['language'] ?? null,
                'platform' => $visit['browserDetails']['platform'] ?? null,
                'user_agent' => $visit['browserDetails']['userAgent'] ?? null,
            ],
            
            // Fraud signals
            'vpn' => $visit['vpn'] ?? false,
            'proxy' => $visit['proxy'] ?? false,
            'tor' => $visit['tor'] ?? false,
            'tampering' => $visit['tampering'] ?? [],
            'cloned_app' => $visit['clonedApp'] ?? false,
            'factory_reset' => $visit['factoryReset'] ?? false,
            'jailbroken' => $visit['jailbroken'] ?? false,
            'frida' => $visit['frida'] ?? false,
            'privacy_settings' => $visit['privacySettings'] ?? false,
            'virtual_machine' => $visit['virtualMachine'] ?? false,
            'raw_device_attributes' => $visit['rawDeviceAttributes'] ?? [],
            
            // Visit history
            'first_seen_at' => $visit['firstSeenAt'] ?? null,
            'last_seen_at' => $visit['lastSeenAt'] ?? null,
            
            // Processed at
            'processed_at' => time()
        ];
    }
    
    /**
     * Get visitor frequency (how many times seen in last 72 hours)
     */
    public function getVisitorFrequency($visitorId, $hours = 72)
    {
        if (empty($visitorId)) {
            return 0;
        }
        
        try {
            $since = time() - ($hours * 3600);
            $response = $this->client->get("/visitors/{$visitorId}", [
                'query' => [
                    'limit' => 500,
                    'before' => time() * 1000 // FingerprintJS uses milliseconds
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if (isset($data['visits'])) {
                $recentVisits = array_filter($data['visits'], function($visit) use ($since) {
                    $visitTime = isset($visit['timestamp']) ? $visit['timestamp'] / 1000 : 0;
                    return $visitTime >= $since;
                });
                
                return count($recentVisits);
            }
            
        } catch (RequestException $e) {
            error_log("FingerprintJS Frequency API Error: " . $e->getMessage());
            return 0;
        }
        
        return 0;
    }
    
    /**
     * Check if visitor should be blocked based on frequency and fraud signals
     */
    public function shouldBlockVisitor($visitorData, $maxVisitsIn72h = 5)
    {
        if (!$visitorData) {
            return false;
        }
        
        $blockReasons = [];
        
        // Check bot probability
        if ($visitorData['bot_probability'] > 0.7) {
            $blockReasons[] = 'high_bot_probability';
        }
        
        // Check fraud signals
        if ($visitorData['vpn']) $blockReasons[] = 'vpn';
        if ($visitorData['proxy']) $blockReasons[] = 'proxy';
        if ($visitorData['tor']) $blockReasons[] = 'tor';
        if ($visitorData['virtual_machine']) $blockReasons[] = 'virtual_machine';
        if ($visitorData['jailbroken']) $blockReasons[] = 'jailbroken';
        if ($visitorData['frida']) $blockReasons[] = 'frida';
        
        // Check tampering
        if (!empty($visitorData['tampering'])) {
            $blockReasons[] = 'tampering';
        }
        
        // Check visit frequency
        if ($visitorData['visitor_id']) {
            $frequency = $this->getVisitorFrequency($visitorData['visitor_id']);
            if ($frequency > $maxVisitsIn72h) {
                $blockReasons[] = 'high_frequency';
            }
        }
        
        return [
            'should_block' => !empty($blockReasons),
            'reasons' => $blockReasons,
            'frequency' => $frequency ?? 0
        ];
    }
    
    /**
     * Generate JavaScript code for client-side fingerprinting
     */
    public function generateClientScript($publicKey, $endpoint = null)
    {
        $endpoint = $endpoint ?: '/js/process.php';
        
        return "
        <script>
        (function() {
            // Load FingerprintJS Pro
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs-pro@3/dist/fp.min.js';
            script.onload = function() {
                // Initialize FingerprintJS Pro
                FingerprintJS.load({
                    token: '{$publicKey}',
                    region: 'us'
                }).then(fp => {
                    // Get visitor identifier
                    return fp.get();
                }).then(result => {
                    // Send to server for processing
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{$endpoint}', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.send(JSON.stringify({
                        requestId: result.requestId,
                        visitorId: result.visitorId,
                        confidence: result.confidence
                    }));
                }).catch(error => {
                    console.error('FingerprintJS Error:', error);
                });
            };
            document.head.appendChild(script);
        })();
        </script>";
    }
    
    /**
     * Cache management
     */
    private function getFromCache($key)
    {
        $cacheFile = $this->cacheDir . '/' . md5($key) . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Cache valid for 1 hour
        if (filemtime($cacheFile) < time() - 3600) {
            unlink($cacheFile);
            return null;
        }
        
        $data = file_get_contents($cacheFile);
        return json_decode($data, true);
    }
    
    private function saveToCache($key, $data)
    {
        $cacheFile = $this->cacheDir . '/' . md5($key) . '.json';
        file_put_contents($cacheFile, json_encode($data));
    }
    
    private function ensureCacheDir()
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Clear cache
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