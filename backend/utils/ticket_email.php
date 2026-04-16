<?php
/**
 * Digital Ticket Email System
 * Sends booking confirmation emails with digital tickets
 */

require_once 'logger.php';
require_once '../config/email_config.php';

class TicketEmail {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Send booking confirmation with digital ticket
     */
    public function sendBookingConfirmation($booking_id, $user_email, $user_name, $event_title, $event_date, $event_time, $event_location, $tickets, $total_amount) {
        // Generate unique ticket ID
        $ticket_id = $this->generateTicketId($booking_id);
        
        // Email subject
        $subject = "🎫 Your Event Booking Confirmation - Ticket #" . $ticket_id;
        
        // Email body
        $message = $this->generateTicketEmail($ticket_id, $user_name, $event_title, $event_date, $event_time, $event_location, $tickets, $total_amount);
        
        // Headers
        $headers = [
            'From: noreply@eventbooking.com',
            'Reply-To: support@eventbooking.com',
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0'
        ];
        
        // Send email using new configuration
        $success = EmailConfig::sendEmail($user_email, $subject, $message, $headers);
        
        // Log the attempt
        if ($success) {
            Logger::info("Ticket email sent successfully", [
                'booking_id' => $booking_id,
                'ticket_id' => $ticket_id,
                'user_email' => $user_email,
                'event_title' => $event_title
            ]);
        } else {
            Logger::error("Failed to send ticket email", [
                'booking_id' => $booking_id,
                'ticket_id' => $ticket_id,
                'user_email' => $user_email,
                'error' => error_get_last()['message'] ?? 'Unknown error'
            ]);
        }
        
        return $success;
    }
    
    /**
     * Generate unique ticket ID
     */
    private function generateTicketId($booking_id) {
        return 'TKT' . date('Y') . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . strtoupper(substr(md5($booking_id . time()), 0, 4));
    }
    
    /**
     * Generate HTML email with digital ticket
     */
    private function generateTicketEmail($ticket_id, $user_name, $event_title, $event_date, $event_time, $event_location, $tickets, $total_amount) {
        $event_date_formatted = date('F j, Y', strtotime($event_date));
        $event_time_formatted = date('g:i A', strtotime($event_time));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Event Booking Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
                .ticket { background: #fff; border: 2px dashed #007bff; margin: 20px; padding: 20px; border-radius: 8px; position: relative; }
                .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
                .ticket-id { font-size: 18px; font-weight: bold; color: #007bff; }
                .event-title { font-size: 24px; font-weight: bold; color: #333; margin: 20px 0; }
                .event-details { margin: 20px 0; }
                .detail-row { display: flex; margin-bottom: 10px; }
                .detail-label { font-weight: bold; width: 120px; color: #666; }
                .detail-value { flex: 1; color: #333; }
                .qr-section { text-align: center; margin: 30px 0; }
                .qr-placeholder { width: 150px; height: 150px; border: 2px solid #ddd; display: inline-block; line-height: 150px; text-align: center; color: #999; font-size: 12px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                .barcode { font-family: 'Courier New', monospace; font-size: 20px; letter-spacing: 2px; margin: 20px 0; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎫 Event Booking Confirmation</h1>
                    <p>Your digital tickets are ready!</p>
                </div>
                
                <div class='ticket'>
                    <div class='ticket-header'>
                        <div class='ticket-id'>Ticket #$ticket_id</div>
                        <div style='color: #28a745; font-weight: bold;'>✓ CONFIRMED</div>
                    </div>
                    
                    <div class='event-title'>$event_title</div>
                    
                    <div class='event-details'>
                        <div class='detail-row'>
                            <div class='detail-label'>Attendee:</div>
                            <div class='detail-value'>$user_name</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Date:</div>
                            <div class='detail-value'>$event_date_formatted</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Time:</div>
                            <div class='detail-value'>$event_time_formatted</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Location:</div>
                            <div class='detail-value'>$event_location</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Tickets:</div>
                            <div class='detail-value'>$tickets</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>Total Paid:</div>
                            <div class='detail-value' style='font-size: 18px; color: #007bff; font-weight: bold;'>KSh$total_amount</div>
                        </div>
                    </div>
                    
                    <div class='barcode'>
                        $ticket_id
                    </div>
                    
                    <div class='qr-section'>
                        <div class='qr-placeholder'>
                            QR Code<br>
                            (Scan at venue)
                        </div>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>Important Information:</strong></p>
                    <ul style='text-align: left;'>
                        <li>Please bring this confirmation email (digital or printed) to the event venue</li>
                        <li>Show the ticket ID and QR code at the entrance</li>
                        <li>Arrive 15 minutes before the event starts</li>
                        <li>This ticket is non-refundable unless the event is cancelled</li>
                    </ul>
                    
                    <div style='background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin: 0 0 10px 0;'>�️ Print Your Ticket</h3>
                        <p style='margin: 0 0 15px 0;'>Click below to open your ticket for printing</p>
                        <a href='http://localhost/event%20booking/backend/utils/print_ticket.php?booking_id=$booking_id' 
                           style='background: white; color: #007bff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                            Print Ticket
                        </a>
                    </div>
                    
                    <p style='margin-top: 20px;'>
                        Thank you for choosing Event Booking!<br>
                        Questions? Contact us at support@eventbooking.com
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send booking cancellation email
     */
    public function sendCancellationNotice($booking_id, $user_email, $user_name, $event_title, $cancellation_reason = '') {
        $subject = "Event Booking Cancellation - $event_title";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Booking Cancellation</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; }
                .header { background: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Booking Cancelled</h1>
                </div>
                <div class='content'>
                    <p>Dear $user_name,</p>
                    <p>Your booking for <strong>$event_title</strong> has been cancelled.</p>
                    " . ($cancellation_reason ? "<p><strong>Reason:</strong> $cancellation_reason</p>" : "") . "
                    <p>We apologize for any inconvenience. If you have any questions, please contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>Event Booking Support<br>
                    support@eventbooking.com</p>
                </div>
            </div>
        </body>
        </html>";
        
        $headers = [
            'From: noreply@eventbooking.com',
            'Reply-To: support@eventbooking.com',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return EmailConfig::sendEmail($user_email, $subject, $message, $headers);
    }
}
?>
