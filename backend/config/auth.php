<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/config.php';

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate JWT Token
 */
function generateJWT($payload) {
    $header = base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]));
    $payload['exp'] = time() + (60 * 60 * 24); // 24 hours
    $payload['iat'] = time();
    $payload_encoded = base64UrlEncode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$header.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = base64UrlEncode($signature);
    
    return "$header.$payload_encoded.$signature_encoded";
}

/**
 * Verify JWT Token
 */
function verifyJWT($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    [$header, $payload, $signature] = $parts;
    
    $signature_check = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
    $signature_check_encoded = base64UrlEncode($signature_check);
    
    if ($signature_check_encoded !== $signature) {
        return false;
    }
    
    $payload_data = json_decode(base64UrlDecode($payload), true);
    
    if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
        return false;
    }
    
    return $payload_data;
}

/**
 * Get authorization token from request
 */
function getAuthToken() {
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Require authentication
 */
function requireAuth() {
    $token = getAuthToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }
    
    $payload = verifyJWT($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit();
    }
    
    return $payload;
}

/**
 * Base64 URL encode
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decode
 */
function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

