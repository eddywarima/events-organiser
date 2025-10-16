<?php
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php'; // We'll create this file next

try {
    $pdo = Database::getConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'fetch_events':
        fetchEvents($pdo);
        break;
    case 'update_status':
        updateEventStatus($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function fetchEvents($pdo) {
    try {
        $query = "SELECT e.id, e.event_type, e.user_id, e.event_date, e.status, 
                         u.name as user_name, u.email as user_email
                  FROM events e 
                  LEFT JOIN users u ON e.user_id = u.id 
                  ORDER BY e.event_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        echo json_encode(['success' => true, 'events' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function updateEventStatus($pdo) {
    $eventId = filter_input(INPUT_POST, 'eventId', FILTER_VALIDATE_INT);
    $status = htmlspecialchars($_POST['status'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    if (!$eventId || !in_array($status, ['accepted', 'rejected', 'canceled'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get event details with user email
        $stmt = $pdo->prepare("SELECT e.*, u.email, u.name FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            throw new Exception("Event not found");
        }
        
        // Validate status transition
        $validTransitions = [
            'pending' => ['accepted', 'rejected'],
            'accepted' => ['canceled']
        ];
        
        if (isset($validTransitions[$event['status']]) && !in_array($status, $validTransitions[$event['status']])) {
            throw new Exception("Invalid status transition");
        }
        
        // Update status
        $updateStmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        $updateStmt->execute([$status, $eventId]);
        
        // Send email notification
        $emailSent = sendStatusEmail($event['email'], $event['name'], $event['event_type'], $status, $event['event_date']);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Event status updated",
            'emailSent' => $emailSent
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function sendStatusEmail($toEmail, $userName, $eventType, $status, $eventDate) {
    $subject = "Your event booking has been " . ucfirst($status);
    
    $message = "
    <html>
    <head>
        <title>Event Status Update</title>
    </head>
    <body>
        <h2>Hello $userName,</h2>
        <p>Your event booking for <strong>$eventType</strong> on " . date('F j, Y', strtotime($eventDate)) . " has been <strong>$status</strong>.</p>
        <p>Status: <span style='color: " . getStatusColor($status) . "; font-weight: bold;'>$status</span></p>
        <p>If you have any questions, please contact our support team.</p>
        <br>
        <p>Best regards,<br>Event Management Team</p>
    </body>
    </html>
    ";
    
    return Mailer::send($toEmail, $subject, $message);
}

function getStatusColor($status) {
    $colors = [
        'accepted' => '#28a745',
        'rejected' => '#dc3545',
        'canceled' => '#ffc107'
    ];
    return $colors[$status] ?? '#6c757d';
}