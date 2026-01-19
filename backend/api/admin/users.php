<?php
/**
 * Admin Users Management API
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

$database = new Database();
$db = $database->getConnection();

if ($method === 'GET') {
    try {
        // Get search query
        $search = $_GET['search'] ?? '';
        $searchQuery = "%$search%";
        
        if (!empty($search)) {
            $stmt = $db->prepare("
                SELECT u.id, u.username, u.email, u.role, u.created_at,
                       c.citizen_id, c.full_name, c.phone_number, c.is_active
                FROM users u
                LEFT JOIN citizens c ON u.id = c.user_id
                WHERE u.username LIKE ? OR u.email LIKE ? OR c.full_name LIKE ? OR c.citizen_id LIKE ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$searchQuery, $searchQuery, $searchQuery, $searchQuery]);
        } else {
            $stmt = $db->prepare("
                SELECT u.id, u.username, u.email, u.role, u.created_at,
                       c.citizen_id, c.full_name, c.phone_number, c.is_active
                FROM users u
                LEFT JOIN citizens c ON u.id = c.user_id
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
        }
        
        $users = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

