<?php
/**
 * Input Sanitizer Utility
 * Prevents XSS attacks and validates input data
 */

class InputSanitizer {
    
    /**
     * Clean string input for XSS prevention
     */
    public static function cleanString($input) {
        if (is_array($input)) {
            return array_map([self::class, 'cleanString'], $input);
        }
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Convert to UTF-8 if needed
        if (!mb_check_encoding($input, 'UTF-8')) {
            $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        }
        
        // Strip HTML tags and encode special characters
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        $input = trim($input);
        
        return $input;
    }
    
    /**
     * Clean email input
     */
    public static function cleanEmail($email) {
        $email = self::cleanString($email);
        $email = strtolower($email);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional validation for common issues
        if (strlen($email) > 254) { // RFC 5321 limit
            return false;
        }
        
        return $email;
    }
    
    /**
     * Clean numeric input
     */
    public static function cleanNumber($input, $min = null, $max = null) {
        $input = self::cleanString($input);
        
        if (!is_numeric($input)) {
            return false;
        }
        
        $num = (float) $input;
        
        if ($min !== null && $num < $min) {
            return false;
        }
        
        if ($max !== null && $num > $max) {
            return false;
        }
        
        return $num;
    }
    
    /**
     * Clean integer input
     */
    public static function cleanInt($input, $min = null, $max = null) {
        $num = self::cleanNumber($input, $min, $max);
        
        if ($num === false) {
            return false;
        }
        
        return (int) $num;
    }
    
    /**
     * Clean date input
     */
    public static function cleanDate($date) {
        $date = self::cleanString($date);
        
        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        
        $dateArray = explode('-', $date);
        if (!checkdate($dateArray[1], $dateArray[2], $dateArray[0])) {
            return false;
        }
        
        return $date;
    }
    
    /**
     * Clean time input
     */
    public static function cleanTime($time) {
        $time = self::cleanString($time);
        
        // Validate time format (HH:MM:SS or HH:MM)
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            return false;
        }
        
        return $time;
    }
    
    /**
     * Clean text area input (allows more characters)
     */
    public static function cleanText($input, $maxLength = null) {
        if (is_array($input)) {
            return array_map([self::class, 'cleanText'], $input);
        }
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Convert to UTF-8 if needed
        if (!mb_check_encoding($input, 'UTF-8')) {
            $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        }
        
        // Allow basic HTML tags for text areas (optional)
        $allowed_tags = '<p><br><strong><em><u><ol><ul><li>';
        $input = strip_tags($input, $allowed_tags);
        
        // Encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Apply length limit if specified
        if ($maxLength !== null && strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return trim($input);
    }
    
    /**
     * Validate and clean file upload
     */
    public static function cleanFile($file, $allowedTypes = [], $maxSize = 1048576) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Generate safe filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $safeName = substr($safeName, 0, 50); // Limit length
        $safeName .= '.' . $extension;
        
        return [
            'tmp_name' => $file['tmp_name'],
            'name' => $safeName,
            'size' => $file['size'],
            'type' => $mimeType
        ];
    }
    
    /**
     * Sanitize entire GET array
     */
    public static function cleanGet($rules = []) {
        $cleaned = [];
        
        foreach ($_GET as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                
                switch ($rule) {
                    case 'email':
                        $cleaned[$key] = self::cleanEmail($value);
                        break;
                    case 'int':
                        $cleaned[$key] = self::cleanInt($value);
                        break;
                    case 'number':
                        $cleaned[$key] = self::cleanNumber($value);
                        break;
                    case 'date':
                        $cleaned[$key] = self::cleanDate($value);
                        break;
                    case 'time':
                        $cleaned[$key] = self::cleanTime($value);
                        break;
                    case 'text':
                        $cleaned[$key] = self::cleanText($value);
                        break;
                    case 'boolean':
                        $cleaned[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    default:
                        $cleaned[$key] = self::cleanString($value);
                }
            } else {
                $cleaned[$key] = self::cleanString($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Sanitize entire POST array
     */
    public static function cleanPost($rules = []) {
        $cleaned = [];
        
        foreach ($_POST as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                
                switch ($rule) {
                    case 'email':
                        $cleaned[$key] = self::cleanEmail($value);
                        break;
                    case 'int':
                        $cleaned[$key] = self::cleanInt($value);
                        break;
                    case 'number':
                        $cleaned[$key] = self::cleanNumber($value);
                        break;
                    case 'date':
                        $cleaned[$key] = self::cleanDate($value);
                        break;
                    case 'time':
                        $cleaned[$key] = self::cleanTime($value);
                        break;
                    case 'text':
                        $cleaned[$key] = self::cleanText($value);
                        break;
                    case 'boolean':
                        $cleaned[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    default:
                        $cleaned[$key] = self::cleanString($value);
                }
            } else {
                $cleaned[$key] = self::cleanString($value);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Generate safe output for HTML
     */
    public static function safeOutput($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Generate safe output for JavaScript
     */
    public static function safeJS($input) {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
?>
