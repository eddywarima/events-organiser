<?php
/**
 * Comprehensive Error Logging System
 * Provides structured logging for debugging and security monitoring
 */

class Logger {
    private static $log_file = '../logs/application.log';
    private static $error_file = '../logs/error.log';
    private static $security_file = '../logs/security.log';
    private static $access_file = '../logs/access.log';
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const SECURITY = 'SECURITY';
    const ACCESS = 'ACCESS';
    
    /**
     * Log general application events
     */
    public static function log($level, $message, $context = []) {
        $log_entry = self::formatLogEntry($level, $message, $context);
        self::writeLog(self::$log_file, $log_entry);
        
        // Also write to specific log files based on level
        switch ($level) {
            case self::ERROR:
            case self::CRITICAL:
                self::writeLog(self::$error_file, $log_entry);
                break;
            case self::SECURITY:
                self::writeLog(self::$security_file, $log_entry);
                break;
            case self::ACCESS:
                self::writeLog(self::$access_file, $log_entry);
                break;
        }
    }
    
    /**
     * Log debug information
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log general information
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log warnings
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log errors
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical errors
     */
    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log security events
     */
    public static function security($message, $context = []) {
        self::log(self::SECURITY, $message, $context);
    }
    
    /**
     * Log access events
     */
    public static function access($message, $context = []) {
        self::log(self::ACCESS, $message, $context);
    }
    
    /**
     * Log login attempts
     */
    public static function logLogin($email, $ip, $success, $reason = '') {
        $context = [
            'email' => $email,
            'ip' => $ip,
            'success' => $success,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($success) {
            self::access("Successful login", $context);
        } else {
            $context['reason'] = $reason;
            self::security("Failed login attempt", $context);
        }
    }
    
    /**
     * Log database errors
     */
    public static function logDatabaseError($query, $error, $context = []) {
        $context['query'] = $query;
        $context['error'] = $error;
        $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        
        self::error("Database error", $context);
    }
    
    /**
     * Log booking events
     */
    public static function logBooking($userId, $eventId, $action, $details = []) {
        $context = [
            'user_id' => $userId,
            'event_id' => $eventId,
            'action' => $action,
            'details' => $details,
            'ip' => self::getClientIP(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::info("Booking action: $action", $context);
    }
    
    /**
     * Log admin actions
     */
    public static function logAdminAction($adminId, $action, $details = []) {
        $context = [
            'admin_id' => $adminId,
            'action' => $action,
            'details' => $details,
            'ip' => self::getClientIP(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::security("Admin action: $action", $context);
    }
    
    /**
     * Format log entry
     */
    private static function formatLogEntry($level, $message, $context) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIP();
        $user_id = $_SESSION['user_id'] ?? 'guest';
        
        $entry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'ip' => $ip,
            'user_id' => $user_id,
            'message' => $message,
            'context' => $context,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
        ];
        
        return json_encode($entry) . PHP_EOL;
    }
    
    /**
     * Write to log file
     */
    private static function writeLog($file, $content) {
        $log_dir = dirname($file);
        
        // Create log directory if it doesn't exist
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Write to file with exclusive lock
        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
        
        // Rotate log if it gets too large (10MB)
        if (filesize($file) > 10485760) {
            self::rotateLog($file);
        }
    }
    
    /**
     * Rotate log file
     */
    private static function rotateLog($file) {
        $timestamp = date('Y-m-d_H-i-s');
        $backup_file = str_replace('.log', "_{$timestamp}.log", $file);
        
        rename($file, $backup_file);
        
        // Keep only last 10 log files
        $pattern = str_replace('.log', '_*.log', $file);
        $files = glob($pattern);
        
        if (count($files) > 10) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $files_to_delete = array_slice($files, 0, count($files) - 10);
            foreach ($files_to_delete as $old_file) {
                unlink($old_file);
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
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
    
    /**
     * Get recent log entries
     */
    public static function getRecentLogs($type = 'application', $lines = 100) {
        $file = self::$log_file;
        
        switch ($type) {
            case 'error':
                $file = self::$error_file;
                break;
            case 'security':
                $file = self::$security_file;
                break;
            case 'access':
                $file = self::$access_file;
                break;
        }
        
        if (!file_exists($file)) {
            return [];
        }
        
        $logs = array_reverse(file($file));
        $recent_logs = array_slice($logs, 0, $lines);
        
        $parsed_logs = [];
        foreach ($recent_logs as $log_line) {
            $log_entry = json_decode(trim($log_line), true);
            if ($log_entry) {
                $parsed_logs[] = $log_entry;
            }
        }
        
        return $parsed_logs;
    }
    
    /**
     * Clear old logs (older than specified days)
     */
    public static function clearOldLogs($days = 30) {
        $log_dir = dirname(self::$log_file);
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        $files = glob($log_dir . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
}
?>
