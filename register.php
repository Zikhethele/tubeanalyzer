<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$name             = trim($_POST['name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate name
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Full name is required.']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// Validate passwords match
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Validate phone (optional — digits, spaces, +, -, parens)
if ($phone !== '' && !preg_match('/^[+\d\s\-().]{7,20}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
    exit;
}

try {
    $user   = new User();
    $userId = $user->registerUser($name, $email, $password, $phone ?: null);

    if ($userId === false) {
        echo json_encode(['success' => false, 'message' => 'An account with that email already exists.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Account created successfully! Welcome to TubeAnalyzer.']);
} catch (Exception $e) {
    error_log('register.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
