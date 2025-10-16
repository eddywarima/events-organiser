<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    // Establish database connection
    $pdo = Database::getConnection();
    
    // Handle different actions
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
        // Fetch all users
        $stmt = $pdo->query("
            SELECT id, name, email, role, suspended_until 
            FROM users 
            ORDER BY role DESC, id ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        exit;
    }

    // Handle POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action']) || !isset($input['user_id'])) {
            throw new Exception('Missing required parameters');
        }

        $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
        if (!$userId) {
            throw new Exception('Invalid user ID');
        }

        // First check if user is admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('User not found');
        }

        if ($user['role'] === 'admin') {
            throw new Exception('Admin accounts cannot be modified');
        }

        switch ($input['action']) {
            case 'suspend':
                $suspendUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET suspended_until = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$suspendUntil, $userId]);
                break;

            case 'unsuspend':
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET suspended_until = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                break;

            case 'delete':
                $stmt = $pdo->prepare("
                    DELETE FROM users 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                break;

            default:
                throw new Exception('Invalid action specified');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Operation completed successfully'
        ]);
        exit;
    }

    throw new Exception('Invalid request method or action');

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