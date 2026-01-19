<?php
/**
 * Admin User Management API (Get, Update, Delete)
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

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];

// Require authentication and admin role
$auth = requireAuth();
if ($auth['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit();
}

// Get user ID from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$userId = end($pathParts);

if (!is_numeric($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($method === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT u.*, c.* 
            FROM users u
            LEFT JOIN citizens c ON u.id = c.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        
        // Remove password from response
        unset($user['password']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Update user
        if (isset($data['email'])) {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$data['email'], $userId]);
        }
        
        // Update citizen data if exists
        $stmt = $db->prepare("SELECT id FROM citizens WHERE user_id = ?");
        $stmt->execute([$userId]);
        $citizenExists = $stmt->fetch();
        
        if ($citizenExists) {
            $stmt = $db->prepare("
                UPDATE citizens SET
                    full_name = COALESCE(?, full_name),
                    sex = COALESCE(?, sex),
                    date_of_birth = COALESCE(?, date_of_birth),
                    marital_status = COALESCE(?, marital_status),
                    phone_number = COALESCE(?, phone_number),
                    address = COALESCE(?, address),
                    mother_name = COALESCE(?, mother_name)
                WHERE user_id = ?
            ");
            $stmt->execute([
                $data['full_name'] ?? null,
                $data['sex'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['marital_status'] ?? null,
                $data['phone_number'] ?? null,
                $data['address'] ?? null,
                $data['mother_name'] ?? null,
                $userId
            ]);
        }
        
        // Fetch updated user
        $stmt = $db->prepare("
            SELECT u.*, c.* 
            FROM users u
            LEFT JOIN citizens c ON u.id = c.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        unset($user['password']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else if ($method === 'DELETE') {
    try {
        // Delete user (cascade will delete citizen record)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

