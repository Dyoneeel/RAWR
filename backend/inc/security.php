<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class Security {
    public static function sanitizeOutput(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Check if session is expired
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::logout();
            redirect('/login.php?expired=1');
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
    
    public static function validateAdminSession(): void {
        self::validateSession();
        
        if (empty($_SESSION['admin_id'])) {
            redirect('/admin/login.php');
        }
    }
    
    public static function logout(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function generateToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCsrfToken(string $token): bool {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    public static function rateLimit(string $key, int $limit, int $window = 60): bool {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $current = $redis->get($key);
        if ($current === false) {
            $redis->setex($key, $window, 1);
            return true;
        }
        
        if ($current >= $limit) {
            return false;
        }
        
        $redis->incr($key);
        return true;
    }
    
    public static function secureUpload(array $file): string {
        $uploadDir = UPLOAD_DIR;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file
        list($valid, $errors) = checkFileUpload($file);
        if (!$valid) {
            throw new Exception(implode(', ', $errors));
        }
        
        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        return $filename;
    }
    
    public static function checkXSS(array $input): bool {
        $dangerousPatterns = [
            '/<script/i', '/onerror=/i', '/onload=/i', '/javascript:/i', 
            '/vbscript:/i', '/eval\(/i', '/document\./i', '/window\./i'
        ];
        
        foreach ($input as $value) {
            if (is_array($value)) {
                if (!self::checkXSS($value)) {
                    return false;
                }
            } else {
                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
}