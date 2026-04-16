<?php
require_once "../config/db.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

header('Content-Type: application/json');

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $conn->connect_error ?? 'Unknown error'
    ]);
    exit();
}

// Get categories with event counts
$include_stats = $_GET['include_stats'] ?? 'false';
$active_only = $_GET['active_only'] ?? 'true';

// Sanitize inputs
$sanitized = InputSanitizer::cleanGet([
    'include_stats' => 'boolean',
    'active_only' => 'boolean'
]);

if ($sanitized === false) {
    Logger::warning("Invalid parameters in get-categories", ['params' => $_GET]);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$include_stats = $sanitized['include_stats'] ?? false;
$active_only = $sanitized['active_only'] ?? true;

// Build query
if ($include_stats) {
    $query = "
        SELECT 
            c.id, c.name, c.description, c.icon, c.color, c.sort_order, c.is_active,
            COALESCE(event_counts.event_count, 0) as event_count,
            COALESCE(active_counts.active_events, 0) as active_events
        FROM categories c
        LEFT JOIN (
            SELECT category_id, COUNT(*) as event_count
            FROM events
            GROUP BY category_id
        ) event_counts ON c.id = event_counts.category_id
        LEFT JOIN (
            SELECT category_id, COUNT(*) as active_events
            FROM events
            WHERE status = 'active'
            GROUP BY category_id
        ) active_counts ON c.id = active_counts.category_id
        " . ($active_only ? "WHERE c.is_active = TRUE" : "") . "
        ORDER BY c.sort_order, c.name
    ";
} else {
    $query = "
        SELECT id, name, description, icon, color, sort_order, is_active
        FROM categories 
        " . ($active_only ? "WHERE is_active = TRUE" : "") . "
        ORDER BY sort_order, name
    ";
}

$result = $conn->query($query);

if (!$result) {
    Logger::error("Database query failed in get-categories", [
        'error' => $conn->error,
        'query' => $query
    ]);
    echo json_encode([
        'success' => false,
        'error' => 'Database query failed',
        'details' => $conn->error
    ]);
    exit();
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
        'is_active' => (bool)$row['is_active'],
        'event_count' => isset($row['event_count']) ? (int)$row['event_count'] : null,
        'active_events' => isset($row['active_events']) ? (int)$row['active_events'] : null
    ];
}

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'total' => count($categories)
]);
?>
