<?php
/**
 * OTP (One-Time Password) Helper Class for ClassTrack
 * Handles OTP generation, validation, and storage
 */

require_once 'database.php';

class OTPHelper {
    private $db;
    private $otpLength = 6;
    private $otpExpiry = 300; // 5 minutes in seconds
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Generate a random OTP
     * @return string
     */
    public function generateOTP() {
        return str_pad(rand(0, 999999), $this->otpLength, '0', STR_PAD_LEFT);
    }
    
    /**
     * Store OTP in database
     * @param string $email
     * @param string $otp
     * @return bool
     */
    public function storeOTP($email, $otp) {
        try {
            // First, delete any existing OTP for this email
            $this->deleteOTP($email);
            
            // Insert new OTP
            $sql = "INSERT INTO password_resets (email, otp, expires_at, created_at) 
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$email, $otp, $this->otpExpiry]);
            
        } catch (Exception $e) {
            error_log("OTP Storage Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate OTP
     * @param string $email
     * @param string $otp
     * @return array
     */
    public function validateOTP($email, $otp) {
        try {
            $sql = "SELECT * FROM password_resets 
                    WHERE email = ? AND otp = ? AND expires_at > NOW() 
                    ORDER BY created_at DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $otp]);
            $result = $stmt->fetch();
            
            if ($result) {
                return ['valid' => true, 'message' => 'OTP is valid'];
            } else {
                return ['valid' => false, 'message' => 'Invalid or expired OTP'];
            }
            
        } catch (Exception $e) {
            error_log("OTP Validation Error: " . $e->getMessage());
            return ['valid' => false, 'message' => 'OTP validation failed'];
        }
    }
    
    /**
     * Delete OTP after use
     * @param string $email
     * @return bool
     */
    public function deleteOTP($email) {
        try {
            $sql = "DELETE FROM password_resets WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$email]);
            
        } catch (Exception $e) {
            error_log("OTP Deletion Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if there's a valid OTP for the email
     * @param string $email
     * @return bool
     */
    public function hasValidOTP($email) {
        try {
            $sql = "SELECT COUNT(*) as count FROM password_resets 
                    WHERE email = ? AND expires_at > NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log("OTP Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired OTPs
     * @return bool
     */
    public function cleanupExpiredOTPs() {
        try {
            $sql = "DELETE FROM password_resets WHERE expires_at <= NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("OTP Cleanup Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remaining time for OTP in seconds
     * @param string $email
     * @return int
     */
    public function getOTPExpiryTime($email) {
        try {
            $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) as remaining_time 
                    FROM password_resets 
                    WHERE email = ? AND expires_at > NOW() 
                    ORDER BY created_at DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['remaining_time'] : 0;
            
        } catch (Exception $e) {
            error_log("OTP Expiry Time Error: " . $e->getMessage());
            return 0;
        }
    }
}
?>
