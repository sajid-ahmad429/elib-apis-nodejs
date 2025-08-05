<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>
        
        <form method="POST" action="/login" class="auth-form" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    required 
                    autocomplete="email"
                    placeholder="Enter your email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
                <div class="form-feedback" id="email-feedback"></div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-group">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        minlength="8"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                        <span class="toggle-icon">👁️</span>
                    </button>
                </div>
                <div class="form-feedback" id="password-feedback"></div>
            </div>
            
            <div class="form-group form-check">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <span class="checkmark"></span>
                    Remember me for 30 days
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                <span class="btn-text">Sign In</span>
                <span class="btn-loader" style="display: none;">
                    <span class="spinner"></span>
                    Signing in...
                </span>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="/register" class="auth-link">Sign up here</a></p>
            <p><a href="/forgot-password" class="auth-link">Forgot your password?</a></p>
        </div>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 2rem 0;
}

.auth-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    padding: 3rem;
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.auth-header p {
    color: #666;
    font-size: 1rem;
}

.auth-form {
    margin-bottom: 2rem;
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    color: #666;
    padding: 0.25rem;
    border-radius: 4px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #333;
}

.form-check {
    display: flex;
    align-items: center;
}

.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.9rem;
    color: #666;
}

.checkbox-container input {
    margin-right: 0.75rem;
    width: 18px;
    height: 18px;
}

.btn-full {
    width: 100%;
    font-size: 1.1rem;
    padding: 1rem;
    position: relative;
    overflow: hidden;
}

.btn-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-feedback {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    min-height: 1.2rem;
}

.form-feedback.valid {
    color: #28a745;
}

.form-feedback.invalid {
    color: #dc3545;
}

.auth-footer {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.auth-footer p {
    margin-bottom: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.auth-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.auth-link:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    // Real-time validation
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
    
    function validateEmail() {
        const email = emailInput.value.trim();
        const feedback = document.getElementById('email-feedback');
        
        if (!email) {
            showFeedback(feedback, 'Email is required', 'invalid');
            return false;
        }
        
        if (!isValidEmail(email)) {
            showFeedback(feedback, 'Please enter a valid email address', 'invalid');
            return false;
        }
        
        showFeedback(feedback, 'Email format is valid', 'valid');
        return true;
    }
    
    function validatePassword() {
        const password = passwordInput.value;
        const feedback = document.getElementById('password-feedback');
        
        if (!password) {
            showFeedback(feedback, 'Password is required', 'invalid');
            return false;
        }
        
        if (password.length < 8) {
            showFeedback(feedback, 'Password must be at least 8 characters', 'invalid');
            return false;
        }
        
        showFeedback(feedback, '', 'valid');
        return true;
    }
    
    function validateForm() {
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        
        return isEmailValid && isPasswordValid;
    }
    
    function showFeedback(element, message, type) {
        element.textContent = message;
        element.className = `form-feedback ${type}`;
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('.toggle-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
        button.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
        button.setAttribute('aria-label', 'Show password');
    }
}
</script>