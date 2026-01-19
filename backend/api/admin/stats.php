<?php
/**
 * Admin Statistics API
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
        // Get total citizens registered
        $stmt = $db->query("SELECT COUNT(*) as count FROM citizens");
        $totalCitizens = $stmt->fetch()['count'];
        
        // Get active citizens
        $stmt = $db->query("SELECT COUNT(*) as count FROM citizens WHERE is_active = 1");
        $activeCitizens = $stmt->fetch()['count'];
        
        // Get total administrators
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $totalAdmins = $stmt->fetch()['count'];
        
        // Get total regular users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        $totalUsers = $stmt->fetch()['count'];
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_citizens' => (int)$totalCitizens,
                'active_citizens' => (int)$activeCitizens,
                'total_admins' => (int)$totalAdmins,
                'total_users' => (int)$totalUsers
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
