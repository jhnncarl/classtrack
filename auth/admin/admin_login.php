<?php
/**
 * ClassTrack Admin Login Page
 * Handles administrator authentication
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Query admins table
        $sql = "SELECT * FROM admins WHERE username = ? AND status = 'active'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $error_message = 'Invalid username or account is inactive.';
        } elseif (!password_verify($password, $admin['password'])) {
            $error_message = 'Invalid password.';
        } else {
            // Set admin session
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['logged_in'] = true;
            
            header('Location: ../../admin/dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        $error_message = 'Login failed. Please try again later.';
    }
}

// Redirect if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Admin Login</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin_login.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
</head>
<body>
    <div class="admin-login-container">
        <div class="login-card">
            <!-- Header Section -->
            <div class="login-header">
                <div class="logo-section">
                    <i class="bi bi-shield-lock admin-logo"></i>
                    <h1 class="admin-title">Admin Portal</h1>
                    <p class="admin-subtitle">ClassTrack Subject-Based QR Attendance Monitoring System</p>
                </div>
            </div>
            
            <!-- Login Form -->
            <div class="login-body">
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-error" style="display: none;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="admin_login.php" class="admin-login-form" novalidate>
                    <div class="form-group">
                        <label for="username">
                            <i class="bi bi-person input-icon"></i>
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="bi bi-lock input-icon"></i>
                            Password
                        </label>
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
                    </div>
                    
                    <button type="submit" class="admin-sign-in-btn" id="adminSignInBtn">
                        <i class="bi bi-shield-check"></i>
                        Sign In as Administrator
                    </button>
                    
                    <div class="back-to-login">
                        <a href="../login.php" class="back-link">
                            <i class="bi bi-arrow-left"></i>
                            Back to User Login
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <div class="security-notice">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>Secure administrator access only</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification Component -->
    <?php require_once '../../assets/components/toast.php'; ?>
    
    <script src="../../assets/js/admin_login.js?v=4"></script>
</body>
</html>
