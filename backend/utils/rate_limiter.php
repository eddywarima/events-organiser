<?php
/**
 * Rate Limiter Utility
 * Prevents brute force attacks by limiting login attempts
 */

class RateLimiter {
    private static $attempts_file = '../logs/login_attempts.json';
    private static $max_attempts = 5;
    private static $lockout_duration = 900; // 15 minutes in seconds
    
    /**
     * Check if IP is allowed to attempt login
     */
    public static function isAllowed($ip) {
        $attempts = self::getAttempts($ip);
        
        if ($attempts['count'] >= self::$max_attempts) {
            $time_since_last = time() - $attempts['last_attempt'];
            
            if ($time_since_last < self::$lockout_duration) {
                $remaining_time = self::$lockout_duration - $time_since_last;
                return [
                    'allowed' => false,
                    'remaining_time' => $remaining_time,
                    'message' => "Too many login attempts. Please try again in " . ceil($remaining_time / 60) . " minutes."
                ];
            } else {
                // Reset attempts after lockout period
                self::resetAttempts($ip);
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Record a failed login attempt
     */
    public static function recordFailedAttempt($ip) {
        $attempts = self::getAttempts($ip);
        $attempts['count']++;
        $attempts['last_attempt'] = time();
        
        self::saveAttempts($ip, $attempts);
    }
    
    /**
     * Clear successful login attempts
     */
    public static function clearAttempts($ip) {
        self::resetAttempts($ip);
    }
    
    /**
     * Get current attempts for IP
     */
    private static function getAttempts($ip) {
        $data = self::loadAttemptsData();
        return $data[$ip] ?? ['count' => 0, 'last_attempt' => 0];
    }
    
    /**
     * Save attempts for IP
     */
    private static function saveAttempts($ip, $attempts) {
        $data = self::loadAttemptsData();
        $data[$ip] = $attempts;
        
        // Clean up old entries (older than 24 hours)
        $current_time = time();
        foreach ($data as $key => $value) {
            if ($current_time - $value['last_attempt'] > 86400) { // 24 hours
                unset($data[$key]);
            }
        }
        
        file_put_contents(self::$attempts_file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Reset attempts for IP
     */
    private static function resetAttempts($ip) {
        $data = self::loadAttemptsData();
        unset($data[$ip]);
        file_put_contents(self::$attempts_file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Load attempts data from file
     */
    private static function loadAttemptsData() {
        if (!file_exists(self::$attempts_file)) {
            // Create logs directory if it doesn't exist
            $dir = dirname(self::$attempts_file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return [];
        }
        
        $json = file_get_contents(self::$attempts_file);
        return json_decode($json, true) ?: [];
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>
