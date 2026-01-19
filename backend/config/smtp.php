<?php
/**
 * SMTP Configuration
 * 
 * Configure your SMTP settings here for email sending.
 * For Gmail, you'll need to use an App Password (not your regular password).
 * 
 * To get a Gmail App Password:
 * 1. Go to your Google Account settings
 * 2. Security → 2-Step Verification (must be enabled)
 * 3. App passwords → Generate app password
 * 4. Use that 16-character password below
 */

return [
    // SMTP Server Settings
    'host' => 'smtp.gmail.com',           // Gmail SMTP server
    'port' => 587,                         // Gmail SMTP port (587 for TLS, 465 for SSL)
    'encryption' => 'tls',                 // 'tls' or 'ssl'
    'username' => '',                      // Your Gmail address (e.g., yourname@gmail.com)
    'password' => '',                      // Gmail App Password (16 characters)
    
    // Email Settings
    'from_email' => 'noreply@citizensystem.com',
    'from_name' => 'Citizen System',
    'reply_to_email' => 'noreply@citizensystem.com',
    'reply_to_name' => 'Citizen System',
    
    // Debug Settings
    'debug' => true,                       // Set to true for SMTP debugging (enable to troubleshoot)
    'debug_level' => 2,                    // 0 = off, 1 = client messages, 2 = client and server messages
];
