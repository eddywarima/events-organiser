<?php
/**
 * PDF Ticket Generator
 * Generates downloadable PDF tickets for confirmed bookings
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/logger.php';

class PDFTicketGenerator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Generate and serve PDF ticket for a booking
     */
    public function generateTicketPDF($booking_id) {
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
            
            // Generate PDF
            $pdf_content = $this->createPDFContent($booking, $ticket_id);
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Ticket_' . $ticket_id . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            echo $pdf_content;
            exit;
            
        } catch (Exception $e) {
            Logger::error("PDF generation failed", [
                'booking_id' => $booking_id,
                'error' => $e->getMessage()
            ]);
            
            // Redirect with error
            header('Location: ../frontend/dashboard.html?error=pdf_failed');
            exit;
        }
    }
    
    /**
     * Create PDF content using HTML to PDF conversion
     */
    private function createPDFContent($booking, $ticket_id) {
        $event_date = date('F j, Y', strtotime($booking['event_date']));
        $event_time = date('g:i A', strtotime($booking['event_time']));
        
        // Generate QR code URL (simplified - in production you'd use a proper QR library)
        $qr_data = "TICKET_ID:$ticket_id|EVENT:" . urlencode($booking['title']) . "|DATE:" . $booking['event_date'] . "|EMAIL:" . urlencode($booking['email']);
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
        
        $html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Event Ticket - $ticket_id</title>
    <style>
        @page {
            size: A4;
            margin: 20px;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
        
        .ticket-body {
            padding: 40px;
        }
        
        .ticket-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
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
        
        .status-badge {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        
        @media print {
            body { margin: 0; }
            .ticket-container { box-shadow: none; }
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
    </div>
</body>
</html>";

        return $html;
    }
}

// Handle PDF generation request
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
    
    // Generate PDF
    $pdf_generator = new PDFTicketGenerator($conn);
    $pdf_generator->generateTicketPDF($booking_id);
} else {
    header('Location: ../frontend/dashboard.html?error=invalid_request');
    exit;
}
?>
