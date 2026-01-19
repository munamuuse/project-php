<?php
/**
 * Check Session API
 * Returns current session status and user information
 */

// Handle OPTIONS preflight request FIRST (before any includes)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';

setCORSHeaders();
require_once __DIR__ . '/../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    startSecureSession();
    
    // Check remember me cookie if not logged in
    if (!isLoggedIn()) {
        checkRememberMeCookie();
    }
    
    if (isLoggedIn()) {
        $user = getCurrentUser();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => $user,
            'session_id' => session_id()
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'message' => 'Session expired or not logged in'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

