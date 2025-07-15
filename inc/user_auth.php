<?php
require_once 'database.php';
require_once 'loyalty_system.php';

class UserAuth {
    private $pdo;
    private $loyaltySystem;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->loyaltySystem = new LoyaltySystem();
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Register a new user
     */
    public function register($email, $password, $name, $phone = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Validate input
            if (!$this->validateEmail($email)) {
                throw new Exception('Invalid email format');
            }
            
            if (!$this->validatePassword($password)) {
                throw new Exception('Password must be at least 8 characters');
            }
            
            if (!$this->validateName($name)) {
                throw new Exception('Name must be between 2 and 100 characters');
            }
            
            // Check if email already exists
            if ($this->emailExists($email)) {
                throw new Exception('Email already registered');
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with loyalty defaults
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password, name, phone, loyalty_points, vip_tier) 
                VALUES (?, ?, ?, ?, 100, 'bronze')
            ");
            $stmt->execute([$email, $hashedPassword, $name, $phone]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Give welcome bonus
            $this->loyaltySystem->recordLoyaltyTransaction($userId, 'bonus', 100, 'registration', 'Welcome bonus for new user');
            
            // Create verification token
            $verificationToken = bin2hex(random_bytes(32));
            $stmt = $this->pdo->prepare("
                INSERT INTO user_verifications (user_id, token, type, expires_at) 
                VALUES (?, ?, 'email', DATE_ADD(NOW(), INTERVAL 24 HOUR))
            ");
            $stmt->execute([$userId, $verificationToken]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'verification_token' => $verificationToken,
                'message' => 'Registration successful! Welcome bonus of 100 points added.'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            // Get user from database
            $stmt = $this->pdo->prepare("
                SELECT id, email, password, name, phone, loyalty_points, current_streak, vip_tier
                FROM users WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Invalid email or password');
            }
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Create session
            $this->createSession($user);
            
            // Log successful login
            $this->logActivity($user['id'], 'login', 'User logged in');
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'message' => 'Login successful!'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, email, name, phone, loyalty_points, total_points_earned,
                   current_streak, longest_streak, vip_tier, vip_points, wallet_balance
            FROM users WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        return $user ? $this->sanitizeUserData($user) : null;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['name', 'phone'];
            $updates = [];
            $values = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'name' && !$this->validateName($data[$field])) {
                        throw new Exception('Invalid name format');
                    }
                    $updates[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                throw new Exception('No valid fields to update');
            }
            
            $values[] = $userId;
            $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($values);
            
            $this->logActivity($userId, 'profile_update', 'Profile updated');
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            if (!$this->validatePassword($newPassword)) {
                throw new Exception('New password does not meet requirements');
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $this->logActivity($userId, 'password_change', 'Password changed');
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reset password request
     */
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal if email exists for security
                return ['success' => true, 'message' => 'If email exists, reset instructions sent'];
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            
            // Store reset token
            $stmt = $this->pdo->prepare("
                INSERT INTO user_verifications (user_id, token, type, expires_at) 
                VALUES (?, ?, 'password_reset', DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ON DUPLICATE KEY UPDATE token = ?, expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$user['id'], $token, $token]);
            
            // Here you would send email with reset link
            // For demo, we'll just return the token
            
            return [
                'success' => true, 
                'reset_token' => $token,
                'message' => 'Password reset instructions sent to email'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Unable to process request'];
        }
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT uv.user_id FROM user_verifications uv
                WHERE uv.token = ? AND uv.type = 'email' 
                AND uv.expires_at > NOW() AND uv.used = 0
            ");
            $stmt->execute([$token]);
            $verification = $stmt->fetch();
            
            if (!$verification) {
                throw new Exception('Invalid or expired verification token');
            }
            
            // Mark user as verified
            $stmt = $this->pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$verification['user_id']]);
            
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE user_verifications SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            // Give verification bonus
            $this->loyaltySystem->recordLoyaltyTransaction(
                $verification['user_id'], 
                'bonus', 
                50, 
                'verification', 
                'Email verification bonus'
            );
            
            return ['success' => true, 'message' => 'Email verified successfully! 50 bonus points added.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Private helper methods
    
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function validatePassword($password) {
        return strlen($password) >= 8;
    }
    
    private function validateName($name) {
        return preg_match('/^[a-zA-Z\s]{2,100}$/', $name);
    }
    
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['vip_tier'] = $user['vip_tier'];
        session_regenerate_id(true);
    }
    
    private function logActivity($userId, $action, $description) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_activity_logs (user_id, action, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId, 
            $action, 
            $description, 
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    private function sanitizeUserData($user) {
        unset($user['password']);
        return $user;
    }
}

// Helper function for templates
function requireLogin($redirectUrl = '/raffle-demo/login.php') {
    $auth = new UserAuth();
    if (!$auth->isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
    return $auth->getCurrentUser();
}

// Helper function to get current user
function getCurrentUser() {
    $auth = new UserAuth();
    return $auth->getCurrentUser();
}
?> 