<?php
/**
 * Debug CSRF Token Issue
 * Tests CSRF token generation and validation
 */

require_once 'config/db.php';
require_once 'utils/csrf.php';
require_once 'utils/logger.php';

header('Content-Type: text/plain');

echo "=== CSRF Token Debug ===\n\n";

// Test 1: Check if session is working
echo "1. Session Status:\n";
if (session_status() === PHP_SESSION_NONE) {
    echo "   ❌ No active session\n";
    session_start();
    echo "   ✅ Started new session\n";
} else {
    echo "   ✅ Session active\n";
}

echo "   Session ID: " . session_id() . "\n\n";

// Test 2: Generate CSRF token
echo "2. Token Generation:\n";
$token = CSRFProtection::getToken();
echo "   Generated token: " . substr($token, 0, 20) . "...\n";
echo "   Token length: " . strlen($token) . "\n";
echo "   Stored in session: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO') . "\n\n";

// Test 3: Token API response
echo "3. API Response Test:\n";
ob_start();
include 'csrf_token.php';
$api_output = ob_get_clean();

echo "   Raw API output: " . $api_output . "\n";

$api_data = json_decode($api_output, true);
if ($api_data) {
    echo "   ✅ Valid JSON\n";
    echo "   API returns: " . key($api_data) . "\n";
    if (isset($api_data['csrf_token'])) {
        echo "   ✅ csrf_token field exists\n";
        echo "   Token value: " . substr($api_data['csrf_token'], 0, 20) . "...\n";
    } else {
        echo "   ❌ csrf_token field missing\n";
        echo "   Available fields: " . implode(', ', array_keys($api_data)) . "\n";
    }
} else {
    echo "   ❌ Invalid JSON\n";
    echo "   JSON error: " . json_last_error_msg() . "\n";
}

echo "\n4. Validation Test:\n";
// Test validation with current token
$test_token = $_POST['csrf_token'] ?? $token;
$validation_result = CSRFProtection::validateToken($test_token);
echo "   Test token: " . substr($test_token, 0, 20) . "...\n";
echo "   Validation result: " . ($validation_result ? '✅ VALID' : '❌ INVALID') . "\n\n";

echo "5. Recommendations:\n";
if (!$validation_result) {
    echo "   ⚠️  Token validation failed\n";
    echo "   Possible causes:\n";
    echo "   - Session mismatch between token generation and validation\n";
    echo "   - Multiple browser tabs open\n";
    echo "   - Browser blocking cookies/localStorage\n";
    echo "   - Token expired\n";
    echo "   \n";
    echo "   Solutions:\n";
    echo "   - Refresh page to get new token\n";
    echo "   - Clear browser cache and cookies\n";
    echo "   - Ensure only one tab open\n";
    echo "   - Check browser console for JavaScript errors\n";
} else {
    echo "   ✅ CSRF token system working correctly\n";
}

echo "\n=== Debug Complete ===\n";
?>
