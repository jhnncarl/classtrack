<?php
require_once '../config/auth.php';
require_once '../config/otp.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $auth = new Auth();
    
    switch ($action) {
        case 'send_otp':
            $email = $_POST['email'] ?? '';
            $result = $auth->sendPasswordResetOTP($email);
            echo json_encode($result);
            exit;
            
        case 'verify_otp':
            $email = $_POST['email'] ?? '';
            $otp = $_POST['otp'] ?? '';
            $result = $auth->verifyPasswordResetOTP($email, $otp);
            echo json_encode($result);
            exit;
            
        case 'reset_password':
            $email = $_POST['email'] ?? '';
            $otp = $_POST['otp'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = $auth->resetPassword($email, $otp, $password);
            echo json_encode($result);
            exit;
            
        case 'check_otp_status':
            $email = $_POST['email'] ?? '';
            $result = $auth->checkUserOTPStatus($email);
            echo json_encode($result);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/forgot_password.css?v=2">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-form-container">
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your email to receive a password reset code</p>
            
            <form method="POST" action="#" class="forgot-password-form" novalidate>
                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email address" 
                        required
                    >
                </div>
                
                <!-- OTP Verification -->
                <div class="otp-section">
                    <label for="otp">OTP Code</label>
                    <div class="otp-input-group">
                        <input 
                            type="text" 
                            id="otp" 
                            name="otp" 
                            placeholder="Enter OTP code" 
                            maxlength="6"
                            required
                        >
                        <button type="button" class="send-otp-btn" id="sendOtpBtn">
                            Send
                        </button>
                    </div>
                </div>
                
                <!-- New Password -->
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-input-container">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your new password" 
                            required
                        >
                        <button type="button" class="password-toggle-btn" id="password-toggle">
                            <svg class="eye-icon" id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" id="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="reset-btn">Reset Password</button>
                
                <p class="login-text">
                    Remember your password? <a href="login.php">Sign In</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification Component -->
    <?php require_once '../assets/components/toast.php'; ?>
    
    <script src="../assets/js/forgot_password.js"></script>
</body>
</html>
