<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo isset($description) ? htmlspecialchars($description) : 'Secure authentication system built with PHP'; ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="/assets/css/main.css" as="style">
    <link rel="preload" href="/assets/js/main.js" as="script">
    
    <!-- Critical CSS (Inlined for performance) -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }
            
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
    
    <!-- Non-critical CSS -->
    <link rel="stylesheet" href="/assets/css/main.css" media="print" onload="this.media='all'; this.onload=null;">
    <noscript><link rel="stylesheet" href="/assets/css/main.css"></noscript>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="/" class="logo"><?php echo APP_NAME; ?></a>
                
                <ul class="nav-links">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/">Dashboard</a></li>
                        <li><a href="/profile">Profile</a></li>
                        <li><a href="/logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/login">Login</a></li>
                        <li><a href="/register">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Flash Messages -->
            <?php 
            $successMessage = getFlashMessage('success');
            $errorMessage = getFlashMessage('error');
            ?>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <?php echo $content; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer style="background: rgba(0,0,0,0.1); color: white; text-align: center; padding: 2rem 0; margin-top: auto;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.8;">
                Version <?php echo APP_VERSION; ?> | 
                Built with security and performance in mind
            </p>
        </div>
    </footer>
    
    <!-- Critical JavaScript (Inlined for performance) -->
    <script>
        // CSRF Token for AJAX requests
        window.csrfToken = '<?php echo generateCSRFToken(); ?>';
        
        // Basic form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Form loading states
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';
                        form.classList.add('loading');
                    }
                });
            });
            
            // Password strength indicator
            const passwordInputs = document.querySelectorAll('input[type="password"][name="password"], input[type="password"][name="new_password"]');
            passwordInputs.forEach(input => {
                const strengthMeter = document.createElement('div');
                strengthMeter.className = 'password-strength';
                strengthMeter.innerHTML = '<div class="strength-bar"></div><span class="strength-text"></span>';
                input.parentNode.appendChild(strengthMeter);
                
                input.addEventListener('input', function() {
                    const strength = calculatePasswordStrength(this.value);
                    updatePasswordStrength(strengthMeter, strength);
                });
            });
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
        
        function updatePasswordStrength(element, strength) {
            const bar = element.querySelector('.strength-bar');
            const text = element.querySelector('.strength-text');
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#2ecc71'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            bar.style.width = (strength * 20) + '%';
            bar.style.backgroundColor = colors[strength - 1] || '#e74c3c';
            text.textContent = labels[strength - 1] || 'Very Weak';
            text.style.color = colors[strength - 1] || '#e74c3c';
        }
    </script>
    
    <!-- Non-critical JavaScript -->
    <script src="/assets/js/main.js" async></script>
    
    <!-- Performance monitoring (if DEBUG_MODE is enabled) -->
    <?php if (DEBUG_MODE): ?>
    <script>
        // Performance monitoring
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                console.log('Page Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
                console.log('DOM Content Loaded:', perfData.domContentLoadedEventEnd - perfData.fetchStart, 'ms');
                console.log('First Paint:', performance.getEntriesByType('paint').find(entry => entry.name === 'first-paint')?.startTime, 'ms');
            }, 0);
        });
    </script>
    <?php endif; ?>
</body>
</html>