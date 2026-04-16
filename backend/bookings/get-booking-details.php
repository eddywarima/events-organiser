<?php
/**
 * Get booking details for PDF download and success page
 */

session_start();
require_once '../config/db.php';
require_once '../utils/logger.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get booking ID from request
$booking_id = intval($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

try {
    // Get booking details with user verification
    $query = "
        SELECT b.id, b.event_id, b.tickets, b.total_amount, b.booking_status, b.booking_date,
               e.title, e.description, e.location, e.event_date, e.event_time, e.ticket_price,
               u.full_name, u.email
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit;
    }
    
    $booking = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => $booking['id'],
            'event_id' => $booking['event_id'],
            'title' => $booking['title'],
            'description' => $booking['description'],
            'location' => $booking['location'],
            'event_date' => $booking['event_date'],
            'event_time' => $booking['event_time'],
            'tickets' => (int)$booking['tickets'],
            'total_amount' => (float)$booking['total_amount'],
            'ticket_price' => (float)$booking['ticket_price'],
            'booking_status' => $booking['booking_status'],
            'booking_date' => $booking['booking_date'],
            'full_name' => $booking['full_name'],
            'email' => $booking['email']
        ]
    ]);
    
} catch (Exception $e) {
    Logger::error("Error fetching booking details", [
        'booking_id' => $booking_id,
        'user_id' => $_SESSION['user_id'] ?? 'unknown',
        'error' => $e->getMessage()
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
