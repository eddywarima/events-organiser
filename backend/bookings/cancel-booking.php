<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

$user_id = $_SESSION['user_id'];
$booking_id = intval($_POST['id']);

// Fetch booking
$stmt = $conn->prepare("SELECT event_id, tickets, booking_status FROM bookings WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Booking not found");

$booking = $result->fetch_assoc();
if ($booking['booking_status'] !== 'confirmed') die("Cannot cancel this booking");

// Cancel booking
$stmt = $conn->prepare("UPDATE bookings SET booking_status='cancelled' WHERE id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();

// Restore tickets
$stmt = $conn->prepare("UPDATE events SET available_tickets = available_tickets + ? WHERE id=?");
$stmt->bind_param("ii", $booking['tickets'], $booking['event_id']);
$stmt->execute();

header("Location: ../../frontend/dashboard.html");
exit;
