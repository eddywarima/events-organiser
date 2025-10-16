<?php
// Include the database connection class
require_once 'db.php';

header('Content-Type: application/json');

// Get the status filter from the URL parameter (default is 'all')
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    // Get the database connection
    $conn = Database::getConnection();

    // Prepare the SQL query based on the status filter
    if ($status === 'all') {
        $sql = "SELECT r.id, u.name AS user_name, r.report_text, r.status, r.submission_date, r.ip_address, r.user_agent, r.created_at
                FROM reports r
                JOIN users u ON r.user_id = u.id
                ORDER BY r.submission_date DESC";
    } else {
        $sql = "SELECT r.id, u.name AS user_name, r.report_text, r.status, r.submission_date, r.ip_address, r.user_agent, r.created_at
                FROM reports r
                JOIN users u ON r.user_id = u.id
                WHERE r.status = :status
                ORDER BY r.submission_date DESC";
    }

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);

    if ($status !== 'all') {
        $stmt->bindParam(':status', $status);
    }

    $stmt->execute();

    // Fetch all reports as an associative array
    $reports = $stmt->fetchAll();

    // Return the reports as JSON
    echo json_encode(['reviews' => $reports]);

} catch (PDOException $e) {
    // Log error and return a JSON error response
    error_log("Error fetching reviews: " . $e->getMessage());
    echo json_encode(['error' => 'Error loading reviews.']);
}
?>
