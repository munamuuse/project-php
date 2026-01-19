<?php
/**
 * Admin Activities and Latest Registrations API
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
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        // Get latest citizen registrations
        $stmt = $db->prepare("
            SELECT c.*, u.username 
            FROM citizens c
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $latestRegistrations = $stmt->fetchAll();
        
        // Format registration dates
        foreach ($latestRegistrations as &$reg) {
            $reg['formatted_date'] = date('M d, Y', strtotime($reg['created_at']));
            $reg['relative_time'] = getRelativeTime($reg['created_at']);
        }
        unset($reg); // Break reference
        
        // Get admin activities (we'll simulate this for now - in a real system you'd have an activity log table)
        // For now, we'll return recent citizen updates
        $stmt = $db->prepare("
            SELECT c.*, u.username as admin_username
            FROM citizens c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE u.role = 'admin'
            ORDER BY c.updated_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $adminActivities = $stmt->fetchAll();
        
        // Format activities
        foreach ($adminActivities as &$activity) {
            $activity['formatted_date'] = date('M d, Y', strtotime($activity['updated_at']));
            $activity['relative_time'] = getRelativeTime($activity['updated_at']);
            $activity['action'] = 'Updated a citizen record';
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'latest_registrations' => $latestRegistrations,
            'admin_activities' => $adminActivities
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function getRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return $time . ' s ago';
    } elseif ($time < 3600) {
        return floor($time / 60) . ' m ago';
    } elseif ($time < 86400) {
        return floor($time / 3600) . ' h ago';
    } elseif ($time < 604800) {
        return floor($time / 86400) . ' d ago';
    } else {
        return date('M d, Y', strtotime($datetime));
    }
}
