<?php
// Suppress error output to ensure clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buffer output to catch any accidental output
ob_start();

try {
    // Include database configuration and email service
    require_once '../config/database.php';
    require_once '../config/auth.php';
    require_once '../config/email_service.php';
    require_once '../vendor/autoload.php';

    // QR Code Generation Function (matching auth.php)
    function generateStudentQRCode($studentNumber, $firstName, $lastName, $email, $course = '', $yearLevel = '') {
        try {
            // Create QR code data as JSON (matching auth.php format)
            $qrData = [
                'student_number' => $studentNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'course' => $course,
                'year' => $yearLevel,
                'type' => 'student',
                'timestamp' => time()
            ];
            
            $qrText = json_encode($qrData);
            error_log("QR Text to encode: " . $qrText);
            
            // Generate QR code image using external APIs (matching auth.php)
            $qrCodeData = null;
            
            // Method 1: Try QR Server API with Long QR format (200x300)
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x300&data=' . urlencode($qrText) . '&ecc=H';
            error_log("QR Server URL: " . $qrCodeUrl);
            
            $imageData = @file_get_contents($qrCodeUrl);
            
            if ($imageData !== false) {
                $qrCodeData = $imageData;
                error_log("QR Server API succeeded");
            } else {
                // Method 2: Try Google Charts API with Long QR format (200x300)
                $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=200x300&cht=qr&chl=' . urlencode($qrText) . '&choe=UTF-8&chld=H';
                error_log("Google Charts URL: " . $qrCodeUrl);
                
                $imageData = @file_get_contents($qrCodeUrl);
                
                if ($imageData !== false) {
                    $qrCodeData = $imageData;
                    error_log("Google Charts API succeeded");
                } else {
                    error_log("All QR APIs failed, using placeholder");
                }
            }
            
            if ($qrCodeData === false || $qrCodeData === null) {
                // Fallback: create a simple text-based QR code placeholder
                $qrCodeData = createTextQRPlaceholder($studentNumber);
            }
            
            // Create filename matching existing format
            $filename = 'Student_' . $studentNumber . '.png';
            $filepath = '../uploads/qrcodes/' . $filename;
            
            // Check if uploads directory exists and is writable
            $uploadsDir = '../uploads/qrcodes/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                error_log("Created uploads directory: " . $uploadsDir);
            }
            
            if (!is_writable($uploadsDir)) {
                error_log("Uploads directory is not writable: " . $uploadsDir);
                return null;
            }
            
            // Save QR code to file
            $saveResult = file_put_contents($filepath, $qrCodeData);
            
            if ($saveResult !== false) {
                error_log("QR code file saved successfully. File size: " . filesize($filepath) . " bytes");
                return 'uploads/qrcodes/' . $filename;
            } else {
                error_log("Failed to save QR code file to: " . $filepath);
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Exception in QR code generation: " . $e->getMessage());
            return null;
        }
    }

    // Create a text-based QR code placeholder (matching auth.php)
    function createTextQRPlaceholder($studentNumber) {
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
            $textWidth = imagefontwidth($fontSize) * strlen($text);
            $x = (200 - $textWidth) / 2;
            
            imagestring($image, $fontSize, $x, $y, $text, $textColor);
            
            // Add "ClassTrack" text
            $systemText = "ClassTrack";
            $textWidth = imagefontwidth($fontSize) * strlen($systemText);
            $x = (200 - $textWidth) / 2;
            $y = 50;
            imagestring($image, $fontSize, $x, $y, $systemText, $textColor);
            
            // Capture image data
            ob_start();
            imagepng($image);
            $imageData = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            
            return $imageData;
            
        } catch (Exception $e) {
            error_log("Exception in text QR placeholder: " . $e->getMessage());
            return null;
        }
    }

    // Placeholder QR Code Function (fallback)
    function createPlaceholderQRCode($studentNumber) {
        try {
            // Create a rectangular QR code image using GD library
            $width = 300;
            $height = 300; 
            $image = imagecreatetruecolor($width, $height);
            
            // Colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray = imagecolorallocate($image, 200, 200, 200);
            
            // Fill white background
            imagefill($image, 0, 0, $white);
            
            // Add border
            imagerectangle($image, 0, 0, $width - 1, $height - 1, $black);
            
            // Create a simple QR-like pattern
            $moduleSize = 5;
            $qrWidth = 180; // QR code area width
            $qrHeight = 180; // QR code area height
            $qrX = ($width - $qrWidth) / 2;
            $qrY = 20;
            
            // Add position markers (corners) for QR-like appearance
            for ($i = 0; $i < 7; $i++) {
                for ($j = 0; $j < 7; $j++) {
                    if (($i == 0 || $i == 6 || $j == 0 || $j == 6) || ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4)) {
                        imagefilledrectangle($image, $qrX + $i * $moduleSize, $qrY + $j * $moduleSize, 
                                            $qrX + ($i + 1) * $moduleSize - 1, $qrY + ($j + 1) * $moduleSize - 1, $black);
                    }
                }
            }
            
            // Add some random pattern in the middle
            for ($i = 9; $i < 36; $i++) {
                for ($j = 9; $j < 36; $j++) {
                    if (rand(0, 1) == 1) {
                        imagefilledrectangle($image, $qrX + $i * $moduleSize, $qrY + $j * $moduleSize, 
                                            $qrX + ($i + 1) * $moduleSize - 1, $qrY + ($j + 1) * $moduleSize - 1, $black);
                    }
                }
            }
            
            // Add student information section below QR code
            $infoY = $qrY + $qrHeight + 20;
            
            // Add student number
            $textColor = imagecolorallocate($image, 0, 0, 0);
            $fontSize = 3;
            $studentText = "Student ID: " . $studentNumber;
            $textWidth = imagefontwidth($fontSize) * strlen($studentText);
            $x = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $x, $infoY, $studentText, $textColor);
            
            // Add "ClassTrack" text
            $infoY += 20;
            $systemText = "ClassTrack System";
            $textWidth = imagefontwidth($fontSize) * strlen($systemText);
            $x = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $x, $infoY, $systemText, $gray);
            
            // Add date
            $infoY += 15;
            $dateText = date("Y-m-d");
            $textWidth = imagefontwidth($fontSize) * strlen($dateText);
            $x = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $x, $infoY, $dateText, $gray);
            
            // Add "Scan for Attendance" text
            $infoY += 20;
            $scanText = "Scan for Attendance";
            $textWidth = imagefontwidth($fontSize) * strlen($scanText);
            $x = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $x, $infoY, $scanText, $black);
            
            // Create filename matching existing format
            $filename = 'Student_' . $studentNumber . '.png';
            $filepath = '../uploads/qrcodes/' . $filename;
            
            // Check if uploads directory exists and is writable
            $uploadsDir = '../uploads/qrcodes/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                error_log("Created uploads directory: " . $uploadsDir);
            }
            
            if (!is_writable($uploadsDir)) {
                error_log("Uploads directory is not writable: " . $uploadsDir);
                imagedestroy($image);
                return null;
            }
            
            // Save image
            $result = imagepng($image, $filepath);
            imagedestroy($image);
            
            if ($result) {
                error_log("Placeholder QR code saved: " . $filepath);
                return 'uploads/qrcodes/' . $filename;
            } else {
                error_log("Failed to save placeholder QR code");
                return null;
            }
        } catch (Exception $e) {
            error_log("Exception in placeholder QR code creation: " . $e->getMessage());
            return null;
        }
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Initialize response
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate account type
    if (!isset($input['accountType']) || empty($input['accountType'])) {
        throw new Exception('Account type is required');
    }
    
    $accountType = $input['accountType'];
    
    // Use existing database connection
    global $db;
    
    // Start transaction
    $db->beginTransaction();
    
    if ($accountType === 'Student' || $accountType === 'Teacher') {
        // Validate student/teacher fields
        $requiredFields = ['firstName', 'lastName', 'email'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }
        
        // Additional validation for students
        if ($accountType === 'Student') {
            if (!isset($input['studentNumber']) || empty(trim($input['studentNumber']))) {
                throw new Exception('Student number is required');
            }
        }
        
        $firstName = trim($input['firstName']);
        $lastName = trim($input['lastName']);
        $email = trim($input['email']);
        $studentNumber = isset($input['studentNumber']) ? trim($input['studentNumber']) : '';
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email exists
        $checkEmail = $db->prepare("SELECT UserID FROM users WHERE Email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Generate password
        $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 12);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Insert user
        $insertUser = $db->prepare("
            INSERT INTO users (first_name, last_name, Email, PasswordHash, Role, AccountStatus, CreatedAt) 
            VALUES (?, ?, ?, ?, ?, 'Active', NOW())
        ");
        $insertUser->execute([$firstName, $lastName, $email, $hashedPassword, $accountType]);
        
        $userId = $db->lastInsertId();
        
        // Create role-specific record
        if ($accountType === 'Student') {
            $insertStudent = $db->prepare("
                INSERT INTO students (UserID, StudentNumber, Course, YearLevel) 
                VALUES (?, ?, ?, ?)
            ");
            $insertStudent->execute([$userId, $studentNumber, '', 0]);
            
            // Generate QR code for student
            error_log("Generating QR code for student: $studentNumber");
            $qrCodePath = generateStudentQRCode($studentNumber, $firstName, $lastName, $email);
            error_log("QR code generated: " . ($qrCodePath ? $qrCodePath : 'FAILED'));
            
            if ($qrCodePath) {
                $updateStudent = $db->prepare("
                    UPDATE students SET QRCodePath = ? WHERE UserID = ?
                ");
                $updateStudent->execute([$qrCodePath, $userId]);
                error_log("QR code saved to database");
            } else {
                error_log("Failed to generate QR code - creating placeholder");
                // Create a placeholder QR code using base64
                $qrCodePath = createPlaceholderQRCode($studentNumber);
                if ($qrCodePath) {
                    $updateStudent = $db->prepare("
                        UPDATE students SET QRCodePath = ? WHERE UserID = ?
                    ");
                    $updateStudent->execute([$qrCodePath, $userId]);
                    error_log("Placeholder QR code saved to database");
                }
            }
        } elseif ($accountType === 'Teacher') {
            $insertTeacher = $db->prepare("
                INSERT INTO teachers (UserID, Department) 
                VALUES (?, ?)
            ");
            $insertTeacher->execute([$userId, null]);
        }
        
        // Send email using EmailService
        try {
            $emailService = new EmailService();
            $subject = 'Your ClassTrack Account Has Been Created';
            
            $emailBody = "
            <html>
            <head>
                <title>ClassTrack Account Created</title>
            </head>
            <body>
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: #2c3e50; color: white; padding: 20px; text-align: center;'>
                        <h1>ClassTrack</h1>
                        <p>Your Account Has Been Created</p>
                    </div>
                    
                    <div style='padding: 20px; background: #f9f9f9;'>
                        <p>Dear $firstName,</p>
                        
                        <p>Your $accountType account has been successfully created in the ClassTrack system.</p>
                        
                        <div style='background: white; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;'>
                            <h3>Your Login Credentials:</h3>
                            <p><strong>Email:</strong> $email</p>
                            <p><strong>Temporary Password:</strong> $tempPassword</p>
                        </div>
                        
                        <div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                            <p><strong>⚠️ Important Security Notice:</strong></p>
                            <ul>
                                <li>This is a temporary password</li>
                                <li>You <strong>must change your password</strong> upon first login</li>
                                <li>Keep your credentials secure and do not share them</li>
                            </ul>
                        </div>
                        
                        <p>To access your account, please visit the ClassTrack login page and use the credentials above.</p>
                        
                        <p>Best regards,<br>ClassTrack Administration</p>
                    </div>
                    
                    <div style='background: #34495e; color: white; padding: 10px; text-align: center; font-size: 12px;'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $emailService->sendAccountCreationEmail($email, $firstName, $subject, $emailBody);
        } catch (Exception $e) {
            error_log("Failed to send email to: $email - Error: " . $e->getMessage());
        }
        
        $response['success'] = true;
        $response['message'] = ucfirst($accountType) . ' account created successfully';
        $responseData = ['userId' => $userId, 'email' => $email, 'tempPassword' => $tempPassword];
        
        // Add QR code information for students
        if ($accountType === 'Student' && isset($qrCodePath)) {
            $responseData['qrCodePath'] = $qrCodePath;
            $responseData['studentNumber'] = $studentNumber;
        }
        
        $response['data'] = $responseData;
        
    } elseif ($accountType === 'Administrator') {
        // Validate admin fields
        if (!isset($input['username']) || empty(trim($input['username']))) {
            throw new Exception('Username is required');
        }
        
        $username = trim($input['username']);
        
        if (strlen($username) < 3) {
            throw new Exception('Username must be at least 3 characters long');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Username can only contain letters, numbers, and underscores');
        }
        
        // Check if username exists
        $checkUsername = $db->prepare("SELECT admin_id FROM admins WHERE username = ?");
        $checkUsername->execute([$username]);
        if ($checkUsername->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Generate password
        $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 12);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Insert admin
        $insertAdmin = $db->prepare("
            INSERT INTO admins (username, password, role, status, created_at) 
            VALUES (?, ?, 'admin', 'active', NOW())
        ");
        $insertAdmin->execute([$username, $hashedPassword]);
        
        $adminId = $db->lastInsertId();
        
        error_log("Admin account created: $username - Password: $tempPassword");
        
        $response['success'] = true;
        $response['message'] = 'Administrator account created successfully';
        $response['data'] = ['adminId' => $adminId, 'username' => $username, 'tempPassword' => $tempPassword];
        
    } else {
        throw new Exception('Invalid account type');
    }
    
    // Commit transaction
    $db->commit();
    
} catch (Exception $e) {
    // Rollback
    if (isset($db)) {
        $db->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Registration Error: " . $e->getMessage());
    
} catch (Error $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = 'A system error occurred. Please try again.';
    error_log("Registration Fatal Error: " . $e->getMessage());
    
} catch (Throwable $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = 'An unexpected error occurred. Please try again.';
    error_log("Registration Throwable Error: " . $e->getMessage());
}

// Clean any buffered output
ob_clean();

// Return JSON response
echo json_encode($response);
exit;
?>
