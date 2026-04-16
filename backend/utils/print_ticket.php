<?php
/**
 * Print Ticket Generator
 * Generates HTML tickets that users can print or save as PDF
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/logger.php';

class PrintTicketGenerator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate printable ticket for a booking
     */
    public function generatePrintableTicket($booking_id) {
        try {
            // Get booking details
            $booking_query = "
                SELECT b.*, e.title, e.description, e.location, e.event_date, e.event_time,
                       u.full_name, u.email
                FROM bookings b
                JOIN events e ON b.event_id = e.id
                JOIN users u ON b.user_id = u.id
                WHERE b.id = ? AND b.booking_status = 'confirmed'
            ";
            
            $stmt = $this->conn->prepare($booking_query);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Booking not found or not confirmed");
            }
            
            $booking = $result->fetch_assoc();
            
            // Generate ticket ID
            $ticket_id = 'TKT' . date('Y') . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . strtoupper(substr(md5($booking_id . time()), 0, 4));
            
            // Generate QR code URL
            $qr_data = "TICKET_ID:$ticket_id|EVENT:" . urlencode($booking['title']) . "|DATE:" . $booking['event_date'] . "|EMAIL:" . urlencode($booking['email']);
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
            
            // Display the ticket
            $this->displayTicket($booking, $ticket_id, $qr_url);
            
        } catch (Exception $e) {
            Logger::error("Print ticket generation failed", [
                'booking_id' => $booking_id,
                'error' => $e->getMessage()
            ]);
            
            // Show error page
            $this->showError("Unable to generate ticket. Please try again or contact support.");
        }
    }
    
    /**
     * Display the ticket HTML
     */
    private function displayTicket($booking, $ticket_id, $qr_url) {
        $event_date = date('F j, Y', strtotime($booking['event_date']));
        $event_time = date('g:i A', strtotime($booking['event_time']));
        
        echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Event Ticket - $ticket_id</title>
    <style>
        @page {
            size: A4;
            margin: 20px;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .ticket-container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .status-badge {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
        }
        
        .ticket-body {
            padding: 40px;
        }
        
        .ticket-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .ticket-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        
        .event-details h2 {
            color: #333;
            font-size: 28px;
            margin: 0 0 20px 0;
            font-weight: bold;
        }
        
        .event-details p {
            color: #666;
            font-size: 16px;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .ticket-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            border: 2px dashed #007bff;
        }
        
        .ticket-id {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #333;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .qr-section {
            text-align: center;
            margin: 30px 0;
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
            border: 2px solid #ddd;
            display: inline-block;
            background: white;
            padding: 10px;
            border-radius: 10px;
            margin: 0 auto;
        }
        
        .barcode {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            letter-spacing: 3px;
            text-align: center;
            margin: 20px 0;
            color: #333;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        
        .footer h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .footer ul {
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
            padding-left: 20px;
        }
        
        .footer li {
            margin-bottom: 8px;
            color: #666;
        }
        
        .price-highlight {
            font-size: 20px;
            color: #007bff;
            font-weight: bold;
        }
        
        .action-buttons {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 10px; 
                background: white;
            }
            
            .ticket-container { 
                box-shadow: none; 
                border: 1px solid #ddd;
            }
            
            .action-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class='ticket-container'>
        <div class='header'>
            <h1>🎫 EVENT BOOKING TICKET</h1>
            <p>Official Ticket Confirmation</p>
            <div class='status-badge'>✓ CONFIRMED</div>
        </div>
        
        <div class='ticket-body'>
            <div class='ticket-info'>
                <div class='event-details'>
                    <h2>{$booking['title']}</h2>
                    <p>{$booking['description']}</p>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>📍 Location:</div>
                        <div class='detail-value'>{$booking['location']}</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>📅 Date:</div>
                        <div class='detail-value'>$event_date</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>⏰ Time:</div>
                        <div class='detail-value'>$event_time</div>
                    </div>
                </div>
                
                <div class='ticket-details'>
                    <div class='ticket-id'>Ticket #$ticket_id</div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>Attendee:</div>
                        <div class='detail-value'>{$booking['full_name']}</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>Email:</div>
                        <div class='detail-value'>{$booking['email']}</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>Tickets:</div>
                        <div class='detail-value'>{$booking['tickets']}</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>Total Paid:</div>
                        <div class='detail-value price-highlight'>KSh{$booking['total_amount']}</div>
                    </div>
                    
                    <div class='detail-row'>
                        <div class='detail-label'>Booking ID:</div>
                        <div class='detail-value'>#{$booking['id']}</div>
                    </div>
                </div>
            </div>
            
            <div class='qr-section'>
                <div class='qr-code'>
                    <img src='$qr_url' alt='QR Code' width='130' height='130' />
                </div>
                <p style='margin-top: 10px; color: #666; font-size: 14px;'>Scan at venue entrance</p>
            </div>
            
            <div class='barcode'>
                $ticket_id
            </div>
        </div>
        
        <div class='footer'>
            <h3>Important Information</h3>
            <ul>
                <li>Please bring this ticket (digital or printed) to the event venue</li>
                <li>Show the QR code and ticket ID at the entrance for verification</li>
                <li>Arrive at least 15 minutes before the event starts</li>
                <li>This ticket is non-refundable unless the event is cancelled</li>
                <li>Keep this ticket safe - do not share with others</li>
            </ul>
            <p style='margin-top: 20px; color: #666;'>
                <strong>Questions?</strong> Contact us at support@eventbooking.com<br>
                <strong>Phone:</strong> +254 700 000 000 | <strong>Website:</strong> www.eventbooking.com
            </p>
        </div>
        
        <div class='action-buttons'>
            <button class='btn btn-primary' onclick='window.print()'>
                <i class='fas fa-print'></i> Print Ticket
            </button>
            <button class='btn btn-secondary' onclick='window.close()'>
                <i class='fas fa-times'></i> Close
            </button>
        </div>
    </div>
    
    <script>
        // Auto-print dialog when page loads
        window.onload = function() {
            setTimeout(function() {
                // Optional: show print dialog automatically
                // window.print();
            }, 1000);
        };
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>";
    }
    
    /**
     * Show error page
     */
    private function showError($message) {
        echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .error-container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <div class='error-icon'>⚠️</div>
        <h2 class='error-title'>Error</h2>
        <p class='error-message'>$message</p>
        <a href='../frontend/dashboard.html' class='btn'>Back to Dashboard</a>
    </div>
</body>
</html>";
    }
}

// Handle ticket generation request
if (isset($_GET['booking_id']) && is_numeric($_GET['booking_id'])) {
    session_start();
    
    // Verify user is logged in and owns this booking
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../frontend/login.html');
        exit;
    }
    
    $booking_id = intval($_GET['booking_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify booking ownership
    $check_query = "SELECT user_id FROM bookings WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../frontend/dashboard.html?error=access_denied');
        exit;
    }
    
    // Generate printable ticket
    $ticket_generator = new PrintTicketGenerator($conn);
    $ticket_generator->generatePrintableTicket($booking_id);
} else {
    header('Location: ../frontend/dashboard.html?error=invalid_request');
    exit;
}
?>
