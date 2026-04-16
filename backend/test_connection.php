<?php
/**
 * Database Connection Test
 * Helps diagnose connection issues
 */

echo "Testing database connection...\n\n";

// Test 1: Basic connection without database
try {
    $conn = new mysqli("localhost", "root", "", "");
    echo "✓ MySQL server is running\n";
    echo "✓ Connection to MySQL successful\n";
    $conn->close();
} catch (Exception $e) {
    echo "✗ Cannot connect to MySQL server\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nSolution: Start MySQL in XAMPP Control Panel\n";
    exit();
}

// Test 2: Database exists
try {
    $conn = new mysqli("localhost", "root", "", "event_booking_system");
    echo "✓ Database 'event_booking_system' exists\n";
    $conn->close();
} catch (Exception $e) {
    echo "✗ Database 'event_booking_system' not found\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nSolution: Run the database schema file\n";
}

echo "\nConnection test completed.\n";
?>
