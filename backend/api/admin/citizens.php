<?php
/**
 * Admin Citizens Management API
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
        // Get search query and pagination
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $searchQuery = "%$search%";
        
        if (!empty($search)) {
            // Get total count
            $countStmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM citizens
                WHERE citizen_id LIKE ? OR full_name LIKE ? OR phone_number LIKE ?
            ");
            $countStmt->execute([$searchQuery, $searchQuery, $searchQuery]);
            $total = $countStmt->fetch()['total'];
            
            // Get citizens
            $stmt = $db->prepare("
                SELECT citizen_id, full_name, sex, date_of_birth, registration_date, is_active, created_at
                FROM citizens
                WHERE citizen_id LIKE ? OR full_name LIKE ? OR phone_number LIKE ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchQuery, $searchQuery, $searchQuery, $limit, $offset]);
        } else {
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) as total FROM citizens");
            $total = $countStmt->fetch()['total'];
            
            // Get citizens
            $stmt = $db->prepare("
                SELECT citizen_id, full_name, sex, date_of_birth, registration_date, is_active, created_at
                FROM citizens
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
        }
        
        $citizens = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'citizens' => $citizens,
            'pagination' => [
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

