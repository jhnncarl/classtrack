<?php
session_start();

// Check if user is logged in (redirect to login if not)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}

// Include database configuration for permission checking
require_once '../config/database.php';
require_once '../config/permissions.php';

// Check student_viewAttendanceHistory permission
$canViewAttendanceHistory = false;
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    try {
        // Get user role from users table
        $stmt = $db->prepare("SELECT Role FROM users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            // Get permission from role_permissions table
            $stmt = $db->prepare("SELECT student_viewAttendanceHistory FROM role_permissions WHERE role = ?");
            $stmt->execute([$userData['Role']]);
            $permissionData = $stmt->fetch();
            
            $canViewAttendanceHistory = $permissionData && $permissionData['student_viewAttendanceHistory'] == 1;
        }
    } catch (Exception $e) {
        error_log("Error checking attendance history permission: " . $e->getMessage());
        $canViewAttendanceHistory = false;
    }
}
// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Student');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student';

// Mock attendance history data (in real implementation, this would come from database)
$attendance_history_data = [
    [
        'date' => '2024-03-15',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-13',
        'day' => 'Wednesday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-11',
        'day' => 'Monday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'absent',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-08',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-06',
        'day' => 'Wednesday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'late',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-04',
        'day' => 'Monday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-03-01',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => 'Web Development',
        'subject_code' => 'WEBDEV101',
        'section' => 'CS-301',
        'teacher' => 'Prof. Sarah Johnson'
    ],
    [
        'date' => '2024-02-28',
        'day' => 'Wednesday',
        'time' => '2:00 PM - 3:30 PM',
        'status' => 'present',
        'subject_name' => 'Data Structures',
        'subject_code' => 'DATA201',
        'section' => 'CS-201',
        'teacher' => 'Dr. Michael Chen'
    ],
    [
        'date' => '2024-02-26',
        'day' => 'Monday',
        'time' => '2:00 PM - 3:30 PM',
        'status' => 'present',
        'subject_name' => 'Data Structures',
        'subject_code' => 'DATA201',
        'section' => 'CS-201',
        'teacher' => 'Dr. Michael Chen'
    ],
    [
        'date' => '2024-02-23',
        'day' => 'Friday',
        'time' => '2:00 PM - 3:30 PM',
        'status' => 'late',
        'subject_name' => 'Data Structures',
        'subject_code' => 'DATA201',
        'section' => 'CS-201',
        'teacher' => 'Dr. Michael Chen'
    ],
    [
        'date' => '2024-02-21',
        'day' => 'Wednesday',
        'time' => '2:00 PM - 3:30 PM',
        'status' => 'present',
        'subject_name' => 'Data Structures',
        'subject_code' => 'DATA201',
        'section' => 'CS-201',
        'teacher' => 'Dr. Michael Chen'
    ],
    [
        'date' => '2024-02-19',
        'day' => 'Monday',
        'time' => '2:00 PM - 3:30 PM',
        'status' => 'absent',
        'subject_name' => 'Data Structures',
        'subject_code' => 'DATA201',
        'section' => 'CS-201',
        'teacher' => 'Dr. Michael Chen'
    ]
];

$user_initials = strtoupper(substr($user_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Attendance History</title>
    
    <!-- Favicon - QR Code Logo for ClassTrack -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' fill='%231a73e8' rx='6'/%3E%3Cg fill='white'%3E%3Crect x='6' y='6' width='8' height='8'/%3E%3Crect x='18' y='6' width='8' height='8'/%3E%3Crect x='6' y='18' width='8' height='8'/%3E%3Crect x='18' y='18' width='8' height='8'/%3E%3Crect x='8' y='8' width='4' height='4' fill='%231a73e8'/%3E%3Crect x='20' y='8' width='4' height='4' fill='%231a73e8'/%3E%3Crect x='8' y='20' width='4' height='4' fill='%231a73e8'/%3E%3Crect x='20' y='20' width='4' height='4' fill='%231a73e8'/%3E%3C/g%3E%3C/svg%3E">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/attendance_history.css?v=2">
    
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
    <!-- Include Navbar -->
    <?php include '../assets/components/navbar.php'; ?>
    
    <!-- Include Sidebar -->
    <?php include '../assets/components/sidebar.php'; ?>

    <!-- Disabled Overlay -->
            <?php if (!$canViewAttendanceHistory): ?>
            <div class="attendance-disabled-overlay">
                <div class="attendance-disabled-content">
                    <div class="attendance-disabled-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="attendance-disabled-message">
                        <h4>Access Restricted</h4>
                        <p>This feature is currently unavailable. Please contact the administrator for assistance with accessing this feature.</p>
                    </div>
                </div>
            </div>
            <style>
            .main-content {
                position: relative !important;
                <?php echo !$canViewAttendanceHistory ? 'opacity: 0.4 !important; pointer-events: none !important;' : ''; ?>
            }
            .sidebar {
                z-index: 1020 !important;
                pointer-events: auto !important;
            }
            .navbar {
                z-index: 1021 !important;
                pointer-events: auto !important;
            }
            .attendance-disabled-overlay {
                position: fixed !important;
                top: 40px !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(252, 229, 161, 0.15) !important;
                backdrop-filter: blur(5px) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 100 !important;
                border-radius: 0 !important;
                border: 2px dashed #fcc626 !important;
                clip-path: polygon(70px 0, 100% 0, 100% 100%, 70px 100%);
                transition: clip-path 0.3s ease !important;
            }
            @media (max-width: 768px) {
                .attendance-disabled-overlay {
                    top: 20px !important;
                    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%) !important;
                }
            }
            .attendance-disabled-content {
                text-align: center !important;
                padding: 40px 32px !important;
                background: white !important;
                border-radius: 12px !important;
                box-shadow: 0 8px 32px rgba(255, 193, 7, 0.3) !important;
                border: 2px solid #ffc107 !important;
                max-width: 450px !important;
                margin: 20px !important;
                position: relative !important;
                z-index: 10000 !important;
            }
            .attendance-disabled-icon {
                font-size: 64px !important;
                color: #ffc107 !important;
                margin-bottom: 20px !important;
            }
            .attendance-disabled-message h4 {
                margin: 0 0 12px 0 !important;
                color: #ffc107 !important;
                font-weight: 600 !important;
                font-size: 20px !important;
            }
            .attendance-disabled-message p {
                margin: 0 !important;
                color: #6c757d !important;
                line-height: 1.6 !important;
                font-size: 15px !important;
            }
            @media (max-width: 576px) {
                .attendance-disabled-content {
                    padding: 30px 24px !important;
                    margin: 15px !important;
                }
                .attendance-disabled-icon {
                    font-size: 48px !important;
                    margin-bottom: 16px !important;
                }
                .attendance-disabled-message h4 {
                    font-size: 18px !important;
                }
                .attendance-disabled-message p {
                    font-size: 14px !important;
                }
            }
            </style>
            <?php endif; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container-fluid attendance-container">           
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-container">
                    <div class="filter-header">
                        <h3 class="filter-title">Filter Attendance Records</h3>
                    </div>
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label for="subjectFilter" class="filter-label">Subject</label>
                            <select id="subjectFilter" class="filter-select">
                                <option value="all">All Subjects</option>
                                <option value="WEBDEV101">Web Development</option>
                                <option value="DATA201">Data Structures</option>
                                <option value="DB302">Database Systems</option>
                                <option value="ML401">Machine Learning</option>
                                <option value="MOB351">Mobile Development</option>
                                <option value="NET251">Computer Networks</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="monthFilter" class="filter-label">Month</label>
                            <select id="monthFilter" class="filter-select">
                                <option value="all">All Months</option>
                                <option value="2024-03">March 2024</option>
                                <option value="2024-02">February 2024</option>
                                <option value="2024-01">January 2024</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="statusFilter" class="filter-label">Status</label>
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        <button class="btn-filter-apply" onclick="applyFilters()">
                            <i class="bi bi-funnel me-2"></i>Apply Filters
                        </button>
                        <button class="btn-filter-clear" onclick="clearFilters()">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Records Section -->
            <div class="records-section">
                <div class="records-container">
                    <div class="records-header">
                        <h3 class="records-title">Attendance History Records</h3>
                        <div class="records-count">
                            <span id="recordCount"><?php echo count($attendance_history_data); ?></span> records found
                        </div>
                    </div>
                    
                    <div class="attendance-list" id="attendanceList">
                        <?php foreach ($attendance_history_data as $index => $record): ?>
                            <div class="attendance-record" data-date="<?php echo $record['date']; ?>" data-status="<?php echo $record['status']; ?>" data-subject="<?php echo $record['subject_code']; ?>" data-month="<?php echo date('Y-m', strtotime($record['date'])); ?>">
                                <div class="record-date">
                                    <div class="date-day"><?php echo date('d', strtotime($record['date'])); ?></div>
                                    <div class="date-month"><?php echo date('M', strtotime($record['date'])); ?></div>
                                </div>
                                <div class="record-details">
                                    <div class="record-info">
                                        <h4 class="record-date-full"><?php echo $record['date']; ?> • <?php echo $record['day']; ?></h4>
                                        <p class="record-time"><?php echo $record['time']; ?></p>
                                        <p class="record-subject"><?php echo htmlspecialchars($record['subject_name']); ?> • <?php echo htmlspecialchars($record['section']); ?></p>
                                        <p class="record-teacher"><?php echo htmlspecialchars($record['teacher']); ?></p>
                                    </div>
                                    <div class="record-status">
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php 
                                            switch($record['status']) {
                                                case 'present':
                                                    echo '<i class="bi bi-check-circle me-1"></i>Present';
                                                    break;
                                                case 'absent':
                                                    echo '<i class="bi bi-x-circle me-1"></i>Absent';
                                                    break;
                                                case 'late':
                                                    echo '<i class="bi bi-clock me-1"></i>Late';
                                                    break;
                                                default:
                                                    echo ucfirst($record['status']);
                                                }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Empty State (hidden by default) -->
                    <div class="empty-records" id="emptyRecords" style="display: none;">
                        <div class="empty-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h3 class="empty-title">No attendance records found</h3>
                        <p class="empty-message">Try adjusting your filters or check back later for new records.</p>
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
    <script src="../assets/js/attendance_history.js?v=30"></script>
    
    </body>
</html>
