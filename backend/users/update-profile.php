<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";
require_once "../utils/csrf.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate CSRF token
CSRFProtection::validateRequest();

// Get current user ID
$user_id = $_SESSION['user_id'];

// Sanitize and validate input (only fields that exist in database)
$rules = [
    'full_name' => 'string'
];
$cleaned = InputSanitizer::cleanPost($rules);

if ($cleaned === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$full_name = $cleaned['full_name'] ?? null;
$user_id = $_SESSION['user_id'];

// Validate full name
if ($full_name && strlen($full_name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Full name must be at least 2 characters']);
    exit();
}

if ($full_name && strlen($full_name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Full name must be less than 100 characters']);
    exit();
}

// Update user profile (only update columns that exist in database)
$stmt = $conn->prepare("
    UPDATE users 
    SET full_name = ?
    WHERE id = ?
");

$stmt->bind_param("si", 
    $full_name, 
    $user_id
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    exit();
}

Logger::info("User profile updated", [
    'user_id' => $user_id,
    'full_name' => $full_name
]);

echo json_encode([
    'success' => true, 
    'message' => 'Profile updated successfully',
    'full_name' => $full_name
]);

?>
