<?php
/**
 * Logout Page (logout.php)
 * Destroys PHP sessions and clears cookies
 */

require_once __DIR__ . '/backend/config/session.php';

startSecureSession();

// Get user ID before destroying session
$userId = $_SESSION['user_id'] ?? null;

// Clear remember me cookie
clearRememberMeCookie($userId);

// Destroy session
destroySession();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit();

