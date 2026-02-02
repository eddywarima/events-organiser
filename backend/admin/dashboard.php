<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Example: get total users, events, bookings
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_events = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$total_bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];

echo json_encode([
    'users' => $total_users,
    'events' => $total_events,
    'bookings' => $total_bookings
]);
