<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/login.html");
    exit();
}

require_once '../config/db.php';

// Fetch user's bookings with event details
$query = "SELECT b.id, e.title, b.tickets, b.total_amount, b.booking_status, b.booking_date
          FROM bookings b
          JOIN events e ON b.event_id = e.id
          WHERE b.user_id = ?
          ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode($bookings);
?>