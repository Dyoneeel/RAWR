<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function registerUser(array $data): array {
        // Validate input
        $errors = [];
        
        if (empty($data['username']) || strlen($data['username']) < 4) {
            $errors[] = 'Username must be at least 4 characters';
        }
        
        if (!validateEmail($data['email'])) {
            $errors[] = 'Invalid email address';
        }
        
        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username/email exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$data['username'], $data['email']]
        );
        
        if ($existing) {
            return ['success' => false, 'errors' => ['Username or email already exists']];
        }
        
        // Handle referral
        $referredBy = null;
        if (!empty($data['referral_code'])) {
            $referrer = $this->db->fetchOne(
                "SELECT id FROM users WHERE referral_code = ?",
                [$data['referral_code']]
            );
            
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }
        
        // Create user
        $referralCode = generateReferralCode();
        $passwordHash = passwordHash($data['password']);
        
        try {
            $this->db->beginTransaction();
            
            $userId = $this->db->insert('users', [
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'referral_code' => $referralCode,
                'referred_by' => $referredBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Initialize mining data
            $this->db->insert('mining_data', [
                'user_id' => $userId,
                'boost_level' => 1,
                'total_mined' => 0
            ]);
            
            // Initialize login streak
            $this->db->insert('login_streaks', [
                'user_id' => $userId,
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_login_date' => date('Y-m-d')
            ]);
            
            // Record referral if applicable
            if ($referredBy) {
                // Check if referral already exists
                $existingReferral = $this->db->fetchOne(
                    "SELECT * FROM referrals WHERE referrer_id = ? AND referred_id = ?",
                    [$referredBy, $userId]
                );
                
                if (!$existingReferral) {
                    $this->db->insert('referrals', [
                        'referrer_id' => $referredBy,
                        'referred_id' => $userId,
                        'referred_at' => date('Y-m-d H:i:s'),
                        'bonus_awarded' => 0
                    ]);
                }
            }
            
            $this->db->commit();
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    public function awardReferralBonus(int $referrerId, int $referredId): void {
        try {
            $this->db->beginTransaction();
            
            // Award new user (50 RAWR)
            $this->db->executeQuery(
                "UPDATE users SET rawr_balance = rawr_balance + 50 WHERE id = ?",
                [$referredId]
            );
            
            // Award referrer (100 RAWR)
            $this->db->executeQuery(
                "UPDATE users SET rawr_balance = rawr_balance + 100 WHERE id = ?",
                [$referrerId]
            );
            
            // Mark bonus as awarded
            $this->db->executeQuery(
                "UPDATE referrals SET bonus_awarded = 1 
                 WHERE referrer_id = ? AND referred_id = ?",
                [$referrerId, $referredId]
            );
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Referral bonus failed: " . $e->getMessage());
        }
    }
    
    public function loginUser(string $username, string $password): array {
        // Get user
        $user = $this->db->fetchOne(
            "SELECT id, username, password_hash, is_banned, referred_by FROM users 
             WHERE username = ? OR email = ?",
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'errors' => ['Invalid username or password']];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'errors' => ['Invalid username or password']];
        }
        
        if ($user['is_banned']) {
            return ['success' => false, 'errors' => ['This account has been banned']];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        return [
            'success' => true,
            'user_id' => $user['id'],
            'referred_by' => $user['referred_by']
        ];
    }
    
    public function logout(): void {
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
}