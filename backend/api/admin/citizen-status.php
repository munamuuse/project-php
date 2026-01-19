<?php
/**
 * Admin Citizen Status Update API (Active/Inactive)
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

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['citizen_id']) || !isset($data['is_active'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Citizen ID and is_active status are required']);
        exit();
    }
    
    $citizenId = $data['citizen_id'];
    $isActive = $data['is_active'] ? 1 : 0;
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Update citizen status
        $stmt = $db->prepare("UPDATE citizens SET is_active = ? WHERE citizen_id = ?");
        $stmt->execute([$isActive, $citizenId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Citizen not found']);
            exit();
        }
        
        // Fetch updated citizen
        $stmt = $db->prepare("SELECT * FROM citizens WHERE citizen_id = ?");
        $stmt->execute([$citizenId]);
        $citizen = $stmt->fetch();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen status updated successfully',
            'citizen' => $citizen
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

