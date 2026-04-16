<?php
/**
 * Cleanup script for expired password reset tokens
 * This script should be run periodically (e.g., via cron job)
 */

require_once "../config/db.php";
require_once "../utils/password_reset.php";
require_once "../utils/logger.php";

echo "Starting cleanup of expired tokens...\n";

try {
    // Clean up expired tokens
    PasswordReset::cleanupExpiredTokens();
    
    Logger::info("Expired password reset tokens cleaned up");
    
    echo "Cleanup completed successfully.\n";
    
} catch (Exception $e) {
    Logger::error("Failed to cleanup expired tokens", ['error' => $e->getMessage()]);
    echo "Cleanup failed: " . $e->getMessage() . "\n";
}
?>
