<?php
/**
 * Application Configuration
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production

// Timezone
date_default_timezone_set('UTC');

// JWT Secret Key (Change this in production!)
define('JWT_SECRET', 'your-secret-key-change-in-production-12345');
define('JWT_ALGORITHM', 'HS256');

// Application paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');

// Application base URL (for password reset links)
// Update this to your actual domain in production
define('APP_BASE_URL', 'http://localhost:3000');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

/**
 * Set CORS headers for API endpoints
 * @param array $allowedOrigins Array of allowed origins (default: localhost ports)
 */
function setCORSHeaders($allowedOrigins = null) {
    if ($allowedOrigins === null) {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001'
        ];
    }
    
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    header("Content-Type: application/json; charset=UTF-8");
}