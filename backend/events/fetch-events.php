<?php
require_once "../config/db.php";

// Fetch all active events
$result = $conn->query("SELECT * FROM events WHERE status='active' ORDER BY event_date ASC");
$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

// If no events exist, return a sample event for testing
if (empty($events)) {
    $events[] = [
        'id' => 1,
        'title' => 'Sample Music Concert',
        'description' => 'Enjoy an amazing music concert with top artists! This is a sample event to test the system.',
        'location' => 'Central Park, New York',
        'event_date' => '2026-03-15',
        'event_time' => '19:00:00',
        'total_tickets' => 100,
        'available_tickets' => 100,
        'ticket_price' => 50.00,
        'image' => null,
        'status' => 'active'
    ];
}

echo json_encode($events);
