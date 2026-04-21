<?php
// Asynchronous Email Sender - Sends emails in background without blocking
require_once '../config/database.php';
require_once '../config/email_service.php';

// Function to send emails asynchronously using cURL
function sendAsyncEmail($emailData) {
    $postData = json_encode($emailData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/classtrack/background_jobs/email_sender_worker.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Very short timeout - don't wait for response
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    
    // Execute but don't wait for response
    curl_exec($ch);
    curl_close($ch);
}

// Function to queue bulk approval emails
function queueBulkApprovalEmails($teacherUsers) {
    foreach ($teacherUsers as $user) {
        $emailData = [
            'type' => 'approval',
            'email' => $user['Email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'userId' => $user['UserID']
        ];
        
        // Send asynchronously - won't block
        sendAsyncEmail($emailData);
    }
}

// Function to queue bulk rejection emails
function queueBulkRejectionEmails($teacherUsers) {
    foreach ($teacherUsers as $user) {
        $emailData = [
            'type' => 'rejection',
            'email' => $user['Email'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'userId' => $user['UserID']
        ];
        
        // Send asynchronously - won't block
        sendAsyncEmail($emailData);
    }
}

// For direct API calls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && isset($input['users'])) {
        if ($input['action'] === 'approval') {
            queueBulkApprovalEmails($input['users']);
            echo json_encode(['success' => true, 'message' => 'Emails queued for sending']);
        } elseif ($input['action'] === 'rejection') {
            queueBulkRejectionEmails($input['users']);
            echo json_encode(['success' => true, 'message' => 'Emails queued for sending']);
        }
    }
}
?>
