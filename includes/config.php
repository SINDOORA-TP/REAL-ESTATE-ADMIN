<?php
/**
 * Magaram Real Estate - Admin Suite Configuration
 */

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security: Secure session cookie settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    if (PHP_SAPI !== 'cli') {
        $cookieParams = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        @session_set_cookie_params($cookieParams);
    }
    @session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'magaram_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Constants
define('APP_NAME', 'Magaram Real Estate');
define('APP_TAGLINE', 'Curating Extraordinary Living Spaces');

// Dynamic base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir === '.' || $scriptDir === '/' || $scriptDir === '\\') {
    $scriptDir = '';
}
// Strip subfolders if running inside properties subfolder
$baseDir = preg_replace('/(\/properties)$/', '', $scriptDir);
if (!empty($baseDir) && $baseDir[0] !== '/') {
    $baseDir = '/' . $baseDir;
}
$baseUrl = rtrim($protocol . $host . $baseDir, '/');
define('BASE_URL', $baseUrl);

// Uploads Directories
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('PROPERTY_UPLOAD_DIR', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'properties');
define('BROCHURE_UPLOAD_DIR', UPLOAD_DIR . DIRECTORY_SEPARATOR . 'brochures');

// Constraints
define('MAX_FILE_SIZE', 8 * 1024 * 1024); // 8MB limit
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp', 'avif']);

// Admin Email
define('ADMIN_EMAIL', 'admin@magaramrealestate.com');
