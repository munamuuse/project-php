<?php
/**
 * Public Home Page (index.php)
 * Traditional PHP page with header, navigation, sidebar, content, and footer
 * Required for PHP & MySQL course project
 */

session_start();

// Database connection
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/config/session.php';

// Check session and remember me cookie
if (!isLoggedIn()) {
    checkRememberMeCookie();
}

$database = new Database();
$db = $database->getConnection();

// Get current user if logged in
$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();

// Get some statistics for display (optional)
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM citizens WHERE is_active = 1");
    $stmt->execute();
    $stats = $stmt->fetch();
    $totalCitizens = $stats['total'] ?? 0;
} catch(PDOException $e) {
    $totalCitizens = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen System - Home</title>
    <link rel="stylesheet" href="frontend/src/App.css">
    <link rel="stylesheet" href="frontend/src/pages/Home.css">
    <style>
        /* Additional styles for PHP page structure */
        .php-page-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .php-content-wrapper {
            display: flex;
            flex: 1;
        }
        .php-sidebar {
            width: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        .php-main-content {
            flex: 1;
            padding: 30px;
        }
        .php-footer {
            background-color: #1e293b;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="php-page-container">
        <!-- Header -->
        <header class="home-header">
            <div class="home-container">
                <div class="home-logo">
                    <div class="logo-emblem">
                        <img src="frontend/src/image/logo.png" alt="Somali Coat of Arms" class="emblem-icon" style="width: 50px; height: 50px;">
                    </div>
                    <div class="logo-text">
                        <h1>Citizen System</h1>
                        <p>Government Citizen Management Platform</p>
                    </div>
                </div>
                
                <!-- Navigation Bar -->
                <nav class="home-nav">
                    <a href="index.php" class="nav-link active">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="btn btn-nav">Dashboard</a>
                        <?php if ($isAdmin): ?>
                            <a href="admin.php" class="btn btn-nav btn-admin">Admin</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-nav">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-nav">Login</a>
                        <a href="register.php" class="btn btn-nav btn-register">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <!-- Content Wrapper -->
        <div class="php-content-wrapper">
            <!-- Left Sidebar -->
            <aside class="php-sidebar">
                <h3>Quick Links</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;">
                        <a href="index.php" style="text-decoration: none; color: #2563eb;">Home</a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="about.php" style="text-decoration: none; color: #2563eb;">About Us</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li style="margin-bottom: 10px;">
                            <a href="dashboard.php" style="text-decoration: none; color: #2563eb;">My Dashboard</a>
                        </li>
                        <?php if ($isAdmin): ?>
                            <li style="margin-bottom: 10px;">
                                <a href="admin.php" style="text-decoration: none; color: #2563eb;">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li style="margin-bottom: 10px;">
                            <a href="login.php" style="text-decoration: none; color: #2563eb;">Login</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="register.php" style="text-decoration: none; color: #2563eb;">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php if ($isLoggedIn): ?>
                    <div style="margin-top: 30px; padding: 15px; background: #e0e7ff; border-radius: 8px;">
                        <p style="margin: 0; font-weight: bold;">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #64748b;">
                            Role: <?php echo htmlspecialchars($currentUser['role']); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- Main Content Area -->
            <main class="php-main-content">
                <h2>Welcome to Citizen System</h2>
                <p>The official government platform for citizen registration and identity management.</p>
                
                <?php if ($isLoggedIn): ?>
                    <div style="background: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3>You are logged in as <?php echo htmlspecialchars($currentUser['username']); ?></h3>
                        <p>Access your dashboard to manage your citizen profile and view your ID card.</p>
                        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div style="background: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3>Get Started</h3>
                        <p>Create an account to register as a citizen and receive your official ID card.</p>
                        <a href="register.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Register Now</a>
                        <a href="login.php" class="btn btn-secondary" style="margin-top: 10px; margin-left: 10px; display: inline-block;">Login</a>
                    </div>
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3>System Statistics</h3>
                        <p style="font-size: 2em; font-weight: bold; color: #2563eb;"><?php echo number_format($totalCitizens); ?></p>
                        <p>Active Citizens Registered</p>
                    </div>
                    
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3>Secure Platform</h3>
                        <p>Your data is protected with industry-standard security measures and encryption.</p>
                    </div>
                    
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3>Official ID Cards</h3>
                        <p>Get your official citizen ID card with all your registered information.</p>
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="php-footer">
            <p>&copy; <?php echo date('Y'); ?> Citizen System. All rights reserved.</p>
            <p>Federal Republic of Somalia - Government Citizen Management Platform</p>
        </footer>
    </div>
</body>
</html>

