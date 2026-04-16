<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    Logger::warning("Unauthorized booking attempt", ['ip' => $_SERVER['REMOTE_ADDR']]);
    header("Location: ../../frontend/login.html");
    exit();
}

require_once '../config/db.php';
require_once '../utils/csrf.php';
require_once '../utils/sanitizer.php';
require_once '../utils/logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    CSRFProtection::validateRequest();
    
    // Sanitize and validate input
    $rules = [
        'event_id' => 'int',
        'tickets' => 'int'
    ];
    $cleaned = InputSanitizer::cleanPost($rules);
    
    if ($cleaned['event_id'] === false || $cleaned['tickets'] === false) {
        Logger::error("Invalid booking data", [
            'user_id' => $_SESSION['user_id'],
            'raw_data' => $_POST
        ]);
        die("Invalid input data");
    }
    
    $event_id = $cleaned['event_id'];
    $tickets = $cleaned['tickets'];

    // Validate input
    if ($tickets < 1) {
        Logger::warning("Invalid ticket count", [
            'user_id' => $_SESSION['user_id'],
            'tickets' => $tickets
        ]);
        die("Invalid number of tickets.");
    }

    // Additional validation: Check if event is in the future and active
    $stmt = $conn->prepare("SELECT event_date, status FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        Logger::warning("Booking attempt for non-existent event", [
            'user_id' => $_SESSION['user_id'],
            'event_id' => $event_id
        ]);
        die("Event not found.");
    }

    $event_check = $result->fetch_assoc();
    
    // Check if event is active
    if ($event_check['status'] !== 'active') {
        Logger::warning("Booking attempt for inactive event", [
            'user_id' => $_SESSION['user_id'],
            'event_id' => $event_id,
            'status' => $event_check['status']
        ]);
        die("Event is not active for booking.");
    }

    // Check if event is in the future
    if (strtotime($event_check['event_date']) <= time()) {
        Logger::warning("Booking attempt for past event", [
            'user_id' => $_SESSION['user_id'],
            'event_id' => $event_id,
            'event_date' => $event_check['event_date']
        ]);
        die("Cannot book events that have already occurred.");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check event availability and update tickets atomically
        $stmt = $conn->prepare("
            UPDATE events 
            SET available_tickets = available_tickets - ? 
            WHERE id = ? AND available_tickets >= ? AND status = 'active'
        ");
        $stmt->bind_param("iii", $tickets, $event_id, $tickets);
        $stmt->execute();
        
        // Check if update was successful
        if ($stmt->affected_rows === 0) {
            Logger::warning("Booking failed - event not available or insufficient tickets", [
                'user_id' => $_SESSION['user_id'],
                'event_id' => $event_id,
                'requested' => $tickets
            ]);
            throw new Exception("Event not found, not active, or not enough tickets available.");
        }
        
        // Get event details for email (after successful update)
        $stmt = $conn->prepare("SELECT title, event_date, event_time, location, ticket_price FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            throw new Exception("Event details not found after update.");
        }

        $event = $result->fetch_assoc();
        $total_amount = $tickets * $event['ticket_price'];

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, tickets, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $_SESSION['user_id'], $event_id, $tickets, $total_amount);
        if (!$stmt->execute()) {
            Logger::logDatabaseError("INSERT bookings", $stmt->error, [
                'user_id' => $_SESSION['user_id'],
                'event_id' => $event_id
            ]);
            throw new Exception("Booking failed.");
        }

        // Commit transaction
        $conn->commit();

        // Log successful booking
        Logger::logBooking($_SESSION['user_id'], $event_id, 'booking_created', [
            'tickets' => $tickets,
            'total_amount' => $total_amount
        ]);

        // Send booking confirmation email with digital ticket
        require_once '../utils/ticket_email.php';
        $ticketEmail = new TicketEmail($conn);
        
        // Get user and event details for email
        $user_result = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $user_result->bind_param("i", $_SESSION['user_id']);
        $user_result->execute();
        $user = $user_result->get_result()->fetch_assoc();
        
        $event_result = $conn->prepare("SELECT title, event_date, event_time, location FROM events WHERE id = ?");
        $event_result->bind_param("i", $event_id);
        $event_result->execute();
        $event = $event_result->get_result()->fetch_assoc();
        
        // Send digital ticket email
        $email_sent = $ticketEmail->sendBookingConfirmation(
            $stmt->insert_id,
            $user['email'],
            $user['full_name'],
            $event['title'],
            $event['event_date'],
            $event['event_time'],
            $event['location'],
            $tickets,
            $total_amount
        );
        
        if (!$email_sent) {
            Logger::warning("Booking successful but email failed", [
                'booking_id' => $stmt->insert_id,
                'user_email' => $user['email']
            ]);
            
            // Set session notification for user
            $_SESSION['email_notification'] = 'Booking confirmed but email delivery failed. Please check your email or contact support.';
        }

        // Redirect to booking success page
        $base_url = "http://localhost/event%20booking/frontend/";
        header("Location: " . $base_url . "booking-success.html?booking_id=" . $stmt->insert_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die($e->getMessage());
    }
} else {
    die("Invalid request method.");
}
?>