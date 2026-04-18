<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Start session
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin/admin_login.php');
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'getAllUsers':
            echo json_encode(getAllUsers($db));
            exit;
            
        case 'getTeachers':
            echo json_encode(getTeachers($db));
            exit;
            
        case 'getStudents':
            echo json_encode(getStudents($db));
            exit;
            
        case 'getPendingUsers':
            echo json_encode(getPendingUsers($db));
            exit;
            
        case 'approveUser':
            $userId = $_POST['userId'] ?? 0;
            echo json_encode(approveUser($db, $userId));
            exit;
            
        case 'rejectUser':
            $userId = $_POST['userId'] ?? 0;
            echo json_encode(rejectUser($db, $userId));
            exit;
            
        case 'deleteUser':
            $userId = $_POST['userId'] ?? 0;
            echo json_encode(deleteUser($db, $userId));
            exit;
            
        case 'createUser':
            $userData = $_POST['userData'] ?? [];
            echo json_encode(createUser($db, $userData));
            exit;
            
            }
}

// Functions to get user data
function getAllUsers($db) {
    $sql = "SELECT u.UserID, u.first_name, u.last_name, u.Email, u.Role, u.AccountStatus, u.CreatedAt, u.ProfilePicture,
            CASE 
                WHEN u.Role = 'Teacher' THEN t.Department
                WHEN u.Role = 'Student' THEN s.Course
                ELSE NULL
            END as AdditionalInfo,
            CASE 
                WHEN u.Role = 'Student' THEN s.StudentNumber
                ELSE NULL
            END as StudentNumber
            FROM users u
            LEFT JOIN teachers t ON u.UserID = t.UserID
            LEFT JOIN students s ON u.UserID = s.UserID
            ORDER BY u.CreatedAt DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTeachers($db) {
    $sql = "SELECT u.UserID, u.first_name, u.last_name, u.Email, u.AccountStatus, u.CreatedAt, u.ProfilePicture,
            t.Department, COUNT(sub.SubjectID) as ClassCount
            FROM users u
            INNER JOIN teachers t ON u.UserID = t.UserID
            LEFT JOIN subjects sub ON t.TeacherID = sub.TeacherID
            WHERE u.Role = 'Teacher'
            GROUP BY u.UserID, t.Department
            ORDER BY u.CreatedAt DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudents($db) {
    $sql = "SELECT DISTINCT u.UserID, u.first_name, u.last_name, u.Email, u.AccountStatus, u.CreatedAt, u.ProfilePicture,
            s.StudentNumber, s.Course, s.YearLevel, sub.SectionName
            FROM users u
            INNER JOIN students s ON u.UserID = s.UserID
            LEFT JOIN enrollments e ON s.StudentID = e.StudentID
            LEFT JOIN subjects sub ON e.SubjectID = sub.SubjectID
            WHERE u.Role = 'Student'
            ORDER BY u.CreatedAt DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingUsers($db) {
    $sql = "SELECT UserID, first_name, last_name, Email, Role, CreatedAt
            FROM users
            WHERE AccountStatus = 'Pending'
            ORDER BY CreatedAt DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// User management functions
function approveUser($db, $userId) {
    try {
        $stmt = $db->prepare("UPDATE users SET AccountStatus = 'Active' WHERE UserID = ?");
        $stmt->execute([$userId]);
        return ['success' => true, 'message' => 'User approved successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error approving user: ' . $e->getMessage()];
    }
}

function rejectUser($db, $userId) {
    try {
        $stmt = $db->prepare("UPDATE users SET AccountStatus = 'Rejected' WHERE UserID = ?");
        $stmt->execute([$userId]);
        return ['success' => true, 'message' => 'User rejected successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error rejecting user: ' . $e->getMessage()];
    }
}

function deleteUser($db, $userId) {
    try {
        $db->beginTransaction();
        
        // Delete from related tables first
        $stmt = $db->prepare("DELETE FROM students WHERE UserID = ?");
        $stmt->execute([$userId]);
        
        $stmt = $db->prepare("DELETE FROM teachers WHERE UserID = ?");
        $stmt->execute([$userId]);
        
        // Delete from users table
        $stmt = $db->prepare("DELETE FROM users WHERE UserID = ?");
        $stmt->execute([$userId]);
        
        $db->commit();
        return ['success' => true, 'message' => 'User deleted successfully'];
    } catch(PDOException $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()];
    }
}

function createUser($db, $userData) {
    try {
        $db->beginTransaction();
        
        // Insert into users table
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, Email, PasswordHash, Role, AccountStatus) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['role'],
            'Active'
        ]);
        
        $userId = $db->lastInsertId();
        
        // Insert into role-specific table
        if ($userData['role'] === 'Teacher') {
            $stmt = $db->prepare("INSERT INTO teachers (UserID, Department) VALUES (?, ?)");
            $stmt->execute([$userId, $userData['department'] ?? null]);
        } elseif ($userData['role'] === 'Student') {
            $stmt = $db->prepare("INSERT INTO students (UserID, StudentNumber, Course, YearLevel) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $userData['student_number'],
                $userData['course'],
                $userData['year_level']
            ]);
        }
        
        $db->commit();
        return ['success' => true, 'message' => 'User created successfully'];
    } catch(PDOException $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()];
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ClassTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/manage_users.css?v=8">
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
<body>
    <!-- Include Navbar Component -->
    <?php include '../assets/components/navbar.php'; ?>
    
    <!-- Include Sidebar Component -->
    <?php include '../assets/components/sidebar.php'; ?>
    
    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="manage-users-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-content">
                    <div class="page-text">
                        <h1 class="page-title">Manage Users</h1>
                        <p class="page-subtitle">Control user access and permissions</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" id="addUserBtn">
                            <i class="bi bi-person-plus me-2"></i>Add User
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- User Management Section -->
            <div class="users-section">
                <!-- Tabs Navigation -->
                <div class="tabs-container">
                    <ul class="nav nav-tabs" id="usersTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-users-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab">
                                <i class="bi bi-people me-2"></i>All Users
                                <span class="badge bg-secondary ms-2">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="teachers-tab" data-bs-toggle="tab" data-bs-target="#teachers" type="button" role="tab">
                                <i class="bi bi-mortarboard me-2"></i>Teachers
                                <span class="badge bg-primary ms-2">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">
                                <i class="bi bi-book me-2"></i>Students
                                <span class="badge bg-success ms-2">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                <i class="bi bi-clock me-2"></i>Pending Approvals
                                <span class="badge bg-warning ms-2">0</span>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Tab Content -->
                <div class="tab-content" id="usersTabsContent">
                    <!-- All Users Tab -->
                    <div class="tab-pane fade show active" id="all-users" role="tabpanel">
                        <div class="users-table-container">
                            <div class="table-header">
                                <div class="table-title">
                                    <h3>All Users</h3>
                                    <p class="text-muted">View and manage all system users</p>
                                </div>
                                <div class="table-actions">
                                    <div class="search-box">
                                        <input type="text" class="form-control" placeholder="Search users..." id="searchAllUsers">
                                    </div>
                                    <button class="btn btn-search btn-sm" id="searchAllUsersBtn">
                                        <i class="bi bi-search me-1"></i>Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Users Table -->
                            <div class="table-scroll-wrapper">
                                    <table class="table users-table" id="allUsersTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" class="form-check-input" id="selectAllAll">
                                            </th>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="allUsersTableBody">
                                        <!-- Empty State -->
                                        <tr class="empty-state">
                                            <td colspan="6">
                                                <div class="empty-state-content">
                                                    <i class="bi bi-people"></i>
                                                    <h4>No users found</h4>
                                                    <p>There are no users in the system yet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Teachers Tab -->
                    <div class="tab-pane fade" id="teachers" role="tabpanel">
                        <div class="users-table-container">
                            <div class="table-header">
                                <div class="table-title">
                                    <h3>Teachers</h3>
                                    <p class="text-muted">Manage teacher accounts and permissions</p>
                                </div>
                                <div class="table-actions">
                                    <div class="search-box">
                                        <input type="text" class="form-control" placeholder="Search teachers..." id="searchTeachers">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <button class="btn btn-search btn-sm" id="searchTeachersBtn">
                                        <i class="bi bi-search me-1"></i>Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Teachers Table -->
                            <div class="table-scroll-wrapper">
                                    <table class="table users-table" id="teachersTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" class="form-check-input" id="selectAllTeachers">
                                            </th>
                                            <th>Teacher</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Classes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="teachersTableBody">
                                        <!-- Empty State -->
                                        <tr class="empty-state">
                                            <td colspan="6">
                                                <div class="empty-state-content">
                                                    <i class="bi bi-mortarboard"></i>
                                                    <h4>No teachers found</h4>
                                                    <p>There are no teachers in the system yet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Students Tab -->
                    <div class="tab-pane fade" id="students" role="tabpanel">
                        <div class="users-table-container">
                            <div class="table-header">
                                <div class="table-title">
                                    <h3>Students</h3>
                                    <p class="text-muted">Manage student accounts and enrollments</p>
                                </div>
                                <div class="table-actions">
                                    <div class="search-box">
                                        <input type="text" class="form-control" placeholder="Search students..." id="searchStudents">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <button class="btn btn-search btn-sm" id="searchStudentsBtn">
                                        <i class="bi bi-search me-1"></i>Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Students Table -->
                            <div class="table-scroll-wrapper">
                                    <table class="table users-table" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" class="form-check-input" id="selectAllStudents">
                                            </th>
                                            <th>Student</th>
                                            <th>ID Number</th>
                                            <th>Section</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentsTableBody">
                                        <!-- Empty State -->
                                        <tr class="empty-state">
                                            <td colspan="6">
                                                <div class="empty-state-content">
                                                    <i class="bi bi-book"></i>
                                                    <h4>No students found</h4>
                                                    <p>There are no students in the system yet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Approvals Tab -->
                    <div class="tab-pane fade" id="pending" role="tabpanel">
                        <div class="users-table-container">
                            <div class="table-header">
                                <div class="table-title">
                                    <h3>Pending Approvals</h3>
                                    <p class="text-muted">Review and approve pending user registrations</p>
                                </div>
                                <div class="table-actions">
                                    <button class="btn btn-success btn-sm" id="approveAllBtn">
                                        <i class="bi bi-check-circle me-1"></i>Approve All
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="rejectAllBtn">
                                        <i class="bi bi-x-circle me-1"></i>Reject All
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Pending Table -->
                            <div class="table-scroll-wrapper">
                                    <table class="table users-table" id="pendingTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" class="form-check-input" id="selectAllPending">
                                            </th>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Email</th>
                                            <th>Applied</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pendingTableBody">
                                        <!-- Empty State -->
                                        <tr class="empty-state">
                                            <td colspan="6">
                                                <div class="empty-state-content">
                                                    <i class="bi bi-clock"></i>
                                                    <h4>No pending approvals</h4>
                                                    <p>All user registrations have been processed.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- RBAC/Permissions Section -->
        <div class="permissions-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-shield-check me-2"></i>Role-Based Access Control (RBAC)
                </h2>
                <p class="section-subtitle">Manage permissions and access levels for different user roles</p>
            </div>
            
            <div class="permissions-container">
                <!-- Role Selector -->
                <div class="role-selector">
                    <label class="form-label">Select Role:</label>
                    <select class="form-select" id="roleSelector">
                        <option value="">Choose a role...</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
                
                <!-- Permissions Panel -->
                <div class="permissions-panel" id="permissionsPanel" style="display: none;">
                        <!-- Teacher Permissions -->
                        <div id="teacherPermissions" style="display: none;">
                            <div class="permission-category">
                                <h4>Class Management</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="createClass">
                                            <i class="bi bi-plus-circle me-2"></i>Create Class
                                        </label>
                                        <span class="permission-description">Allow teachers to create new classes/subjects</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="createClass">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="joinClass">
                                            <i class="bi bi-door-open me-2"></i>Join Class
                                        </label>
                                        <span class="permission-description">Allow teachers to join classes</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="joinClass">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="manageClass">
                                            <i class="bi bi-gear me-2"></i>Manage Class
                                        </label>
                                        <span class="permission-description">Allow teachers to manage class settings and members</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="manageClass">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-category">
                                <h4>Attendance & Reports</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="takeAttendance">
                                            <i class="bi bi-check2-square me-2"></i>Take Attendance
                                        </label>
                                        <span class="permission-description">Allow teachers to conduct attendance sessions</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="takeAttendance">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="viewReports">
                                            <i class="bi bi-graph-up me-2"></i>View Reports
                                        </label>
                                        <span class="permission-description">Allow teachers to view attendance reports</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="viewReports">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="exportReports">
                                            <i class="bi bi-download me-2"></i>Export Reports
                                        </label>
                                        <span class="permission-description">Allow teachers to export attendance data</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="exportReports">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-category">
                                <h4>Account Settings</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="editTeacherInfo">
                                            <i class="bi bi-person-gear me-2"></i>Edit Teacher Information
                                        </label>
                                        <span class="permission-description">Allow teachers to change their personal information in settings</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editTeacherInfo">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Student Permissions -->
                        <div id="studentPermissions" style="display: none;">
                            <div class="permission-category">
                                <h4>Class Management</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_createClass">
                                            <i class="bi bi-plus-circle me-2"></i>Create Class
                                        </label>
                                        <span class="permission-description">Allow students to create new classes/subjects</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_createClass">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_joinClass">
                                            <i class="bi bi-door-open me-2"></i>Join Class
                                        </label>
                                        <span class="permission-description">Allow students to join existing classes</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_joinClass">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_unenrollClass">
                                            <i class="bi bi-box-arrow-right me-2"></i>Unenroll from Class
                                        </label>
                                        <span class="permission-description">Allow students to unenroll from classes</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_unenrollClass">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-category">
                                <h4>Attendance & Records</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_viewAttendanceRecord">
                                            <i class="bi bi-clipboard-check me-2"></i>View Attendance Record
                                        </label>
                                        <span class="permission-description">Allow students to view their current attendance records</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_viewAttendanceRecord">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_viewAttendanceHistory">
                                            <i class="bi bi-clock-history me-2"></i>View Attendance History
                                        </label>
                                        <span class="permission-description">Allow students to view their complete attendance history</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_viewAttendanceHistory">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-category">
                                <h4>Account Settings</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="student_editStudentInfo">
                                            <i class="bi bi-person-gear me-2"></i>Edit Student Information
                                        </label>
                                        <span class="permission-description">Allow students to change their personal information in settings</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_editStudentInfo">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Administrator Permissions -->
                        <div id="administratorPermissions" style="display: none;">
                            
                            <div class="permission-category">
                                <h4>User Account Management</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="approveTeacherAccounts">
                                            <i class="bi bi-check-circle me-2"></i>Approve Teacher Accounts
                                        </label>
                                        <span class="permission-description">Allow administrators to approve new teacher account registrations</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="approveTeacherAccounts">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="rejectTeacherAccounts">
                                            <i class="bi bi-x-circle me-2"></i>Reject Teacher Accounts
                                        </label>
                                        <span class="permission-description">Allow administrators to reject new teacher account registrations</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="rejectTeacherAccounts">
                                    </div>
                                </div>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="createAdminUser">
                                            <i class="bi bi-person-plus-fill me-2"></i>Create Admin User
                                        </label>
                                        <span class="permission-description">Allow administrators to create new administrator accounts</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="createAdminUser">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="permission-category">
                                <h4>Account Settings</h4>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <label class="form-check-label" for="editAdminProfile">
                                            <i class="bi bi-person-gear me-2"></i>Edit Admin Profile
                                        </label>
                                        <span class="permission-description">Allow administrators to change their personal information in settings</span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editAdminProfile">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="permissions-actions">
                            <button class="btn btn-secondary" id="resetPermissionsBtn">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset to Default
                            </button>
                            <button class="btn btn-primary" id="savePermissionsBtn">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 400px !important;">
            <div class="modal-content confirmation-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="confirmation-header" style="padding: 32px 32px 24px 32px !important; text-align: center !important;">
                        <div class="confirmation-icon" style="width: 64px !important; height: 64px !important; border-radius: 50% !important; background: #fef7e0 !important; display: flex !important; align-items: center !important; justify-content: center !important; margin: 0 auto 16px auto !important;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 32px !important; color: #fbbc04 !important;"></i>
                        </div>
                        <h5 class="confirmation-title" id="confirmationModalLabel" style="font-size: 20px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Confirm Action</h5>
                    </div>

                    <!-- Message Section -->
                    <div class="confirmation-message" style="padding: 0 32px 24px 32px !important;">
                        <p class="confirmation-text" style="color: #5f6368 !important; font-size: 16px !important; line-height: 1.4 !important; margin: 0 !important; text-align: center !important; font-family: 'Montserrat', sans-serif !important;">
                            Are you sure you want to proceed with this action?
                        </p>
                    </div>

                    <!-- Footer Actions -->
                    <div class="confirmation-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: center !important; gap: 12px !important;">
                        <button type="button" class="btn btn-cancel" id="confirmCancelBtn" data-bs-dismiss="modal" style="background: #f1f3f4 !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 12px 24px !important; border-radius: 8px !important; cursor: pointer !important; font-family: 'Montserrat', sans-serif !important; transition: background-color 0.2s ease !important;">Cancel</button>
                        <button type="button" class="btn btn-confirm" id="confirmActionBtn" style="background: #1a73e8 !important; border: none !important; color: white !important; font-size: 14px !important; font-weight: 500 !important; padding: 12px 24px !important; border-radius: 8px !important; cursor: pointer !important; font-family: 'Montserrat', sans-serif !important; transition: background-color 0.2s ease !important;">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content edit-user-modal">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="bi bi-person-gear me-2"></i>
                        Edit User Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="userId">
                        
                        <!-- User Profile Section -->
                        <div class="profile-section">
                            <div class="profile-avatar-container">
                                <div class="profile-avatar">
                                    <img id="editProfileImage" src="" alt="Profile" class="rounded-circle">
                                    <i class="bi bi-person-circle" id="editProfileIcon"></i>
                                </div>
                                <div class="profile-upload">
                                    <button type="button" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-camera me-1"></i>
                                        Change Photo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="bi bi-person me-2"></i>
                                Personal Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editFirstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="editFirstName" name="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editLastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="editLastName" name="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editEmail" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                        </div>

                        <!-- Role & Status -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="bi bi-shield me-2"></i>
                                Role & Status
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editRole" class="form-label">Role</label>
                                    <select class="form-select" id="editRole" name="role">
                                        <option value="Student">Student</option>
                                        <option value="Teacher">Teacher</option>
                                        <option value="Administrator">Administrator</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editStatus" class="form-label">Account Status</label>
                                    <select class="form-select" id="editStatus" name="status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Student Specific Fields -->
                        <div class="form-section" id="studentFields" style="display: none;">
                            <h6 class="section-title">
                                <i class="bi bi-book me-2"></i>
                                Academic Information
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editStudentNumber" class="form-label">Student Number</label>
                                    <input type="text" class="form-control" id="editStudentNumber" name="studentNumber">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="editCourse" class="form-label">Course</label>
                                    <input type="text" class="form-control" id="editCourse" name="course">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editYearLevel" class="form-label">Year Level</label>
                                    <select class="form-select" id="editYearLevel" name="yearLevel">
                                        <option value="">Select Year</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                        <option value="5">5th Year</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <!-- Section field removed -->
                                </div>
                            </div>
                        </div>

                        <!-- Teacher Specific Fields -->
                        <div class="form-section" id="teacherFields" style="display: none;">
                            <h6 class="section-title">
                                <i class="bi bi-mortarboard me-2"></i>
                                Professional Information
                            </h6>
                            <div class="mb-3">
                                <label for="editDepartment" class="form-label">Department</label>
                                <input type="text" class="form-control" id="editDepartment" name="department">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script src="../assets/js/manage_users.js?v=8"></script>
</body>
</html>
