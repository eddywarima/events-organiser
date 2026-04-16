<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

header('Content-Type: application/json');

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get user profile
$stmt = $conn->prepare("
    SELECT 
        u.id, u.full_name, u.email, u.created_at
    FROM users u 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Get user booking stats
$booking_stmt = $conn->prepare("
    SELECT COUNT(*) as total_bookings, SUM(total_amount) as total_spent
    FROM bookings 
    WHERE user_id = ? AND booking_status = 'confirmed'
");
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$booking_stats = $booking_stmt->get_result()->fetch_assoc();

// Format response
$profile = [
    'id' => (int)$user['id'],
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'created_at' => $user['created_at'],
    'total_bookings' => (int)$booking_stats['total_bookings'],
    'total_spent' => (float)$booking_stats['total_spent']
];

echo json_encode(['success' => true, 'profile' => $profile]);
?>
