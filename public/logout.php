<?php
require_once __DIR__ . '/../backend/inc/init.php';

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: /RAWR/public/login.php?logout=1');
exit;