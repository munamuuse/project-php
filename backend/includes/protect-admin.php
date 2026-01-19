<?php
/**
 * Admin Protected Page Helper
 * Include this file at the top of any admin page
 */

require_once __DIR__ . '/../config/session.php';

startSecureSession();

// Check remember me cookie if not logged in
if (!isLoggedIn()) {
    checkRememberMeCookie();
}

// If not logged in, redirect to login
if (!isLoggedIn()) {
    $currentPage = $_SERVER['PHP_SELF'];
    header('Location: login.php?redirect=' . urlencode($currentPage) . '&expired=1');
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Check session timeout
if (!checkSessionTimeout()) {
    header('Location: login.php?expired=1');
    exit();
}

