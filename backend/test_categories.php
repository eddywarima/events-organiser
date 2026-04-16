<?php
/**
 * Test Categories API
 * Helps debug the categories loading issue
 */

header('Content-Type: text/plain');

echo "Testing Categories API...\n\n";

// Test 1: Direct API call
echo "1. Testing direct API call...\n";
$url = 'http://localhost/event%20booking/backend/categories/get-categories.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "✗ Failed to call API\n";
} else {
    echo "✓ API responded\n";
    echo "Response length: " . strlen($response) . " characters\n";
    
    // Check if response is valid JSON
    $json = json_decode($response, true);
    if ($json === null) {
        echo "✗ Response is not valid JSON\n";
        echo "First 200 characters:\n";
        echo substr($response, 0, 200) . "\n";
    } else {
        echo "✓ Response is valid JSON\n";
        if (isset($json['success'])) {
            echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        }
        if (isset($json['categories'])) {
            echo "Categories count: " . count($json['categories']) . "\n";
        }
        if (isset($json['error'])) {
            echo "Error: " . $json['error'] . "\n";
        }
    }
}

echo "\n2. Testing database connection...\n";
try {
    require_once 'config/db.php';
    if ($conn->connect_error) {
        echo "✗ Database connection failed: " . $conn->connect_error . "\n";
    } else {
        echo "✓ Database connection successful\n";
        
        // Test categories table
        $result = $conn->query("SELECT COUNT(*) as count FROM categories");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✓ Categories table exists with $count records\n";
        } else {
            echo "✗ Categories table query failed: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
