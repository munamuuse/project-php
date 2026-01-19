<?php
/**
 * PHP Session Management Functions
 * Handles session initialization, timeout, and validation
 */

/**
 * Start PHP session with secure settings
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
    }
}

/**
 * Check if session has expired (5 minutes of inactivity)
 */
function checkSessionTimeout() {
    $timeout = 300; // 5 minutes in seconds
    
    if (isset($_SESSION['last_activity'])) {
        // Check if session has expired
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session expired
            destroySession();
            return false;
        }
    } else {
        // First activity - set timestamp
        $_SESSION['last_activity'] = time();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Initialize user session after login
 */
function initUserSession($userData) {
    startSecureSession();
    
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['role'] = $userData['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSecureSession();
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (!checkSessionTimeout()) {
        return false;
    }
    
    return true;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Destroy session and clear all session data
 */
function destroySession() {
    startSecureSession();
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Set remember me cookie
 */
function setRememberMeCookie($userId, $username) {
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    
    // Store token in database (replace existing if user_id exists)
    try {
        // Delete old token for this user
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new token
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $expiresDate = date('Y-m-d H:i:s', $expires);
        $stmt->execute([$userId, $token, $expiresDate]);
        
        // Set cookie
        setcookie('remember_token', $token, $expires, '/', '', false, true);
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * Check remember me cookie and auto-login
 */
function checkRememberMeCookie() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $token = $_COOKIE['remember_token'];
    
    try {
        // Check if token exists and is valid
        $stmt = $db->prepare("SELECT rt.user_id, rt.expires_at, u.id, u.username, u.email, u.role FROM remember_tokens rt JOIN users u ON rt.user_id = u.id WHERE rt.token = ? AND rt.expires_at > NOW()");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Token is valid - initialize session
            initUserSession([
                'id' => $result['user_id'],
                'username' => $result['username'],
                'email' => $result['email'],
                'role' => $result['role']
            ]);
            
            return true;
        } else {
            // Invalid token - clear cookie
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }
    } catch(PDOException $e) {
        return false;
    }
}

/**
 * Clear remember me cookie
 */
function clearRememberMeCookie($userId = null) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Delete from database
        if ($userId) {
            require_once __DIR__ . '/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            try {
                $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR token = ?");
                $stmt->execute([$userId, $token]);
            } catch(PDOException $e) {
                // Ignore errors
            }
        }
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

