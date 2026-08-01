<?php
// declare(strict_types=1);

// return [
//     'success' => true,
//     'message' => 'Controller reached successfully'
// ];


header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ini_set('display_errors', 0);
// error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/errors.log');



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// --- LOAD DEPENDENCIES (ABSOLUTE PATHS) ---
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Auth.php';
require_once __DIR__ . '/controllers/AnalyzeController.php';

authStart();
$currentUser = authCurrentUser();

try {
    $channelName = trim($_POST['channel'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $consent     = $_POST['consent'] ?? '';

    if ($channelName === '' || $email === '') {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    if ($consent === '') {
        echo json_encode([
            'success' => false,
            'message' => 'You must agree to the Terms of Use & Privacy Policy'
        ]);
        exit;
    }

    $controller = new AnalyzeController();
    $result = $controller->analyze($channelName, $email, $currentUser['id'] ?? null);

    // Enforce JSON shape
    echo json_encode([
        'success' => $result['success'] ?? false,
        'message' => $result['message'] ?? 'Request completed'
    ]);

} catch (Throwable $e) {
    error_log(
        $e->getMessage() .
        ' | ' .
        $e->getFile() .
        ':' .
        $e->getLine()
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
}
