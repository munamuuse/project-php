<?php
/**
 * Email Helper Functions using PHPMailer
 */

// Check if PHPMailer is available
$phpmailerAvailable = false;
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
}

/**
 * Get SMTP configuration
 */
function getSMTPConfig() {
    $configFile = __DIR__ . '/smtp.php';
    if (file_exists($configFile)) {
        return require $configFile;
    }
    
    // Default configuration (update these in smtp.php)
    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => 'noreply@citizensystem.com',
        'from_name' => 'Citizen System',
        'reply_to_email' => 'noreply@citizensystem.com',
        'reply_to_name' => 'Citizen System',
        'debug' => false,
        'debug_level' => 2,
    ];
}

/**
 * Send password reset email using PHPMailer
 * 
 * @param string $email User's email address
 * @param string $token Reset token
 * @param string $resetUrl Full URL to reset password page
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendPasswordResetEmail($email, $token, $resetUrl) {
    global $phpmailerAvailable;
    
    // If PHPMailer is not available, fall back to mail() function
    if (!$phpmailerAvailable) {
        error_log("PHPMailer not available, falling back to mail() function");
        return sendPasswordResetEmailFallback($email, $token, $resetUrl);
    }
    
    try {
        $config = getSMTPConfig();
        
        // Check if SMTP is configured
        if (empty($config['username']) || empty($config['password'])) {
            error_log("SMTP not configured. Please configure backend/config/smtp.php");
            // Fall back to mail() function
            return sendPasswordResetEmailFallback($email, $token, $resetUrl);
        }
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];
        
        // Enable verbose debug output (only if debug is enabled)
        if ($config['debug']) {
            $mail->SMTPDebug = $config['debug_level'];
            $mail->Debugoutput = function($str, $level) {
                // Log to error log
                error_log("SMTP Debug ($level): $str");
                // Also output to browser if in debug mode (for diagnostic script)
                if (php_sapi_name() !== 'cli' && ini_get('display_errors')) {
                    echo "SMTP Debug ($level): $str\n";
                }
            };
        }
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email);
        $mail->addReplyTo($config['reply_to_email'], $config['reply_to_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset Request - Citizen System';
        
        // Get base URL from config or use default
        $baseUrl = defined('APP_BASE_URL') ? APP_BASE_URL : 'http://localhost:3000';
        if (empty($resetUrl)) {
            $resetUrl = $baseUrl . '/reset-password?token=' . urlencode($token);
        }
        
        // HTML email template
        $htmlBody = getPasswordResetEmailTemplate($resetUrl);
        
        // Plain text version
        $textBody = getPasswordResetEmailText($resetUrl);
        
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        
        // Send email
        $mail->send();
        
        error_log("Password reset email sent successfully to: $email");
        return ['success' => true, 'error' => null];
        
    } catch (\Exception $e) {
        $errorInfo = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
        $errorMsg = "Failed to send password reset email to: $email. Error: " . $errorInfo;
        error_log($errorMsg);
        
        // Try fallback method
        error_log("Attempting fallback mail() method");
        return sendPasswordResetEmailFallback($email, $token, $resetUrl);
    }
}

/**
 * Fallback email function using PHP mail()
 */
function sendPasswordResetEmailFallback($email, $token, $resetUrl) {
    $baseUrl = defined('APP_BASE_URL') ? APP_BASE_URL : 'http://localhost:3000';
    if (empty($resetUrl)) {
        $resetUrl = $baseUrl . '/reset-password?token=' . urlencode($token);
    }
    
    $subject = 'Password Reset Request - Citizen System';
    $htmlBody = getPasswordResetEmailTemplate($resetUrl);
    $textBody = getPasswordResetEmailText($resetUrl);
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Citizen System <noreply@citizensystem.com>\r\n";
    $headers .= "Reply-To: noreply@citizensystem.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = @mail($email, $subject, $htmlBody, $headers);
    
    if (!$success) {
        $error = error_get_last();
        error_log("mail() function failed: " . ($error['message'] ?? 'Unknown error'));
        return ['success' => false, 'error' => 'Email sending failed. Please check server configuration.'];
    }
    
    return ['success' => true, 'error' => null];
}

/**
 * Get HTML email template
 */
function getPasswordResetEmailTemplate($resetUrl) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 12px 30px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            .button:hover { background: #1d4ed8; }
            .footer { text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px; }
            .warning { color: #dc2626; font-size: 14px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Password Reset Request</h1>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>We received a request to reset your password for your Citizen System account.</p>
                <p>Click the button below to reset your password:</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($resetUrl) . '" class="button">Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #2563eb;">' . htmlspecialchars($resetUrl) . '</p>
                <p class="warning"><strong>Important:</strong> This link will expire in 30 minutes for security reasons.</p>
                <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>
            <div class="footer">
                <p>This is an automated message from Citizen System. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Get plain text email template
 */
function getPasswordResetEmailText($resetUrl) {
    $text = "Password Reset Request\n\n";
    $text .= "Hello,\n\n";
    $text .= "We received a request to reset your password for your Citizen System account.\n\n";
    $text .= "Click the following link to reset your password:\n";
    $text .= $resetUrl . "\n\n";
    $text .= "This link will expire in 30 minutes for security reasons.\n\n";
    $text .= "If you did not request a password reset, please ignore this email.\n\n";
    $text .= "This is an automated message from Citizen System.\n";
    return $text;
}
