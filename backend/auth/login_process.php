<?php
require_once __DIR__ . '/../inc/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /RAWR/public/login.php');
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'])) {
    header('Location: /RAWR/public/login.php?error=csrf');
    exit;
}

// Sanitize input
$username = sanitizeInput($_POST['username']);
$password = sanitizeInput($_POST['password']);

// Validate input
if (empty($username) || empty($password)) {
    header('Location: /RAWR/public/login.php?error=empty');
    exit;
}

// Authenticate user
$auth = new Auth();
$result = $auth->loginUser($username, $password);

if ($result['success']) {
    // Update login streak
    $db = Database::getInstance();
    $streak = $db->fetchOne("SELECT * FROM login_streaks WHERE user_id = ?", [$_SESSION['user_id']]);
    $today = date('Y-m-d');
    
    if ($streak) {
        $lastLogin = new DateTime($streak['last_login_date']);
        $todayDate = new DateTime($today);
        $interval = $lastLogin->diff($todayDate);
        
        if ($interval->days === 1) {
            // Consecutive login
            $db->update('login_streaks', [
                'current_streak' => $streak['current_streak'] + 1,
                'last_login_date' => $today
            ], 'user_id = ?', [$_SESSION['user_id']]);
            
            // Update longest streak if needed
            if (($streak['current_streak'] + 1) > $streak['longest_streak']) {
                $db->update('login_streaks', [
                    'longest_streak' => $streak['current_streak'] + 1
                ], 'user_id = ?', [$_SESSION['user_id']]);
            }
        } elseif ($interval->days > 1) {
            // Broken streak, reset
            $db->update('login_streaks', [
                'current_streak' => 1,
                'last_login_date' => $today
            ], 'user_id = ?', [$_SESSION['user_id']]);
        }
    } else {
        // First login
        $db->insert('login_streaks', [
            'user_id' => $_SESSION['user_id'],
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_login_date' => $today
        ]);
    }
    
    // Redirect to dashboard
    header('Location: ../dashboard.php');
    exit;
} else {
    // Login failed
    $error = $result['errors'][0] ?? 'login_failed';
    header('Location: /RAWR/public/login.php?error=' . urlencode($error));
    exit;
}