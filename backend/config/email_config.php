<?php
/**
 * Email Configuration
 * Handles different environments (localhost vs production)
 */

class EmailConfig {
    private static $isLocalhost = null;
    
    /**
     * Check if running on localhost
     */
    private static function isLocalhost() {
        if (self::$isLocalhost === null) {
            self::$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', [
                'localhost',
                '127.0.0.1',
                '::1'
            ]) || 
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
        }
        return self::$isLocalhost;
    }
    
    /**
     * Send email with appropriate method
     */
    public static function sendEmail($to, $subject, $message, $headers = []) {
        if (self::isLocalhost()) {
            return self::sendLocalEmail($to, $subject, $message, $headers);
        } else {
            return self::sendProductionEmail($to, $subject, $message, $headers);
        }
    }
    
    /**
     * Local development email (saves to file)
     */
    private static function sendLocalEmail($to, $subject, $message, $headers) {
        $email_data = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'timestamp' => date('Y-m-d H:i:s'),
            'sent' => true
        ];
        
        // Create email log file if it doesn't exist
        $log_file = __DIR__ . '/../../logs/email_log.json';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Read existing logs
        $emails = [];
        if (file_exists($log_file)) {
            $json_data = file_get_contents($log_file);
            if ($json_data) {
                $emails = json_decode($json_data, true) ?: [];
            }
        }
        
        // Add new email
        $emails[] = $email_data;
        
        // Keep only last 50 emails
        if (count($emails) > 50) {
            $emails = array_slice($emails, -50);
        }
        
        // Save to file
        file_put_contents($log_file, json_encode($emails, JSON_PRETTY_PRINT));
        
        return true;
    }
    
    /**
     * Production email (uses real SMTP)
     */
    private static function sendProductionEmail($to, $subject, $message, $headers) {
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Get email log for viewing
     */
    public static function getEmailLog() {
        $log_file = __DIR__ . '/../../logs/email_log.json';
        
        if (!file_exists($log_file)) {
            return [];
        }
        
        $json_data = file_get_contents($log_file);
        return json_decode($json_data, true) ?: [];
    }
    
    /**
     * Clear email log
     */
    public static function clearEmailLog() {
        $log_file = __DIR__ . '/../../logs/email_log.json';
        if (file_exists($log_file)) {
            unlink($log_file);
        }
        return true;
    }
}
?>
