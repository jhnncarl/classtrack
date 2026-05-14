<?php
/**
 * Email Configuration for ClassTrack
 * Handles Gmail SMTP settings for sending OTP emails
 */

require_once 'env.php';

class EmailConfig {
    // Gmail SMTP Configuration
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->host = envValue('SMTP_HOST', 'smtp.gmail.com');
        $this->port = (int) envValue('SMTP_PORT', 587);
        $this->username = envValue('SMTP_USERNAME', '');
        $this->password = envValue('SMTP_PASSWORD', '');
        $this->encryption = envValue('SMTP_ENCRYPTION', 'tls');
        $this->fromEmail = envValue('SMTP_FROM_EMAIL', $this->username);
        $this->fromName = envValue('SMTP_FROM_NAME', 'ClassTrack');
    }
    
    /**
     * Get SMTP configuration
     * @return array
     */
    public function getSmtpConfig() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName
        ];
    }
    
    /**
     * Check if email configuration is complete
     * @return bool
     */
    public function isConfigured() {
        return !empty($this->username) && !empty($this->password) && !empty($this->fromEmail);
    }
    
    /**
     * Get configuration status message
     * @return string
     */
    public function getConfigStatus() {
        if ($this->isConfigured()) {
            return 'Email configuration is complete';
        } else {
            return 'Email configuration incomplete. Please set Gmail credentials.';
        }
    }
}
?>
