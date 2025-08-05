<?php
class User {
    private $db;
    private $table = 'users';
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $avatar;
    public $email_verified;
    public $is_active;
    public $is_locked;
    public $failed_login_attempts;
    public $last_login;
    public $created_at;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        // Validate input
        if (!$this->validateUserData($data)) {
            return false;
        }
        
        // Check if user exists
        if ($this->emailExists($data['email']) || $this->usernameExists($data['username'])) {
            throw new Exception('User already exists');
        }
        
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO {$this->table} (
                username, email, password_hash, first_name, last_name, phone,
                email_verification_token, email_verification_expires
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            // Generate email verification token
            $verificationToken = generateRandomToken();
            $verificationExpires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
            
            $result = $stmt->execute([
                sanitizeInput($data['username']),
                sanitizeInput($data['email']),
                hashPassword($data['password']),
                sanitizeInput($data['first_name']),
                sanitizeInput($data['last_name']),
                sanitizeInput($data['phone'] ?? null),
                $verificationToken,
                $verificationExpires
            ]);
            
            if ($result) {
                $this->id = $this->db->lastInsertId();
                $this->db->commit();
                
                // Log user creation
                $this->logSecurityEvent('user_created', [
                    'user_id' => $this->id,
                    'username' => $data['username'],
                    'email' => $data['email']
                ]);
                
                return $this->id;
            }
            
            $this->db->rollback();
            return false;
            
        } catch (Exception $e) {
            $this->db->rollback();
            logSecurityEvent('user_creation_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Authenticate user login
     */
    public function authenticate($email, $password) {
        // Check rate limiting
        if (!$this->checkLoginRateLimit($email)) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
        
        $user = $this->findByEmail($email);
        
        if (!$user) {
            $this->logLoginAttempt($email, false);
            $this->incrementLoginRateLimit($email);
            throw new Exception('Invalid credentials');
        }
        
        // Check if account is locked
        if ($user['is_locked'] || ($user['locked_until'] && strtotime($user['locked_until']) > time())) {
            $this->logLoginAttempt($email, false);
            throw new Exception('Account is locked. Please contact administrator.');
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            $this->logLoginAttempt($email, false);
            throw new Exception('Account is deactivated.');
        }
        
        // Verify password
        if (!verifyPassword($password, $user['password_hash'])) {
            $this->handleFailedLogin($user['id'], $email);
            throw new Exception('Invalid credentials');
        }
        
        // Check if password needs rehashing
        if (passwordNeedsRehash($user['password_hash'])) {
            $this->updatePassword($user['id'], $password);
        }
        
        // Successful login
        $this->handleSuccessfulLogin($user);
        $this->logLoginAttempt($email, true);
        
        return $user;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([sanitizeInput($email)]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([sanitizeInput($username)]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        if (!validatePassword($newPassword)) {
            throw new Exception('Password does not meet security requirements');
        }
        
        $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([hashPassword($newPassword), $userId]);
        
        if ($result) {
            $this->logSecurityEvent('password_changed', ['user_id' => $userId]);
        }
        
        return $result;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone'];
        $updateFields = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $values[] = sanitizeInput($data[$field]);
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $values[] = $userId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([sanitizeInput($email)]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([sanitizeInput($username)]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Validate user data
     */
    private function validateUserData($data) {
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        if (!validateEmail($data['email'])) {
            throw new Exception('Invalid email format');
        }
        
        if (!validatePassword($data['password'])) {
            throw new Exception('Password does not meet security requirements');
        }
        
        if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            throw new Exception('Username must be between 3 and 50 characters');
        }
        
        return true;
    }
    
    /**
     * Handle failed login
     */
    private function handleFailedLogin($userId, $email) {
        // Increment failed attempts
        $sql = "UPDATE {$this->table} SET 
                failed_login_attempts = failed_login_attempts + 1,
                last_failed_login = CURRENT_TIMESTAMP 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        // Check if account should be locked
        $user = $this->findById($userId);
        if ($user['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $this->lockAccount($userId);
        }
        
        $this->logLoginAttempt($email, false);
        $this->incrementLoginRateLimit($email);
    }
    
    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin($user) {
        $sql = "UPDATE {$this->table} SET 
                failed_login_attempts = 0,
                last_login = CURRENT_TIMESTAMP,
                last_login_ip = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([getClientIP(), $user['id']]);
        
        // Start user session
        $this->startSession($user);
        
        $this->logSecurityEvent('successful_login', [
            'user_id' => $user['id'],
            'username' => $user['username']
        ]);
    }
    
    /**
     * Lock user account
     */
    private function lockAccount($userId) {
        $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
        $sql = "UPDATE {$this->table} SET locked_until = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$lockUntil, $userId]);
        
        $this->logSecurityEvent('account_locked', ['user_id' => $userId]);
    }
    
    /**
     * Start user session
     */
    private function startSession($user) {
        regenerateSession();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = getClientIP();
        
        // Create session record in database
        $this->createSessionRecord($user['id']);
    }
    
    /**
     * Create session record
     */
    private function createSessionRecord($userId) {
        $sessionToken = generateRandomToken(64);
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $sql = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $sessionToken,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);
        
        $_SESSION['session_token'] = $sessionToken;
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($email, $success) {
        $sql = "INSERT INTO login_attempts (email, ip_address, user_agent, success) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $email,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $success ? 1 : 0
        ]);
    }
    
    /**
     * Check login rate limit
     */
    private function checkLoginRateLimit($email) {
        $identifier = getClientIP() . ':' . $email;
        return $this->checkDatabaseRateLimit($identifier, 'login', MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME);
    }
    
    /**
     * Increment login rate limit
     */
    private function incrementLoginRateLimit($email) {
        $identifier = getClientIP() . ':' . $email;
        $this->incrementDatabaseRateLimit($identifier, 'login', LOCKOUT_TIME);
    }
    
    /**
     * Database-based rate limiting
     */
    private function checkDatabaseRateLimit($identifier, $action, $maxAttempts, $timeWindow) {
        // Clean expired entries
        $sql = "DELETE FROM rate_limits WHERE expires_at < NOW()";
        $this->db->prepare($sql)->execute();
        
        $sql = "SELECT attempts FROM rate_limits WHERE identifier = ? AND action = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $action]);
        $result = $stmt->fetch();
        
        return !$result || $result['attempts'] < $maxAttempts;
    }
    
    /**
     * Increment database rate limit
     */
    private function incrementDatabaseRateLimit($identifier, $action, $timeWindow) {
        $expiresAt = date('Y-m-d H:i:s', time() + $timeWindow);
        
        $sql = "INSERT INTO rate_limits (identifier, action, attempts, expires_at) 
                VALUES (?, ?, 1, ?) 
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, expires_at = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $action, $expiresAt, $expiresAt]);
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent($eventType, $data = []) {
        $sql = "INSERT INTO security_logs (user_id, event_type, event_data, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'] ?? null,
            $eventType,
            json_encode($data),
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            // Deactivate session in database
            $sql = "UPDATE user_sessions SET is_active = FALSE WHERE session_token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['session_token']]);
        }
        
        $this->logSecurityEvent('user_logout', [
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        session_unset();
        session_destroy();
    }
}
?>