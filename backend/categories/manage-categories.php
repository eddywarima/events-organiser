<?php
require_once "../auth/auth_check.php";
require_once "../config/db.php";
require_once "../utils/csrf.php";
require_once "../utils/sanitizer.php";
require_once "../utils/logger.php";

// Only admin
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all categories
    $result = $conn->query("
        SELECT id, name, description, icon, color, sort_order, is_active 
        FROM categories 
        ORDER BY sort_order, name
    ");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'color' => $row['color'],
            'sort_order' => (int)$row['sort_order'],
            'is_active' => (bool)$row['is_active']
        ];
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    CSRFProtection::validateRequest();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $rules = [
                'name' => 'string',
                'description' => 'text',
                'icon' => 'string',
                'color' => 'string',
                'sort_order' => 'int'
            ];
            $cleaned = InputSanitizer::cleanPost($rules);
            
            if ($cleaned === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                exit();
            }
            
            $name = $cleaned['name'];
            $description = $cleaned['description'];
            $icon = $cleaned['icon'];
            $color = $cleaned['color'];
            $sort_order = $cleaned['sort_order'] ?? 0;
            
            // Validate required fields
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit();
            }
            
            // Validate color format (hex)
            if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                echo json_encode(['success' => false, 'message' => 'Invalid color format. Use hex color (e.g., #FF0000)']);
                exit();
            }
            
            // Check for duplicate name
            $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
                exit();
            }
            
            // Insert new category
            $stmt = $conn->prepare("
                INSERT INTO categories (name, description, icon, color, sort_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssi", $name, $description, $icon, $color, $sort_order);
            
            if ($stmt->execute()) {
                Logger::info("Category created", ['name' => $name, 'admin_id' => $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Category created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create category']);
            }
            break;
            
        case 'update':
            $rules = [
                'id' => 'int',
                'name' => 'string',
                'description' => 'text',
                'icon' => 'string',
                'color' => 'string',
                'sort_order' => 'int',
                'is_active' => 'boolean'
            ];
            $cleaned = InputSanitizer::cleanPost($rules);
            
            if ($cleaned === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                exit();
            }
            
            $id = $cleaned['id'];
            $name = $cleaned['name'];
            $description = $cleaned['description'];
            $icon = $cleaned['icon'];
            $color = $cleaned['color'];
            $sort_order = $cleaned['sort_order'] ?? 0;
            $is_active = $cleaned['is_active'] ?? true;
            
            // Validate required fields
            if (empty($name) || empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Category ID and name are required']);
                exit();
            }
            
            // Validate color format
            if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                echo json_encode(['success' => false, 'message' => 'Invalid color format. Use hex color (e.g., #FF0000)']);
                exit();
            }
            
            // Check for duplicate name (excluding current category)
            $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check_stmt->bind_param("si", $name, $id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists']);
                exit();
            }
            
            // Update category
            $stmt = $conn->prepare("
                UPDATE categories 
                SET name = ?, description = ?, icon = ?, color = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ssssiii", $name, $description, $icon, $color, $sort_order, $is_active, $id);
            
            if ($stmt->execute()) {
                Logger::info("Category updated", ['id' => $id, 'name' => $name, 'admin_id' => $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update category']);
            }
            break;
            
        case 'delete':
            $rules = ['id' => 'int'];
            $cleaned = InputSanitizer::cleanPost($rules);
            
            if ($cleaned === false || empty($cleaned['id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
                exit();
            }
            
            $id = $cleaned['id'];
            
            // Check if category has events
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM events WHERE category_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $event_count = $check_stmt->get_result()->fetch_assoc()['count'];
            
            if ($event_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete category with existing events']);
                exit();
            }
            
            // Delete category
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                Logger::info("Category deleted", ['id' => $id, 'admin_id' => $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>
