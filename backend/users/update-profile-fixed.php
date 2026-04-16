<?php
/**
 * Fixed Profile Update Script
 * Only updates columns that exist in the database
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../config/db.php';
require_once '../utils/csrf.php';
require_once '../utils/sanitizer.php';
require_once '../utils/logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    CSRFProtection::validateRequest();
    
    // Only sanitize and validate fields that exist in database
    $rules = [
        'full_name' => 'string'
    ];
    $cleaned = InputSanitizer::cleanPost($rules);
    
    if ($cleaned === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }
    
    $full_name = $cleaned['full_name'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    // Validate full name
    if ($full_name && strlen($full_name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Full name must be at least 2 characters']);
        exit();
    }
    
    if ($full_name && strlen($full_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Full name must be less than 100 characters']);
        exit();
    }
    
    try {
        // Update user profile (only existing columns)
        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $full_name, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile");
        }
        
        // Log the update
        Logger::info("Profile updated", [
            'user_id' => $user_id,
            'full_name' => $full_name
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully',
            'full_name' => $full_name
        ]);
        
    } catch (Exception $e) {
        Logger::error("Profile update failed", [
            'user_id' => $user_id,
            'error' => $e->getMessage()
        ]);
        
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
