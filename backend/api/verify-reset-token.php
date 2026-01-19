<?php
/**
 * Verify Reset Token API
 * GET: Verify if a reset token is valid
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
require_once __DIR__ . '/../config/database.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['error' => 'Token is required', 'valid' => false]);
            exit();
        }
        
        // Connect to database
        try {
            $database = new Database();
            $db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed', 'valid' => false]);
            exit();
        }
        
        try {
            // Check if token exists, is not used, and not expired
            $stmt = $db->prepare("
                SELECT email, expires_at, is_used 
                FROM password_reset_tokens 
                WHERE token = ? AND is_used = 0 AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reset && !empty($reset['email'])) {
                http_response_code(200);
                echo json_encode([
                    'valid' => true,
                    'message' => 'Token is valid'
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'valid' => false,
                    'error' => 'Invalid or expired token'
                ]);
            }
            
        } catch(PDOException $e) {
            error_log("Token verification error: " . $e->getMessage() . " | Code: " . $e->getCode());
            
            // If table doesn't exist, provide helpful error
            $errorCode = (string)$e->getCode();
            $errorMsg = $e->getMessage();
            
            if ($errorCode === '42S02' || strpos($errorMsg, "doesn't exist") !== false) {
                http_response_code(500);
                echo json_encode(['error' => 'Database table not found', 'valid' => false]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'An error occurred', 'valid' => false]);
            }
        }
    } catch (Exception $e) {
        error_log("Token verification general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred', 'valid' => false]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
