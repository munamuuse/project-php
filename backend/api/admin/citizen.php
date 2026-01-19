<?php
/**
 * Admin Citizen Management API (Get, Update, Delete)
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

// Get citizen ID from query parameter
$citizenId = $_GET['citizen_id'] ?? '';

if (empty($citizenId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Citizen ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($method === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.email 
            FROM citizens c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.citizen_id = ?
        ");
        $stmt->execute([$citizenId]);
        $citizen = $stmt->fetch();
        
        if (!$citizen) {
            http_response_code(404);
            echo json_encode(['error' => 'Citizen not found']);
            exit();
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'citizen' => $citizen
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = ['full_name', 'sex', 'date_of_birth', 'marital_status', 
                        'phone_number', 'address', 'mother_name', 'registration_date', 'expiration_date', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $updateValues[] = $citizenId;
        $sql = "UPDATE citizens SET " . implode(', ', $updateFields) . " WHERE citizen_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateValues);
        
        // Fetch updated citizen
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.email 
            FROM citizens c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.citizen_id = ?
        ");
        $stmt->execute([$citizenId]);
        $citizen = $stmt->fetch();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen updated successfully',
            'citizen' => $citizen
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else if ($method === 'DELETE') {
    // For DELETE requests, get citizen_id from query parameter
    $citizenId = $_GET['citizen_id'] ?? '';
    
    if (empty($citizenId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Citizen ID is required']);
        exit();
    }
    
    try {
        // Delete citizen
        $stmt = $db->prepare("DELETE FROM citizens WHERE citizen_id = ?");
        $stmt->execute([$citizenId]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Citizen not found']);
            exit();
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen deleted successfully'
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

