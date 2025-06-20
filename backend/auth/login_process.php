<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    header('Location: /login.php?error=csrf');
    exit;
}

// Sanitize inputs
$username = sanitizeInput($_POST['username'] ?? '');
$password = sanitizeInput($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: /RAWR/public/login.php?error=empty');
    exit;
}

$auth = new Auth();
$result = $auth->loginUser($username, $password);

if ($result['success']) {
    // Check for referral bonus
    if ($result['referred_by']) {
        $db = Database::getInstance();
        
        // Check if bonus already awarded
        $referral = $db->fetchOne(
            "SELECT bonus_awarded FROM referrals 
             WHERE referrer_id = ? AND referred_id = ?",
            [$result['referred_by'], $result['user_id']]
        );
        
        // Award bonus if not already given
        if ($referral && !$referral['bonus_awarded']) {
            $auth->awardReferralBonus($result['referred_by'], $result['user_id']);
        }
    }
    
    header('Location: /RAWR/public/dashboard.php');
    exit;
} else {
    // Handle failed login
    $error = urlencode(implode(', ', $result['errors']));
    header("Location: /RAWR/public/login.php?error=$error");
    exit;
}