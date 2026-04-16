<?php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    // Check if this is an API request (expects JSON)
    $is_api_request = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) ||
                     (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                     (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    if ($is_api_request) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    } else {
        header("Location: ../../frontend/login.html");
        exit;
    }
}
?>
