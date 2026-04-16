<?php
/**
 * System Test and Health Check
 * Checks for common issues and system health
 */

require_once 'config/db.php';
require_once 'utils/logger.php';
require_once 'utils/session_helper.php';

header('Content-Type: application/json');

$tests = [];

// Test 1: Database Connection
$tests['database'] = [
    'name' => 'Database Connection',
    'status' => 'unknown',
    'message' => 'Testing...'
];

try {
    $result = $conn->query("SELECT 1");
    $tests['database']['status'] = 'pass';
    $tests['database']['message'] = 'Database connection successful';
} catch (Exception $e) {
    $tests['database']['status'] = 'fail';
    $tests['database']['message'] = 'Database connection failed: ' . $e->getMessage();
}

// Test 2: Required Tables
$tests['tables'] = [
    'name' => 'Required Tables',
    'status' => 'unknown',
    'message' => 'Checking...',
    'details' => []
];

$required_tables = [
    'users', 'events', 'bookings', 'categories', 
    'email_verification_logs', 'email_verification_settings',
    'password_resets', 'password_reset_requests', 'user_preferences'
];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0;
    $tests['tables']['details'][$table] = $exists ? 'exists' : 'missing';
    
    if (!$exists) {
        $tests['tables']['status'] = 'fail';
        $tests['tables']['message'] = "Missing table: $table";
    }
}

if ($tests['tables']['status'] === 'unknown') {
    $tests['tables']['status'] = 'pass';
    $tests['tables']['message'] = 'All required tables exist';
}

// Test 3: Email Verification Settings
$tests['email_settings'] = [
    'name' => 'Email Verification Settings',
    'status' => 'unknown',
    'message' => 'Checking...'
];

$result = $conn->query("SELECT COUNT(*) as count FROM email_verification_settings");
$count = $result->fetch_assoc()['count'];

if ($count >= 7) {
    $tests['email_settings']['status'] = 'pass';
    $tests['email_settings']['message'] = "Email verification settings configured ($count settings)";
} else {
    $tests['email_settings']['status'] = 'fail';
    $tests['email_settings']['message'] = "Insufficient email verification settings ($count/7)";
}

// Test 4: Categories Data
$tests['categories'] = [
    'name' => 'Default Categories',
    'status' => 'unknown',
    'message' => 'Checking...'
];

$result = $conn->query("SELECT COUNT(*) as count FROM categories");
$count = $result->fetch_assoc()['count'];

if ($count >= 10) {
    $tests['categories']['status'] = 'pass';
    $tests['categories']['message'] = "Default categories loaded ($count categories)";
} else {
    $tests['categories']['status'] = 'fail';
    $tests['categories']['message'] = "Missing default categories ($count/10)";
}

// Test 5: Directory Permissions
$tests['directories'] = [
    'name' => 'Directory Permissions',
    'status' => 'unknown',
    'message' => 'Checking...',
    'details' => []
];

$required_dirs = [
    '../logs',
    '../uploads',
    '../uploads/avatars'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        $tests['directories']['details'][$dir] = 'missing';
        $tests['directories']['status'] = 'fail';
        $tests['directories']['message'] = "Missing directory: $dir";
    } elseif (!is_writable($dir)) {
        $tests['directories']['details'][$dir] = 'not_writable';
        $tests['directories']['status'] = 'fail';
        $tests['directories']['message'] = "Directory not writable: $dir";
    } else {
        $tests['directories']['details'][$dir] = 'ok';
    }
}

if ($tests['directories']['status'] === 'unknown') {
    $tests['directories']['status'] = 'pass';
    $tests['directories']['message'] = 'All directories accessible';
}

// Test 6: Session Functionality
$tests['session'] = [
    'name' => 'Session Functionality',
    'status' => 'unknown',
    'message' => 'Testing...'
];

try {
    SessionHelper::start();
    SessionHelper::set('test', 'value');
    $value = SessionHelper::get('test');
    SessionHelper::remove('test');
    
    if ($value === 'value') {
        $tests['session']['status'] = 'pass';
        $tests['session']['message'] = 'Session functionality working';
    } else {
        $tests['session']['status'] = 'fail';
        $tests['session']['message'] = 'Session read/write failed';
    }
} catch (Exception $e) {
    $tests['session']['status'] = 'fail';
    $tests['session']['message'] = 'Session error: ' . $e->getMessage();
}

// Test 7: User Table Structure
$tests['user_structure'] = [
    'name' => 'User Table Structure',
    'status' => 'unknown',
    'message' => 'Checking...',
    'details' => []
];

$required_user_fields = [
    'id', 'full_name', 'email', 'password', 'role', 'status',
    'email_verified', 'email_verification_token', 'email_verification_expires',
    'phone', 'bio', 'avatar', 'date_of_birth', 'gender'
];

$result = $conn->query("DESCRIBE users");
$existing_fields = [];

while ($row = $result->fetch_assoc()) {
    $existing_fields[] = $row['Field'];
}

foreach ($required_user_fields as $field) {
    if (in_array($field, $existing_fields)) {
        $tests['user_structure']['details'][$field] = 'exists';
    } else {
        $tests['user_structure']['details'][$field] = 'missing';
        $tests['user_structure']['status'] = 'fail';
        $tests['user_structure']['message'] = "Missing user field: $field";
    }
}

if ($tests['user_structure']['status'] === 'unknown') {
    $tests['user_structure']['status'] = 'pass';
    $tests['user_structure']['message'] = 'User table structure correct';
}

// Test 8: Events Table Structure
$tests['event_structure'] = [
    'name' => 'Events Table Structure',
    'status' => 'unknown',
    'message' => 'Checking...',
    'details' => []
];

$required_event_fields = [
    'id', 'title', 'description', 'location', 'event_date', 
    'event_time', 'total_tickets', 'available_tickets', 
    'ticket_price', 'image', 'status', 'category_id'
];

$result = $conn->query("DESCRIBE events");
$existing_fields = [];

while ($row = $result->fetch_assoc()) {
    $existing_fields[] = $row['Field'];
}

foreach ($required_event_fields as $field) {
    if (in_array($field, $existing_fields)) {
        $tests['event_structure']['details'][$field] = 'exists';
    } else {
        $tests['event_structure']['details'][$field] = 'missing';
        $tests['event_structure']['status'] = 'fail';
        $tests['event_structure']['message'] = "Missing event field: $field";
    }
}

if ($tests['event_structure']['status'] === 'unknown') {
    $tests['event_structure']['status'] = 'pass';
    $tests['event_structure']['message'] = 'Events table structure correct';
}

// Calculate overall status
$overall_status = 'pass';
$failed_tests = [];

foreach ($tests as $test) {
    if ($test['status'] === 'fail') {
        $overall_status = 'fail';
        $failed_tests[] = $test['name'];
    } elseif ($test['status'] === 'unknown') {
        $overall_status = 'unknown';
    }
}

$response = [
    'overall_status' => $overall_status,
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => $tests,
    'summary' => [
        'total' => count($tests),
        'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'pass')),
        'failed' => count(array_filter($tests, fn($t) => $t['status'] === 'fail')),
        'unknown' => count(array_filter($tests, fn($t) => $t['status'] === 'unknown'))
    ]
];

if ($overall_status === 'fail') {
    $response['message'] = 'System health check failed. Issues found in: ' . implode(', ', $failed_tests);
} elseif ($overall_status === 'unknown') {
    $response['message'] = 'System health check completed with some unknown results';
} else {
    $response['message'] = 'System health check passed. All systems operational.';
}

echo json_encode($response);
?>
