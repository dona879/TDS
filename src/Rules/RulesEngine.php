<?php

namespace YellowCloaker\Rules;

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
 * Dynamic Rules Engine for YellowCloaker
 */
class RulesEngine
{
    private $rulesStore;
    private $dataDir;
    
    public function __construct($dataDir = null)
    {
        $this->dataDir = $dataDir ?: __DIR__ . '/../../logs';
        $this->rulesStore = new Store("filter_rules", $this->dataDir);
    }
    
    /**
     * Evaluate all active rules against visitor data
     */
    public function evaluateRules($visitorData, $deviceData, $fingerprintData = null)
    {
        $activeRules = $this->rulesStore->findBy(['status', '=', 'active']);
        $matchedRules = [];
        $blockReasons = [];
        
        foreach ($activeRules as $rule) {
            if ($this->evaluateRule($rule, $visitorData, $deviceData, $fingerprintData)) {
                $matchedRules[] = $rule;
                $blockReasons[] = $rule['name'];
                
                // Update rule statistics
                $this->updateRuleStats($rule['_id']);
                
                // If rule has priority and should stop evaluation
                if ($rule['priority'] === 'high' && $rule['stop_on_match']) {
                    break;
                }
            }
        }
        
        return [
            'should_block' => !empty($matchedRules),
            'matched_rules' => $matchedRules,
            'block_reasons' => $blockReasons
        ];
    }
    
    /**
     * Evaluate a single rule
     */
    private function evaluateRule($rule, $visitorData, $deviceData, $fingerprintData)
    {
        $conditions = $rule['conditions'];
        $operator = $rule['operator'] ?? 'AND'; // AND or OR
        
        $results = [];
        
        foreach ($conditions as $condition) {
            $results[] = $this->evaluateCondition($condition, $visitorData, $deviceData, $fingerprintData);
        }
        
        // Apply operator
        if ($operator === 'OR') {
            return in_array(true, $results);
        } else {
            return !in_array(false, $results);
        }
    }
    
    /**
     * Evaluate a single condition
     */
    private function evaluateCondition($condition, $visitorData, $deviceData, $fingerprintData)
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        // Get actual value from data
        $actualValue = $this->getFieldValue($field, $visitorData, $deviceData, $fingerprintData);
        
        // Evaluate based on operator
        switch ($operator) {
            case 'equals':
                return $actualValue == $value;
            case 'not_equals':
                return $actualValue != $value;
            case 'contains':
                return stripos($actualValue, $value) !== false;
            case 'not_contains':
                return stripos($actualValue, $value) === false;
            case 'starts_with':
                return stripos($actualValue, $value) === 0;
            case 'ends_with':
                return substr(strtolower($actualValue), -strlen($value)) === strtolower($value);
            case 'regex':
                return preg_match($value, $actualValue);
            case 'greater_than':
                return floatval($actualValue) > floatval($value);
            case 'less_than':
                return floatval($actualValue) < floatval($value);
            case 'in_list':
                $list = is_array($value) ? $value : explode(',', $value);
                return in_array($actualValue, array_map('trim', $list));
            case 'not_in_list':
                $list = is_array($value) ? $value : explode(',', $value);
                return !in_array($actualValue, array_map('trim', $list));
            default:
                return false;
        }
    }
    
    /**
     * Get field value from visitor data
     */
    private function getFieldValue($field, $visitorData, $deviceData, $fingerprintData)
    {
        // Device data fields
        if (strpos($field, 'device.') === 0) {
            $key = substr($field, 7);
            return $deviceData[$key] ?? '';
        }
        
        // Fingerprint data fields
        if (strpos($field, 'fingerprint.') === 0 && $fingerprintData) {
            $key = substr($field, 12);
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $value = $fingerprintData;
                foreach ($parts as $part) {
                    $value = $value[$part] ?? '';
                }
                return $value;
            }
            return $fingerprintData[$key] ?? '';
        }
        
        // Visitor data fields (legacy compatibility)
        return $visitorData[$field] ?? '';
    }
    
    /**
     * Create a new rule
     */
    public function createRule($data)
    {
        $rule = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'conditions' => $data['conditions'],
            'operator' => $data['operator'] ?? 'AND',
            'action' => $data['action'] ?? 'block',
            'priority' => $data['priority'] ?? 'medium',
            'stop_on_match' => $data['stop_on_match'] ?? false,
            'status' => $data['status'] ?? 'active',
            'created_at' => time(),
            'updated_at' => time(),
            'stats' => [
                'matches' => 0,
                'last_match' => null
            ]
        ];
        
        return $this->rulesStore->insert($rule);
    }
    
    /**
     * Update a rule
     */
    public function updateRule($id, $data)
    {
        $rule = $this->rulesStore->findById($id);
        if (!$rule) {
            return false;
        }
        
        $rule['name'] = $data['name'] ?? $rule['name'];
        $rule['description'] = $data['description'] ?? $rule['description'];
        $rule['conditions'] = $data['conditions'] ?? $rule['conditions'];
        $rule['operator'] = $data['operator'] ?? $rule['operator'];
        $rule['action'] = $data['action'] ?? $rule['action'];
        $rule['priority'] = $data['priority'] ?? $rule['priority'];
        $rule['stop_on_match'] = $data['stop_on_match'] ?? $rule['stop_on_match'];
        $rule['status'] = $data['status'] ?? $rule['status'];
        $rule['updated_at'] = time();
        
        return $this->rulesStore->update($rule);
    }
    
    /**
     * Delete a rule
     */
    public function deleteRule($id)
    {
        return $this->rulesStore->deleteById($id);
    }
    
    /**
     * Get all rules
     */
    public function getAllRules()
    {
        try {
            // First check if the store has any data
            $dataPath = $this->dataDir . '/filter_rules';
            if (!is_dir($dataPath)) {
                return [];
            }
            
            $files = glob($dataPath . '/*.json');
            if (empty($files)) {
                return [];
            }
            
            return $this->rulesStore->findAll();
        } catch (Exception $e) {
            // If there's an error, return empty array
            return [];
        }
    }
    
    /**
     * Get rule by ID
     */
    public function getRule($id)
    {
        return $this->rulesStore->findById($id);
    }
    
    /**
     * Update rule statistics
     */
    private function updateRuleStats($ruleId)
    {
        $rule = $this->rulesStore->findById($ruleId);
        if ($rule) {
            $rule['stats']['matches']++;
            $rule['stats']['last_match'] = time();
            $this->rulesStore->update($rule);
        }
    }
    
    /**
     * Get rule statistics
     */
    public function getRuleStats($days = 30)
    {
        $rules = $this->rulesStore->findAll();
        $stats = [];
        
        foreach ($rules as $rule) {
            $stats[] = [
                'id' => $rule['_id'],
                'name' => $rule['name'],
                'matches' => $rule['stats']['matches'] ?? 0,
                'last_match' => $rule['stats']['last_match'] ?? null,
                'status' => $rule['status']
            ];
        }
        
        // Sort by matches descending
        usort($stats, function($a, $b) {
            return $b['matches'] - $a['matches'];
        });
        
        return $stats;
    }
    
    /**
     * Get available fields for rule conditions
     */
    public function getAvailableFields()
    {
        return [
            'visitor' => [
                'ip' => 'IP Address',
                'country' => 'Country',
                'isp' => 'ISP',
                'os' => 'Operating System',
                'ua' => 'User Agent',
                'referer' => 'Referer'
            ],
            'device' => [
                'device.device_brand' => 'Device Brand',
                'device.device_model' => 'Device Model',
                'device.device_type' => 'Device Type',
                'device.os_name' => 'OS Name',
                'device.os_version' => 'OS Version',
                'device.browser_name' => 'Browser Name',
                'device.browser_version' => 'Browser Version',
                'device.is_bot' => 'Is Bot',
                'device.detection_confidence' => 'Detection Confidence'
            ],
            'fingerprint' => [
                'fingerprint.visitor_id' => 'Visitor ID',
                'fingerprint.confidence' => 'Fingerprint Confidence',
                'fingerprint.bot_probability' => 'Bot Probability',
                'fingerprint.vpn' => 'VPN Usage',
                'fingerprint.proxy' => 'Proxy Usage',
                'fingerprint.tor' => 'Tor Usage',
                'fingerprint.incognito' => 'Incognito Mode',
                'fingerprint.virtual_machine' => 'Virtual Machine',
                'fingerprint.jailbroken' => 'Jailbroken Device',
                'fingerprint.device.timezone' => 'Timezone',
                'fingerprint.device.language' => 'Language',
                'fingerprint.device.platform' => 'Platform'
            ]
        ];
    }
    
    /**
     * Get available operators
     */
    public function getAvailableOperators()
    {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'contains' => 'Contains',
            'not_contains' => 'Does Not Contain',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'regex' => 'Regular Expression',
            'greater_than' => 'Greater Than',
            'less_than' => 'Less Than',
            'in_list' => 'In List',
            'not_in_list' => 'Not In List'
        ];
    }
}