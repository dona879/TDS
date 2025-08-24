<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации

if (version_compare(phpversion(), '7.2.0', '<')) {
    die("PHP version should be 7.2 or higher! Change your PHP version and return.");
}

require_once '../settings.php';
require_once 'password.php';
check_password();

// Check if enhanced features are enabled
if (!$enhanced_enabled) {
    die("Enhanced analytics is not enabled. Please enable it in settings.");
}

require_once '../vendor/autoload.php';
require_once 'db.php';

use SleekDB\Store;

date_default_timezone_set($stats_timezone);

$startdate = isset($_GET['startdate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'], new DateTimeZone($stats_timezone)) :
    new DateTime("7 days ago", new DateTimeZone($stats_timezone));
$enddate = isset($_GET['enddate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'], new DateTimeZone($stats_timezone)) :
    new DateTime("now", new DateTimeZone($stats_timezone));
$startdate->setTime(0, 0, 0);
$enddate->setTime(23, 59, 59);

$date_str = '';
if (isset($_GET['startdate']) && isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str = "&startdate={$startstr}&enddate={$endstr}";
}

$dataDir = __DIR__ . "/../logs";

// Get enhanced analytics data
function getEnhancedAnalytics($startTimestamp, $endTimestamp) {
    global $dataDir;
    
    $blackStore = new Store("enhanced_black_clicks", $dataDir);
    $whiteStore = new Store("enhanced_white_clicks", $dataDir);
    
    // Get data within date range
    $blackClicks = $blackStore->findBy(['time', '>=', $startTimestamp]);
    $blackClicks = array_filter($blackClicks, function($click) use ($endTimestamp) {
        return $click['time'] <= $endTimestamp;
    });
    
    $whiteClicks = $whiteStore->findBy(['time', '>=', $startTimestamp]);
    $whiteClicks = array_filter($whiteClicks, function($click) use ($endTimestamp) {
        return $click['time'] <= $endTimestamp;
    });
    
    return [
        'black_clicks' => $blackClicks,
        'white_clicks' => $whiteClicks
    ];
}

function generateDeviceAnalytics($clicks) {
    $deviceBrands = [];
    $deviceModels = [];
    $deviceTypes = [];
    $browsers = [];
    $osVersions = [];
    $botDetection = ['bot' => 0, 'human' => 0];
    
    foreach ($clicks as $click) {
        // Device brands
        $brand = $click['device_brand'] ?: 'Unknown';
        $deviceBrands[$brand] = ($deviceBrands[$brand] ?? 0) + 1;
        
        // Device models
        $model = $click['device_model'] ?: 'Unknown';
        $deviceModels[$model] = ($deviceModels[$model] ?? 0) + 1;
        
        // Device types
        $type = $click['device_type'] ?: 'Unknown';
        $deviceTypes[$type] = ($deviceTypes[$type] ?? 0) + 1;
        
        // Browsers
        $browser = $click['browser_name'] ?: 'Unknown';
        $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;
        
        // OS Versions
        $os = ($click['os'] ?: 'Unknown') . ' ' . ($click['os_version'] ?: '');
        $osVersions[trim($os)] = ($osVersions[trim($os)] ?? 0) + 1;
        
        // Bot detection
        if ($click['is_bot_enhanced'] ?? false) {
            $botDetection['bot']++;
        } else {
            $botDetection['human']++;
        }
    }
    
    // Sort by count descending
    arsort($deviceBrands);
    arsort($deviceModels);
    arsort($deviceTypes);
    arsort($browsers);
    arsort($osVersions);
    
    return [
        'device_brands' => array_slice($deviceBrands, 0, 10, true),
        'device_models' => array_slice($deviceModels, 0, 10, true),
        'device_types' => $deviceTypes,
        'browsers' => array_slice($browsers, 0, 10, true),
        'os_versions' => array_slice($osVersions, 0, 10, true),
        'bot_detection' => $botDetection
    ];
}

function generateFingerprintAnalytics($clicks) {
    $visitorFrequency = [];
    $fraudSignals = [
        'vpn' => 0,
        'proxy' => 0,
        'tor' => 0,
        'virtual_machine' => 0,
        'incognito' => 0
    ];
    $confidenceDistribution = ['high' => 0, 'medium' => 0, 'low' => 0];
    $topVisitors = [];
    
    foreach ($clicks as $click) {
        $visitorId = $click['visitor_id'] ?? null;
        
        if ($visitorId) {
            $topVisitors[$visitorId] = ($topVisitors[$visitorId] ?? 0) + 1;
        }
        
        // Fraud signals
        if ($click['vpn'] ?? false) $fraudSignals['vpn']++;
        if ($click['proxy'] ?? false) $fraudSignals['proxy']++;
        if ($click['tor'] ?? false) $fraudSignals['tor']++;
        if ($click['virtual_machine'] ?? false) $fraudSignals['virtual_machine']++;
        if ($click['incognito'] ?? false) $fraudSignals['incognito']++;
        
        // Confidence distribution
        $confidence = $click['fingerprint_confidence'] ?? 0;
        if ($confidence >= 0.8) {
            $confidenceDistribution['high']++;
        } elseif ($confidence >= 0.5) {
            $confidenceDistribution['medium']++;
        } else {
            $confidenceDistribution['low']++;
        }
    }
    
    arsort($topVisitors);
    
    return [
        'fraud_signals' => $fraudSignals,
        'confidence_distribution' => $confidenceDistribution,
        'top_visitors' => array_slice($topVisitors, 0, 20, true),
        'unique_visitors' => count($topVisitors)
    ];
}

function generateTemporalAnalytics($clicks, $startTimestamp, $endTimestamp) {
    $hourlyData = [];
    $dailyData = [];
    
    // Initialize arrays
    for ($i = 0; $i < 24; $i++) {
        $hourlyData[$i] = 0;
    }
    
    $currentDay = $startTimestamp;
    while ($currentDay <= $endTimestamp) {
        $dailyData[date('Y-m-d', $currentDay)] = 0;
        $currentDay += 86400; // Add one day
    }
    
    foreach ($clicks as $click) {
        $timestamp = $click['time'];
        $hour = (int)date('H', $timestamp);
        $day = date('Y-m-d', $timestamp);
        
        $hourlyData[$hour]++;
        if (isset($dailyData[$day])) {
            $dailyData[$day]++;
        }
    }
    
    return [
        'hourly' => $hourlyData,
        'daily' => $dailyData
    ];
}

$analytics = getEnhancedAnalytics($startdate->getTimestamp(), $enddate->getTimestamp());
$deviceAnalytics = generateDeviceAnalytics($analytics['black_clicks']);
$fingerprintAnalytics = generateFingerprintAnalytics($analytics['black_clicks']);
$temporalAnalytics = generateTemporalAnalytics($analytics['black_clicks'], $startdate->getTimestamp(), $enddate->getTimestamp());
$blockedAnalytics = generateDeviceAnalytics($analytics['white_clicks']);

$totalClicks = count($analytics['black_clicks']) + count($analytics['white_clicks']);
$blockRate = $totalClicks > 0 ? (count($analytics['white_clicks']) / $totalClicks) * 100 : 0;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Enhanced Analytics - YellowCloaker</title>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <!-- favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png" />
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet" />
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/nalika-icon.css" />
    <link rel="stylesheet" href="css/main.css" />
    <link rel="stylesheet" href="css/metisMenu/metisMenu.min.css" />
    <link rel="stylesheet" href="css/metisMenu/metisMenu-vertical.css" />
    <link rel="stylesheet" href="css/style.css" />
    
    <style>
        .analytics-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .metric-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            color: white;
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .fraud-signal {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 2px;
        }
        .fraud-signal.vpn { background: #dc3545; color: white; }
        .fraud-signal.proxy { background: #fd7e14; color: white; }
        .fraud-signal.tor { background: #6f42c1; color: white; }
        .fraud-signal.vm { background: #20c997; color: white; }
        .fraud-signal.incognito { background: #6c757d; color: white; }
    </style>
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="">
            <div class="sidebar-header">
                <a href="/admin/index.php?password=<?=$_GET['password']?><?=$date_str?>">
                    <img class="main-logo" src="img/logo/logo.png" alt="" />
                </a>
                <strong>
                    <img src="img/favicon.png" alt="" style="width:50px" />
                </strong>
            </div>
            <div class="nalika-profile">
                <div class="profile-dtl">
                    <a href="https://t.me/yellow_web">
                        <img src="img/notification/4.jpg" alt="" />
                    </a>
                    <?php include "version.php" ?>
                </div>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li>
                            <a class="has-arrow" href="index.php?password=<?=$_GET['password']?><?=$date_str?>" aria-expanded="false">
                                <i class="icon nalika-bar-chart icon-wrap"></i>
                                <span class="mini-click-non">Traffic</span>
                            </a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li><a href="statistics.php?password=<?=$_GET['password']?><?=$date_str?>"><span class="mini-sub-pro">Statistics</span></a></li>
                                <li><a href="index.php?password=<?=$_GET['password']?><?=$date_str?>"><span class="mini-sub-pro">Allowed</span></a></li>
                                <li><a href="index.php?filter=leads&password=<?=$_GET['password']?><?=$date_str?>"><span class="mini-sub-pro">Leads</span></a></li>
                                <li><a href="index.php?filter=blocked&password=<?=$_GET['password']?><?=$date_str?>"><span class="mini-sub-pro">Blocked</span></a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="rules.php?password=<?=$_GET['password']?><?=$date_str?>" aria-expanded="false">
                                <i class="icon nalika-forms icon-wrap"></i>
                                <span class="mini-click-non">Filter Rules</span>
                            </a>
                        </li>
                        <li class="active">
                            <a href="analytics.php?password=<?=$_GET['password']?><?=$date_str?>" aria-expanded="false">
                                <i class="icon nalika-analytics icon-wrap"></i>
                                <span class="mini-click-non">Analytics</span>
                            </a>
                        </li>
                        <li>
                            <a href="editsettings.php?password=<?=$_GET['password']?><?=$date_str?>" aria-expanded="false">
                                <i class="icon nalika-table icon-wrap"></i>
                                <span class="mini-click-non">Settings</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>

    <!-- Start Welcome area -->
    <div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="logo-pro">
                        <a href="index.html">
                            <img class="main-logo" src="img/logo/logo.png" alt="" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="header-top-wraper">
                                <div class="row">
                                    <div class="col-lg-1 col-md-0 col-sm-1 col-xs-12">
                                        <div class="menu-switcher-pro">
                                            <button type="button" id="sidebarCollapse" class="btn bar-button-pro header-drl-controller-btn btn-info navbar-btn">
                                                <i class="icon nalika-menu-task"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-11 col-md-1 col-sm-12 col-xs-12">
                                        <div class="header-right-info">
                                            <ul class="nav navbar-nav mai-top-nav header-right-menu">
                                                <li class="nav-item">
                                                    <a class="nav-link" href="" onclick="location.reload()">Refresh</a>
                                                    <a class="nav-link" href="#" id='litepicker'>Date:</a>
                                                    <a class="nav-link">
                                                        <?php
                                                        $calendsd = isset($_GET['startdate']) ? $_GET['startdate'] : '';
                                                        $calended = isset($_GET['enddate']) ? $_GET['enddate'] : '';
                                                        if ($calendsd !== '' && $calended !== '') {
                                                            if ($calendsd === $calended) {
                                                                echo $calendsd;
                                                            } else {
                                                                echo "{$calendsd} - {$calended}";
                                                            }
                                                        } else {
                                                            echo $startdate->format('d.m.y') . ' - ' . $enddate->format('d.m.y');
                                                        } ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Content -->
        <div class="container-fluid">
            
            <!-- Key Metrics -->
            <div class="row">
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                        <div class="metric-value"><?= number_format($totalClicks) ?></div>
                        <div class="metric-label">Total Clicks</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(45deg, #28a745, #1e7e34);">
                        <div class="metric-value"><?= number_format(count($analytics['black_clicks'])) ?></div>
                        <div class="metric-label">Allowed Clicks</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(45deg, #dc3545, #c82333);">
                        <div class="metric-value"><?= number_format(count($analytics['white_clicks'])) ?></div>
                        <div class="metric-label">Blocked Clicks</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                        <div class="metric-value"><?= number_format($blockRate, 1) ?>%</div>
                        <div class="metric-label">Block Rate</div>
                    </div>
                </div>
            </div>

            <!-- Temporal Analytics -->
            <div class="row">
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Daily Traffic Distribution</h5>
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Hourly Traffic Pattern</h5>
                        <div class="chart-container">
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Analytics -->
            <div class="row">
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Device Brands (Allowed Traffic)</h5>
                        <div class="chart-container">
                            <canvas id="deviceBrandsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Device Types</h5>
                        <div class="chart-container">
                            <canvas id="deviceTypesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Browser and OS Analytics -->
            <div class="row">
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Top Browsers</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Browser</th>
                                        <th>Clicks</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deviceAnalytics['browsers'] as $browser => $count): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($browser) ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td><?= number_format(($count / count($analytics['black_clicks'])) * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Operating Systems</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>OS Version</th>
                                        <th>Clicks</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deviceAnalytics['os_versions'] as $os => $count): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($os) ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td><?= number_format(($count / count($analytics['black_clicks'])) * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fingerprint Analytics -->
            <?php if ($fingerprintAnalytics['unique_visitors'] > 0): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="analytics-card">
                        <h5>Fraud Signals Detected</h5>
                        <div class="mb-3">
                            <?php foreach ($fingerprintAnalytics['fraud_signals'] as $signal => $count): ?>
                            <?php if ($count > 0): ?>
                            <span class="fraud-signal <?= $signal ?>"><?= ucfirst($signal) ?>: <?= $count ?></span>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-container">
                            <canvas id="fraudSignalsChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="analytics-card">
                        <h5>Fingerprint Confidence</h5>
                        <div class="chart-container">
                            <canvas id="confidenceChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="analytics-card">
                        <h5>Top Repeat Visitors</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Visitor ID</th>
                                        <th>Visits</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($fingerprintAnalytics['top_visitors'], 0, 10, true) as $visitorId => $visits): ?>
                                    <tr>
                                        <td><code><?= substr($visitorId, 0, 12) ?>...</code></td>
                                        <td><span class="badge badge-<?= $visits > 5 ? 'danger' : ($visits > 2 ? 'warning' : 'info') ?>"><?= $visits ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bot Detection -->
            <div class="row">
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Enhanced Bot Detection</h5>
                        <div class="chart-container">
                            <canvas id="botDetectionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytics-card">
                        <h5>Top Device Models</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Device Model</th>
                                        <th>Clicks</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deviceAnalytics['device_models'] as $model => $count): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($model) ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td><?= number_format(($count / count($analytics['black_clicks'])) * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var picker = new Litepicker({
            element: document.getElementById('litepicker'),
            format: 'DD.MM.YY',
            autoApply: false,
            lang: "ru-RU",
            buttonText: {"apply": "Выбрать", "cancel": "Отмена"},
            singleMode: false,
            setup: (p) => {
                p.on('button:apply', (date1, date2) => {
                    var searchParams = new URLSearchParams(window.location.search);
                    var d1 = moment(date1.dateInstance).format('DD.MM.YY');
                    var d2 = moment(date2.dateInstance).format('DD.MM.YY');
                    searchParams.set('startdate', d1);
                    searchParams.set('enddate', d2);
                    window.location.search = searchParams.toString();
                });
            }
        });
    </script>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/jquery.meanmenu.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/metisMenu/metisMenu.min.js"></script>
    <script src="js/metisMenu/metisMenu-active.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>

    <script>
        // Chart.js configurations
        Chart.defaults.global.responsive = true;
        Chart.defaults.global.maintainAspectRatio = false;

        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($temporalAnalytics['daily'])) ?>,
                datasets: [{
                    label: 'Daily Clicks',
                    data: <?= json_encode(array_values($temporalAnalytics['daily'])) ?>,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Hourly Chart
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($temporalAnalytics['hourly'])) ?>,
                datasets: [{
                    label: 'Hourly Clicks',
                    data: <?= json_encode(array_values($temporalAnalytics['hourly'])) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Device Brands Chart
        const brandsCtx = document.getElementById('deviceBrandsChart').getContext('2d');
        new Chart(brandsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($deviceAnalytics['device_brands'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($deviceAnalytics['device_brands'])) ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                    ]
                }]
            }
        });

        // Device Types Chart
        const typesCtx = document.getElementById('deviceTypesChart').getContext('2d');
        new Chart(typesCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($deviceAnalytics['device_types'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($deviceAnalytics['device_types'])) ?>,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
                }]
            }
        });

        <?php if ($fingerprintAnalytics['unique_visitors'] > 0): ?>
        // Fraud Signals Chart
        const fraudCtx = document.getElementById('fraudSignalsChart').getContext('2d');
        new Chart(fraudCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($fingerprintAnalytics['fraud_signals'])) ?>,
                datasets: [{
                    label: 'Fraud Signals',
                    data: <?= json_encode(array_values($fingerprintAnalytics['fraud_signals'])) ?>,
                    backgroundColor: ['#dc3545', '#fd7e14', '#6f42c1', '#20c997', '#6c757d']
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Confidence Chart
        const confidenceCtx = document.getElementById('confidenceChart').getContext('2d');
        new Chart(confidenceCtx, {
            type: 'doughnut',
            data: {
                labels: ['High (>80%)', 'Medium (50-80%)', 'Low (<50%)'],
                datasets: [{
                    data: <?= json_encode(array_values($fingerprintAnalytics['confidence_distribution'])) ?>,
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            }
        });
        <?php endif; ?>

        // Bot Detection Chart
        const botCtx = document.getElementById('botDetectionChart').getContext('2d');
        new Chart(botCtx, {
            type: 'doughnut',
            data: {
                labels: ['Human', 'Bot'],
                datasets: [{
                    data: <?= json_encode(array_values($deviceAnalytics['bot_detection'])) ?>,
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            }
        });
    </script>
</body>
</html>