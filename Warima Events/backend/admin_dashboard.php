<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();

    // Fetch total users count
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetchColumn();

    // Fetch total events count
    $stmt = $pdo->query("SELECT COUNT(*) as total_events FROM events");
    $totalEvents = $stmt->fetchColumn();

    // Prepare response
    $response = [
        'success' => true,
        'total_users' => (int)$totalUsers,
        'total_events' => (int)$totalEvents,
        'last_updated' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}