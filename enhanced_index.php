<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once 'enhanced_core.php';
require_once 'settings.php';
require_once 'db.php';
require_once 'main.php';

// Check if enhanced features are enabled
if ($enhanced_enabled) {
    // Enhanced configuration
    $enhancedConfig = [
        'fpjs_api_key' => $fpjs_api_key,
        'fpjs_public_key' => $fpjs_public_key,
        'enable_fingerprinting' => $enhanced_fingerprinting,
        'max_visits_72h' => $max_visits_72h
    ];
    
    // Create enhanced cloaker instance
    $cloaker = new EnhancedCloaker(
        $os_white, $country_white, $lang_white, $ip_black_filename, $ip_black_cidr, 
        $tokens_black, $url_should_contain, $ua_black, $isp_black, 
        $block_without_referer, $referer_stopwords, $block_vpnandtor, 
        $enhancedConfig
    );
    
    // Use enhanced check method
    $checkMethod = 'enhancedCheck';
} else {
    // Fallback to original cloaker
    $cloaker = new Cloaker(
        $os_white, $country_white, $lang_white, $ip_black_filename, $ip_black_cidr, 
        $tokens_black, $url_should_contain, $ua_black, $isp_black, 
        $block_without_referer, $referer_stopwords, $block_vpnandtor
    );
    
    $checkMethod = 'check';
}

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($tds_mode=='full') {
    if ($enhanced_enabled && method_exists($cloaker, 'logEnhancedClick')) {
        $cloaker->logEnhancedClick('white', ['fullcloak']);
    } else {
        add_white_click($cloaker->detect, ['fullcloak']);
    }
    white(false);
    return;
}

//если используются js-проверки, то сначала используются они
//проверка же обычная идёт далее в файле js/jsprocessing.php
if ($use_js_checks===true) {
    // Inject FingerprintJS script if enabled
    if ($enhanced_enabled && method_exists($cloaker, 'getFingerprintScript')) {
        $fingerprintScript = $cloaker->getFingerprintScript();
        if (!empty($fingerprintScript)) {
            // Add script to be injected in white page
            global $additional_scripts;
            $additional_scripts = ($additional_scripts ?? '') . $fingerprintScript;
        }
    }
    white(true);
}
else{
    //Проверяем зашедшего пользователя
    $check_result = $cloaker->$checkMethod();

    if ($check_result == 0 || $tds_mode==='off') { //Обычный юзверь или отключена фильтрация
        // Enhanced logging for black clicks
        if ($enhanced_enabled && method_exists($cloaker, 'logEnhancedClick')) {
            $cloaker->logEnhancedClick('black');
        }
        black($cloaker->detect);
        return;
    } else { //Обнаружили бота или модера
        // Enhanced logging for white clicks
        if ($enhanced_enabled && method_exists($cloaker, 'logEnhancedClick')) {
            $cloaker->logEnhancedClick('white');
        } else {
            add_white_click($cloaker->detect, $cloaker->result);
        }
        white(false);
        return;
    }
}