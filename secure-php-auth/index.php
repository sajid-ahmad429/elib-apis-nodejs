<?php
/**
 * Secure PHP Authentication System
 * Entry Point with Routing
 * 
 * @author Your Name
 * @version 1.0.0
 * @license MIT
 */

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Include configuration and autoloader
require_once 'config/config.php';
require_once 'includes/autoloader.php';

// Initialize error handling
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
$csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;";
header("Content-Security-Policy: $csp");

// Initialize the application
$router = new Router();

// Define routes
$router->get('/', 'AuthController@dashboard');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');
$router->get('/profile', 'AuthController@profile');
$router->post('/profile', 'AuthController@updateProfile');
$router->post('/change-password', 'AuthController@changePassword');

// AJAX endpoints
$router->post('/check-username', 'AuthController@checkUsername');
$router->post('/check-email', 'AuthController@checkEmail');

// Handle the request
try {
    $router->dispatch();
} catch (Exception $e) {
    logSecurityEvent('routing_error', ['error' => $e->getMessage()]);
    
    if (DEBUG_MODE) {
        echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        http_response_code(500);
        include 'views/errors/500.php';
    }
}

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'time' => date('Y-m-d H:i:s')
    ];
    
    logSecurityEvent('php_error', $error);
    
    if (DEBUG_MODE) {
        echo "<b>Error:</b> $errstr in <b>$errfile</b> on line <b>$errline</b><br>";
    }
    
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    logSecurityEvent('php_exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    if (DEBUG_MODE) {
        echo '<pre>Uncaught exception: ' . htmlspecialchars($exception->getMessage()) . '</pre>';
    } else {
        http_response_code(500);
        include 'views/errors/500.php';
    }
}
?>