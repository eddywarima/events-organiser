<?php
require_once "../config/db.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

header('Content-Type: application/json');

// Get search parameters
$query = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 10);
$sort_by = $_GET['sort'] ?? 'event_date';
$sort_order = $_GET['order'] ?? 'ASC';

// Sanitize inputs
$sanitized = InputSanitizer::cleanGet([
    'q' => 'string',
    'location' => 'string',
    'date_from' => 'date',
    'date_to' => 'date',
    'min_price' => 'float',
    'max_price' => 'float',
    'page' => 'int',
    'limit' => 'int',
    'sort' => 'string',
    'order' => 'string'
]);

if ($sanitized === false) {
    Logger::warning("Invalid search parameters", ['params' => $_GET]);
    echo json_encode(['error' => 'Invalid search parameters']);
    exit();
}

// Use sanitized values
$query = $sanitized['q'] ?? '';
$location = $sanitized['location'] ?? '';
$date_from = $sanitized['date_from'] ?? '';
$date_to = $sanitized['date_to'] ?? '';
$min_price = $sanitized['min_price'] ?? '';
$max_price = $sanitized['max_price'] ?? '';
$page = $sanitized['page'] ?? 1;
$limit = $sanitized['limit'] ?? 10;
$sort_by = $sanitized['sort'] ?? 'event_date';
$sort_order = $sanitized['order'] ?? 'ASC';

// Validate sort field
$allowed_sort_fields = ['event_date', 'title', 'ticket_price', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'event_date';
}

// Validate sort order
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Build WHERE conditions
$where_conditions = ["status = 'active'"];
$params = [];
$types = "";

// Search query (title, description, location)
if (!empty($query)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_term = "%" . $query . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Location filter
if (!empty($location)) {
    $where_conditions[] = "location LIKE ?";
    $params[] = "%" . $location . "%";
    $types .= "s";
}

// Date range filter
if (!empty($date_from)) {
    $where_conditions[] = "event_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "event_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Price range filter
if (!empty($min_price)) {
    $where_conditions[] = "ticket_price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $where_conditions[] = "ticket_price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Calculate offset
$offset = ($page - 1) * $limit;

// Build the main query
$where_clause = "WHERE " . implode(" AND ", $where_conditions);
$order_clause = "ORDER BY $sort_by $sort_order";
$limit_clause = "LIMIT $limit OFFSET $offset";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM events $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total = $total_result->fetch_assoc()['total'];

// Get events
$events_query = "
    SELECT id, title, description, location, event_date, event_time, 
           total_tickets, available_tickets, ticket_price, image, status
    FROM events 
    $where_clause 
    $order_clause 
    $limit_clause
";

$stmt = $conn->prepare($events_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'location' => $row['location'],
        'event_date' => $row['event_date'],
        'event_time' => $row['event_time'],
        'total_tickets' => (int)$row['total_tickets'],
        'available_tickets' => (int)$row['available_tickets'],
        'ticket_price' => (float)$row['ticket_price'],
        'image' => $row['image'],
        'status' => $row['status']
    ];
}

// Get available locations for filters
$locations_query = "SELECT DISTINCT location FROM events WHERE status = 'active' ORDER BY location";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row['location'];
}

// Log search
Logger::info("Event search performed", [
    'query' => $query,
    'filters' => [
        'location' => $location,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'min_price' => $min_price,
        'max_price' => $max_price
    ],
    'results_count' => count($events),
    'total_count' => $total
]);

// Return results
echo json_encode([
    'events' => $events,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => (int)$total,
        'total_pages' => ceil($total / $limit)
    ],
    'filters' => [
        'locations' => $locations
    ],
    'search_params' => [
        'query' => $query,
        'location' => $location,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'sort_by' => $sort_by,
        'sort_order' => $sort_order
    ]
]);
?>
