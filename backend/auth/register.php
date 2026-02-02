<?php
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$full_name = trim($_POST['full_name']);
$email     = trim($_POST['email']);
$password  = $_POST['password'];

// Basic validation
if (empty($full_name) || empty($email) || empty($password)) {
    die("All fields are required");
}

if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    die("Password must be at least 8 characters long and contain at least one uppercase letter and one number");
}

// Check if email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    die("Email already registered");
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare(
    "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)"
);
$stmt->bind_param("sss", $full_name, $email, $hashed_password);

if ($stmt->execute()) {
    $base_url = "http://localhost/event%20booking/frontend/";
    header("Location: " . $base_url . "login.html");
    exit;
} else {
    die("Registration failed");
}
