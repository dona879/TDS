<?php

namespace YellowCloaker\Analytics;

// Include SleekDB Store - this handles all dependencies
require_once __DIR__ . '/../../db/Store.php';

// Manually include all Classes files that seem to be missing from autoload
$classesDir = __DIR__ . '/../../db/Classes/';
foreach (glob($classesDir . '*.php') as $file) {
    require_once $file;
}

use SleekDB\Store;
use Exception;

/**
 * Analytics Engine for YellowCloaker
 * Provides comprehensive traffic and performance analytics
 */
class AnalyticsEngine
{
    private $logsStore;
    private $rulesStore;
    private $dataDir;
    
    public function __construct($dataDir = null)
    {
        $this->dataDir = $dataDir ?: __DIR__ . '/../../logs';
        $this->logsStore = new Store("traffic_logs", $this->dataDir);
        $this->rulesStore = new Store("filter_rules", $this->dataDir);
    }
    
    /**
     * Get traffic statistics
     */
    public function getTrafficStats($timeframe = '24h')
    {
        $timeLimit = $this->getTimeLimit($timeframe);
        
        try {
            $logs = $this->logsStore->findBy(['timestamp', '>=', $timeLimit]);
            
            $stats = [
                'total_requests' => count($logs),
                'blocked_requests' => 0,
                'allowed_requests' => 0,
                'unique_visitors' => [],
                'top_countries' => [],
                'top_devices' => [],
                'top_browsers' => [],
                'hourly_distribution' => []
            ];
            
            foreach ($logs as $log) {
                // Count blocked vs allowed
                if (isset($log['action']) && $log['action'] === 'block') {
                    $stats['blocked_requests']++;
                } else {
                    $stats['allowed_requests']++;
                }
                
                // Track unique visitors
                if (isset($log['visitor_id'])) {
                    $stats['unique_visitors'][$log['visitor_id']] = true;
                }
                
                // Track countries
                if (isset($log['country'])) {
                    $country = $log['country'];
                    $stats['top_countries'][$country] = ($stats['top_countries'][$country] ?? 0) + 1;
                }
                
                // Track devices
                if (isset($log['device_brand'])) {
                    $device = $log['device_brand'];
                    $stats['top_devices'][$device] = ($stats['top_devices'][$device] ?? 0) + 1;
                }
                
                // Track browsers
                if (isset($log['browser_name'])) {
                    $browser = $log['browser_name'];
                    $stats['top_browsers'][$browser] = ($stats['top_browsers'][$browser] ?? 0) + 1;
                }
                
                // Hourly distribution
                if (isset($log['timestamp'])) {
                    $hour = date('H', $log['timestamp']);
                    $stats['hourly_distribution'][$hour] = ($stats['hourly_distribution'][$hour] ?? 0) + 1;
                }
            }
            
            // Convert unique visitors to count
            $stats['unique_visitors'] = count($stats['unique_visitors']);
            
            // Sort top lists
            arsort($stats['top_countries']);
            arsort($stats['top_devices']);
            arsort($stats['top_browsers']);
            ksort($stats['hourly_distribution']);
            
            // Calculate percentages
            if ($stats['total_requests'] > 0) {
                $stats['block_rate'] = round(($stats['blocked_requests'] / $stats['total_requests']) * 100, 2);
                $stats['allow_rate'] = round(($stats['allowed_requests'] / $stats['total_requests']) * 100, 2);
            } else {
                $stats['block_rate'] = 0;
                $stats['allow_rate'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to retrieve traffic stats: ' . $e->getMessage(),
                'total_requests' => 0,
                'blocked_requests' => 0,
                'allowed_requests' => 0,
                'unique_visitors' => 0,
                'block_rate' => 0,
                'allow_rate' => 0
            ];
        }
    }
    
    /**
     * Get device statistics
     */
    public function getDeviceStats($timeframe = '24h')
    {
        $timeLimit = $this->getTimeLimit($timeframe);
        
        try {
            $logs = $this->logsStore->findBy(['timestamp', '>=', $timeLimit]);
            
            $stats = [
                'device_brands' => [],
                'device_models' => [],
                'operating_systems' => [],
                'browsers' => [],
                'screen_resolutions' => [],
                'mobile_vs_desktop' => ['mobile' => 0, 'desktop' => 0, 'tablet' => 0]
            ];
            
            foreach ($logs as $log) {
                // Device brands
                if (isset($log['device_brand'])) {
                    $brand = $log['device_brand'];
                    $stats['device_brands'][$brand] = ($stats['device_brands'][$brand] ?? 0) + 1;
                }
                
                // Device models
                if (isset($log['device_model'])) {
                    $model = $log['device_model'];
                    $stats['device_models'][$model] = ($stats['device_models'][$model] ?? 0) + 1;
                }
                
                // Operating systems
                if (isset($log['os_name']) && isset($log['os_version'])) {
                    $os = $log['os_name'] . ' ' . $log['os_version'];
                    $stats['operating_systems'][$os] = ($stats['operating_systems'][$os] ?? 0) + 1;
                }
                
                // Browsers
                if (isset($log['browser_name']) && isset($log['browser_version'])) {
                    $browser = $log['browser_name'] . ' ' . $log['browser_version'];
                    $stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;
                }
                
                // Screen resolutions
                if (isset($log['screen_width']) && isset($log['screen_height'])) {
                    $resolution = $log['screen_width'] . 'x' . $log['screen_height'];
                    $stats['screen_resolutions'][$resolution] = ($stats['screen_resolutions'][$resolution] ?? 0) + 1;
                }
                
                // Mobile vs Desktop
                if (isset($log['device_type'])) {
                    $type = strtolower($log['device_type']);
                    if (in_array($type, ['mobile', 'desktop', 'tablet'])) {
                        $stats['mobile_vs_desktop'][$type]++;
                    }
                }
            }
            
            // Sort all stats
            arsort($stats['device_brands']);
            arsort($stats['device_models']);
            arsort($stats['operating_systems']);
            arsort($stats['browsers']);
            arsort($stats['screen_resolutions']);
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to retrieve device stats: ' . $e->getMessage(),
                'device_brands' => [],
                'device_models' => [],
                'operating_systems' => [],
                'browsers' => [],
                'screen_resolutions' => [],
                'mobile_vs_desktop' => ['mobile' => 0, 'desktop' => 0, 'tablet' => 0]
            ];
        }
    }
    
    /**
     * Convert timeframe to timestamp
     */
    private function getTimeLimit($timeframe)
    {
        $now = time();
        
        switch ($timeframe) {
            case '1h':
                return $now - 3600;
            case '24h':
                return $now - 86400;
            case '7d':
                return $now - 604800;
            case '30d':
                return $now - 2592000;
            default:
                return $now - 86400; // Default to 24h
        }
    }
}