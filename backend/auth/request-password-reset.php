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

// Get and sanitize email
$rules = ['email' => 'email'];
$cleaned = InputSanitizer::cleanPost($rules);

if ($cleaned['email'] === false) {
    Logger::warning("Invalid email format in password reset request", ['email' => $_POST['email'] ?? '']);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

$email = $cleaned['email'];
$ip_address = $_SERVER['REMOTE_ADDR'];

// Process password reset request
$result = PasswordReset::createResetRequest($email, $ip_address);

// Log the attempt
if ($result['success']) {
    Logger::info("Password reset request sent", ['email' => $email, 'ip' => $ip_address]);
} else {
    Logger::warning("Password reset request failed", [
        'email' => $email, 
        'ip' => $ip_address, 
        'reason' => $result['message']
    ]);
}

echo json_encode($result);
?>
