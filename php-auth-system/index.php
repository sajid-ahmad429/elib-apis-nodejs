<?php
require_once 'config/config.php';
require_once 'includes/autoload.php';

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
$router->post('/change-password', 'AuthController@changePassword');

// Handle the request
$router->dispatch();
?>