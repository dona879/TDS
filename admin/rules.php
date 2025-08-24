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
if (!$enhanced_enabled || !$rules_engine_enabled) {
    die("Enhanced rules engine is not enabled. Please enable it in settings.");
}

require_once '../vendor/autoload.php';
use YellowCloaker\Rules\RulesEngine;

$rulesEngine = new RulesEngine();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create_rule':
            $data = json_decode($_POST['data'], true);
            $result = $rulesEngine->createRule($data);
            echo json_encode(['success' => true, 'id' => $result['_id']]);
            exit();
            
        case 'update_rule':
            $id = $_POST['id'];
            $data = json_decode($_POST['data'], true);
            $result = $rulesEngine->updateRule($id, $data);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'delete_rule':
            $id = $_POST['id'];
            $result = $rulesEngine->deleteRule($id);
            echo json_encode(['success' => $result]);
            exit();
            
        case 'get_rule':
            $id = $_POST['id'];
            $rule = $rulesEngine->getRule($id);
            echo json_encode($rule);
            exit();
    }
}

// Get all rules for display
$rules = $rulesEngine->getAllRules();
$availableFields = $rulesEngine->getAvailableFields();
$availableOperators = $rulesEngine->getAvailableOperators();
$ruleStats = $rulesEngine->getRuleStats();

date_default_timezone_set($stats_timezone);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Enhanced Rules Management - YellowCloaker</title>
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
        .rule-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .rule-active { border-left: 4px solid #28a745; }
        .rule-inactive { border-left: 4px solid #dc3545; }
        .condition-group {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 3px;
            padding: 10px;
            margin: 5px 0;
        }
        .btn-sm { margin: 2px; }
        .stats-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #007bff;
            color: white;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }
        .modal-lg { max-width: 800px; }
        .form-group { margin-bottom: 15px; }
        .condition-row {
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="">
            <div class="sidebar-header">
                <a href="/admin/index.php?password=<?=$_GET['password']?>">
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
                            <a class="has-arrow" href="index.php?password=<?=$_GET['password']?>" aria-expanded="false">
                                <i class="icon nalika-bar-chart icon-wrap"></i>
                                <span class="mini-click-non">Traffic</span>
                            </a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li><a href="statistics.php?password=<?=$_GET['password']?>"><span class="mini-sub-pro">Statistics</span></a></li>
                                <li><a href="index.php?password=<?=$_GET['password']?>"><span class="mini-sub-pro">Allowed</span></a></li>
                                <li><a href="index.php?filter=leads&password=<?=$_GET['password']?>"><span class="mini-sub-pro">Leads</span></a></li>
                                <li><a href="index.php?filter=blocked&password=<?=$_GET['password']?>"><span class="mini-sub-pro">Blocked</span></a></li>
                            </ul>
                        </li>
                        <li class="active">
                            <a href="rules.php?password=<?=$_GET['password']?>" aria-expanded="false">
                                <i class="icon nalika-forms icon-wrap"></i>
                                <span class="mini-click-non">Filter Rules</span>
                            </a>
                        </li>
                        <li>
                            <a href="analytics.php?password=<?=$_GET['password']?>" aria-expanded="false">
                                <i class="icon nalika-analytics icon-wrap"></i>
                                <span class="mini-click-non">Analytics</span>
                            </a>
                        </li>
                        <li>
                            <a href="editsettings.php?password=<?=$_GET['password']?>" aria-expanded="false">
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
                                                    <button class="btn btn-success" onclick="showCreateRuleModal()">
                                                        <i class="fa fa-plus"></i> Create New Rule
                                                    </button>
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

        <!-- Rules Content -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Filter Rules Management</h4>
                            <p class="text-muted">Create and manage dynamic filtering rules for enhanced traffic control</p>
                        </div>
                        <div class="card-body">
                            
                            <!-- Rules Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5><?= count($rules) ?></h5>
                                            <p>Total Rules</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5><?= count(array_filter($rules, function($r) { return $r['status'] === 'active'; })) ?></h5>
                                            <p>Active Rules</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h5><?= array_sum(array_column($ruleStats, 'matches')) ?></h5>
                                            <p>Total Matches</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5><?= count(array_filter($ruleStats, function($r) { return $r['matches'] > 0; })) ?></h5>
                                            <p>Rules with Matches</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rules List -->
                            <div id="rules-container">
                                <?php foreach ($rules as $rule): ?>
                                <div class="rule-card <?= $rule['status'] === 'active' ? 'rule-active' : 'rule-inactive' ?>" data-rule-id="<?= $rule['_id'] ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5>
                                                <?= htmlspecialchars($rule['name']) ?>
                                                <span class="badge badge-<?= $rule['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($rule['status']) ?>
                                                </span>
                                                <span class="badge badge-<?= $rule['priority'] === 'high' ? 'danger' : ($rule['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($rule['priority']) ?>
                                                </span>
                                                <?php 
                                                $ruleStatData = array_filter($ruleStats, function($s) use ($rule) { return $s['id'] === $rule['_id']; });
                                                $matches = !empty($ruleStatData) ? reset($ruleStatData)['matches'] : 0;
                                                ?>
                                                <span class="stats-badge"><?= $matches ?> matches</span>
                                            </h5>
                                            <p class="text-muted"><?= htmlspecialchars($rule['description']) ?></p>
                                            
                                            <div class="conditions-preview">
                                                <strong>Conditions (<?= strtoupper($rule['operator']) ?>):</strong>
                                                <?php foreach ($rule['conditions'] as $condition): ?>
                                                <div class="condition-group">
                                                    <code><?= htmlspecialchars($condition['field']) ?></code>
                                                    <span class="badge badge-light"><?= htmlspecialchars($condition['operator']) ?></span>
                                                    <code><?= htmlspecialchars($condition['value']) ?></code>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="btn-group-vertical">
                                                <button class="btn btn-sm btn-primary" onclick="editRule('<?= $rule['_id'] ?>')">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-<?= $rule['status'] === 'active' ? 'warning' : 'success' ?>" 
                                                        onclick="toggleRuleStatus('<?= $rule['_id'] ?>', '<?= $rule['status'] === 'active' ? 'inactive' : 'active' ?>')">
                                                    <i class="fa fa-<?= $rule['status'] === 'active' ? 'pause' : 'play' ?>"></i> 
                                                    <?= $rule['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteRule('<?= $rule['_id'] ?>')">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Created: <?= date('Y-m-d H:i', $rule['created_at']) ?><br>
                                                    Updated: <?= date('Y-m-d H:i', $rule['updated_at']) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Rule Modal -->
    <div class="modal fade" id="ruleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ruleModalTitle">Create New Rule</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="ruleForm">
                        <input type="hidden" id="ruleId" name="ruleId">
                        
                        <div class="form-group">
                            <label for="ruleName">Rule Name *</label>
                            <input type="text" class="form-control" id="ruleName" name="ruleName" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ruleDescription">Description</label>
                            <textarea class="form-control" id="ruleDescription" name="ruleDescription" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ruleOperator">Conditions Operator</label>
                                    <select class="form-control" id="ruleOperator" name="ruleOperator">
                                        <option value="AND">AND (All must match)</option>
                                        <option value="OR">OR (Any must match)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="rulePriority">Priority</label>
                                    <select class="form-control" id="rulePriority" name="rulePriority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ruleStatus">Status</label>
                                    <select class="form-control" id="ruleStatus" name="ruleStatus">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="stopOnMatch" name="stopOnMatch">
                                <label class="form-check-label" for="stopOnMatch">
                                    Stop evaluation on match (for high priority rules)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Conditions</label>
                            <div id="conditions-container">
                                <!-- Conditions will be added here -->
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="addCondition()">
                                <i class="fa fa-plus"></i> Add Condition
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRule()">Save Rule</button>
                </div>
            </div>
        </div>
    </div>

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
        const availableFields = <?= json_encode($availableFields) ?>;
        const availableOperators = <?= json_encode($availableOperators) ?>;
        let conditionCounter = 0;

        function showCreateRuleModal() {
            $('#ruleModalTitle').text('Create New Rule');
            $('#ruleForm')[0].reset();
            $('#ruleId').val('');
            $('#conditions-container').empty();
            conditionCounter = 0;
            addCondition();
            $('#ruleModal').modal('show');
        }

        function editRule(ruleId) {
            $.post('rules.php?password=<?=$_GET['password']?>', {
                action: 'get_rule',
                id: ruleId
            }, function(rule) {
                $('#ruleModalTitle').text('Edit Rule');
                $('#ruleId').val(rule._id);
                $('#ruleName').val(rule.name);
                $('#ruleDescription').val(rule.description);
                $('#ruleOperator').val(rule.operator);
                $('#rulePriority').val(rule.priority);
                $('#ruleStatus').val(rule.status);
                $('#stopOnMatch').prop('checked', rule.stop_on_match);
                
                $('#conditions-container').empty();
                conditionCounter = 0;
                
                rule.conditions.forEach(function(condition) {
                    addCondition(condition);
                });
                
                $('#ruleModal').modal('show');
            }, 'json');
        }

        function addCondition(condition = null) {
            conditionCounter++;
            const conditionHtml = `
                <div class="condition-row" id="condition-${conditionCounter}">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-control condition-field" name="conditions[${conditionCounter}][field]" required>
                                <option value="">Select Field</option>
                                ${generateFieldOptions(condition?.field)}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control condition-operator" name="conditions[${conditionCounter}][operator]" required>
                                <option value="">Select Operator</option>
                                ${generateOperatorOptions(condition?.operator)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control condition-value" name="conditions[${conditionCounter}][value]" 
                                   placeholder="Value" value="${condition?.value || ''}" required>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeCondition(${conditionCounter})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#conditions-container').append(conditionHtml);
        }

        function removeCondition(id) {
            $(`#condition-${id}`).remove();
        }

        function generateFieldOptions(selectedField = '') {
            let options = '';
            Object.keys(availableFields).forEach(category => {
                options += `<optgroup label="${category.toUpperCase()}">`;
                Object.keys(availableFields[category]).forEach(field => {
                    const selected = field === selectedField ? 'selected' : '';
                    options += `<option value="${field}" ${selected}>${availableFields[category][field]}</option>`;
                });
                options += '</optgroup>';
            });
            return options;
        }

        function generateOperatorOptions(selectedOperator = '') {
            let options = '';
            Object.keys(availableOperators).forEach(operator => {
                const selected = operator === selectedOperator ? 'selected' : '';
                options += `<option value="${operator}" ${selected}>${availableOperators[operator]}</option>`;
            });
            return options;
        }

        function saveRule() {
            const formData = new FormData($('#ruleForm')[0]);
            const ruleId = $('#ruleId').val();
            
            // Collect conditions
            const conditions = [];
            $('.condition-row').each(function() {
                const field = $(this).find('.condition-field').val();
                const operator = $(this).find('.condition-operator').val();
                const value = $(this).find('.condition-value').val();
                
                if (field && operator && value) {
                    conditions.push({ field, operator, value });
                }
            });
            
            const ruleData = {
                name: $('#ruleName').val(),
                description: $('#ruleDescription').val(),
                operator: $('#ruleOperator').val(),
                priority: $('#rulePriority').val(),
                status: $('#ruleStatus').val(),
                stop_on_match: $('#stopOnMatch').is(':checked'),
                conditions: conditions
            };
            
            const action = ruleId ? 'update_rule' : 'create_rule';
            const postData = {
                action: action,
                data: JSON.stringify(ruleData)
            };
            
            if (ruleId) {
                postData.id = ruleId;
            }
            
            $.post('rules.php?password=<?=$_GET['password']?>', postData, function(response) {
                if (response.success) {
                    $('#ruleModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error saving rule');
                }
            }, 'json');
        }

        function toggleRuleStatus(ruleId, newStatus) {
            $.post('rules.php?password=<?=$_GET['password']?>', {
                action: 'update_rule',
                id: ruleId,
                data: JSON.stringify({ status: newStatus })
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error updating rule status');
                }
            }, 'json');
        }

        function deleteRule(ruleId) {
            if (confirm('Are you sure you want to delete this rule?')) {
                $.post('rules.php?password=<?=$_GET['password']?>', {
                    action: 'delete_rule',
                    id: ruleId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting rule');
                    }
                }, 'json');
            }
        }
    </script>
</body>
</html>