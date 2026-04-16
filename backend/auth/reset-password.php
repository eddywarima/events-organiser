<?php
require_once "../config/db.php";
require_once "../utils/csrf.php";
require_once "../utils/sanitizer.php";
require_once "../utils/password_reset.php";
require_once "../utils/logger.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::warning("Invalid request method for password reset", ['method' => $_SERVER['REQUEST_METHOD']]);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate CSRF token
CSRFProtection::validateRequest();

// Get and sanitize inputs
$rules = [
    'token' => 'string',
    'password' => 'string',
    'confirm_password' => 'string'
];
$cleaned = InputSanitizer::cleanPost($rules);

if ($cleaned['token'] === false || $cleaned['password'] === false || $cleaned['confirm_password'] === false) {
    Logger::warning("Invalid input data in password reset", ['token_length' => strlen($_POST['token'] ?? '')]);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$token = $cleaned['token'];
$password = $cleaned['password'];
$confirm_password = $cleaned['confirm_password'];

// Validate password confirmation
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit();
}

// Process password reset
$result = PasswordReset::resetPassword($token, $password);

// Log the attempt
if ($result['success']) {
    Logger::info("Password reset successful", ['token' => substr($token, 0, 8) . '...']);
} else {
    Logger::warning("Password reset failed", [
        'token' => substr($token, 0, 8) . '...',
        'reason' => $result['message']
    ]);
}

echo json_encode($result);
?>
