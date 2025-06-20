<?php
declare(strict_types=1);
require_once 'config.php';

// backend/inc/functions.php
// Sanitize input for XSS and unwanted tags
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Hash password securely
function passwordHash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Check if user is logged in and session is valid
function isLoggedIn(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // SESSION_TIMEOUT should be defined elsewhere (e.g., config)
    return !empty($_SESSION['user_id']) 
        && !empty($_SESSION['last_activity']) 
        && (time() - $_SESSION['last_activity']) < (defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600);
}

// Redirect to login if not logged in
function userOnly(): void {
    if (!isLoggedIn()) {
        redirect('/RAWR/public/login.php');
    }
}

// Example redirect function if not already defined
if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

function generateReferralCode(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generateRandomString(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function formatBalance(float $amount, int $decimals = 8): string {
    return number_format($amount, $decimals, '.', '');
}

function jsonResponse(array $data, int $statusCode = 200): void {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function adminOnly(): void {
    if (!isLoggedIn() || empty($_SESSION['admin_id'])) {
        redirect('/admin/login.php');
    }
}

function checkFileUpload(array $file): array {
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            default => 'Unknown upload error',
        };
        return [false, $errors];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File exceeds maximum allowed size';
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_FILE_TYPES)) {
        $errors[] = 'Invalid file type';
    }
    
    return [empty($errors), $errors];
}

function getMiningReward(int $boost_level): float {
    $base = MINING_BASE_REWARD;
    $multiplier = 1 + ($boost_level * 0.2);
    return round($base * $multiplier, 8);
}

function calculateConversion(int $rawr_amount): int {
    return floor($rawr_amount / CONVERSION_RATE);
}

function createLoginAttemptsTable() {
    $db = Database::getInstance();
    $db->executeQuery("
        CREATE TABLE IF NOT EXISTS login_attempts (
            ip_address VARCHAR(45) PRIMARY KEY,
            attempts INT NOT NULL DEFAULT 1,
            last_attempt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
}
