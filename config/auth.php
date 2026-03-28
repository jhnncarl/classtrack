<?php
/**
 * Authentication System for ClassTrack
 * Handles user login, registration, and session management
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';
require_once 'otp.php';
require_once 'email_service.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Register a new user
     * @param array $userData
     * @return array
     */
    public function register($userData) {
        try {
            // Validate email
            if ($this->getUserByEmail($userData['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Validate student number for student role
            if ($userData['role'] === 'Student') {
                if ($this->getStudentByNumber($userData['student_number'])) {
                    return ['success' => false, 'message' => 'Student number already exists'];
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (first_name, last_name, Email, PasswordHash, Role, AccountStatus) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                $userData['first_name'],
                $userData['last_name'],
                $userData['email'],
                $hashedPassword,
                $userData['role'],
                'Pending' // New accounts need approval (except students)
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create role-specific record
            if ($userData['role'] === 'Student') {
                $this->createStudentRecord($userId, $userData);
                // Auto-approve students
                $this->updateAccountStatus($userId, 'Active');
                
                // Generate and save QR code for student
                error_log("Starting QR code generation for user ID: $userId");
                $qrCodePath = $this->generateAndSaveStudentQRCode($userId);
                error_log("QR code path generated: " . ($qrCodePath ?? 'NULL'));
                
                $studentData = $this->getStudentData($userId);
                error_log("Student data retrieved: " . json_encode($studentData));
                
                // Get QR code data for modal display
                $qrCodeData = $this->getQRCodeImageData($qrCodePath);
                error_log("QR code data length: " . ($qrCodeData ? strlen($qrCodeData) : 'NULL'));
                
                return [
                    'success' => true, 
                    'user_id' => $userId,
                    'data' => $studentData,
                    'qr_code' => $qrCodeData
                ];
            } elseif ($userData['role'] === 'Teacher') {
                $this->createTeacherRecord($userId, $userData);
                return ['success' => true, 'user_id' => $userId];
            }
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            // Check if email exists and get user data
            $sql = "SELECT * FROM users WHERE Email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Account not found. Please check your email or register for a new account.'];
            }
            
            // Check account status
            if ($user['AccountStatus'] !== 'Active') {
                switch ($user['AccountStatus']) {
                    case 'Pending':
                        return ['success' => false, 'message' => 'Account is pending approval. Please contact your administrator.'];
                    case 'Suspended':
                        return ['success' => false, 'message' => 'Account has been suspended. Please contact your administrator.'];
                    case 'Inactive':
                        return ['success' => false, 'message' => 'Account is inactive. Please contact your administrator.'];
                    default:
                        return ['success' => false, 'message' => 'Account is not active. Please contact your administrator.'];
                }
            }
            
            // Verify password
            if (!password_verify($password, $user['PasswordHash'])) {
                return ['success' => false, 'message' => 'Invalid password. Please try again.'];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['user_profile_path'] = $user['ProfilePicture'] ?? null;
            $_SESSION['logged_in'] = true;
            
            // Get role-specific data
            if ($user['Role'] === 'Student') {
                $_SESSION['student_data'] = $this->getStudentData($user['UserID']);
            } elseif ($user['Role'] === 'Teacher') {
                $_SESSION['teacher_data'] = $this->getTeacherData($user['UserID']);
            }
            
            return ['success' => true, 'role' => $user['Role']];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again later.'];
        }
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user role
     * @return string|null
     */
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        header('Location: ../auth/login.php');
        exit;
    }
    
    /**
     * Require login to access page
     * @param string $requiredRole
     */
    public function requireLogin($requiredRole = null) {
        if (!$this->isLoggedIn()) {
            header('Location: ../auth/login.php');
            exit;
        }
        
        if ($requiredRole && $this->getUserRole() !== $requiredRole) {
            // Redirect based on current role
            $role = $this->getUserRole();
            if ($role === 'Student') {
                header('Location: ../student/dashboard.php');
            } elseif ($role === 'Teacher') {
                header('Location: ../teacher/dashboard.php');
            } elseif ($role === 'Administrator') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../auth/login.php');
            }
            exit;
        }
    }
    
    /**
     * Get user by email
     * @param string $email
     * @return array|null
     */
    private function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE Email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get student by student number
     * @param string $studentNumber
     * @return array|null
     */
    private function getStudentByNumber($studentNumber) {
        $sql = "SELECT * FROM students WHERE StudentNumber = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentNumber]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create student record
     * @param int $userId
     * @param array $userData
     */
    private function createStudentRecord($userId, $userData) {
        $sql = "INSERT INTO students (UserID, StudentNumber, Course, YearLevel) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $userData['student_number'] ?? '',
            $userData['course'] ?? '',
            $userData['year_level'] ?? 1
        ]);
    }
    
    /**
     * Create teacher record
     * @param int $userId
     * @param array $userData
     */
    private function createTeacherRecord($userId, $userData) {
        $sql = "INSERT INTO teachers (UserID, Department) 
                VALUES (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $userData['department'] ?? ''
        ]);
    }
    
    /**
     * Update account status
     * @param int $userId
     * @param string $status
     */
    private function updateAccountStatus($userId, $status) {
        $sql = "UPDATE users SET AccountStatus = ? WHERE UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $userId]);
    }
    
    /**
     * Get student data
     * @param int $userId
     * @return array|null
     */
    private function getStudentData($userId) {
        $sql = "SELECT s.*, u.first_name, u.last_name, u.Email,
                       CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM students s 
                JOIN users u ON s.UserID = u.UserID 
                WHERE s.UserID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Add year level text
            $yearLevels = [
                1 => '1st Year',
                2 => '2nd Year', 
                3 => '3rd Year',
                4 => '4th Year',
                5 => '5th Year'
            ];
            
            $result['year_level_text'] = $yearLevels[$result['YearLevel']] ?? '';
        }
        
        return $result;
    }
    
    /**
     * Get teacher data
     * @param int $userId
     * @return array|null
     */
    private function getTeacherData($userId) {
        $sql = "SELECT t.*, u.first_name, u.last_name, u.Email,
                       CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM teachers t 
                JOIN users u ON t.UserID = u.UserID 
                WHERE t.UserID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Send OTP for password reset
     * @param string $email
     * @return array
     */
    public function sendPasswordResetOTP($email) {
        try {
            // Check if user exists
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Email address not found'];
            }
            
            // Check if user account is active
            if ($user['AccountStatus'] !== 'Active') {
                return ['success' => false, 'message' => 'Account is not active. Please contact administrator.'];
            }
            
            // Generate and store OTP
            $otpHelper = new OTPHelper();
            $otp = $otpHelper->generateOTP();
            
            if (!$otpHelper->storeOTP($email, $otp)) {
                return ['success' => false, 'message' => 'Failed to generate OTP. Please try again.'];
            }
            
            // Send OTP email
            $emailService = new EmailService();
            $emailResult = $emailService->sendOTPEmail($email, $otp);
            
            if (!$emailResult['success']) {
                return ['success' => false, 'message' => $emailResult['message']];
            }
            
            return ['success' => true, 'message' => 'OTP sent to your email'];
            
        } catch (Exception $e) {
            error_log("Send OTP Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
        }
    }
    
    /**
     * Verify OTP for password reset
     * @param string $email
     * @param string $otp
     * @return array
     */
    public function verifyPasswordResetOTP($email, $otp) {
        try {
            $otpHelper = new OTPHelper();
            $result = $otpHelper->validateOTP($email, $otp);
            
            if ($result['valid']) {
                return ['success' => true, 'message' => 'OTP verified successfully'];
            } else {
                return ['success' => false, 'message' => $result['message']];
            }
            
        } catch (Exception $e) {
            error_log("Verify OTP Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'OTP verification failed'];
        }
    }
    
    /**
     * Reset password with OTP
     * @param string $email
     * @param string $otp
     * @param string $newPassword
     * @return array
     */
    public function resetPassword($email, $otp, $newPassword) {
        try {
            // First verify OTP
            $verifyResult = $this->verifyPasswordResetOTP($email, $otp);
            if (!$verifyResult['success']) {
                return $verifyResult;
            }
            
            // Get user by email
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET PasswordHash = ? WHERE UserID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashedPassword, $user['UserID']]);
            
            // Clean up OTP
            $otpHelper = new OTPHelper();
            $otpHelper->deleteOTP($email);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (Exception $e) {
            error_log("Reset Password Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed. Please try again.'];
        }
    }
    
    /**
     * Check if user has valid OTP
     * @param string $email
     * @return array
     */
    public function checkUserOTPStatus($email) {
        try {
            // Check if user exists
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Email address not found'];
            }
            
            $otpHelper = new OTPHelper();
            $hasValidOTP = $otpHelper->hasValidOTP($email);
            $expiryTime = $otpHelper->getOTPExpiryTime($email);
            
            return [
                'success' => true,
                'has_valid_otp' => $hasValidOTP,
                'expiry_time' => $expiryTime,
                'message' => $hasValidOTP ? 'Valid OTP exists' : 'No valid OTP found'
            ];
            
        } catch (Exception $e) {
            error_log("Check OTP Status Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to check OTP status'];
        }
    }
    
    /**
     * Get user by email (public method)
     * @param string $email
     * @return array|null
     */
    public function getUserByEmailPublic($email) {
        return $this->getUserByEmail($email);
    }
    
    /**
     * Generate and save QR code for student to file system and database
     * @param int $userId
     * @return string|null
     */
    private function generateAndSaveStudentQRCode($userId) {
        try {
            // Get student data
            $studentData = $this->getStudentData($userId);
            if (!$studentData) {
                return null;
            }
            
            // Create QR code data with student information
            $qrData = [
                'user_id' => $userId,
                'student_number' => $studentData['StudentNumber'],
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'full_name' => $studentData['full_name'],
                'email' => $studentData['Email'],
                'timestamp' => time()
            ];
            
            // Convert to JSON string
            $qrText = json_encode($qrData);
            
            // Generate QR code image using external APIs
            // Try multiple QR code generation methods
            $qrCodeData = null;
            
            // Method 1: Try QR Server API with Long QR format
            $qrText = json_encode($qrData);
            error_log("QR Text to encode: " . $qrText);
            
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x300&data=' . urlencode($qrText) . '&ecc=H';
            error_log("QR Server URL: " . $qrCodeUrl);
            
            $imageData = @file_get_contents($qrCodeUrl);
            
            if ($imageData !== false) {
                $qrCodeData = $imageData;
                error_log("QR Server API succeeded");
            } else {
                // Method 2: Try Google Charts API with Long QR format
                $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=200x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8&chld=H';
                error_log("Google Charts URL: " . $qrCodeUrl);
                
                $imageData = @file_get_contents($qrCodeUrl);
                
                if ($imageData !== false) {
                    $qrCodeData = $imageData;
                    error_log("Google Charts API succeeded");
                } else {
                    error_log("All QR APIs failed, using text placeholder");
                }
            }
            
            if ($qrCodeData === false || $qrCodeData === null) {
                // Fallback: create a simple text-based QR code placeholder
                $qrCodeData = $this->createTextQRPlaceholder($studentData['StudentNumber']);
            }
            
            // Create uploads directory if it doesn't exist
            $uploadsDir = __DIR__ . '/../uploads/qrcodes';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                error_log("Created uploads directory: $uploadsDir");
            }
            
            // Generate unique filename
            $filename = 'Student_' . $studentData['StudentNumber'] . '.png';
            $filepath = $uploadsDir . '/' . $filename;
            $relativePath = 'uploads/qrcodes/' . $filename;
            
            error_log("Saving QR code to: $filepath");
            error_log("Relative path for database: $relativePath");
            
            // Save QR code to file
            $saveResult = file_put_contents($filepath, $qrCodeData);
            
            if ($saveResult !== false) {
                error_log("QR code file saved successfully. File size: " . filesize($filepath) . " bytes");
                
                // Update database with QR code path
                $this->updateStudentQRCodePath($userId, $relativePath);
                
                return $relativePath;
            } else {
                error_log("Failed to save QR code file to: $filepath");
                return null;
            }
            
        } catch (Exception $e) {
            error_log("QR Code Generation and Save Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update student QR code path in database
     * @param int $userId
     * @param string $qrCodePath
     */
    private function updateStudentQRCodePath($userId, $qrCodePath) {
        try {
            error_log("Updating QR Code Path for User ID: $userId with path: $qrCodePath");
            
            $sql = "UPDATE students SET QRCodePath = ? WHERE UserID = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$qrCodePath, $userId]);
            
            if ($result) {
                error_log("QR Code Path updated successfully in database");
            } else {
                error_log("Failed to update QR Code Path in database");
            }
            
            // Verify the update
            $verifySql = "SELECT QRCodePath FROM students WHERE UserID = ?";
            $verifyStmt = $this->db->prepare($verifySql);
            $verifyStmt->execute([$userId]);
            $savedPath = $verifyStmt->fetchColumn();
            error_log("QR Code Path in database after update: " . ($savedPath ?? 'NULL'));
            
        } catch (Exception $e) {
            error_log("Update QR Code Path Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get QR code image data from file path
     * @param string $qrCodePath
     * @return string|null
     */
    private function getQRCodeImageData($qrCodePath) {
        try {
            if (empty($qrCodePath)) {
                return null;
            }
            
            $fullPath = __DIR__ . '/../' . $qrCodePath;
            if (file_exists($fullPath)) {
                return file_get_contents($fullPath);
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Get QR Code Image Data Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate QR code for student (legacy method for modal display)
     * @param int $userId
     * @return string|null
     */
    private function generateStudentQRCode($userId) {
        try {
            // Get student data
            $studentData = $this->getStudentData($userId);
            if (!$studentData) {
                return null;
            }
            
            // Create QR code data with student information
            $qrData = [
                'user_id' => $userId,
                'student_number' => $studentData['StudentNumber'],
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'full_name' => $studentData['full_name'],
                'email' => $studentData['Email'],
                'timestamp' => time()
            ];
            
            // Convert to JSON string
            $qrText = json_encode($qrData);
            
            // Generate QR code image using Google Charts API
            $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=200x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8';
            
            // Add debugging
            error_log("QR Code URL: " . $qrCodeUrl);
            
            // Download QR code image
            $imageData = @file_get_contents($qrCodeUrl);
            
            if ($imageData === false) {
                // Fallback: create a simple text-based QR code placeholder
                return $this->createTextQRPlaceholder($studentData['StudentNumber']);
            }
            
            return $imageData;
            
        } catch (Exception $e) {
            error_log("QR Code Generation Error: " . $e->getMessage());
            // Return fallback placeholder
            $studentData = $this->getStudentData($userId);
            return $this->createTextQRPlaceholder($studentData['StudentNumber'] ?? 'UNKNOWN');
        }
    }
    
    /**
     * Create a text-based QR code placeholder
     * @param string $studentNumber
     * @return string
     */
    private function createTextQRPlaceholder($studentNumber) {
        try {
            // Create a simple image with student number as fallback
            $image = imagecreate(200, 300);
            $bgColor = imagecolorallocate($image, 255, 255, 255);
            $textColor = imagecolorallocate($image, 0, 0, 0);
            
            // Add border
            imagerectangle($image, 0, 0, 199, 299, $textColor);
            
            // Add student number text using built-in font
            $text = "ID: " . $studentNumber;
            $fontSize = 4; // Built-in font size (1-5)
            $x = 100;
            $y = 150;
            
            // Center text using built-in font
            $textWidth = imagefontwidth(4);
            $textHeight = imagefontheight(4);
            $textX = $x - ($textWidth / 2);
            $textY = $y - ($textHeight / 2);
            
            imagestring($image, $fontSize, $textX, $textY, $text, $textColor);
            
            // Add "ClassTrack" text using built-in font
            imagestring($image, 3, 50, 200, 'ClassTrack Student', $textColor);
            
            // Capture image to string
            ob_start();
            imagepng($image);
            $imageData = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($image);
            return $imageData;
            
        } catch (Exception $e) {
            error_log("Text QR Placeholder Error: " . $e->getMessage());
            // Return minimal fallback
            return null;
        }
    }
}

// Create global auth instance
$auth = new Auth();
