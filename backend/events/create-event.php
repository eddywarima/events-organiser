<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";
require_once "../utils/csrf.php";
require_once "../utils/sanitizer.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// Validate CSRF token
CSRFProtection::validateRequest();

// Sanitize and validate input
$rules = [
    'title' => 'string',
    'description' => 'text',
    'location' => 'string',
    'event_date' => 'date',
    'event_time' => 'time',
    'total_tickets' => 'int',
    'ticket_price' => 'number'
];
$cleaned = InputSanitizer::cleanPost($rules);

// Validate each field
if ($cleaned['title'] === false || empty($cleaned['title'])) {
    die("Invalid title");
}
if ($cleaned['description'] === false || empty($cleaned['description'])) {
    die("Invalid description");
}
if ($cleaned['location'] === false || empty($cleaned['location'])) {
    die("Invalid location");
}
if ($cleaned['event_date'] === false) {
    die("Invalid date format");
}
if ($cleaned['event_time'] === false) {
    die("Invalid time format");
}
if ($cleaned['total_tickets'] === false || $cleaned['total_tickets'] < 1) {
    die("Invalid number of tickets");
}
if ($cleaned['ticket_price'] === false || $cleaned['ticket_price'] < 0) {
    die("Invalid ticket price");
}

// Get form data
$title = $cleaned['title'];
$description = $cleaned['description'];
$location = $cleaned['location'];
$event_date = $cleaned['event_date'];
$event_time = $cleaned['event_time'];
$total_tickets = $cleaned['total_tickets'];
$ticket_price = $cleaned['ticket_price'];
$available_tickets = $total_tickets;

// Validate event date
if (strtotime($event_date) < strtotime(date('Y-m-d'))) {
    die("Event date must be in the future.");
}

// Handle image upload
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 1048576; // 1MB

    $cleaned_file = InputSanitizer::cleanFile($_FILES['image'], $allowed_types, $max_size);

    if ($cleaned_file === false) {
        die("Invalid image file. Only JPG, PNG, GIF allowed, max 1MB.");
    }

    $image_path = "uploads/events/" . $cleaned_file['name'];
    move_uploaded_file($cleaned_file['tmp_name'], $image_path);
}

// Insert into DB
$stmt = $conn->prepare(
    "INSERT INTO events (title, description, location, event_date, event_time, total_tickets, available_tickets, ticket_price, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssssiiids",
    $title,
    $description,
    $location,
    $event_date,
    $event_time,
    $total_tickets,
    $available_tickets,
    $ticket_price,
    $image_path
);

if ($stmt->execute()) {
    $base_url = "http://localhost/event%20booking/frontend/";
    header("Location: " . $base_url . "admin-dashboard.html");
    exit;
} else {
    die("Error creating event");
}
