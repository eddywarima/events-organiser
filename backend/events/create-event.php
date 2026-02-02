<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// Get form data
$title = $_POST['title'];
$description = $_POST['description'];
$location = $_POST['location'];
$event_date = $_POST['event_date'];
$event_time = $_POST['event_time'];
$total_tickets = intval($_POST['total_tickets']);
$ticket_price = floatval($_POST['ticket_price']);
$available_tickets = $total_tickets;

// Validate event date
if (strtotime($event_date) < strtotime(date('Y-m-d'))) {
    die("Event date must be in the future.");
}

// Handle image upload
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        die("Invalid image type. Only JPG, PNG, GIF allowed.");
    }
    if ($_FILES['image']['size'] > 1048576) { // 1MB
        die("Image too large. Max 1MB.");
    }
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_path = "../uploads/events/" . time() . "." . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
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
