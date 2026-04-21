<?php
// Email Worker - Actually sends emails in background
ignore_user_abort(true); // Continue running even if client disconnects
set_time_limit(0); // No time limit

require_once '../config/email_service.php';

// Get email data from POST
$emailData = json_decode(file_get_contents('php://input'), true);

if (!$emailData || !isset($emailData['type'], $emailData['email'])) {
    exit; // Invalid data
}

try {
    $emailService = new EmailService();
    
    if ($emailData['type'] === 'approval') {
        $result = $emailService->sendTeacherApprovalEmail(
            $emailData['email'],
            $emailData['firstName'],
            $emailData['lastName']
        );
        
        // Log the result
        logEmailResult($emailData['userId'], 'approval', $result['success'] ?? false);
        
    } elseif ($emailData['type'] === 'rejection') {
        $result = $emailService->sendTeacherRejectionEmail(
            $emailData['email'],
            $emailData['firstName'],
            $emailData['lastName']
        );
        
        // Log the result
        logEmailResult($emailData['userId'], 'rejection', $result['success'] ?? false);
    }
    
} catch (Exception $e) {
    // Log error but don't output anything since this is background process
    error_log("Email sending failed: " . $e->getMessage());
    logEmailResult($emailData['userId'] ?? 0, $emailData['type'], false, $e->getMessage());
}

function logEmailResult($userId, $type, $success, $error = null) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=classtrack_db", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create email_log table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS email_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT,
            email_type ENUM('approval', 'rejection') NOT NULL,
            success BOOLEAN DEFAULT FALSE,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (UserID, email_type, created_at)
        )");
        
        $stmt = $db->prepare("INSERT INTO email_log (UserID, email_type, success, error_message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $success, $error]);
        
    } catch (Exception $e) {
        error_log("Failed to log email result: " . $e->getMessage());
    }
}
?>
