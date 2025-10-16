<?php
class Mailer {
    public static function send($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: events@yourdomain.com',
            'Reply-To: no-reply@yourdomain.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        try {
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            error_log("Email sent to $to: " . ($result ? 'Success' : 'Failed'));
            return $result;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
}