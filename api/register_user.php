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
    require_once '../config/email_service.php';
    
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
        
        $firstName = trim($input['firstName']);
        $lastName = trim($input['lastName']);
        $email = trim($input['email']);
        
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
        $response['data'] = ['userId' => $userId, 'email' => $email, 'tempPassword' => $tempPassword];
        
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
