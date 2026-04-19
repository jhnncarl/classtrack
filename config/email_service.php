<?php
/**
 * Email Service for ClassTrack
 * Handles sending emails using PHPMailer and Gmail SMTP
 */

require_once 'email.php';

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;
    
    public function __construct() {
        $this->config = new EmailConfig();
        $this->initializeMailer();
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
