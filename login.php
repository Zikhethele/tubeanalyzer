<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Auth.php';
require_once __DIR__ . '/models/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

authStart();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your email and password.']);
    exit;
}

// Throttle guesses regardless of outcome so timing can't reveal validity,
// and cap attempts per session to blunt brute-forcing (mirrors AdminAuth's gate).
$_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
usleep(300000);

if ($_SESSION['login_attempts'] > 20) {
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Try again later.']);
    exit;
}

try {
    $userModel = new User();
    $user      = $userModel->verifyCredentials($email, $password);

    if ($user === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    authLogin($user);
    $userModel->updateLastSeen($user['id']);
    unset($_SESSION['login_attempts']);

    echo json_encode([
        'success' => true,
        'message' => 'Welcome back!',
        'user'    => ['name' => $user['name'], 'email' => $user['email']],
    ]);
} catch (Exception $e) {
    error_log('login.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
