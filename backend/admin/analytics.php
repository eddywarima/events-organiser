<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header('Content-Type: application/json');

// Get time period from request
$period = $_GET['period'] ?? '30days'; // 7days, 30days, 90days, 1year

// Calculate date range
$endDate = date('Y-m-d');
switch ($period) {
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case '1year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
}

$analytics = [];

// 1. Overall Statistics
$analytics['overview'] = [
    'total_users' => $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'],
    'total_events' => $conn->query("SELECT COUNT(*) AS count FROM events")->fetch_assoc()['count'],
    'total_bookings' => $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_amount) AS total FROM bookings WHERE booking_status='confirmed'")->fetch_assoc()['total'] ?? 0,
    'active_events' => $conn->query("SELECT COUNT(*) AS count FROM events WHERE status='active' AND event_date >= CURDATE()")->fetch_assoc()['count'],
    'new_users_period' => $conn->query("SELECT COUNT(*) AS count FROM users WHERE created_at >= '$startDate'")->fetch_assoc()['count']
];

// 2. Booking Trends (Daily)
$analytics['booking_trends'] = [];
$bookingQuery = "
    SELECT 
        DATE(booking_date) as date,
        COUNT(*) as bookings,
        SUM(total_amount) as revenue
    FROM bookings 
    WHERE booking_date >= '$startDate' 
    AND booking_status = 'confirmed'
    GROUP BY DATE(booking_date)
    ORDER BY date ASC
";

$result = $conn->query($bookingQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['booking_trends'][] = [
        'date' => $row['date'],
        'bookings' => (int)$row['bookings'],
        'revenue' => (float)$row['revenue']
    ];
}

// 3. User Registration Trends
$analytics['user_trends'] = [];
$userQuery = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as users
    FROM users 
    WHERE created_at >= '$startDate'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$result = $conn->query($userQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['user_trends'][] = [
        'date' => $row['date'],
        'users' => (int)$row['users']
    ];
}

// 4. Top Events by Bookings
$analytics['top_events'] = [];
$topEventsQuery = "
    SELECT 
        e.id,
        e.title,
        COUNT(b.id) as booking_count,
        SUM(b.total_amount) as total_revenue,
        e.event_date
    FROM events e
    LEFT JOIN bookings b ON e.id = b.event_id AND b.booking_status = 'confirmed'
    WHERE e.created_at >= '$startDate'
    GROUP BY e.id, e.title, e.event_date
    ORDER BY booking_count DESC
    LIMIT 10
";

$result = $conn->query($topEventsQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['top_events'][] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'bookings' => (int)$row['booking_count'],
        'revenue' => (float)$row['total_revenue'],
        'event_date' => $row['event_date']
    ];
}

// 5. User Activity Summary
$analytics['user_activity'] = [
    'users_with_bookings' => $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM bookings WHERE booking_status='confirmed'")->fetch_assoc()['count'],
    'avg_bookings_per_user' => $conn->query("SELECT AVG(booking_count) as avg FROM (SELECT COUNT(*) as booking_count FROM bookings WHERE booking_status='confirmed' GROUP BY user_id) as t")->fetch_assoc()['avg'] ?? 0,
    'total_tickets_sold' => $conn->query("SELECT SUM(tickets) as total FROM bookings WHERE booking_status='confirmed'")->fetch_assoc()['total'] ?? 0,
    'avg_tickets_per_booking' => $conn->query("SELECT AVG(tickets) as avg FROM bookings WHERE booking_status='confirmed'")->fetch_assoc()['avg'] ?? 0
];

// 6. Monthly Revenue (for longer periods)
$analytics['monthly_revenue'] = [];
$monthlyQuery = "
    SELECT 
        DATE_FORMAT(booking_date, '%Y-%m') as month,
        SUM(total_amount) as revenue,
        COUNT(*) as bookings
    FROM bookings 
    WHERE booking_date >= '$startDate' 
    AND booking_status = 'confirmed'
    GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
    ORDER BY month ASC
";

$result = $conn->query($monthlyQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['monthly_revenue'][] = [
        'month' => $row['month'],
        'revenue' => (float)$row['revenue'],
        'bookings' => (int)$row['bookings']
    ];
}

// 7. Event Status Distribution
$analytics['event_status'] = [
    'active' => $conn->query("SELECT COUNT(*) as count FROM events WHERE status='active'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM events WHERE status='cancelled'")->fetch_assoc()['count'],
    'past_events' => $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date < CURDATE()")->fetch_assoc()['count']
];

// 8. Booking Status Distribution
$analytics['booking_status'] = [
    'confirmed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status='confirmed'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status='cancelled'")->fetch_assoc()['count']
];

// 9. Recent Activity
$analytics['recent_activity'] = [];
$recentQuery = "
    SELECT 
        'booking' as type,
        b.booking_date as date,
        u.full_name as user_name,
        e.title as event_title,
        b.tickets,
        b.total_amount
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN events e ON b.event_id = e.id
    ORDER BY b.booking_date DESC
    LIMIT 10
";

$result = $conn->query($recentQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['recent_activity'][] = [
        'type' => $row['type'],
        'date' => $row['date'],
        'user_name' => $row['user_name'],
        'event_title' => $row['event_title'],
        'tickets' => (int)$row['tickets'],
        'amount' => (float)$row['total_amount']
    ];
}

// 10. Popular Event Locations
$analytics['popular_locations'] = [];
$locationQuery = "
    SELECT 
        location,
        COUNT(*) as event_count,
        SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_count
    FROM events
    WHERE created_at >= '$startDate'
    GROUP BY location
    ORDER BY event_count DESC
    LIMIT 10
";

$result = $conn->query($locationQuery);
while ($row = $result->fetch_assoc()) {
    $analytics['popular_locations'][] = [
        'location' => $row['location'],
        'events' => (int)$row['event_count'],
        'active' => (int)$row['active_count']
    ];
}

echo json_encode($analytics);
?>
