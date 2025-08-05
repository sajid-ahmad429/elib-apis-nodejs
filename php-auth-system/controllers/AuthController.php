<?php
class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Show dashboard (home page)
     */
    public function dashboard() {
        if (!isLoggedIn()) {
            redirect('/login');
        }
        
        $userData = $this->user->findById($_SESSION['user_id']);
        $this->loadView('dashboard', ['user' => $userData]);
    }
    
    /**
     * Show login form
     */
    public function showLogin() {
        if (isLoggedIn()) {
            redirect('/');
        }
        
        $this->loadView('auth/login', [
            'csrf_token' => generateCSRFToken(),
            'error' => getFlashMessage('error'),
            'success' => getFlashMessage('success')
        ]);
    }
    
    /**
     * Process login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token. Please try again.');
            }
            
            // Validate required fields
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']);
            
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required.');
            }
            
            // Check rate limiting
            if (!checkRateLimit('login_' . getClientIP(), MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME)) {
                throw new Exception('Too many login attempts. Please try again later.');
            }
            
            // Authenticate user
            $user = $this->user->authenticate($email, $password);
            
            // Handle "Remember Me" functionality
            if ($rememberMe) {
                $this->setRememberMeCookie($user['id']);
            }
            
            // Redirect to intended page or dashboard
            $redirectUrl = $_SESSION['intended_url'] ?? '/';
            unset($_SESSION['intended_url']);
            
            setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!');
            redirect($redirectUrl);
            
        } catch (Exception $e) {
            incrementRateLimit('login_' . getClientIP());
            logSecurityEvent('login_failed', [
                'email' => $email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            setFlashMessage('error', $e->getMessage());
            redirect('/login');
        }
    }
    
    /**
     * Show registration form
     */
    public function showRegister() {
        if (isLoggedIn()) {
            redirect('/');
        }
        
        $this->loadView('auth/register', [
            'csrf_token' => generateCSRFToken(),
            'error' => getFlashMessage('error'),
            'success' => getFlashMessage('success')
        ]);
    }
    
    /**
     * Process registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token. Please try again.');
            }
            
            // Check registration rate limiting
            if (!checkRateLimit('register_' . getClientIP(), MAX_REGISTER_ATTEMPTS, 3600)) {
                throw new Exception('Too many registration attempts. Please try again later.');
            }
            
            // Sanitize and validate input
            $data = [
                'username' => sanitizeInput($_POST['username'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'terms' => isset($_POST['terms'])
            ];
            
            // Additional validation
            if (!$data['terms']) {
                throw new Exception('You must accept the terms and conditions.');
            }
            
            if ($data['password'] !== $data['confirm_password']) {
                throw new Exception('Passwords do not match.');
            }
            
            // Create user
            $userId = $this->user->create($data);
            
            if ($userId) {
                incrementRateLimit('register_' . getClientIP());
                
                setFlashMessage('success', 'Registration successful! You can now log in.');
                redirect('/login');
            } else {
                throw new Exception('Registration failed. Please try again.');
            }
            
        } catch (Exception $e) {
            incrementRateLimit('register_' . getClientIP());
            logSecurityEvent('registration_failed', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            setFlashMessage('error', $e->getMessage());
            redirect('/register');
        }
    }
    
    /**
     * Show user profile
     */
    public function profile() {
        if (!isLoggedIn()) {
            redirect('/login');
        }
        
        $userData = $this->user->findById($_SESSION['user_id']);
        
        $this->loadView('auth/profile', [
            'user' => $userData,
            'csrf_token' => generateCSRFToken(),
            'error' => getFlashMessage('error'),
            'success' => getFlashMessage('success')
        ]);
    }
    
    /**
     * Update profile
     */
    public function updateProfile() {
        if (!isLoggedIn()) {
            redirect('/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token. Please try again.');
            }
            
            $data = [
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? '')
            ];
            
            // Validate required fields
            if (empty($data['first_name']) || empty($data['last_name'])) {
                throw new Exception('First name and last name are required.');
            }
            
            $result = $this->user->updateProfile($_SESSION['user_id'], $data);
            
            if ($result) {
                setFlashMessage('success', 'Profile updated successfully!');
            } else {
                throw new Exception('Failed to update profile. Please try again.');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
        }
        
        redirect('/profile');
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        if (!isLoggedIn()) {
            redirect('/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
        }
        
        try {
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid security token. Please try again.');
            }
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required.');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match.');
            }
            
            // Verify current password
            $userData = $this->user->findById($_SESSION['user_id']);
            if (!verifyPassword($currentPassword, $userData['password_hash'])) {
                throw new Exception('Current password is incorrect.');
            }
            
            // Update password
            $result = $this->user->updatePassword($_SESSION['user_id'], $newPassword);
            
            if ($result) {
                setFlashMessage('success', 'Password changed successfully!');
            } else {
                throw new Exception('Failed to change password. Please try again.');
            }
            
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
        }
        
        redirect('/profile');
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isLoggedIn()) {
            // Clear remember me cookie
            $this->clearRememberMeCookie();
            
            // Logout user
            $this->user->logout();
            
            setFlashMessage('success', 'You have been logged out successfully.');
        }
        
        redirect('/login');
    }
    
    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($userId) {
        $token = generateRandomToken(64);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database (you might want to create a remember_tokens table)
        // For now, we'll store it in session
        $_SESSION['remember_token'] = $token;
        
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }
    
    /**
     * Clear remember me cookie
     */
    private function clearRememberMeCookie() {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            unset($_SESSION['remember_token']);
        }
    }
    
    /**
     * Load view with data
     */
    private function loadView($view, $data = []) {
        extract($data);
        
        // Start output buffering
        ob_start();
        
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: $view");
        }
        
        // Get the buffer contents and clean it
        $content = ob_get_clean();
        
        // Load layout
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * AJAX endpoint for checking username availability
     */
    public function checkUsername() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $username = sanitizeInput($_POST['username'] ?? '');
        
        if (empty($username)) {
            echo json_encode(['available' => false, 'message' => 'Username is required']);
            return;
        }
        
        $exists = $this->user->usernameExists($username);
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Username is already taken' : 'Username is available'
        ]);
    }
    
    /**
     * AJAX endpoint for checking email availability
     */
    public function checkEmail() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            echo json_encode(['available' => false, 'message' => 'Email is required']);
            return;
        }
        
        if (!validateEmail($email)) {
            echo json_encode(['available' => false, 'message' => 'Invalid email format']);
            return;
        }
        
        $exists = $this->user->emailExists($email);
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Email is already registered' : 'Email is available'
        ]);
    }
}
?>