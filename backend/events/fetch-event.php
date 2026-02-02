<?php
require_once "../config/db.php";

// Public access to view event details
$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM events WHERE id=$id AND status='active'");
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Event not found']);
    exit;
}
$event = $result->fetch_assoc();
echo json_encode($event);
