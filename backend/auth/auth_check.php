<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/login.html");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}
