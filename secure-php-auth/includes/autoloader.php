<?php
/**
 * Autoloader and Helper Functions Loader
 */

// Security check
if (!defined('SECURE_ACCESS')) {
    exit('Direct access not allowed');
}

/**
 * Custom autoloader for classes
 */
spl_autoload_register(function ($class_name) {
    $directories = [
        'classes/',
        'controllers/',
        'models/',
        'helpers/',
        'middleware/'
    ];
    
    foreach ($directories as $directory) {
        $file = ROOT_PATH . '/' . $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // If class not found, log it
    logSecurityEvent('class_not_found', ['class' => $class_name]);
    return false;
});

// Load helper functions
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/database_helpers.php';
require_once 'includes/validation.php';

/**
 * Safe include function
 */
function safe_include($file) {
    $full_path = ROOT_PATH . '/' . $file;
    if (file_exists($full_path) && is_readable($full_path)) {
        return include $full_path;
    }
    
    logSecurityEvent('file_include_failed', ['file' => $file]);
    return false;
}

/**
 * Load view helper
 */
function load_view($view, $data = []) {
    // Extract data array to variables
    if (is_array($data)) {
        extract($data, EXTR_SKIP);
    }
    
    $view_file = VIEWS_PATH . '/' . $view . '.php';
    
    if (file_exists($view_file)) {
        ob_start();
        include $view_file;
        $content = ob_get_clean();
        
        // Load layout
        include VIEWS_PATH . '/layouts/main.php';
    } else {
        throw new Exception("View not found: $view");
    }
}

/**
 * Load partial view
 */
function load_partial($partial, $data = []) {
    if (is_array($data)) {
        extract($data, EXTR_SKIP);
    }
    
    $partial_file = VIEWS_PATH . '/partials/' . $partial . '.php';
    
    if (file_exists($partial_file)) {
        include $partial_file;
    } else {
        throw new Exception("Partial not found: $partial");
    }
}

/**
 * Asset helper function
 */
function asset($path) {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

/**
 * URL helper function
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Check if we're in AJAX request
 */
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * JSON response helper
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect helper
 */
function redirect($url, $status_code = 302) {
    if (headers_sent()) {
        echo "<script>window.location.href='$url';</script>";
    } else {
        header("Location: $url", true, $status_code);
    }
    exit;
}

/**
 * Flash message helpers
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        return $message;
    }
    return null;
}

function has_flash($type) {
    return isset($_SESSION['flash_messages'][$type]);
}

/**
 * Debug helper (only works in debug mode)
 */
function dd($data) {
    if (DEBUG_MODE) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        exit;
    }
}

/**
 * Memory usage helper
 */
function get_memory_usage() {
    return [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'formatted_current' => format_bytes(memory_get_usage(true)),
        'formatted_peak' => format_bytes(memory_get_peak_usage(true))
    ];
}

/**
 * Format bytes helper
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Generate unique filename
 */
function generate_unique_filename($original_filename, $extension = null) {
    if ($extension === null) {
        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    }
    
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($original_filename, PATHINFO_FILENAME));
    
    return $clean_name . '_' . $timestamp . '_' . $random . '.' . $extension;
}

/**
 * Check if request is POST
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Get request method
 */
function request_method() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get client user agent
 */
function get_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Check if HTTPS is enabled
 */
function is_https() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

/**
 * Get current URL
 */
function current_url() {
    $protocol = is_https() ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 */
function base_url() {
    $protocol = is_https() ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}

/**
 * Time ago helper
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Truncate string helper
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $suffix;
}

/**
 * Start profiling (for performance monitoring)
 */
function start_profiling($name) {
    if (DEBUG_MODE) {
        $GLOBALS['profile_data'][$name] = microtime(true);
    }
}

/**
 * End profiling
 */
function end_profiling($name) {
    if (DEBUG_MODE && isset($GLOBALS['profile_data'][$name])) {
        $time = microtime(true) - $GLOBALS['profile_data'][$name];
        logSecurityEvent('performance_profile', [
            'operation' => $name,
            'execution_time' => $time * 1000 . 'ms'
        ]);
        return $time;
    }
    return 0;
}
?>