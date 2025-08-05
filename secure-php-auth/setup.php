<?php
/**
 * Automated Setup Script for Secure PHP Authentication System
 * 
 * This script helps automate the installation process
 * Run this once after uploading files to your server
 */

// Prevent running in production
if (isset($_GET['force']) && $_GET['force'] !== 'yes') {
    die('Setup disabled. Add ?force=yes to URL if you really want to run this.');
}

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System already installed. Delete config/installed.lock to reinstall.');
}

$errors = [];
$warnings = [];
$success = [];

/**
 * Check system requirements
 */
function checkRequirements() {
    global $errors, $warnings, $success;
    
    // PHP Version
    if (version_compare(PHP_VERSION, '7.4.0') < 0) {
        $errors[] = 'PHP 7.4+ required. Current: ' . PHP_VERSION;
    } else {
        $success[] = 'PHP version: ' . PHP_VERSION;
    }
    
    // Required extensions
    $required_extensions = ['mysqli', 'json', 'session', 'gd', 'openssl'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Required PHP extension missing: $ext";
        } else {
            $success[] = "Extension loaded: $ext";
        }
    }
    
    // Optional but recommended extensions
    $recommended_extensions = ['mbstring', 'curl', 'fileinfo'];
    foreach ($recommended_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $warnings[] = "Recommended PHP extension missing: $ext";
        } else {
            $success[] = "Extension loaded: $ext";
        }
    }
    
    // Directory permissions
    $directories = ['logs', 'cache', 'uploads'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Cannot create directory: $dir";
            } else {
                $success[] = "Created directory: $dir";
            }
        }
        
        if (!is_writable($dir)) {
            $errors[] = "Directory not writable: $dir";
        } else {
            $success[] = "Directory writable: $dir";
        }
    }
    
    // Config file
    if (!is_readable('config/config.php')) {
        $errors[] = 'Config file not readable: config/config.php';
    } else {
        $success[] = 'Config file readable';
    }
    
    // .htaccess file for Apache
    if (!file_exists('.htaccess')) {
        $warnings[] = '.htaccess file missing - URL rewriting may not work';
    } else {
        $success[] = '.htaccess file exists';
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    global $errors, $success;
    
    require_once 'config/config.php';
    
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($mysqli->connect_error) {
            $errors[] = 'Database connection failed: ' . $mysqli->connect_error;
            return false;
        }
        
        $success[] = 'Database connection successful';
        
        // Check if tables exist
        $tables = ['users', 'user_sessions', 'login_attempts', 'security_logs', 'rate_limits'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $result = $mysqli->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            $errors[] = 'Missing database tables: ' . implode(', ', $missing_tables);
            $errors[] = 'Please import database/schema.sql';
            return false;
        }
        
        $success[] = 'All required database tables exist';
        $mysqli->close();
        return true;
        
    } catch (Exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
        return false;
    }
}

/**
 * Create default admin user
 */
function createAdminUser() {
    global $errors, $success;
    
    require_once 'config/config.php';
    require_once 'includes/functions.php';
    
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        // Check if admin user already exists
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $email = 'admin@example.com';
        $username = 'admin';
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $success[] = 'Admin user already exists';
            return true;
        }
        
        // Create admin user
        $password = 'Admin@123456';
        $passwordHash = hashPassword($password);
        
        $stmt = $mysqli->prepare("
            INSERT INTO users (
                username, email, password_hash, first_name, last_name, 
                email_verified, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        $firstName = 'System';
        $lastName = 'Administrator';
        
        $stmt->bind_param('sssss', $username, $email, $passwordHash, $firstName, $lastName);
        
        if ($stmt->execute()) {
            $success[] = 'Default admin user created successfully';
            $success[] = 'Email: admin@example.com';
            $success[] = 'Password: Admin@123456';
            $success[] = 'CHANGE THIS PASSWORD IMMEDIATELY!';
            return true;
        } else {
            $errors[] = 'Failed to create admin user: ' . $stmt->error;
            return false;
        }
        
    } catch (Exception $e) {
        $errors[] = 'Error creating admin user: ' . $e->getMessage();
        return false;
    }
}

/**
 * Generate security keys
 */
function generateSecurityKeys() {
    global $success;
    
    // Generate random keys for future use
    $keys = [
        'app_key' => bin2hex(random_bytes(32)),
        'jwt_secret' => bin2hex(random_bytes(32)),
        'encryption_key' => bin2hex(random_bytes(32))
    ];
    
    $keyFile = 'config/keys.php';
    $keyContent = "<?php\n// Auto-generated security keys\n";
    
    foreach ($keys as $name => $value) {
        $keyContent .= "define('" . strtoupper($name) . "', '$value');\n";
    }
    
    if (file_put_contents($keyFile, $keyContent)) {
        $success[] = 'Security keys generated: config/keys.php';
    }
}

/**
 * Create lock file
 */
function createLockFile() {
    global $success;
    
    $lockContent = "<?php\n// Installation completed on " . date('Y-m-d H:i:s') . "\n";
    $lockContent .= "// Delete this file to reinstall\n";
    
    if (file_put_contents('config/installed.lock', $lockContent)) {
        $success[] = 'Installation lock file created';
    }
}

/**
 * Handle form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkRequirements();
    
    if (empty($errors)) {
        if (testDatabaseConnection()) {
            createAdminUser();
            generateSecurityKeys();
            createLockFile();
            
            if (empty($errors)) {
                $success[] = '🎉 Installation completed successfully!';
                $success[] = 'You can now delete this setup.php file';
                $success[] = 'Navigate to your site to start using the system';
            }
        }
    }
} else {
    checkRequirements();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Secure PHP Auth System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #667eea;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .content { padding: 2rem; }
        .status-list {
            margin: 1rem 0;
            padding: 0;
            list-style: none;
        }
        .status-list li {
            padding: 0.5rem 1rem;
            margin: 0.25rem 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        .success::before { content: '✅ '; }
        .warning::before { content: '⚠️ '; }
        .error::before { content: '❌ '; }
        .btn {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            margin: 1rem 0;
        }
        .btn:hover { background: #5a6fd8; }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .section {
            margin: 2rem 0;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }
        .section h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .highlight {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Secure PHP Auth System</h1>
            <p>Automated Setup & Installation</p>
        </div>
        
        <div class="content">
            <?php if (!empty($success)): ?>
                <div class="section">
                    <h3>✅ Success</h3>
                    <ul class="status-list">
                        <?php foreach ($success as $msg): ?>
                            <li class="success"><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($warnings)): ?>
                <div class="section">
                    <h3>⚠️ Warnings</h3>
                    <ul class="status-list">
                        <?php foreach ($warnings as $msg): ?>
                            <li class="warning"><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="section">
                    <h3>❌ Errors</h3>
                    <ul class="status-list">
                        <?php foreach ($errors as $msg): ?>
                            <li class="error"><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="highlight">
                    <h3>🎉 Installation Complete!</h3>
                    <p>Your secure authentication system has been set up successfully.</p>
                    <p><strong>Next steps:</strong></p>
                    <ol>
                        <li>Delete this <code>setup.php</code> file for security</li>
                        <li>Change the default admin password immediately</li>
                        <li>Update <code>config/config.php</code> for production settings</li>
                        <li>Set <code>DEBUG_MODE</code> to <code>false</code> in production</li>
                        <li>Configure your web server properly</li>
                    </ol>
                    <p><a href="index.php" class="btn">Go to Login Page</a></p>
                </div>
                
            <?php elseif (empty($errors)): ?>
                <div class="section">
                    <h3>Ready to Install</h3>
                    <p>All system requirements are met. Click the button below to complete the installation.</p>
                    
                    <div class="highlight">
                        <h4>Before proceeding:</h4>
                        <ol>
                            <li>Make sure you've created the database and imported <code>database/schema.sql</code></li>
                            <li>Updated database credentials in <code>config/config.php</code></li>
                            <li>Set proper file permissions for logs, cache, and uploads directories</li>
                        </ol>
                    </div>
                    
                    <form method="post">
                        <button type="submit" class="btn">Complete Installation</button>
                    </form>
                </div>
                
            <?php else: ?>
                <div class="section">
                    <h3>Fix Errors Before Continuing</h3>
                    <p>Please resolve the errors above before proceeding with the installation.</p>
                    
                    <h4>Common Solutions:</h4>
                    <ul>
                        <li><strong>Missing PHP extensions:</strong> Install via your package manager or enable in php.ini</li>
                        <li><strong>Directory permissions:</strong> Run <code>chmod 755 logs cache uploads</code></li>
                        <li><strong>Database connection:</strong> Check credentials in config/config.php</li>
                        <li><strong>Missing tables:</strong> Import database/schema.sql into your database</li>
                    </ul>
                    
                    <button type="button" onclick="location.reload()" class="btn">Recheck Requirements</button>
                </div>
            <?php endif; ?>
            
            <div class="section">
                <h3>📖 Documentation</h3>
                <p>For detailed setup instructions, troubleshooting, and configuration options, please refer to the <code>README.md</code> file.</p>
                
                <h4>Quick Database Setup:</h4>
                <pre>mysql -u root -p
CREATE DATABASE secure_auth_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE secure_auth_system;
SOURCE database/schema.sql;
EXIT;</pre>
            </div>
        </div>
    </div>
</body>
</html>