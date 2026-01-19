<?php
/**
 * Forgot Password API - Request Password Reset
 * POST: Send password reset email
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
require_once __DIR__ . '/../config/email.php';

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
        if (empty($data['email']) && empty($data['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email or username is required']);
            exit();
        }
        
        $emailOrUsername = trim($data['email'] ?? $data['username'] ?? '');
        
        if (empty($emailOrUsername)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email or username is required']);
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
        
        // Find user by email or username
        try {
            $stmt = $db->prepare("SELECT id, email, username FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$emailOrUsername, $emailOrUsername]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("User lookup error: " . $e->getMessage() . " | Code: " . $e->getCode());
            http_response_code(500);
            echo json_encode(['error' => 'An error occurred while looking up your account.']);
            exit();
        }
        
        // Security: Don't reveal if email exists or not
        // Always return success message, but only send email if user exists
        if ($user && !empty($user['email'])) {
            try {
                // Generate secure random token
                $token = bin2hex(random_bytes(32)); // 64 character hex string
                
                // Set expiration time (30 minutes from now)
                $expiresAt = date('Y-m-d H:i:s', time() + (30 * 60));
                
                // Delete any existing reset tokens for this user (cleanup old/unused tokens)
                try {
                    $stmt = $db->prepare("DELETE FROM password_reset_tokens WHERE email = ? AND (is_used = 0 OR expires_at < NOW())");
                    $stmt->execute([$user['email']]);
                } catch (PDOException $e) {
                    // If table doesn't exist, log error but continue
                    $errorCode = (string)$e->getCode();
                    $errorMsg = $e->getMessage();
                    
                    if ($errorCode === '42S02' || strpos($errorMsg, "doesn't exist") !== false) {
                        error_log("password_reset_tokens table does not exist. Please run the migration.");
                        // Continue anyway - we'll try to insert and it will fail with a clear error
                    } else {
                        error_log("Delete token error: " . $e->getMessage());
                        // Continue - try to insert anyway
                    }
                }
                
                // Insert new reset token
                try {
                    $stmt = $db->prepare("INSERT INTO password_reset_tokens (email, token, expires_at, is_used) VALUES (?, ?, ?, 0)");
                    $stmt->execute([$user['email'], $token, $expiresAt]);
                } catch (PDOException $e) {
                    error_log("Insert token error: " . $e->getMessage() . " | Code: " . $e->getCode());
                    
                    // If table doesn't exist, provide helpful error
                    $errorCode = (string)$e->getCode();
                    $errorMsg = $e->getMessage();
                    
                    if ($errorCode === '42S02' || strpos($errorMsg, "doesn't exist") !== false) {
                        http_response_code(500);
                        echo json_encode(['error' => 'Database table not found. Please contact administrator.']);
                        exit();
                    } else {
                        throw $e;
                    }
                }
                
                // Get base URL from config or use default
                $baseUrl = defined('APP_BASE_URL') ? APP_BASE_URL : 'http://localhost:3000';
                $resetUrl = $baseUrl . '/reset-password?token=' . urlencode($token);
                
                // Send password reset email (don't fail if email fails)
                try {
                    error_log("Attempting to send password reset email to: " . $user['email']);
                    $emailResult = sendPasswordResetEmail($user['email'], $token, $resetUrl);
                    if (!$emailResult['success']) {
                        $errorMsg = $emailResult['error'] ?? 'Unknown error';
                        error_log("Email send failed for " . $user['email'] . ": " . $errorMsg);
                        error_log("Reset URL that was attempted: " . $resetUrl);
                        error_log("Token generated: " . substr($token, 0, 10) . "...");
                    } else {
                        error_log("Password reset email sent successfully to: " . $user['email']);
                    }
                } catch (Exception $emailError) {
                    // Log but don't fail the request
                    error_log("Email send exception for " . $user['email'] . ": " . $emailError->getMessage());
                    error_log("Exception trace: " . $emailError->getTraceAsString());
                }
                
            } catch (Exception $tokenError) {
                error_log("Token generation/insertion error: " . $tokenError->getMessage());
                // Still return success for security (don't reveal if email exists)
            }
        }
        
        // Always return success (security: don't reveal if email exists)
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
        
    } catch (PDOException $e) {
        error_log("Password reset PDO error: " . $e->getMessage() . " | Code: " . $e->getCode());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred. Please try again later.']);
    } catch (Exception $e) {
        error_log("Password reset general error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred. Please try again later.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
