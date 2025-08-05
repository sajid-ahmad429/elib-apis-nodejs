<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join us today - it's free and easy</p>
        </div>
        
        <form method="POST" action="/register" class="auth-form" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        class="form-control" 
                        required 
                        autocomplete="given-name"
                        placeholder="John"
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                    >
                    <div class="form-feedback" id="first_name-feedback"></div>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        class="form-control" 
                        required 
                        autocomplete="family-name"
                        placeholder="Doe"
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                    >
                    <div class="form-feedback" id="last_name-feedback"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    required 
                    autocomplete="username"
                    placeholder="Choose a unique username"
                    minlength="3"
                    maxlength="50"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                >
                <div class="form-feedback" id="username-feedback"></div>
            </div>
            
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
                <label for="phone" class="form-label">Phone Number (Optional)</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    autocomplete="tel"
                    placeholder="+1 (555) 123-4567"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                >
                <div class="form-feedback" id="phone-feedback"></div>
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
                        autocomplete="new-password"
                        placeholder="Create a strong password"
                        minlength="8"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                        <span class="toggle-icon">👁️</span>
                    </button>
                </div>
                <div class="password-requirements">
                    <h6>Password Requirements:</h6>
                    <ul id="password-requirements-list">
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-lowercase">One lowercase letter</li>
                        <li id="req-uppercase">One uppercase letter</li>
                        <li id="req-number">One number</li>
                        <li id="req-special">One special character</li>
                    </ul>
                </div>
                <div class="form-feedback" id="password-feedback"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-input-group">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        minlength="8"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')" aria-label="Toggle password visibility">
                        <span class="toggle-icon">👁️</span>
                    </button>
                </div>
                <div class="form-feedback" id="confirm_password-feedback"></div>
            </div>
            
            <div class="form-group form-check">
                <label class="checkbox-container">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span class="checkmark"></span>
                    I agree to the <a href="/terms" target="_blank" class="auth-link">Terms of Service</a> and <a href="/privacy" target="_blank" class="auth-link">Privacy Policy</a>
                </label>
                <div class="form-feedback" id="terms-feedback"></div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">
                <span class="btn-text">Create Account</span>
                <span class="btn-loader" style="display: none;">
                    <span class="spinner"></span>
                    Creating account...
                </span>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="/login" class="auth-link">Sign in here</a></p>
        </div>
    </div>
</div>

<style>
.form-row {
    display: flex;
    gap: 1rem;
}

.form-row .form-group {
    flex: 1;
}

.password-requirements {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.875rem;
}

.password-requirements h6 {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: #333;
}

.password-requirements ul {
    margin: 0;
    padding-left: 1.25rem;
    list-style: none;
}

.password-requirements li {
    margin-bottom: 0.25rem;
    position: relative;
    color: #666;
    transition: color 0.3s ease;
}

.password-requirements li::before {
    content: '✗';
    position: absolute;
    left: -1.25rem;
    color: #dc3545;
    font-weight: bold;
}

.password-requirements li.valid {
    color: #28a745;
}

.password-requirements li.valid::before {
    content: '✓';
    color: #28a745;
}

@media (max-width: 640px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const inputs = {
        first_name: document.getElementById('first_name'),
        last_name: document.getElementById('last_name'),
        username: document.getElementById('username'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        password: document.getElementById('password'),
        confirm_password: document.getElementById('confirm_password'),
        terms: document.getElementById('terms')
    };
    
    // Real-time validation
    Object.keys(inputs).forEach(key => {
        if (key !== 'terms') {
            inputs[key].addEventListener('blur', () => validateField(key));
            inputs[key].addEventListener('input', () => {
                if (key === 'password') {
                    updatePasswordRequirements();
                    if (inputs.confirm_password.value) {
                        validateField('confirm_password');
                    }
                } else if (key === 'confirm_password') {
                    validateField('confirm_password');
                }
            });
        }
    });
    
    inputs.terms.addEventListener('change', () => validateField('terms'));
    
    // Username availability check (debounced)
    let usernameTimeout;
    inputs.username.addEventListener('input', function() {
        clearTimeout(usernameTimeout);
        usernameTimeout = setTimeout(() => checkUsernameAvailability(), 500);
    });
    
    // Email availability check (debounced)
    let emailTimeout;
    inputs.email.addEventListener('input', function() {
        clearTimeout(emailTimeout);
        emailTimeout = setTimeout(() => checkEmailAvailability(), 500);
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
    
    function validateField(fieldName) {
        const input = inputs[fieldName];
        const feedback = document.getElementById(`${fieldName}-feedback`);
        let isValid = true;
        let message = '';
        
        switch (fieldName) {
            case 'first_name':
            case 'last_name':
                if (!input.value.trim()) {
                    isValid = false;
                    message = `${fieldName.replace('_', ' ')} is required`;
                } else if (input.value.trim().length < 2) {
                    isValid = false;
                    message = `${fieldName.replace('_', ' ')} must be at least 2 characters`;
                }
                break;
                
            case 'username':
                const username = input.value.trim();
                if (!username) {
                    isValid = false;
                    message = 'Username is required';
                } else if (username.length < 3) {
                    isValid = false;
                    message = 'Username must be at least 3 characters';
                } else if (username.length > 50) {
                    isValid = false;
                    message = 'Username must be less than 50 characters';
                } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                    isValid = false;
                    message = 'Username can only contain letters, numbers, and underscores';
                }
                break;
                
            case 'email':
                const email = input.value.trim();
                if (!email) {
                    isValid = false;
                    message = 'Email is required';
                } else if (!isValidEmail(email)) {
                    isValid = false;
                    message = 'Please enter a valid email address';
                }
                break;
                
            case 'phone':
                const phone = input.value.trim();
                if (phone && !isValidPhone(phone)) {
                    isValid = false;
                    message = 'Please enter a valid phone number';
                }
                break;
                
            case 'password':
                const password = input.value;
                if (!password) {
                    isValid = false;
                    message = 'Password is required';
                } else {
                    const strength = calculatePasswordStrength(password);
                    if (strength < 5) {
                        isValid = false;
                        message = 'Password does not meet all requirements';
                    }
                }
                break;
                
            case 'confirm_password':
                const confirmPassword = input.value;
                const originalPassword = inputs.password.value;
                if (!confirmPassword) {
                    isValid = false;
                    message = 'Please confirm your password';
                } else if (confirmPassword !== originalPassword) {
                    isValid = false;
                    message = 'Passwords do not match';
                }
                break;
                
            case 'terms':
                if (!input.checked) {
                    isValid = false;
                    message = 'You must accept the terms and conditions';
                }
                break;
        }
        
        showFeedback(feedback, message, isValid ? 'valid' : 'invalid');
        return isValid;
    }
    
    function updatePasswordRequirements() {
        const password = inputs.password.value;
        const requirements = {
            'req-length': password.length >= 8,
            'req-lowercase': /[a-z]/.test(password),
            'req-uppercase': /[A-Z]/.test(password),
            'req-number': /[0-9]/.test(password),
            'req-special': /[^A-Za-z0-9]/.test(password)
        };
        
        Object.keys(requirements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.classList.toggle('valid', requirements[id]);
            }
        });
    }
    
    function checkUsernameAvailability() {
        const username = inputs.username.value.trim();
        if (username.length < 3) return;
        
        const feedback = document.getElementById('username-feedback');
        feedback.textContent = 'Checking availability...';
        feedback.className = 'form-feedback checking';
        
        fetch('/check-username', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': window.csrfToken
            },
            body: `username=${encodeURIComponent(username)}`
        })
        .then(response => response.json())
        .then(data => {
            showFeedback(feedback, data.message, data.available ? 'valid' : 'invalid');
        })
        .catch(() => {
            showFeedback(feedback, 'Unable to check availability', 'invalid');
        });
    }
    
    function checkEmailAvailability() {
        const email = inputs.email.value.trim();
        if (!isValidEmail(email)) return;
        
        const feedback = document.getElementById('email-feedback');
        feedback.textContent = 'Checking availability...';
        feedback.className = 'form-feedback checking';
        
        fetch('/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': window.csrfToken
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            showFeedback(feedback, data.message, data.available ? 'valid' : 'invalid');
        })
        .catch(() => {
            showFeedback(feedback, 'Unable to check availability', 'invalid');
        });
    }
    
    function validateForm() {
        let isValid = true;
        Object.keys(inputs).forEach(key => {
            if (!validateField(key)) {
                isValid = false;
            }
        });
        return isValid;
    }
    
    function showFeedback(element, message, type) {
        element.textContent = message;
        element.className = `form-feedback ${type}`;
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function isValidPhone(phone) {
        return /^[\+]?[\s\-\(\)]?[\d\s\-\(\)]{10,}$/.test(phone);
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return strength;
    }
});
</script>