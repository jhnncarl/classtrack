<?php
/**
 * Email Service for ClassTrack
 * Handles sending emails using PHPMailer and Gmail SMTP
 */

require_once 'email.php';
require_once 'app_config.php';

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;
    private $baseUrl;
    
    public function __construct() {
        $this->config = new EmailConfig();
        $this->initializeMailer();
        // Set base URL - change this for different deployments
        $this->baseUrl = $this->getBaseUrl();
    }
    
    /**
     * Get base URL for the application
     * @return string
     */
    private function getBaseUrl() {
        // Use the configured base URL
        if (defined('CLASSTRACK_BASE_URL')) {
            return CLASSTRACK_BASE_URL;
        }
        
        // Fallback for development if not configured
        if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
            return 'http://localhost/classtrack';
        }
        
        // Default fallback
        return 'https://your-domain.com/classtrack';
    }
    
    /**
     * Initialize PHPMailer
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        $smtpConfig = $this->config->getSmtpConfig();
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $smtpConfig['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $smtpConfig['username'];
        $this->mailer->Password = $smtpConfig['password'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $smtpConfig['port'];
        
        // Set timeout
        $this->mailer->Timeout = 30;
        
        // Set charset
        $this->mailer->CharSet = 'UTF-8';
    }
    
    /**
     * Send account creation email
     * @param string $toEmail
     * @param string $firstName
     * @param string $subject
     * @param string $emailBody
     * @return array
     */
    public function sendAccountCreationEmail($toEmail, $firstName, $subject, $emailBody) {
        try {
            if (!$this->config->isConfigured()) {
                return ['success' => false, 'message' => 'Email service not configured'];
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $smtpConfig = $this->config->getSmtpConfig();
            $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $this->mailer->addAddress($toEmail, $firstName);
            
            // Set reply-to to a different address (optional)
            $this->mailer->addReplyTo('admin@classtrack.system', 'ClassTrack Support');
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $emailBody;
            
            // Create plain text version
            $plainTextBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $emailBody));
            $this->mailer->AltBody = $plainTextBody;
            
            $this->mailer->send();
            
            return ['success' => true, 'message' => 'Account creation email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send account creation email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send OTP email
     * @param string $toEmail
     * @param string $otp
     * @return array
     */
    public function sendOTPEmail($toEmail, $otp) {
        try {
            if (!$this->config->isConfigured()) {
                return ['success' => false, 'message' => 'Email service not configured'];
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $smtpConfig = $this->config->getSmtpConfig();
            $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $this->mailer->addAddress($toEmail);
            
            // Set reply-to to a different address (optional)
            $this->mailer->addReplyTo('admin@classtrack.system', 'ClassTrack Support');
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'ClassTrack Admin - Password Reset OTP';
            
            $emailBody = $this->getOTPEmailTemplate($otp);
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = $this->getOTPEmailTextTemplate($otp);
            
            $this->mailer->send();
            
            return ['success' => true, 'message' => 'OTP sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send OTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get HTML email template for OTP
     * @param string $otp
     * @return string
     */
    private function getOTPEmailTemplate($otp) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ClassTrack Password Reset</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f4f4f4;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: #667eea;
                    margin-bottom: 10px;
                }
                .otp-code {
                    background: #667eea;
                    color: white;
                    font-size: 32px;
                    font-weight: bold;
                    padding: 20px;
                    text-align: center;
                    border-radius: 8px;
                    letter-spacing: 5px;
                    margin: 30px 0;
                }
                .info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-left: 4px solid #667eea;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">ClassTrack</div>
                    <h2>Password Reset Request</h2>
                </div>
                
                <p>Hello,</p>
                
                <p>You have requested to reset your password for your ClassTrack account. The system administrator has generated a One-Time Password (OTP) for you to proceed:</p>
                
                <div class="otp-code">' . htmlspecialchars($otp) . '</div>
                
                <div class="info">
                    <strong>Important Information:</strong>
                    <ul>
                        <li>This OTP is valid for <strong>5 minutes</strong> only</li>
                        <li>Do not share this OTP with anyone</li>
                        <li>If you didn\'t request this password reset, please ignore this email</li>
                    </ul>
                </div>
                
                <p>If you have any questions or concerns, please contact the system administrator.</p>
                
                <div class="footer">
                    <p>Best regards,<br>ClassTrack Administration</p>
                    <p><em>This is an automated message from the ClassTrack system. Please do not reply to this email.</em></p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get plain text email template for OTP
     * @param string $otp
     * @return string
     */
    private function getOTPEmailTextTemplate($otp) {
        return '
ClassTrack Admin - Password Reset OTP

Hello,

You have requested to reset your password for your ClassTrack account. 
The system administrator has generated a One-Time Password (OTP) for you to proceed:

OTP: ' . $otp . '

Important Information:
- This OTP is valid for 5 minutes only
- Do not share this OTP with anyone
- If you didn\'t request this password reset, please ignore this email

If you have any questions or concerns, please contact the system administrator.

Best regards,
ClassTrack Administration

This is an automated message from the ClassTrack system. Please do not reply to this email.';
    }
    
    /**
     * Test email configuration
     * @return array
     */
    public function testConfiguration() {
        try {
            if (!$this->config->isConfigured()) {
                return ['success' => false, 'message' => 'Email not configured'];
            }
            
            // Try to connect to SMTP
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mailer->SMTPConnect();
            
            return ['success' => true, 'message' => 'Email configuration is valid'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email configuration error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send teacher account approval email
     * @param string $toEmail
     * @param string $firstName
     * @param string $lastName
     * @return array
     */
    public function sendTeacherApprovalEmail($toEmail, $firstName, $lastName) {
        try {
            if (!$this->config->isConfigured()) {
                return ['success' => false, 'message' => 'Email service not configured'];
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $smtpConfig = $this->config->getSmtpConfig();
            $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $this->mailer->addAddress($toEmail, $firstName . ' ' . $lastName);
            
            // Set reply-to to a different address (optional)
            $this->mailer->addReplyTo('admin@classtrack.system', 'ClassTrack Support');
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'ClassTrack - Teacher Account Approved';
            
            $emailBody = $this->getTeacherApprovalEmailTemplate($firstName, $lastName);
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = $this->getTeacherApprovalEmailTextTemplate($firstName, $lastName);
            
            $this->mailer->send();
            
            return ['success' => true, 'message' => 'Teacher approval email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send teacher approval email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send teacher account rejection email
     * @param string $toEmail
     * @param string $firstName
     * @param string $lastName
     * @return array
     */
    public function sendTeacherRejectionEmail($toEmail, $firstName, $lastName) {
        try {
            if (!$this->config->isConfigured()) {
                return ['success' => false, 'message' => 'Email service not configured'];
            }
            
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $smtpConfig = $this->config->getSmtpConfig();
            $this->mailer->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $this->mailer->addAddress($toEmail, $firstName . ' ' . $lastName);
            
            // Set reply-to to a different address (optional)
            $this->mailer->addReplyTo('admin@classtrack.system', 'ClassTrack Support');
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'ClassTrack - Teacher Account Status Update';
            
            $emailBody = $this->getTeacherRejectionEmailTemplate($firstName, $lastName);
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = $this->getTeacherRejectionEmailTextTemplate($firstName, $lastName);
            
            $this->mailer->send();
            
            return ['success' => true, 'message' => 'Teacher rejection email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email Send Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send teacher rejection email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get HTML email template for teacher approval
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    private function getTeacherApprovalEmailTemplate($firstName, $lastName) {
        $loginUrl = $this->baseUrl . '/auth/login.php';
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ClassTrack - Teacher Account Approved</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f4f4f4;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: #28a745;
                    margin-bottom: 10px;
                }
                .approval-badge {
                    background: #28a745;
                    color: white;
                    font-size: 18px;
                    font-weight: bold;
                    padding: 15px;
                    text-align: center;
                    border-radius: 8px;
                    margin: 30px 0;
                }
                .login-button {
                    display: inline-block;
                    background: #007bff;
                    color: white;
                    text-decoration: none;
                    padding: 15px 30px;
                    border-radius: 8px;
                    font-weight: bold;
                    text-align: center;
                    margin: 20px 0;
                }
                .login-button:hover {
                    background: #0056b3;
                }
                .info {
                    background: #d4edda;
                    padding: 15px;
                    border-left: 4px solid #28a745;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">ClassTrack</div>
                    <h2>Teacher Account Approved! 🎉</h2>
                </div>
                
                <p>Dear ' . htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) . ',</p>
                
                <p>Good news! Your teacher account registration for ClassTrack has been <strong>approved</strong> by the system administrator.</p>
                
                <div class="approval-badge">
                    ✓ ACCOUNT APPROVED
                </div>
                
                <p style="text-align: center; margin: 30px 0;">
                    <strong>You can now access your teacher account:</strong><br>
                    <a href="' . htmlspecialchars($loginUrl) . '" class="login-button">
                        🚀 Login to ClassTrack
                    </a>
                </p>
                
                <div class="info">
                    <strong>What\'s Next?</strong>
                    <ul>
                        <li>Click the button above to log in to your account</li>
                        <li>Start creating and managing your classes</li>
                        <li>Take attendance and generate reports</li>
                        <li>Access all teacher features and tools</li>
                    </ul>
                </div>
                
                <p>If you have any questions or need assistance getting started, please don\'t hesitate to contact our support team.</p>
                
                <div class="footer">
                    <p>Best regards,<br>ClassTrack Administration</p>
                    <p><em>This is an automated message from the ClassTrack system. Please do not reply to this email.</em></p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get plain text email template for teacher approval
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    private function getTeacherApprovalEmailTextTemplate($firstName, $lastName) {
        $loginUrl = $this->baseUrl . '/auth/login.php';
        return '
ClassTrack - Teacher Account Approved

Dear ' . $firstName . ' ' . $lastName . ',

Good news! Your teacher account registration for ClassTrack has been approved by the system administrator.

✓ ACCOUNT APPROVED

You can now access your teacher account:
Login URL: ' . $loginUrl . '

What\'s Next?
- Click the link above to log in to your account
- Start creating and managing your classes
- Take attendance and generate reports
- Access all teacher features and tools

If you have any questions or need assistance getting started, please don\'t hesitate to contact our support team.

Best regards,
ClassTrack Administration

This is an automated message from the ClassTrack system. Please do not reply to this email.';
    }
    
    /**
     * Get HTML email template for teacher rejection
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    private function getTeacherRejectionEmailTemplate($firstName, $lastName) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ClassTrack - Teacher Account Status Update</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f4f4f4;
                }
                .container {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: #dc3545;
                    margin-bottom: 10px;
                }
                .status-badge {
                    background: #dc3545;
                    color: white;
                    font-size: 18px;
                    font-weight: bold;
                    padding: 15px;
                    text-align: center;
                    border-radius: 8px;
                    margin: 30px 0;
                }
                .info {
                    background: #f8d7da;
                    padding: 15px;
                    border-left: 4px solid #dc3545;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">ClassTrack</div>
                    <h2>Teacher Account Status Update</h2>
                </div>
                
                <p>Dear ' . htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) . ',</p>
                
                <p>We regret to inform you that your teacher account registration for ClassTrack has been <strong>rejected</strong> by the system administrator.</p>
                
                <div class="status-badge">
                    ✗ ACCOUNT REJECTED
                </div>
                
                <div class="info">
                    <strong>Important Information:</strong>
                    <ul>
                        <li>Your registration did not meet the current requirements</li>
                        <li>You may contact the administration for more details</li>
                        <li>If you believe this is an error, please reach out to support</li>
                    </ul>
                </div>
                
                <p>If you have any questions about this decision or would like to inquire about future registration opportunities, please contact our support team.</p>
                
                <div class="footer">
                    <p>Best regards,<br>ClassTrack Administration</p>
                    <p><em>This is an automated message from the ClassTrack system. Please do not reply to this email.</em></p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get plain text email template for teacher rejection
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    private function getTeacherRejectionEmailTextTemplate($firstName, $lastName) {
        return '
ClassTrack - Teacher Account Status Update

Dear ' . $firstName . ' ' . $lastName . ',

We regret to inform you that your teacher account registration for ClassTrack has been rejected by the system administrator.

✗ ACCOUNT REJECTED

Important Information:
- Your registration did not meet the current requirements
- You may contact the administration for more details
- If you believe this is an error, please reach out to support

If you have any questions about this decision or would like to inquire about future registration opportunities, please contact our support team.

Best regards,
ClassTrack Administration

This is an automated message from the ClassTrack system. Please do not reply to this email.';
    }
    
    /**
     * Get configuration status
     * @return array
     */
    public function getConfigurationStatus() {
        return [
            'configured' => $this->config->isConfigured(),
            'status_message' => $this->config->getConfigStatus(),
            'smtp_host' => $this->config->getSmtpConfig()['host'],
            'smtp_port' => $this->config->getSmtpConfig()['port'],
            'encryption' => $this->config->getSmtpConfig()['encryption']
        ];
    }
}
?>
