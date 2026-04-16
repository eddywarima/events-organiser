<?php
/**
 * Debug Categories API
 * Shows exactly what the API is returning
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG: Categories API ===\n\n";

// Test 1: Check if required files exist
echo "1. Checking required files...\n";
$required_files = [
    '../config/db.php',
    '../utils/sanitizer.php',
    '../utils/logger.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

echo "\n2. Testing database connection...\n";
try {
    require_once '../config/db.php';
    if ($conn->connect_error) {
        echo "✗ Database connection failed: " . $conn->connect_error . "\n";
        exit();
    } else {
        echo "✓ Database connected\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit();
}

echo "\n3. Testing categories table...\n";
$result = $conn->query("SHOW TABLES LIKE 'categories'");
if ($result && $result->num_rows > 0) {
    echo "✓ Categories table exists\n";
    
    $count_result = $conn->query("SELECT COUNT(*) as count FROM categories");
    if ($count_result) {
        $count = $count_result->fetch_assoc()['count'];
        echo "✓ Categories table has $count records\n";
    } else {
        echo "✗ Cannot count categories: " . $conn->error . "\n";
    }
} else {
    echo "✗ Categories table does not exist\n";
    echo "  SOLUTION: Import the database schema\n";
    exit();
}

echo "\n4. Testing the actual API...\n";
echo "Calling: get-categories.php\n";
echo "---\n";

// Capture output
ob_start();
include 'get-categories.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output;
echo "\n---\n";

// Check if it's valid JSON
$json = json_decode($output, true);
if ($json === null) {
    echo "✗ Output is NOT valid JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    
    // Look for HTML error patterns
    if (strpos($output, '<br') !== false) {
        echo "✗ Contains HTML error tags\n";
    }
    if (strpos($output, 'Fatal error') !== false) {
        echo "✗ Contains Fatal error\n";
    }
    if (strpos($output, 'Warning') !== false) {
        echo "✗ Contains PHP Warning\n";
    }
} else {
    echo "✓ Output is valid JSON\n";
    if (isset($json['success'])) {
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    }
    if (isset($json['categories'])) {
        echo "Categories returned: " . count($json['categories']) . "\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
