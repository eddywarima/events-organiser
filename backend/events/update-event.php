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
$id = intval($_POST['id']);
$title = $_POST['title'];
$description = $_POST['description'];
$location = $_POST['location'];
$event_date = $_POST['event_date'];
$event_time = $_POST['event_time'];
$total_tickets = intval($_POST['total_tickets']);
$ticket_price = floatval($_POST['ticket_price']);

// Validate event date
if (strtotime($event_date) < strtotime(date('Y-m-d'))) {
    die("Event date must be in the future.");
}

// Fetch current available tickets
$current = $conn->query("SELECT total_tickets, available_tickets, image FROM events WHERE id=$id")->fetch_assoc();
$diff = $total_tickets - $current['total_tickets'];
$available_tickets = $current['available_tickets'] + $diff;
$image_path = $current['image'];

// Handle image upload
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

// Update event
$stmt = $conn->prepare(
    "UPDATE events SET title=?, description=?, location=?, event_date=?, event_time=?, total_tickets=?, available_tickets=?, ticket_price=?, image=? WHERE id=?"
);
$stmt->bind_param(
    "ssssiiidsi",
    $title,
    $description,
    $location,
    $event_date,
    $event_time,
    $total_tickets,
    $available_tickets,
    $ticket_price,
    $image_path,
    $id
);

if ($stmt->execute()) {
    $base_url = "http://localhost/event%20booking/frontend/";
    header("Location: " . $base_url . "admin-dashboard.html");
    exit;
} else {
    die("Failed to update event");
}
