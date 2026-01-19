<?php
/**
 * Get Citizen Profile API
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

if ($method === 'GET') {
    // Require authentication
    $auth = requireAuth();
    $userId = $auth['user_id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.email 
            FROM citizens c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $citizen = $stmt->fetch();
        
        if (!$citizen) {
            http_response_code(404);
            echo json_encode(['error' => 'Citizen profile not found']);
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
    // Update citizen profile
    $auth = requireAuth();
    $userId = $auth['user_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = ['full_name', 'sex', 'date_of_birth', 'marital_status', 
                            'phone_number', 'address', 'mother_name', 'profile_picture'];
            
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
            
            $updateValues[] = $userId;
            $sql = "UPDATE citizens SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($updateValues);
        
        // Fetch updated record
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.email 
            FROM citizens c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $citizen = $stmt->fetch();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
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

