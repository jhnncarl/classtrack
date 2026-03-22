<?php
/**
 * ClassTrack Login Page
 * Handles user authentication
 */

require_once '../config/auth.php';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        // Redirect based on role
        $role = $result['role'];
        if ($role === 'Student') {
            header('Location: ../student/dashboard.php');
        } elseif ($role === 'Teacher') {
            header('Location: ../teacher/dashboard.php');
        } elseif ($role === 'Administrator') {
            header('Location: ../admin/dashboard.php');
        }
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $auth->getUserRole();
    if ($role === 'Student') {
        header('Location: ../student/dashboard.php');
    } elseif ($role === 'Teacher') {
        header('Location: ../teacher/dashboard.php');
    } elseif ($role === 'Administrator') {
        header('Location: ../admin/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Side: Login Form -->
        <div class="login-form-section">
            <div class="login-form-container">
                <h2>Welcome back</h2>
                <p class="subtitle">Please enter your credentials to access your account</p>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" class="login-form" novalidate>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email" 
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required
                        >
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="sign-in-btn">Sign In</button>
                    
                    <p class="account-text">
                        Don't have an account? <a href="#" class="signup-link">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Right Side: ClassTrack Logo -->
        <div class="logo-section">
            <div class="logo-container">
                <i class="bi bi-qr-code-scan logo-icon"></i>
                <h1 class="brand-name">ClassTrack</h1>
                <p class="brand-tagline">Subject-Based QR Attendance Monitoring System</p>
            </div>
        </div>
    </div>
    
    <!-- Registration Modal -->
    <div class="registration-modal" id="registrationModal">
        <div class="modal-overlay" id="modalOverlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Choose Account Type</h3>
                <button class="close-btn" id="closeModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-description">Select the type of account you want to register:</p>
                <div class="role-selection">
                    <button class="role-btn student-btn" id="studentBtn">
                        <svg class="role-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path d="M12 14v7"></path>
                        </svg>
                        <span>Student</span>
                    </button>
                    <button class="role-btn teacher-btn" id="teacherBtn">
                        <svg class="role-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 7l-7 5 7 5V7z"></path>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        <span>Teacher</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification Component -->
    <div id="toast-container" class="toast-container">
        <div id="login-toast" class="toast toast-error">
            <div class="toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="toast-content">
                <div class="toast-title">Error</div>
                <div class="toast-message">Invalid credentials</div>
            </div>
            <button class="toast-close" id="loginToastClose">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
    
    <script src="../assets/js/login.js"></script>
</body>
</html>
