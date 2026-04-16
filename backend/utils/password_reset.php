<?php
/**
 * Password Reset Utility
 * Handles secure token generation, validation, and password reset functionality
 */

class PasswordReset {
    private static $token_length = 64;
    private static $expiration_hours = 1; // Token expires in 1 hour
    private static $max_requests_per_hour = 3; // Rate limiting
    
    /**
     * Generate secure reset token
     */
    public static function generateToken() {
        return bin2hex(random_bytes(self::$token_length / 2));
    }
    
    /**
     * Create password reset request
     */
    public static function createResetRequest($email, $ip_address) {
        global $conn;
        
        // Check rate limiting
        if (!self::checkRateLimit($email, $ip_address)) {
            return [
                'success' => false,
                'message' => 'Too many reset requests. Please try again later.'
            ];
        }
        
        // Find user by email
        $stmt = $conn->prepare("SELECT id, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal if email exists or not for security
            return [
                'success' => true,
                'message' => 'If the email exists, a reset link has been sent.'
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Check if user account is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Account is not active. Please contact support.'
            ];
        }
        
        // Delete any existing tokens for this user
        self::invalidateUserTokens($user['id']);
        
        // Generate new token
        $token = self::generateToken();
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . self::$expiration_hours . ' hour'));
        
        // Store reset token
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user['id'], $email, $token, $expires_at);
        
        if (!$stmt->execute()) {
            return [
                'success' => false,
                'message' => 'Failed to create reset request. Please try again.'
            ];
        }
        
        // Log the request
        self::logResetRequest($email, $ip_address);
        
        // Send reset email
        $email_sent = self::sendResetEmail($email, $token);
        
        if (!$email_sent) {
            return [
                'success' => false,
                'message' => 'Failed to send reset email. Please try again.'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Password reset link has been sent to your email.'
        ];
    }
    
    /**
     * Validate reset token
     */
    public static function validateToken($token) {
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT pr.user_id, pr.email, pr.expires_at, u.status 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.used_at IS NULL
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'valid' => false,
                'message' => 'Invalid or expired reset link.'
            ];
        }
        
        $reset_data = $result->fetch_assoc();
        
        // Check if token has expired
        if (strtotime($reset_data['expires_at']) < time()) {
            return [
                'valid' => false,
                'message' => 'Reset link has expired. Please request a new one.'
            ];
        }
        
        // Check if user account is active
        if ($reset_data['status'] !== 'active') {
            return [
                'valid' => false,
                'message' => 'Account is not active. Please contact support.'
            ];
        }
        
        return [
            'valid' => true,
            'user_id' => $reset_data['user_id'],
            'email' => $reset_data['email']
        ];
    }
    
    /**
     * Reset password using token
     */
    public static function resetPassword($token, $new_password) {
        global $conn;
        
        // Validate token first
        $validation = self::validateToken($token);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long.'
            ];
        }
        
        if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            return [
                'success' => false,
                'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.'
            ];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $validation['user_id']);
        
        if (!$stmt->execute()) {
            return [
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ];
        }
        
        // Mark token as used
        $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Password has been reset successfully.'
        ];
    }
    
    /**
     * Check rate limiting for password reset requests
     */
    private static function checkRateLimit($email, $ip_address) {
        global $conn;
        
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as request_count 
            FROM password_reset_requests 
            WHERE email = ? AND requested_at > ?
        ");
        $stmt->bind_param("ss", $email, $one_hour_ago);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $request_count = $result->fetch_assoc()['request_count'];
        
        return $request_count < self::$max_requests_per_hour;
    }
    
    /**
     * Log password reset request
     */
    private static function logResetRequest($email, $ip_address) {
        global $conn;
        
        $stmt = $conn->prepare("INSERT INTO password_reset_requests (email, ip_address) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $ip_address);
        $stmt->execute();
    }
    
    /**
     * Invalidate all existing tokens for a user
     */
    private static function invalidateUserTokens($user_id) {
        global $conn;
        
        $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    /**
     * Send password reset email
     */
    private static function sendResetEmail($email, $token) {
        $reset_link = "http://localhost/event%20booking/frontend/reset-password.html?token=" . urlencode($token);
        $subject = "Password Reset Request - Event Booking System";
        
        $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>You have requested to reset your password for the Event Booking System.</p>
            <p>Please click the link below to reset your password:</p>
            <p><a href='{$reset_link}'>{$reset_link}</a></p>
            <p>This link will expire in " . self::$expiration_hours . " hour(s).</p>
            <p>If you did not request this password reset, please ignore this email.</p>
            <p>Best regards,<br>Event Booking System Team</p>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=iso-8859-1',
            'From: Event Booking System <noreply@eventbooking.com>',
            'Reply-To: noreply@eventbooking.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Clean up expired tokens (run periodically)
     */
    public static function cleanupExpiredTokens() {
        global $conn;
        
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
        $stmt->execute();
        
        // Clean up old reset request logs (older than 7 days)
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stmt = $conn->prepare("DELETE FROM password_reset_requests WHERE requested_at < ?");
        $stmt->bind_param("s", $seven_days_ago);
        $stmt->execute();
    }
}
?>
