<?php
/**
 * CSRF Protection Utility
 * Generates and validates CSRF tokens
 */

class CSRFProtection {
    private static $token_length = 32;
    private static $session_key = 'csrf_token';
    
    /**
     * Generate a new CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(self::$token_length));
        $_SESSION[self::$session_key] = $token;
        
        return $token;
    }
    
    /**
     * Get existing token or generate new one
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$session_key])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::$session_key];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$session_key])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::$session_key], $token);
    }
    
    /**
     * Clear CSRF token
     */
    public static function clearToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$session_key]);
    }
    
    /**
     * Get hidden input field HTML for forms
     */
    public static function getHiddenInput() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate token from POST request and die if invalid
     */
    public static function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!self::validateToken($token)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'CSRF token validation failed. Please refresh the page and try again.']);
                exit();
            }
        }
    }
}
?>
