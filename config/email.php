<?php
/**
 * Email Configuration for ClassTrack
 * Handles Gmail SMTP settings for sending OTP emails
 */

class EmailConfig {
    // Gmail SMTP Configuration
    private $host = 'smtp.gmail.com';
    private $port = 587;
    private $username = 'classtrack.admin@gmail.com'; // Your Gmail address
    private $password = 'pemc ckxu rmwr lezr'; // Your App Password (not regular password)
    private $encryption = 'tls';
    private $fromEmail = 'classtrack.admin@gmail.com'; // Same as username
    private $fromName = 'ClassTrack Admin (No-Reply)';
    
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
