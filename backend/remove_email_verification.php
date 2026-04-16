<?php
/**
 * Remove Email Verification Features
 * Cleans up database and removes email verification functionality
 */

require_once 'config/db.php';

header('Content-Type: text/plain');

echo "Removing email verification features...\n";

try {
    // Remove email verification columns from users table
    echo "Removing email verification columns from users table...\n";
    
    $conn->query("ALTER TABLE users DROP COLUMN IF EXISTS email_verified");
    echo "✓ Removed email_verified column\n";
    
    $conn->query("ALTER TABLE users DROP COLUMN IF EXISTS email_verification_token");
    echo "✓ Removed email_verification_token column\n";
    
    $conn->query("ALTER TABLE users DROP COLUMN IF EXISTS email_verification_expires");
    echo "✓ Removed email_verification_expires column\n";
    
    $conn->query("ALTER TABLE users DROP COLUMN IF EXISTS email_verification_attempts");
    echo "✓ Removed email_verification_attempts column\n";
    
    // Drop email verification tables
    echo "Dropping email verification tables...\n";
    
    $conn->query("DROP TABLE IF EXISTS email_verification_logs");
    echo "✓ Dropped email_verification_logs table\n";
    
    $conn->query("DROP TABLE IF EXISTS email_verification_settings");
    echo "✓ Dropped email_verification_settings table\n";
    
    echo "\nEmail verification features removed successfully!\n";
    echo "The system now works without email verification.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database permissions.\n";
}
?>
