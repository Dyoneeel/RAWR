<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Set error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    
    error_log("Error: $errstr in $errfile on line $errline");
    
    if (defined('DEV_MODE') && DEV_MODE === true) {
        echo "<div class='error'>Error: $errstr in $errfile on line $errline</div>";
    }
    
    return true;
});

set_exception_handler(function($e) {
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    if (defined('DEV_MODE') && DEV_MODE === true) {
        echo "<div class='error'><strong>Exception:</strong> " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "</div>";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo "An error occurred. Please try again later.";
    }
});

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/RAWR/', // <-- Make sure this is /RAWR/ not /RAWR/public/
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_name('RAWR_SESSION');
    session_start();
}

// Include required files
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';

// Initialize database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("System maintenance in progress. Please try again later.");
}

createLoginAttemptsTable();


// Check for maintenance mode
if (file_exists(SITE_ROOT . 'maintenance.flag')) {
    header('HTTP/1.1 503 Service Unavailable');
    die("System maintenance in progress. Please try again later.");
}

// Set default timezone
date_default_timezone_set('UTC');