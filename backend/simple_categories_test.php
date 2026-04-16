<?php
/**
 * Simple Categories Test
 * Bypasses browser issues to test API directly
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Categories API Test</h2>";

// Test 1: Direct file inclusion
echo "<h3>1. Direct API Test:</h3>";
echo "<pre>";

// Capture output
ob_start();
try {
    include 'categories/get-categories.php';
    $output = ob_get_clean();
    
    echo "Raw Output:\n";
    echo htmlspecialchars($output) . "\n\n";
    
    // Test JSON
    $json = json_decode($output, true);
    if ($json === null) {
        echo "❌ NOT VALID JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        
        // Look for common error patterns
        if (strpos($output, 'connect_error') !== false) {
            echo "❌ Database connection error\n";
        }
        if (strpos($output, 'Fatal error') !== false) {
            echo "❌ Fatal PHP error\n";
        }
        if (strpos($output, 'Warning') !== false) {
            echo "❌ PHP warning\n";
        }
    } else {
        echo "✅ Valid JSON\n";
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
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Test 2: Database connection test
echo "<h3>2. Database Connection Test:</h3>";
echo "<pre>";
try {
    require_once 'config/db.php';
    if ($conn->connect_error) {
        echo "❌ Database connection failed\n";
        echo "Error: " . $conn->connect_error . "\n";
    } else {
        echo "✅ Database connected successfully\n";
        
        // Test categories table
        $result = $conn->query("SELECT COUNT(*) as count FROM categories");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Categories table exists with $count records\n";
            
            // Show some sample data
            $sample = $conn->query("SELECT id, name FROM categories LIMIT 3");
            if ($sample && $sample->num_rows > 0) {
                echo "Sample categories:\n";
                while ($row = $sample->fetch_assoc()) {
                    echo "- {$row['id']}: {$row['name']}\n";
                }
            }
        } else {
            echo "❌ Categories table query failed: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Database exception: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 3: File permissions
echo "<h3>3. File Permissions:</h3>";
echo "<pre>";
$files = [
    'config/db.php',
    'utils/sanitizer.php',
    'utils/logger.php',
    'categories/get-categories.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
        if (is_readable($file)) {
            echo "   ✅ readable\n";
        } else {
            echo "   ❌ not readable\n";
        }
    } else {
        echo "❌ $file missing\n";
    }
}
echo "</pre>";

echo "<h3>4. Browser Test:</h3>";
echo "<p>If you see this page, the server is working. The issue is likely:</p>";
echo "<ul>";
echo "<li>MySQL server not running in XAMPP</li>";
echo "<li>Database 'event_booking_system' doesn't exist</li>";
echo "<li>Categories table is empty or missing</li>";
echo "<li>Browser security policies blocking API calls</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Start MySQL in XAMPP Control Panel</li>";
echo "<li>Import database schema if needed</li>";
echo "<li>Check browser console for CORS errors</li>";
echo "</ol>";
?>
