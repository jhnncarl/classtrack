<?php
// Start session for user authentication
session_start();

// Debug: Log session state at the very beginning
error_log("=== SETTINGS.PHP DEBUG START ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Include database configuration
require_once '../config/database.php';
require_once '../config/permissions.php';

// Test database connection
try {
    $test_query = $db->query("SELECT 1 as test");
    $test_result = $test_query->fetch();
    error_log("Database connection test: " . ($test_result ? 'SUCCESS' : 'FAILED'));
} catch (Exception $e) {
    error_log("Database connection ERROR: " . $e->getMessage());
}

// Check if user is logged in (redirect to login if not)
if (!isset($_SESSION['user_id'])) {
    error_log("ERROR: User not logged in - redirecting to login");
    header('Location: ../auth/login.php');
    exit();
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    error_log("POST request detected with action: " . $_POST['action']);
    if ($_POST['action'] === 'update_profile') {
        error_log("Calling updateUserProfile function...");
        updateUserProfile();
    } elseif ($_POST['action'] === 'get_profile_path') {
        // Return current profile path for navbar refresh
        header('Content-Type: application/json');
        if (isset($_SESSION['user_profile_path'])) {
            echo json_encode([
                'success' => true,
                'profilePath' => $_SESSION['user_profile_path']
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

// Function to update user profile
function updateUserProfile() {
    global $db;
    
    // Debug: Log function start
    error_log("=== UPDATE USER PROFILE FUNCTION STARTED ===");
    
    // Regular user session handling
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        error_log("ERROR: User not logged in or invalid user_id");
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    $user_id = $_SESSION['user_id'];
    error_log("User ID: " . $user_id);
    
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['yearLevel'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $current_password = $_POST['currentPassword'] ?? '';
    $new_password = $_POST['newPassword'] ?? '';
    
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Parsed data - First Name: '$first_name', Last Name: '$last_name', Email: '$email'");
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        error_log("ERROR: Required fields missing");
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("ERROR: Invalid email format: '$email'");
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    try {
        error_log("Starting database transaction...");
        // Start transaction
        $db->beginTransaction();
        
        // Get user's current data and role
        $stmt = $db->prepare("SELECT Role, Email FROM users WHERE UserID = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch();
        
        if (!$current_user) {
            error_log("ERROR: User with ID $user_id not found");
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        error_log("Current user data - Role: '{$current_user['Role']}', Email: '{$current_user['Email']}'");
        
        // Check if email is being changed and if it already exists
        if ($email !== $current_user['Email']) {
            error_log("Email change detected, checking for duplicates...");
            $check_email = $db->prepare("SELECT UserID FROM users WHERE Email = ? AND UserID != ?");
            $check_email->execute([$email, $user_id]);
            if ($check_email->fetch()) {
                error_log("ERROR: Email '$email' already exists for another user");
                echo json_encode(['success' => false, 'message' => 'Email address already exists']);
                exit;
            }
        }
        
        // Update basic user information in users table
        error_log("Updating user - First Name: '$first_name', Last Name: '$last_name', Email: '$email', User ID: $user_id");
        
        // First, get current data to compare
        $current_data = $db->prepare("SELECT first_name, last_name, Email FROM users WHERE UserID = ?");
        $current_data->execute([$user_id]);
        $existing_data = $current_data->fetch();
        
        if ($existing_data) {
            error_log("Current data in database - First Name: '{$existing_data['first_name']}', Last Name: '{$existing_data['last_name']}', Email: '{$existing_data['Email']}'");
            
            // Check if data is actually different
            $first_name_changed = $existing_data['first_name'] !== $first_name;
            $last_name_changed = $existing_data['last_name'] !== $last_name;
            $email_changed = $existing_data['Email'] !== $email;
            
            error_log("Changes detected - First Name changed: " . ($first_name_changed ? 'YES' : 'NO') . ", Last Name changed: " . ($last_name_changed ? 'YES' : 'NO') . ", Email changed: " . ($email_changed ? 'YES' : 'NO'));
            
            if ($first_name_changed || $last_name_changed || $email_changed) {
                $update_users = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, Email = ? WHERE UserID = ?");
                $result = $update_users->execute([$first_name, $last_name, $email, $user_id]);
                
                error_log("Users table update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("Users table rows affected: " . $update_users->rowCount());
                
                if ($update_users->rowCount() === 0) {
                    error_log("WARNING: Update executed but 0 rows affected - this might indicate the data was already the same");
                }
            } else {
                error_log("INFO: No changes needed for users table - data is the same");
            }
        } else {
            error_log("ERROR: Could not retrieve current user data");
        }
        
        // Handle profile picture upload FIRST (before other validation)
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            error_log("Profile picture upload detected - processing separately");
            
            $file = $_FILES['profilePicture'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validate file type
            if (!in_array($file['type'], $allowed_types)) {
                error_log("ERROR: Invalid file type: " . $file['type']);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
                exit;
            }
            
            // Validate file size
            if ($file['size'] > $max_size) {
                error_log("ERROR: File too large: " . $file['size']);
                echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = '../uploads/profiles/' . $filename;
            
            // Create uploads directory if it doesn't exist
            if (!is_dir('../uploads/profiles/')) {
                mkdir('../uploads/profiles/', 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                error_log("Profile picture uploaded successfully: " . $upload_path);
                
                // Update database with new profile picture path
                $profile_path = 'uploads/profiles/' . $filename;
                $update_profile = $db->prepare("UPDATE users SET ProfilePicture = ? WHERE UserID = ?");
                $result = $update_profile->execute([$profile_path, $user_id]);
                
                if ($result) {
                    error_log("Database updated with profile picture: " . $profile_path);
                    
                    // Update session
                    $_SESSION['user_profile_path'] = $profile_path;
                    
                    // For standalone profile picture upload, skip other validations and return success
                    $is_profile_only_upload = !isset($_POST['firstName']) && !isset($_POST['lastName']) && !isset($_POST['email']);
                    
                    if ($is_profile_only_upload) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Profile picture updated successfully!',
                            'updatedData' => [
                                'profilePicture' => $profile_path
                            ]
                        ]);
                        exit;
                    }
                } else {
                    error_log("ERROR: Failed to update database with profile picture");
                    echo json_encode(['success' => false, 'message' => 'Failed to save profile picture to database']);
                    exit;
                }
            } else {
                error_log("ERROR: Failed to move uploaded file");
                echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture']);
                exit;
            }
        }
        
        // Handle password change if provided
        if (!empty($new_password)) {
            error_log("Password change requested");
            if (empty($current_password)) {
                error_log("ERROR: Current password required for password change");
                echo json_encode(['success' => false, 'message' => 'Current password is required to change password']);
                exit;
            }
            
            // Verify current password
            error_log("Verifying current password...");
            $stmt = $db->prepare("SELECT PasswordHash FROM users WHERE UserID = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch();
            
            if (!$user_data || !password_verify($current_password, $user_data['PasswordHash'])) {
                error_log("ERROR: Current password verification failed");
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Validate new password
            if (strlen($new_password) < 6) {
                error_log("ERROR: New password too short");
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
                exit;
            }
            
            // Update password
            error_log("Updating password...");
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = $db->prepare("UPDATE users SET PasswordHash = ? WHERE UserID = ?");
            $result = $update_password->execute([$new_password_hash, $user_id]);
            
            error_log("Password update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        } else {
            error_log("No password change requested");
        }
        
        // Update role-specific data
        if ($current_user['Role'] === 'Student') {
            error_log("Updating student-specific data...");
            
            // Check if student record exists
            $check_student = $db->prepare("SELECT StudentID FROM students WHERE UserID = ?");
            $check_student->execute([$user_id]);
            $student_record = $check_student->fetch();
            
            if ($student_record) {
                error_log("Student record found, updating Course and YearLevel");
                // Convert year level text to number for database
                $year_level_num = 1;
                if (strpos($year_level, '1st') !== false) $year_level_num = 1;
                elseif (strpos($year_level, '2nd') !== false) $year_level_num = 2;
                elseif (strpos($year_level, '3rd') !== false) $year_level_num = 3;
                elseif (strpos($year_level, '4th') !== false) $year_level_num = 4;
                elseif (strpos($year_level, '5th') !== false) $year_level_num = 5;
                
                error_log("Year level conversion: '$year_level' -> $year_level_num");
                
                $update_student = $db->prepare("UPDATE students SET Course = ?, YearLevel = ? WHERE UserID = ?");
                $result = $update_student->execute([$course, $year_level_num, $user_id]);
                error_log("Student table update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("Student table rows affected: " . $update_student->rowCount());
            } else {
                error_log("WARNING: No student record found for UserID $user_id");
            }
            
        } elseif ($current_user['Role'] === 'Teacher') {
            error_log("Updating teacher-specific data...");
            
            // Check if teacher record exists
            $check_teacher = $db->prepare("SELECT TeacherID FROM teachers WHERE UserID = ?");
            $check_teacher->execute([$user_id]);
            $teacher_record = $check_teacher->fetch();
            
            if ($teacher_record) {
                error_log("Teacher record found, updating Department");
                $update_teacher = $db->prepare("UPDATE teachers SET Department = ? WHERE UserID = ?");
                $result = $update_teacher->execute([$department, $user_id]);
                error_log("Teacher table update result: " . ($result ? 'SUCCESS' : 'FAILED'));
                error_log("Teacher table rows affected: " . $update_teacher->rowCount());
            } else {
                error_log("WARNING: No teacher record found for UserID $user_id");
            }
        }
        
        // Update session with new data
        error_log("Updating session data...");
        $_SESSION['user_first_name'] = $first_name;
        $_SESSION['user_last_name'] = $last_name;
        $_SESSION['user_email'] = $email;
        
        // Commit transaction
        $db->commit();
        error_log("Transaction committed successfully");
        
        // Fetch updated user data to return to frontend
        $updated_data = [
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'course' => $course,
            'yearLevel' => $year_level,
            'department' => $department
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully',
            'updatedData' => $updated_data
        ]);
        error_log("SUCCESS: Profile update completed");
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $db->rollback();
        error_log("DATABASE ERROR: " . $e->getMessage());
        error_log("ERROR TRACE: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
    }
    
    exit;
}


// Get user information from session
$user_role = '';
$user_name = '';
$user_id = '';
$user_email = '';
$user_fullname = '';
$department = '';
$course = '';
$year_level_num = '';
$profile_picture = '';

// Regular user session handling
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'student';
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Demo Student');
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ($user_role === 'student' ? 'STU001234' : 'TCH001234');

// Fetch actual user data from database
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT u.first_name, u.last_name, u.Email, u.Role, u.ProfilePicture,
                              CASE 
                                WHEN u.Role = 'Teacher' THEN t.Department 
                                WHEN u.Role = 'Student' THEN s.Course 
                                ELSE NULL 
                              END as department_or_course,
                              CASE 
                                WHEN u.Role = 'Student' THEN s.YearLevel
                                ELSE NULL 
                              END as year_level
                              FROM users u 
                              LEFT JOIN teachers t ON u.UserID = t.UserID 
                              LEFT JOIN students s ON u.UserID = s.UserID 
                              WHERE u.UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            $first_name = $user_data['first_name'] ?? '';
            $last_name = $user_data['last_name'] ?? '';
            $user_fullname = trim($first_name . ' ' . $last_name);
            $user_email = $user_data['Email'];
            $user_role = $user_data['Role'];
            $profile_picture = $user_data['ProfilePicture'] ?? '';
            $department = $user_data['department_or_course'] ?? '';
            $course = $user_role === 'Student' ? ($user_data['department_or_course'] ?? '') : '';
            $year_level_num = $user_data['year_level'] ?? '';
            
            // Debug: Log profile picture retrieval
            error_log("SETTINGS PAGE - Profile picture retrieved: " . ($profile_picture ?: 'NULL'));
            error_log("SETTINGS PAGE - Profile picture display logic - !empty(profile_picture): " . (!empty($profile_picture) ? 'TRUE' : 'FALSE'));
            
            // Update session with fresh data
            $_SESSION['user_first_name'] = $user_data['first_name'];
            $_SESSION['user_last_name'] = $user_data['last_name'];
            $_SESSION['user_role'] = $user_role;
            $_SESSION['user_profile_path'] = $profile_picture;
            
            error_log("SETTINGS PAGE - Session user_profile_path set to: " . ($_SESSION['user_profile_path'] ?: 'NULL'));
        }
    } catch(PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        // Use demo values if database fails
        $user_email = 'demo.student@classtrack.edu';
        $first_name = 'Demo';
        $last_name = 'Student';
        $user_fullname = $first_name . ' ' . $last_name;
    }
} else {
    // Demo values
    $user_email = 'demo.student@classtrack.edu';
    $first_name = 'Demo';
    $last_name = 'Student';
    $user_fullname = $first_name . ' ' . $last_name;
}

// Convert year level number to text for display
$year_level_text = '';
if (!empty($year_level_num)) {
    switch($year_level_num) {
        case 1: $year_level_text = '1st Year'; break;
        case 2: $year_level_text = '2nd Year'; break;
        case 3: $year_level_text = '3rd Year'; break;
        case 4: $year_level_text = '4th Year'; break;
        case 5: $year_level_text = '5th Year'; break;
        default: $year_level_text = $year_level_num . ' Year'; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Settings</title>
    
    <!-- Favicon - Settings Icon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%231a73e8' rx='6'/%3E%3Cg fill='white'%3E%3Cpath d='M16 8c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 14c-3.3 0-6-2.7-6-6s2.7-6 6-6 6 2.7 6 6-2.7 6-6 6z'/%3E%3Cpath d='M16 11c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3z'/%3E%3Cpath d='M16 14c-1.1 0-2 0.9-2 2s0.9 2 2 2 2-0.9 2-2-0.9-2-2-2z'/%3E%3C/g%3E%3C/svg%3E">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/settings.css?v=1">
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
                <?php
                // Check editProfile permission
                $canEditProfile = false;
                if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
                    try {
                        // Get user role directly from users table
                        $stmt = $db->prepare("SELECT Role FROM users WHERE UserID = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userData = $stmt->fetch();
                        
                        if ($userData) {
                            // Get permission from role_permissions table
                            $stmt = $db->prepare("SELECT editProfile FROM role_permissions WHERE role = ?");
                            $stmt->execute([$userData['Role']]);
                            $permissionData = $stmt->fetch();
                            
                            $canEditProfile = $permissionData && $permissionData['editProfile'] == 1;
                        }
                    } catch (Exception $e) {
                        error_log("Error checking user permission: " . $e->getMessage());
                        $canEditProfile = false;
                    }
                }
                
                // Debug: Log permission status
                error_log("User Edit Profile Permission Status: " . ($canEditProfile ? 'GRANTED' : 'DENIED'));
                error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
                ?>
                
                <!-- Disabled Overlay -->
                <?php if (!$canEditProfile): ?>
                <div class="profile-disabled-overlay">
                    <div class="profile-disabled-content">
                        <div class="profile-disabled-icon">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <div class="profile-disabled-message">
                            <h4>Access Restricted</h4>
                            <p>This feature is currently unavailable. Please enable the Edit Profile Information permission to proceed.</p>
                        </div>
                    </div>
                </div>
                <style>
                #profile-tab {
                    position: relative !important;
                    <?php echo !$canEditProfile ? 'opacity: 0.4 !important; pointer-events: none !important;' : ''; ?>
                }
                .profile-disabled-overlay {
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    background: rgba(220, 53, 69, 0.15) !important;
                    backdrop-filter: blur(5px) !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    z-index: 9999 !important;
                    border-radius: 8px !important;
                    border: 2px dashed #dc3545 !important;
                }
                .profile-disabled-content {
                    text-align: center !important;
                    padding: 40px 32px !important;
                    background: white !important;
                    border-radius: 12px !important;
                    box-shadow: 0 8px 32px rgba(220, 53, 69, 0.3) !important;
                    border: 2px solid #dc3545 !important;
                    max-width: 450px !important;
                    margin: 20px !important;
                    position: relative !important;
                    z-index: 10000 !important;
                }
                .profile-disabled-icon {
                    font-size: 64px !important;
                    color: #dc3545 !important;
                    margin-bottom: 20px !important;
                }
                .profile-disabled-message h4 {
                    margin: 0 0 12px 0 !important;
                    color: #dc3545 !important;
                    font-weight: 600 !important;
                    font-size: 20px !important;
                }
                .profile-disabled-message p {
                    margin: 0 !important;
                    color: #6c757d !important;
                    line-height: 1.6 !important;
                    font-size: 15px !important;
                }
                @media (max-width: 576px) {
                    .profile-disabled-content {
                        padding: 30px 24px !important;
                        margin: 15px !important;
                    }
                    .profile-disabled-icon {
                        font-size: 48px !important;
                        margin-bottom: 16px !important;
                    }
                    .profile-disabled-message h4 {
                        font-size: 18px !important;
                    }
                    .profile-disabled-message p {
                        font-size: 14px !important;
                    }
                }
                </style>
                <?php endif; ?>
                
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
                                <h3 class="profile-name"><?php echo htmlspecialchars($user_fullname); ?></h3>
                                <p class="profile-role"><?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                                <button type="button" class="btn-change-avatar" onclick="changeProfilePicture()">
                                    <i class="bi bi-camera me-1"></i>Change Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- User Information Fields -->
                    <form id="settingsForm" method="POST" enctype="multipart/form-data">
                    <div class="profile-info-section">
                        <h4 class="section-title">Personal Information</h4>
                        
                        <!-- Student/Teacher Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" placeholder="Enter first name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" placeholder="Enter last name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_email); ?>" placeholder="Enter email address">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user_role)); ?>" readonly>
                            </div>
                        </div>
                        
                        <?php if (strtolower($user_role) === 'student'): ?>
                        <!-- Student Specific Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <input type="text" class="form-control" id="course" value="<?php echo htmlspecialchars($course); ?>" placeholder="Enter course">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year Level</label>
                                <select class="form-control" id="yearLevel">
                                    <option value="1st Year" <?php echo ($year_level_text === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo ($year_level_text === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo ($year_level_text === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo ($year_level_text === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="5th Year" <?php echo ($year_level_text === '5th Year') ? 'selected' : ''; ?>>5th Year</option>
                                </select>
                            </div>
                        </div>
                        <?php elseif (strtolower($user_role) === 'teacher'): ?>
                        <!-- Teacher Specific Fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($department); ?>" placeholder="Enter department">
                            </div>
                        </div>
                        <?php endif; ?>
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
                        <button type="button" class="btn btn-primary" id="saveInfoBtn" onclick="saveInfo()" disabled>
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
                        <p class="text-muted">Customize your experience with personalized settings</p>
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
    <!-- Custom JavaScript -->
    <script src="../assets/js/settings.js?v=10"></script>
    
    <!-- Hidden File Input for Profile Photo -->
    <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;" onchange="handleProfilePhotoSelect(this)">
</body>
</html>
