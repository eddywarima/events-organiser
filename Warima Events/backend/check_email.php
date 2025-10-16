<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    if (empty($input['email'])) {
        throw new Exception('Email parameter is required');
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    $pdo = Database::getConnection();

    // Get detailed email status
    $stmt = $pdo->prepare("SELECT 
        id,
        deleted_at IS NOT NULL AS is_deleted,
        deleted_at
        FROM users 
        WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch();

    $response = [
        'valid' => true,
        'exists' => !empty($result),
        'is_active' => !empty($result) && empty($result['is_deleted']),
        'can_register' => empty($result) || !empty($result['is_deleted'])
    ];

    if (!empty($result)) {
        $response['account_status'] = $result['is_deleted'] ? 'deleted' : 'active';
        if ($result['is_deleted']) {
            $response['deleted_since'] = $result['deleted_at'];
        }
    }

    ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database operation failed',
        'valid' => false
    ]);
} catch (Exception $e) {
    error_log("System Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'valid' => false
    ]);
}