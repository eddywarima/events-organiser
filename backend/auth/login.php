<?php
require_once "../config/db.php";
require_once "../utils/csrf.php";
require_once "../utils/rate_limiter.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Logger::warning("Invalid request method for login", ['method' => $_SERVER['REQUEST_METHOD']]);
    die("Invalid request");
}

// Validate CSRF token
CSRFProtection::validateRequest();

// Rate limiting check
$client_ip = RateLimiter::getClientIP();
$rate_check = RateLimiter::isAllowed($client_ip);

if (!$rate_check['allowed']) {
    Logger::security("Login blocked due to rate limiting", [
        'ip' => $client_ip,
        'reason' => $rate_check['message']
    ]);
    die($rate_check['message']);
}

// Sanitize and validate input
$rules = [
    'email' => 'email',
    'password' => 'string'
];
$cleaned = InputSanitizer::cleanPost($rules);

if ($cleaned['email'] === false) {
    die("Invalid email format");
}

$email = $cleaned['email'];
$password = $cleaned['password'];

$stmt = $conn->prepare(
    "SELECT id, password, role, status, email_verified FROM users WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    RateLimiter::recordFailedAttempt($client_ip);
    Logger::logLogin($email, $client_ip, false, 'User not found');
    die("Invalid login details");
}

$user = $result->fetch_assoc();

// Check account status
if ($user['status'] !== 'active') {
    RateLimiter::recordFailedAttempt($client_ip);
    Logger::logLogin($email, $client_ip, false, 'Account blocked');
    die("Account is blocked");
}

// Verify password
if (!password_verify($password, $user['password'])) {
    RateLimiter::recordFailedAttempt($client_ip);
    Logger::logLogin($email, $client_ip, false, 'Invalid password');
    die("Invalid login details");
}

// Clear failed attempts on successful login
RateLimiter::clearAttempts($client_ip);
Logger::logLogin($email, $client_ip, true);

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

// Create session
$_SESSION['user_id'] = $user['id'];
$_SESSION['role']    = $user['role'];

// Redirect based on role
$base_url = "http://localhost/event%20booking/frontend/";
if ($user['role'] === 'admin') {
    header("Location: " . $base_url . "admin-dashboard.html");
} else {
    header("Location: " . $base_url . "profile.html");
}
exit;
