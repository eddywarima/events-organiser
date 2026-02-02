<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/login.html");
    exit();
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = intval($_POST['event_id']);
    $tickets = intval($_POST['tickets']);

    // Validate input
    if ($tickets < 1) {
        die("Invalid number of tickets.");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check event availability with lock
        $stmt = $conn->prepare("SELECT available_tickets, ticket_price FROM events WHERE id = ? AND status = 'active' FOR UPDATE");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("Event not found or not available.");
        }

        $event = $result->fetch_assoc();
        if ($event['available_tickets'] < $tickets) {
            throw new Exception("Not enough tickets available.");
        }

        $total_amount = $tickets * $event['ticket_price'];

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, tickets, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $_SESSION['user_id'], $event_id, $tickets, $total_amount);
        if (!$stmt->execute()) {
            throw new Exception("Booking failed.");
        }

        // Update available tickets
        $stmt = $conn->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
        $stmt->bind_param("ii", $tickets, $event_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update tickets.");
        }

        // Commit transaction
        $conn->commit();

        // Redirect to user dashboard
        $base_url = "http://localhost/event%20booking/frontend/";
        header("Location: " . $base_url . "dashboard.html");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
} else {
    die("Invalid request method.");
}
?>