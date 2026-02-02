<?php
/**
 * Database connection file
 * Used across the entire backend
 */

$DB_HOST = "localhost";
$DB_USER = "root";        // change if needed
$DB_PASS = "";            // change if needed
$DB_NAME = "event_booking_system";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset for security & performance
$conn->set_charset("utf8mb4");
