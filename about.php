<?php
/**
 * About Page (about.php)
 * Traditional PHP page with header, navigation, sidebar, content, and footer
 */

session_start();
require_once __DIR__ . '/backend/config/session.php';

// Check session and remember me cookie
if (!isLoggedIn()) {
    checkRememberMeCookie();
}

$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Citizen System</title>
    <link rel="stylesheet" href="frontend/src/App.css">
    <link rel="stylesheet" href="frontend/src/pages/Home.css">
    <style>
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
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link active">About</a>
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
                <h3>Navigation</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;">
                        <a href="index.php" style="text-decoration: none; color: #2563eb;">Home</a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="about.php" style="text-decoration: none; color: #2563eb; font-weight: bold;">About Us</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li style="margin-bottom: 10px;">
                            <a href="dashboard.php" style="text-decoration: none; color: #2563eb;">My Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li style="margin-bottom: 10px;">
                            <a href="login.php" style="text-decoration: none; color: #2563eb;">Login</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="register.php" style="text-decoration: none; color: #2563eb;">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </aside>

            <!-- Main Content Area -->
            <main class="php-main-content">
                <h2>About Citizen System</h2>
                
                <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
                    <h3>Our Mission</h3>
                    <p>The Citizen System is the official government platform for citizen registration and identity management in the Federal Republic of Somalia. Our mission is to provide a secure, efficient, and user-friendly system for citizens to register and manage their official identity information.</p>
                    
                    <h3 style="margin-top: 30px;">Features</h3>
                    <ul>
                        <li>Secure citizen registration</li>
                        <li>Official ID card generation</li>
                        <li>Profile management</li>
                        <li>Administrative dashboard</li>
                        <li>Secure authentication system</li>
                    </ul>
                    
                    <h3 style="margin-top: 30px;">Technology</h3>
                    <p>This system is built using modern web technologies including PHP, MySQL, and React, ensuring security, reliability, and excellent user experience.</p>
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

