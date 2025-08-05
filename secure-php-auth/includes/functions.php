<?php
/**
 * Core Helper Functions
 */

if (!defined('SECURE_ACCESS')) {
    exit('Direct access not allowed');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Clean and sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    $checks = [
        PASSWORD_REQUIRE_UPPERCASE => preg_match('/[A-Z]/', $password),
        PASSWORD_REQUIRE_LOWERCASE => preg_match('/[a-z]/', $password),
        PASSWORD_REQUIRE_NUMBERS => preg_match('/[0-9]/', $password),
        PASSWORD_REQUIRE_SPECIAL => preg_match('/[^A-Za-z0-9]/', $password)
    ];
    
    foreach ($checks as $required => $passed) {
        if ($required && !$passed) {
            return false;
        }
    }
    
    return true;
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, HASH_ALGO, HASH_OPTIONS);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function passwordNeedsRehash($hash) {
    return password_needs_rehash($hash, HASH_ALGO, HASH_OPTIONS);
}

/**
 * Generate random token
 */
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Session management functions
 */
function regenerateSession() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    if (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_TIME) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    regenerateSession();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && checkSessionTimeout();
}

/**
 * Rate limiting functions
 */
function checkRateLimit($key, $maxAttempts, $timeWindow) {
    $db = Database::getInstance();
    
    // Clean expired entries first
    $db->delete("DELETE FROM rate_limits WHERE expires_at < NOW()");
    
    // Check current attempts
    $result = $db->selectOne(
        "SELECT attempts FROM rate_limits WHERE identifier = ? AND action = ?",
        [$key, 'rate_limit']
    );
    
    if (!$result) {
        return true; // No previous attempts
    }
    
    return $result['attempts'] < $maxAttempts;
}

function incrementRateLimit($key, $timeWindow = 3600) {
    $db = Database::getInstance();
    $expiresAt = date('Y-m-d H:i:s', time() + $timeWindow);
    
    // Try to update existing record
    $affected = $db->update(
        "UPDATE rate_limits SET attempts = attempts + 1, expires_at = ? WHERE identifier = ? AND action = ?",
        [$expiresAt, $key, 'rate_limit']
    );
    
    // If no existing record, create new one
    if ($affected === 0) {
        $db->insert(
            "INSERT INTO rate_limits (identifier, action, attempts, expires_at) VALUES (?, ?, 1, ?)",
            [$key, 'rate_limit', $expiresAt]
        );
    }
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                
                // Validate IP and exclude private ranges for forwarded IPs
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                } elseif ($key === 'REMOTE_ADDR' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    // For REMOTE_ADDR, allow private IPs (for local development)
                    return $ip;
                }
            }
        }
    }
    
    return '0.0.0.0'; // Fallback
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'event' => $event,
        'details' => $details,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null
    ];
    
    // Log to database
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO security_logs (user_id, event_type, event_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [
                $logEntry['user_id'],
                $event,
                json_encode($details),
                $logEntry['ip'],
                $logEntry['user_agent']
            ]
        );
    } catch (Exception $e) {
        // If database logging fails, log to file
        error_log("Security event logging failed: " . $e->getMessage());
    }
    
    // Also log to file for backup
    $logFile = LOGS_PATH . '/security.log';
    $logLine = json_encode($logEntry) . "\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    
    // Rotate log file if it gets too large
    if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
        rotateLogFile($logFile);
    }
}

/**
 * Rotate log file
 */
function rotateLogFile($logFile) {
    $info = pathinfo($logFile);
    $baseFileName = $info['dirname'] . '/' . $info['filename'];
    $extension = $info['extension'];
    
    // Move existing rotated logs
    for ($i = LOG_MAX_FILES - 1; $i > 0; $i--) {
        $oldFile = $baseFileName . '.' . $i . '.' . $extension;
        $newFile = $baseFileName . '.' . ($i + 1) . '.' . $extension;
        
        if (file_exists($oldFile)) {
            if ($i === LOG_MAX_FILES - 1) {
                unlink($oldFile); // Delete oldest log
            } else {
                rename($oldFile, $newFile);
            }
        }
    }
    
    // Move current log to .1
    $rotatedFile = $baseFileName . '.1.' . $extension;
    rename($logFile, $rotatedFile);
}

/**
 * Validate username
 */
function validateUsername($username) {
    if (strlen($username) < 3 || strlen($username) > 50) {
        return false;
    }
    
    // Allow letters, numbers, underscores, and hyphens
    return preg_match('/^[a-zA-Z0-9_-]+$/', $username);
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return true; // Phone is optional
    }
    
    // Remove all non-digit characters
    $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Basic validation - should be 10-15 digits possibly with + prefix
    return preg_match('/^\+?[0-9]{10,15}$/', $cleanPhone);
}

/**
 * Upload file securely
 */
function uploadFile($file, $allowedTypes = null, $maxSize = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    $maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
    
    // Check file size
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large');
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Validate file content (additional security)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
        throw new Exception('Invalid file content');
    }
    
    // Generate unique filename
    $newFilename = generate_unique_filename($file['name'], $extension);
    $uploadPath = UPLOAD_PATH_FULL . '/' . $newFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return $newFilename;
}

/**
 * Create thumbnail image
 */
function createThumbnail($imagePath, $thumbPath, $maxWidth = 150, $maxHeight = 150) {
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $imageType = $imageInfo[2];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = $originalWidth * $ratio;
    $newHeight = $originalHeight * $ratio;
    
    // Create image resource
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($imagePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($imagePath);
            break;
        default:
            return false;
    }
    
    // Create thumbnail
    $thumbnailImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);
        $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbnailImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled(
        $thumbnailImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $originalWidth, $originalHeight
    );
    
    // Save thumbnail
    $result = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumbnailImage, $thumbPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumbnailImage, $thumbPath, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumbnailImage, $thumbPath);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($thumbnailImage);
    
    return $result;
}

/**
 * Send email (placeholder for future implementation)
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    // Placeholder for email functionality
    // You can implement this using PHPMailer or similar library
    
    logSecurityEvent('email_sent', [
        'to' => $to,
        'subject' => $subject,
        'is_html' => $isHtml
    ]);
    
    return true; // Simulate success for now
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Generate avatar from initials
 */
function generateAvatar($firstName, $lastName, $size = 100) {
    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    
    // Create image
    $image = imagecreatetruecolor($size, $size);
    
    // Colors
    $bgColor = imagecolorallocate($image, 102, 126, 234); // #667eea
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Add text
    $fontSize = $size * 0.4;
    $fontFile = null; // Use default font
    
    // Calculate text position
    $textBox = imagettfbbox($fontSize, 0, $fontFile, $initials);
    $textWidth = $textBox[4] - $textBox[0];
    $textHeight = $textBox[1] - $textBox[7];
    
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2 + $textHeight;
    
    if ($fontFile && file_exists($fontFile)) {
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $initials);
    } else {
        // Fallback to built-in font
        $x = ($size - strlen($initials) * 10) / 2;
        $y = ($size - 15) / 2;
        imagestring($image, 5, $x, $y, $initials, $textColor);
    }
    
    // Output as base64
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();
    
    imagedestroy($image);
    
    return 'data:image/png;base64,' . base64_encode($imageData);
}

/**
 * Cache functions
 */
function cacheGet($key) {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $cacheFile = CACHE_PATH_FULL . '/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $cacheData = unserialize(file_get_contents($cacheFile));
    
    if ($cacheData['expires'] < time()) {
        unlink($cacheFile);
        return false;
    }
    
    return $cacheData['data'];
}

function cacheSet($key, $data, $lifetime = null) {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $lifetime = $lifetime ?? CACHE_LIFETIME;
    $cacheFile = CACHE_PATH_FULL . '/' . md5($key) . '.cache';
    
    $cacheData = [
        'data' => $data,
        'expires' => time() + $lifetime
    ];
    
    return file_put_contents($cacheFile, serialize($cacheData), LOCK_EX) !== false;
}

function cacheDelete($key) {
    $cacheFile = CACHE_PATH_FULL . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return true;
}

function cacheClear() {
    $files = glob(CACHE_PATH_FULL . '/*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    return true;
}
?>