<?php
require_once __DIR__ . '/../inc/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// Validate CSRF token
if (!validateCsrfToken($_POST['csrf_token'])) {
    header('Location: ../register.php?error=csrf');
    exit;
}

// Sanitize input
$data = [
    'username' => sanitizeInput($_POST['username']),
    'email' => sanitizeInput($_POST['email']),
    'password' => $_POST['password'],
    'confirm_password' => $_POST['confirm_password'],
    'referral_code' => isset($_POST['referral_code']) ? sanitizeInput($_POST['referral_code']) : null,
    'terms' => isset($_POST['terms']) ? true : false,
];

// Validate terms acceptance
if (!$data['terms']) {
    header('Location: ../register.php?error=terms');
    exit;
}

// Validate password match
if ($data['password'] !== $data['confirm_password']) {
    header('Location: ../register.php?error=password');
    exit;
}

// Validate password strength
if (strlen($data['password']) < 8 || 
    !preg_match('/[A-Z]/', $data['password']) || 
    !preg_match('/[a-z]/', $data['password']) || 
    !preg_match('/[0-9]/', $data['password'])) {
    header('Location: ../register.php?error=weak_password');
    exit;
}

// Process registration
$auth = new Auth();
$result = $auth->registerUser($data);

if ($result['success']) {
    // Handle referral bonus if applicable
    if (!empty($data['referral_code'])) {
        $db = Database::getInstance();
        $referrer = $db->fetchOne("SELECT id FROM users WHERE referral_code = ?", [$data['referral_code']]);
        
        if ($referrer) {
            // Award bonus to referrer
            $db->executeQuery(
                "UPDATE users SET rawr_balance = rawr_balance + 50 WHERE id = ?",
                [$referrer['id']]
            );
            
            // Log referral bonus
            $db->insert('referrals', [
                'referrer_id' => $referrer['id'],
                'referred_id' => $result['user_id'],
                'referred_at' => date('Y-m-d H:i:s'),
                'bonus_awarded' => 1
            ]);
        }
    }
    
    // Redirect to login with success message
    header('Location: /RAWR/public/login.php?registered=1');
    exit;
} else {
    // Registration failed
    $error = $result['errors'][0] ?? 'registration_failed';
    header('Location: ../register.php?error=' . urlencode($error));
    exit;
}