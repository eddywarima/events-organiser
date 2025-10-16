<?php
require_once 'db_config.php';

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (!self::$instance) {
            try {
                self::$instance = new PDO(
                    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection error. Please try again later.");
            }
        }
        return self::$instance;
    }
}

// Create connection instance
$conn = Database::getConnection();
?>