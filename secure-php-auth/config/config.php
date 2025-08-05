<?php
/**
 * Configuration File
 * All application settings and constants
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Database Configuration (MySQLi)
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_auth_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

// Application Settings
define('APP_NAME', 'Secure Auth System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/secure-php-auth');
define('DEBUG_MODE', true); // Set to false in production

// Security Configuration
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('HASH_OPTIONS', [
    'memory_cost' => 65536, // 64 MB
    'time_cost' => 4,       // 4 iterations
    'threads' => 3,         // 3 threads
]);

// Password Policy
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_REGENERATE_TIME', 300); // 5 minutes
define('SESSION_COOKIE_LIFETIME', 0); // Browser session
define('SESSION_COOKIE_PATH', '/');
define('SESSION_COOKIE_DOMAIN', '');
define('SESSION_COOKIE_SECURE', false); // Set to true for HTTPS
define('SESSION_COOKIE_HTTPONLY', true);

// Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('MAX_REGISTER_ATTEMPTS', 3);
define('REGISTER_LOCKOUT_TIME', 3600); // 1 hour

// CSRF Protection
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// File Upload Settings
define('MAX_UPLOAD_SIZE', 2097152); // 2MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', 'uploads/');

// Email Configuration (for future features)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

// Logging Configuration
define('LOG_LEVEL', 'info'); // debug, info, warning, error
define('LOG_MAX_SIZE', 10485760); // 10MB
define('LOG_MAX_FILES', 5);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_PATH', 'cache/');

// API Configuration
define('API_ENABLED', false);
define('API_RATE_LIMIT', 100); // requests per hour
define('API_KEY_LENGTH', 64);

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
]);

// Content Security Policy
define('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; media-src 'self'; object-src 'none'; frame-src 'none';");

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Memory limit
ini_set('memory_limit', '128M');

// Maximum execution time
ini_set('max_execution_time', 30);

// Session configuration
ini_set('session.cookie_lifetime', SESSION_COOKIE_LIFETIME);
ini_set('session.cookie_path', SESSION_COOKIE_PATH);
ini_set('session.cookie_domain', SESSION_COOKIE_DOMAIN);
ini_set('session.cookie_secure', SESSION_COOKIE_SECURE);
ini_set('session.cookie_httponly', SESSION_COOKIE_HTTPONLY);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// File upload limits
ini_set('upload_max_filesize', '2M');
ini_set('post_max_size', '2M');
ini_set('max_file_uploads', 1);

// Disable dangerous functions
if (!DEBUG_MODE) {
    ini_set('expose_php', 0);
    ini_set('allow_url_fopen', 0);
    ini_set('allow_url_include', 0);
}

// Custom constants for application logic
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_PENDING', 2);
define('USER_STATUS_SUSPENDED', 3);

define('LOG_TYPE_INFO', 'info');
define('LOG_TYPE_WARNING', 'warning');
define('LOG_TYPE_ERROR', 'error');
define('LOG_TYPE_SECURITY', 'security');

// Application paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH_FULL', ROOT_PATH . '/' . CACHE_PATH);
define('UPLOAD_PATH_FULL', ROOT_PATH . '/' . UPLOAD_PATH);

// Create required directories if they don't exist
$required_dirs = [LOGS_PATH, CACHE_PATH_FULL, UPLOAD_PATH_FULL];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Environment-specific settings
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    
    // Production environment
    if (strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
        define('ENVIRONMENT', 'production');
        ini_set('session.cookie_secure', 1);
        
        // Force HTTPS in production
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url", true, 301);
            exit();
        }
    } else {
        define('ENVIRONMENT', 'development');
    }
} else {
    define('ENVIRONMENT', 'cli');
}

// Security check - prevent execution if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    exit('Direct access not allowed');
}
?>