<?php
require_once __DIR__ . '/../backend/inc/init.php';

// Destroy session and redirect to login
session_destroy();

// Redirect to login page with logout message
header('Location: login.php?logout=1');
exit;