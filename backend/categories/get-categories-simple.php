<?php
/**
 * Simple Categories API
 * Minimal version to avoid browser security issues
 */

// Set headers first to prevent any HTML output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display to prevent HTML in output
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Basic database connection
    $conn = new mysqli('localhost', 'root', '', 'event_booking_system');
    
    if ($conn->connect_error) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'categories' => []
        ]);
        exit;
    }
    
    // Simple query - no complex joins
    $query = "SELECT id, name, description, icon, color, sort_order, is_active 
               FROM categories 
               WHERE is_active = TRUE 
               ORDER BY sort_order, name";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => 'Query failed',
            'categories' => []
        ]);
        exit;
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'color' => $row['color'],
            'sort_order' => (int)$row['sort_order'],
            'is_active' => (bool)$row['is_active']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'total' => count($categories)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'categories' => []
    ]);
}
?>
