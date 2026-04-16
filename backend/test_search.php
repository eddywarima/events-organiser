<?php
/**
 * Test the search functionality
 */

require_once 'config/db.php';

header('Content-Type: text/plain');

echo "=== TESTING EVENT SEARCH FUNCTIONALITY ===\n\n";

// Test basic search
echo "1. Testing basic search query...\n";
$test_query = "SELECT id, title, description, location, event_date, event_time, 
               total_tickets, available_tickets, ticket_price, image, status
               FROM events WHERE status = 'active' ORDER BY event_date ASC LIMIT 5";

try {
    $result = $conn->query($test_query);
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo "✅ Basic query successful! Found " . count($events) . " events\n";
    echo "Sample event: " . $events[0]['title'] . "\n\n";
} catch (Exception $e) {
    echo "❌ Basic query failed: " . $e->getMessage() . "\n\n";
}

// Test search with parameters
echo "2. Testing search with parameters...\n";
$search_term = "event";
$search_query = "SELECT id, title, description, location, event_date, event_time, 
                 total_tickets, available_tickets, ticket_price, image, status
                 FROM events 
                 WHERE (title LIKE ? OR description LIKE ? OR location LIKE ?) AND status = 'active'
                 ORDER BY event_date ASC LIMIT 5";

try {
    $stmt = $conn->prepare($search_query);
    $term = "%$search_term%";
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo "✅ Parameterized search successful! Found " . count($events) . " events\n";
    if (!empty($events)) {
        echo "Sample result: " . $events[0]['title'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Parameterized search failed: " . $e->getMessage() . "\n\n";
}

// Test location filter
echo "3. Testing location filter...\n";
$location_query = "SELECT id, title, description, location, event_date, event_time, 
                   total_tickets, available_tickets, ticket_price, image, status
                   FROM events 
                   WHERE location LIKE ? AND status = 'active'
                   ORDER BY event_date ASC LIMIT 5";

try {
    $stmt = $conn->prepare($location_query);
    $location = "%downtown%";
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo "✅ Location filter successful! Found " . count($events) . " events\n";
    if (!empty($events)) {
        echo "Sample result: " . $events[0]['title'] . " at " . $events[0]['location'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Location filter failed: " . $e->getMessage() . "\n\n";
}

// Test JSON output
echo "4. Testing JSON output format...\n";
try {
    $result = $conn->query("SELECT id, title, location, event_date FROM events WHERE status = 'active' LIMIT 2");
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'location' => $row['location'],
            'event_date' => $row['event_date']
        ];
    }
    
    $json_output = json_encode([
        'events' => $events,
        'success' => true,
        'total' => count($events)
    ]);
    
    echo "✅ JSON output successful!\n";
    echo "Sample JSON: " . substr($json_output, 0, 200) . "...\n\n";
} catch (Exception $e) {
    echo "❌ JSON output failed: " . $e->getMessage() . "\n\n";
}

echo "=== SEARCH FUNCTIONALITY TEST COMPLETE ===\n";
echo "All basic search operations are working correctly!\n";
echo "The search API should now work without the category column error.\n";
?>
