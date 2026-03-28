<?php
require_once '../config/auth.php';

// Initialize variables
$success_message = '';
$error_message = '';
$userData = [];

// Debug: Check if there are any existing messages
error_log("Teacher Registration - Success: " . ($success_message ?? 'none') . ", Error: " . ($error_message ?? 'none'));

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'department' => $_POST['department'] ?? '',
        'role' => 'Teacher'
    ];
    
    // Validate input
    $errors = [];
    if (empty($userData['first_name'])) $errors[] = 'First name is required';
    if (empty($userData['last_name'])) $errors[] = 'Last name is required';
    if (empty($userData['email'])) $errors[] = 'Email is required';
    if (empty($userData['password'])) $errors[] = 'Password is required';
    if (empty($userData['department'])) $errors[] = 'Department is required';
    
    if (empty($errors)) {
        $auth = new Auth();
        $result = $auth->register($userData);
        
        if ($result['success']) {
            $success_message = 'Teacher account created successfully! Your account is pending approval.';
            // Clear form
            $userData = [];
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Teacher Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/teacher_registration.css?v=3">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-form-container">
            <h2>Create Teacher Account</h2>
            <p class="subtitle">Fill in your information to get started with ClassTrack</p>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" style="display: none;">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" style="display: none;">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="registration-form" novalidate>
                <!-- Two Column Layout -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input 
                            type="text" 
                            id="first-name" 
                            name="first_name" 
                            placeholder="Enter your first name" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input 
                            type="text" 
                            id="last-name" 
                            name="last_name" 
                            placeholder="Enter your last name" 
                            required
                        >
                    </div>
                </div>
                
                <div class="form-row">
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
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input 
                            type="text" 
                            id="department" 
                            name="department" 
                            placeholder="Enter your department (e.g., IT, CS, Engineering)" 
                            required
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Create a strong password" 
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
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="confirm-password" 
                                name="confirm_password" 
                                placeholder="Confirm your password" 
                                required
                            >
                            <button type="button" class="password-toggle-btn" id="confirm-password-toggle">
                                <svg class="eye-icon" id="confirm-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" id="confirm-eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="register-btn">Create Account</button>
                
                <p class="login-text">
                    Already have an account? <a href="../auth/login.php">Sign In</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification Component -->
    <?php require_once '../assets/components/toast.php'; ?>
    
    <!-- Approval Information Modal -->
    <div id="approvalModal" class="approval-modal">
        <div class="approval-modal-overlay" id="approvalModalOverlay"></div>
        <div class="approval-modal-content">
            <div class="approval-modal-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h3 class="approval-modal-title">Registration Submitted!</h3>
            <p class="approval-modal-message">
                Your teacher account has been successfully created and is now pending administrator approval. 
                You will be notified once your account is approved. This process typically takes 24 to 48 hours.
            </p>
            <button type="button" class="approval-modal-btn" id="approvalModalBtn">OK</button>
        </div>
    </div>
    
    <script src="../assets/js/register.js?v=9"></script>
</body>
</html>
