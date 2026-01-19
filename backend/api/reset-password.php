<?php
/**
 * Reset Password API
 * POST: Reset password using token
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
require_once __DIR__ . '/../config/auth.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    try {
        // Get JSON input
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'Request body is required']);
            exit();
        }
        
        $data = json_decode($rawInput, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON format']);
            exit();
        }
        
        // Validate input
        if (empty($data['token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Token is required']);
            exit();
        }
        
        if (empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password is required']);
            exit();
        }
        
        if (empty($data['confirmPassword'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password confirmation is required']);
            exit();
        }
        
        $token = trim($data['token']);
        $password = $data['password'];
        $confirmPassword = $data['confirmPassword'];
        
        // Validate password match
        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
            exit();
        }
        
        // Validate password strength
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters']);
            exit();
        }
        
        // Connect to database
        try {
            $database = new Database();
            $db = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed. Please try again later.']);
            exit();
        }
        
        try {
            // Begin transaction
            $db->beginTransaction();
            
            // Verify token - check if it exists, is not used, and not expired
            $stmt = $db->prepare("
                SELECT email, expires_at, is_used 
                FROM password_reset_tokens 
                WHERE token = ? AND is_used = 0 AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset || empty($reset['email'])) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or expired token']);
                exit();
            }
            
            // Hash new password using secure bcrypt
            $hashedPassword = hashPassword($password);
            
            // Update user password
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $reset['email']]);
            
            // Check if password was actually updated
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'User account not found']);
                exit();
            }
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_reset_tokens SET is_used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            // Commit transaction
            $db->commit();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ]);
            
        } catch(PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Password reset error: " . $e->getMessage() . " | Code: " . $e->getCode());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred. Please try again later.']);
        }
    } catch (Exception $e) {
        error_log("Password reset general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred. Please try again later.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
