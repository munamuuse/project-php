<?php
/**
 * Protected Page Helper
 * Include this file at the top of any page that requires authentication
 */

require_once __DIR__ . '/../config/session.php';

startSecureSession();

// Check remember me cookie if not logged in
if (!isLoggedIn()) {
    checkRememberMeCookie();
}

// If still not logged in, redirect to login
if (!isLoggedIn()) {
    $currentPage = $_SERVER['PHP_SELF'];
    header('Location: login.php?redirect=' . urlencode($currentPage) . '&expired=1');
    exit();
}

// Check session timeout
if (!checkSessionTimeout()) {
    header('Location: login.php?expired=1');
    exit();
}

