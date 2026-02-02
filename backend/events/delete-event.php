<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

if (!isset($_GET['id'])) {
    die("Event ID missing");
}

$id = intval($_GET['id']);
$conn->query("DELETE FROM events WHERE id=$id");

header("Location: ../../frontend/admin-dashboard.html");
exit;
