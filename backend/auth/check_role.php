<?php
session_start();

$expected = $_GET['expected'] ?? 'user';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== $expected) {
    echo json_encode(['ok' => false]);
} else {
    echo json_encode(['ok' => true]);
}
?>