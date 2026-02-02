<?php
require_once "../config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$email    = trim($_POST['email']);
$password = $_POST['password'];

$stmt = $conn->prepare(
    "SELECT id, password, role, status FROM users WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Invalid login details");
}

$user = $result->fetch_assoc();

// Check account status
if ($user['status'] !== 'active') {
    die("Account is blocked");
}

// Verify password
if (!password_verify($password, $user['password'])) {
    die("Invalid login details");
}

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
    header("Location: " . $base_url . "index.html");
}
exit;
