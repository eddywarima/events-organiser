<?php
/**
 * Check for user notifications (email failures, etc.)
 */

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$notifications = [];

// Check for email notification
if (isset($_SESSION['email_notification'])) {
    $notifications['email_notification'] = $_SESSION['email_notification'];
    
    // Clear the notification after retrieving
    unset($_SESSION['email_notification']);
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
?>
