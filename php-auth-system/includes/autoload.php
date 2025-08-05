<?php
spl_autoload_register(function ($class) {
    $directories = [
        'classes/',
        'controllers/',
        'models/',
        'middleware/',
        'helpers/',
        'includes/'
    ];
    
    foreach ($directories as $directory) {
        $file = __DIR__ . '/../' . $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load helper functions
require_once __DIR__ . '/functions.php';
?>