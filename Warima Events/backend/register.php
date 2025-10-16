<?php
// Environment configuration - set to 'development' or 'production'
define('ENVIRONMENT', 'development');

// Buffer all output to prevent corrupting JSON
ob_start();

// Set strict JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Error handling configuration
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'db.php';

/**
 * Send JSON response and terminate script
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    ob_end_clean();
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

try {
    // Get and validate JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }

    // Validate required fields
    $required = ['name', 'email', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            json_response(['success' => false, 'message' => "$field is required"], 400);
        }
    }

    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    // Validate password match
    if ($input['password'] !== $input['confirm_password']) {
        json_response(['success' => false, 'message' => 'Passwords do not match'], 400);
    }

    // Validate password strength
    if (strlen($input['password']) < 6) {
        json_response(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
    }

    // Database operations
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // Check for existing ACTIVE user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->execute([$input['email']]);
    
    if ($stmt->rowCount() > 0) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Email already registered to an active account'], 409);
    }

    // Check for soft-deleted user to reactivate
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NOT NULL FOR UPDATE");
    $stmt->execute([$input['email']]);
    $userExists = $stmt->fetch();

    // Hash password
    $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    if (!$hashedPassword) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Password hashing failed'], 500);
    }

    if ($userExists) {
        // Reactivate deleted account
        $stmt = $pdo->prepare("UPDATE users SET 
            name = :name,
            password = :password,
            deleted_at = NULL,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = :id");
        $success = $stmt->execute([
            ':name' => $input['name'],
            ':password' => $hashedPassword,
            ':id' => $userExists['id']
        ]);
        $userId = $userExists['id'];
    } else {
        // Create new account
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $success = $stmt->execute([
            ':name' => $input['name'],
            ':email' => $input['email'],
            ':password' => $hashedPassword
        ]);
        $userId = $pdo->lastInsertId();
    }

    if (!$success || !$userId) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Registration failed'], 500);
    }

    $pdo->commit();

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Set session data
    $_SESSION = [
        'user_id' => $userId,
        'user_email' => $input['email'],
        'user_name' => $input['name'],
        'logged_in' => true
    ];

    // Successful response
    json_response([
        'success' => true,
        'message' => 'Registration successful',
        'redirect' => 'dashboard.html',
        'user_id' => $userId
    ]);

} catch (PDOException $e) {
    // Database error handling
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Database operation failed',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ], 500);
} catch (Exception $e) {
    // General error handling
    error_log("System Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}