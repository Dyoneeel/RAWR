<?php
require_once __DIR__ . '/../inc/init.php';

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: /login.php?logout=1');
exit;