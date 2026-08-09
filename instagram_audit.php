<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Auth.php';
require_once __DIR__ . '/controllers/InstagramAuditController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$currentUser = authRequireUser();

$username = trim($_POST['username'] ?? '');
$username = ltrim($username, '@');

if ($username === '' || !preg_match('/^[A-Za-z0-9._]{1,30}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid Instagram username.']);
    exit;
}

try {
    $controller = new InstagramAuditController();
    $result     = $controller->audit($username, $currentUser['id']);

    echo json_encode($result);
} catch (Throwable $e) {
    error_log('instagram_audit.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
