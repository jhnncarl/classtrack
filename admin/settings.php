<?php
// Start session for admin authentication
session_start();

// Debug: Log session state at the very beginning
error_log("=== ADMIN SETTINGS.PHP DEBUG START ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Include database configuration
require_once '../config/database.php';

// Test database connection
try {
    $test_query = $db->query("SELECT 1 as test");
    $test_result = $test_query->fetch();
    error_log("Database connection test: " . ($test_result ? 'SUCCESS' : 'FAILED'));
} catch (Exception $e) {
    error_log("Database connection ERROR: " . $e->getMessage());
}

// Check if admin is logged in (redirect to login if not)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("ERROR: Admin not logged in - redirecting to admin login");
    header('Location: ../auth/admin/admin_login.php');
    exit();
}

// Handle form submission for admin profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("POST request detected with action: " . $_POST['action']);
    if ($_POST['action'] === 'update_admin_profile') {
        error_log("Calling updateAdminProfile function...");
        updateAdminProfile();
    } elseif ($_POST['action'] === 'get_profile_path') {
        // Return current profile path for navbar refresh
        header('Content-Type: application/json');
        if (isset($_SESSION['admin_profile_path'])) {
            echo json_encode([
                'success' => true,
                'profilePath' => $_SESSION['admin_profile_path']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'profilePath' => ''
            ]);
        }
        exit;
    } else {
        error_log("Unknown action: " . $_POST['action']);
    }
} else {
    error_log("No POST request or action missing");
}

// Function to update admin profile
function updateAdminProfile() {
    global $db;
    
    error_log("=== UPDATE ADMIN PROFILE FUNCTION STARTED ===");
    
    if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
        error_log("ERROR: Admin not logged in or invalid admin_id");
        echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
        exit;
    }
    $admin_id = $_SESSION['admin_id'];
    error_log("Admin ID: " . $admin_id);
    
    $username = trim($_POST['username'] ?? '');
    $current_password = $_POST['currentPassword'] ?? '';
    $new_password = $_POST['newPassword'] ?? '';
    
    error_log("Admin POST data - Username: '$username'");
    
    // Validate required fields
    if (empty($username)) {
        error_log("ERROR: Username is required");
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit;
    }
    
    try {
        error_log("Starting admin database transaction...");
        $db->beginTransaction();
        
        // Get admin's current data
        $stmt = $db->prepare("SELECT username, password, role FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $current_admin = $stmt->fetch();
        
        if (!$current_admin) {
            error_log("ERROR: Admin with ID $admin_id not found");
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            exit;
        }
        
        error_log("Current admin data - Username: '{$current_admin['username']}', Role: '{$current_admin['role']}'");
        
        // Check if username is being changed and if it already exists
        if ($username !== $current_admin['username']) {
            error_log("Username change detected, checking for duplicates...");
            $check_username = $db->prepare("SELECT admin_id FROM admins WHERE username = ? AND admin_id != ?");
            $check_username->execute([$username, $admin_id]);
            if ($check_username->fetch()) {
                error_log("ERROR: Username '$username' already exists for another admin");
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }
        }
        
        // Handle profile picture upload
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            error_log("Admin profile picture upload detected");
            
            $file = $_FILES['profilePicture'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
                exit;
            }
            
            if ($file['size'] > $max_size) {
                echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
                exit;
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'admin_profile_' . $admin_id . '_' . time() . '.' . $extension;
            $upload_path = '../uploads/profiles/' . $filename;
            
            if (!is_dir('../uploads/profiles/')) {
                mkdir('../uploads/profiles/', 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                error_log("Admin profile picture uploaded: " . $upload_path);
                
                $profile_path = 'uploads/profiles/' . $filename;
                $update_profile = $db->prepare("UPDATE admins SET profile_pic = ? WHERE admin_id = ?");
                $result = $update_profile->execute([$profile_path, $admin_id]);
                
                if ($result) {
                    $_SESSION['admin_profile_path'] = $profile_path;
                    error_log("Admin profile picture updated in database: " . $profile_path);
                }
            }
        }
        
        // Update admin username
        if ($username !== $current_admin['username']) {
            $update_admin = $db->prepare("UPDATE admins SET username = ? WHERE admin_id = ?");
            $result = $update_admin->execute([$username, $admin_id]);
            error_log("Admin username update result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                $_SESSION['admin_username'] = $username;
            }
        }
        
        // Handle password change if provided
        if (!empty($new_password)) {
            if (empty($current_password)) {
                echo json_encode(['success' => false, 'message' => 'Current password is required to change password']);
                exit;
            }
            
            if (!password_verify($current_password, $current_admin['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
                exit;
            }
            
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = $db->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
            $result = $update_password->execute([$new_password_hash, $admin_id]);
            error_log("Admin password update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        $db->commit();
        error_log("Admin transaction committed successfully");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Admin profile updated successfully',
            'updatedData' => [
                'username' => $username
            ]
        ]);
        
    } catch(PDOException $e) {
        $db->rollback();
        error_log("ADMIN DATABASE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
    
    exit;
}

// Get admin information from session
$admin_username = '';
$admin_role = '';
$admin_id = '';
$profile_picture = '';

// Admin session handling
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Administrator';
$admin_id = $_SESSION['admin_id'] ?? '';

// Fetch actual admin data from database
if (isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id'])) {
    try {
        $stmt = $db->prepare("SELECT username, role, profile_pic FROM admins WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin_data = $stmt->fetch();
        
        if ($admin_data) {
            $admin_username = $admin_data['username'];
            $admin_role = $admin_data['role'];
            $profile_picture = $admin_data['profile_pic'] ?? '';
            
            // Update session with fresh data
            $_SESSION['admin_username'] = $admin_data['username'];
            $_SESSION['admin_role'] = $admin_data['role'];
            $_SESSION['admin_profile_path'] = $profile_picture;
            
            error_log("ADMIN SETTINGS PAGE - Admin data retrieved: Username='$admin_username', Role='$admin_role'");
        }
    } catch(PDOException $e) {
        error_log("Error fetching admin data: " . $e->getMessage());
        $admin_username = 'Admin';
        $admin_role = 'Administrator';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - ClassTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/settings.css?v=2">
    <link rel="stylesheet" href="../assets/css/toast.css">
    
    <!-- Inline script to prevent sidebar flicker -->
    <script>
    (function() {
        // Prevent sidebar flicker by setting state immediately
        var savedState = localStorage.getItem('sidebarState');
        var isDesktop = window.innerWidth > 768;
        
        if (savedState === 'expanded' && isDesktop) {
            // Set CSS variables immediately
            document.documentElement.style.setProperty('--sidebar-width', '250px');
            document.documentElement.style.setProperty('--main-content-margin', '250px');
        } else {
            document.documentElement.style.setProperty('--sidebar-width', '70px');
            document.documentElement.style.setProperty('--main-content-margin', isDesktop ? '70px' : '0px');
        }
        
        // Also set inline styles as backup for immediate rendering
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.querySelector('.main-content');
        if (sidebar && mainContent) {
            if (savedState === 'expanded' && isDesktop) {
                sidebar.style.width = '250px';
                mainContent.style.marginLeft = '250px';
            } else {
                sidebar.style.width = '70px';
                mainContent.style.marginLeft = isDesktop ? '70px' : '0px';
            }
        }
    })();
    </script>
</head>
<body class="settings-body">
    <!-- Include Navbar -->
    <?php include '../assets/components/navbar.php'; ?>
    
    <!-- Include Sidebar -->
    <?php include '../assets/components/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <div class="nav-tabs-custom">
                    <button class="nav-tab active" data-tab="profile">
                        <i class="bi bi-person me-2"></i>Profile
                    </button>
                    <button class="nav-tab" data-tab="preferences">
                        <i class="bi bi-gear me-2"></i>Preferences
                    </button>
                    <button class="nav-tab" data-tab="security">
                        <i class="bi bi-shield me-2"></i>Security
                    </button>
                </div>
            </div>

            <!-- Profile Section -->
            <div id="profile-tab" class="tab-content active">
                <div class="settings-container">
                    <!-- Profile Icon Section -->
                    <div class="profile-icon-section">
                        <div class="profile-avatar-container">
                            <div class="profile-avatar">
                                <img id="profileImagePreview" src="<?php echo !empty($profile_picture) ? '/classtrack/' . ltrim($profile_picture, '/') : ''; ?>" alt="Profile" style="display: <?php echo !empty($profile_picture) ? 'block' : 'none'; ?>; width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <i class="bi bi-person-circle" id="defaultAvatarIcon" style="display: <?php echo !empty($profile_picture) ? 'none' : 'block'; ?>;"></i>
                                <div class="avatar-overlay" onclick="changeProfilePicture()">
                                    <i class="bi bi-camera"></i>
                                </div>
                            </div>
                            <div class="profile-avatar-info">
                                <h3 class="profile-name"><?php echo htmlspecialchars($admin_username); ?></h3>
                                <p class="profile-role"><?php echo htmlspecialchars(ucfirst($admin_role)); ?></p>
                                <button type="button" class="btn-change-avatar" onclick="changeProfilePicture()">
                                    <i class="bi bi-camera me-1"></i>Change Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Information Fields -->
                    <form id="adminSettingsForm" method="POST" enctype="multipart/form-data">
                    <div class="profile-info-section">
                        <h4 class="section-title">Admin Information</h4>
                        
                        <!-- Admin Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin_username); ?>" placeholder="Enter username">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($admin_role)); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Update Password Section -->
                    <div class="password-section">
                        <h4 class="section-title">Security</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
                                    <button class="btn-toggle-password" type="button" onclick="togglePasswordVisibility('currentPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
                                    <button class="btn-toggle-password" type="button" onclick="togglePasswordVisibility('newPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                                    <button class="btn-toggle-password" type="button" onclick="togglePasswordVisibility('confirmPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="saveInfoBtn" onclick="saveAdminInfo()" disabled>
                            Save Info
                        </button>
                    </div>
                    </form>
                </div>
            </div>

            <!-- Preferences Tab (Placeholder) -->
            <div id="preferences-tab" class="tab-content">
                <div class="settings-container">
                    <div class="text-center py-5">
                        <i class="bi bi-gear text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">Preferences Coming Soon</h4>
                        <p class="text-muted">Customize your admin experience with personalized settings</p>
                    </div>
                </div>
            </div>

            <!-- Security Tab (Placeholder) -->
            <div id="security-tab" class="tab-content">
                <div class="settings-container">
                    <div class="text-center py-5">
                        <i class="bi bi-shield text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">Security Settings Coming Soon</h4>
                        <p class="text-muted">Advanced security options and account protection</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>
    <!-- Admin Settings JavaScript -->
    <script src="../assets/js/admin_settings.js?v=1"></script>
    
    <!-- Hidden File Input for Profile Photo -->
    <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;" onchange="handleProfilePhotoSelect(this)">
</body>
</html>
