<?php
/**
 * Database Connection Layer (PDO)
 */
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static ?string $connectionError = null;

    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::$connectionError = $e->getMessage();
                return null;
            }
        }
        return self::$instance;
    }

    public static function getError(): ?string {
        return self::$connectionError;
    }

    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }
}

/**
 * Shorthand helper function
 */
function get_db(): ?PDO {
    return Database::getConnection();
}

