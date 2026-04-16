<?php
/**
 * Email Viewer for Localhost Development
 * Allows viewing emails that would have been sent
 */

require_once 'config/email_config.php';

header('Content-Type: application/json');

// Get action from query parameter
$action = $_GET['action'] ?? 'view';

switch ($action) {
    case 'view':
        echo json_encode([
            'success' => true,
            'emails' => EmailConfig::getEmailLog()
        ]);
        break;
        
    case 'clear':
        EmailConfig::clearEmailLog();
        echo json_encode([
            'success' => true,
            'message' => 'Email log cleared'
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
?>
