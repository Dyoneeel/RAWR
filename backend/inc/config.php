<?php
declare(strict_types=1);

// Site Configuration
define('SITE_NAME', 'RAWR - The Lion\'s Game');
define('BASE_URL', 'http://localhost/RAWR/public/');
define('SITE_ROOT', realpath(dirname(__DIR__)) . '/');
define('UPLOAD_DIR', SITE_ROOT . 'uploads/kyc_docs/');
define('DEV_MODE', true);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rawr_casino');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Game Settings
define('MINING_COOLDOWN', 300); // 5 minutes in seconds
define('MINING_BASE_REWARD', 0.5);
define('CONVERSION_RATE', 100); // 100 RAWR = 1 Ticket
define('MAX_DAILY_STREAK', 7);

// Security Settings
define('SALT', 'your_random_salt_here_32chars_long');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('CSRF_TOKEN_LIFE', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Timezone
date_default_timezone_set('UTC');
if (DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}