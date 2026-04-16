<?php
/**
 * Test Simple Categories API
 */

echo "<h2>Testing Simple Categories API</h2>";

// Test the API
$url = 'http://localhost/event%20booking/backend/categories/get-categories-simple.php';

echo "<h3>Direct API Call:</h3>";
echo "<pre>";

// Use file_get_contents to test
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to call API\n";
} else {
    echo "Raw Response:\n";
    echo htmlspecialchars($response) . "\n\n";
    
    // Test JSON
    $json = json_decode($response, true);
    if ($json === null) {
        echo "❌ NOT valid JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    } else {
        echo "✅ Valid JSON\n";
        echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
        if (isset($json['categories'])) {
            echo "Categories count: " . count($json['categories']) . "\n";
            if (count($json['categories']) > 0) {
                echo "First category: " . $json['categories'][0]['name'] . "\n";
            }
        }
        if (isset($json['error'])) {
            echo "Error: " . $json['error'] . "\n";
        }
    }
}

echo "</pre>";

echo "<h3>Database Check:</h3>";
echo "<pre>";

try {
    $conn = new mysqli('localhost', 'root', '', 'event_booking_system');
    if ($conn->connect_error) {
        echo "❌ Database connection failed\n";
        echo "Error: " . $conn->connect_error . "\n";
        echo "\n<strong>SOLUTION:</strong>\n";
        echo "1. Start MySQL in XAMPP Control Panel\n";
        echo "2. Create database 'event_booking_system'\n";
        echo "3. Import categories table\n";
    } else {
        echo "✅ Database connected\n";
        
        // Check if categories table exists
        $result = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Categories table exists\n";
            
            // Count records
            $count = $conn->query("SELECT COUNT(*) as count FROM categories");
            if ($count) {
                $num = $count->fetch_assoc()['count'];
                echo "✅ Categories table has $num records\n";
                
                if ($num == 0) {
                    echo "⚠️  Table is empty - need to insert default categories\n";
                }
            }
        } else {
            echo "❌ Categories table does not exist\n";
            echo "\n<strong>SOLUTION:</strong>\n";
            echo "Import the database schema file:\n";
            echo "backend/database/empty_schema.sql\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Start MySQL</strong> in XAMPP Control Panel</li>";
echo "<li><strong>Test this page</strong> to verify API works</li>";
echo "<li><strong>Refresh admin-categories.html</strong> - should now work</li>";
echo "</ol>";
?>
