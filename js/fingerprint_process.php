<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../settings.php';

// Only process if fingerprinting is enabled
if (!$enhanced_fingerprinting || empty($fpjs_api_key)) {
    http_response_code(404);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['requestId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Store fingerprint data in session/cookie for main processing
session_start();
$_SESSION['fpjs_request_id'] = $input['requestId'];
$_SESSION['fpjs_visitor_id'] = $input['visitorId'] ?? '';
$_SESSION['fpjs_confidence'] = $input['confidence'] ?? 0;

// Also set as cookie for backup
setcookie('fpjs_request_id', $input['requestId'], time() + 3600, '/');

echo json_encode(['status' => 'success']);
?>