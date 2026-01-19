<?php
/**
 * Admin Create Citizen API
 * Allows admins to create citizen records for any user
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

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['user_id', 'full_name', 'sex', 'date_of_birth', 'registration_date', 'expiration_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit();
        }
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$data['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        
        // Check if citizen already exists for this user
        $stmt = $db->prepare("SELECT id FROM citizens WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Citizen record already exists for this user']);
            exit();
        }
        
        // Generate Citizen ID (format: CTZ-YYYYMMDD-XXXXX where XXXXX is user_id padded)
        $citizenId = 'CTZ-' . date('Ymd') . '-' . str_pad($data['user_id'], 5, '0', STR_PAD_LEFT);
        
        // Insert citizen record
        $stmt = $db->prepare("
            INSERT INTO citizens (
                citizen_id, user_id, full_name, sex, date_of_birth, 
                marital_status, phone_number, address, mother_name, 
                profile_picture, registration_date, expiration_date, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $citizenId,
            $data['user_id'],
            $data['full_name'],
            $data['sex'],
            $data['date_of_birth'],
            $data['marital_status'] ?? 'single',
            $data['phone_number'] ?? null,
            $data['address'] ?? null,
            $data['mother_name'] ?? null,
            $data['profile_picture'] ?? null,
            $data['registration_date'],
            $data['expiration_date'],
            $data['is_active'] ?? 1
        ]);
        
        $citizenIdDb = $db->lastInsertId();
        
        // Fetch the created citizen record
        $stmt = $db->prepare("
            SELECT c.*, u.username, u.email 
            FROM citizens c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$citizenIdDb]);
        $citizen = $stmt->fetch();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen created successfully',
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

