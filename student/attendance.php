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

// Check student_viewAttendanceRecord permission
$canViewAttendanceRecord = false;
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    try {
        // Get user role from users table
        $stmt = $db->prepare("SELECT Role FROM users WHERE UserID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            // Get permission from role_permissions table
            $stmt = $db->prepare("SELECT student_viewAttendanceRecord FROM role_permissions WHERE role = ?");
            $stmt->execute([$userData['Role']]);
            $permissionData = $stmt->fetch();
            
            $canViewAttendanceRecord = $permissionData && $permissionData['student_viewAttendanceRecord'] == 1;
        }
    } catch (Exception $e) {
        error_log("Error checking attendance record permission: " . $e->getMessage());
        $canViewAttendanceRecord = false;
    }
}

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Student');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student';

// Get subject information from URL parameter or database
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
$subject_name = '';
$current_subject = [];
$attendance_data = [];

try {
    // Get student ID from users table based on session user_id
    $stmt = $db->prepare("SELECT StudentID FROM students WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if ($student) {
        $student_id = $student['StudentID'];
        
        // If no subject_id provided, get the first subject the student is enrolled in
        if (!$subject_id) {
            $stmt = $db->prepare("
                SELECT s.SubjectID 
                FROM enrollments e 
                JOIN subjects s ON e.SubjectID = s.SubjectID 
                WHERE e.StudentID = ? 
                LIMIT 1
            ");
            $stmt->execute([$student_id]);
            $first_subject = $stmt->fetch();
            $subject_id = $first_subject ? $first_subject['SubjectID'] : null;
        }
        
        if ($subject_id) {
            // Verify student is enrolled in this subject and get subject details
            $stmt = $db->prepare("
                SELECT 
                    s.SubjectID,
                    s.SubjectCode,
                    s.SubjectName,
                    s.SectionName,
                    s.Schedule,
                    CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
                FROM enrollments e
                JOIN subjects s ON e.SubjectID = s.SubjectID
                JOIN teachers t ON s.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                WHERE e.StudentID = ? AND s.SubjectID = ?
                LIMIT 1
            ");
            $stmt->execute([$student_id, $subject_id]);
            $subject_info = $stmt->fetch();
            
            if ($subject_info) {
                $subject_name = $subject_info['SubjectName'];
                $current_subject = [
                    'name' => $subject_info['SubjectName'],
                    'section' => $subject_info['SectionName'],
                    'teacher' => $subject_info['teacher_name']
                ];
                
                // Get attendance records for this specific subject
                $query = "
                    SELECT 
                        ar.AttendanceStatus,
                        ar.ScanTime,
                        s.SessionDate,
                        s.StartTime,
                        s.EndTime
                    FROM attendancerecords ar
                    JOIN attendancesessions s ON ar.SessionID = s.SessionID
                    WHERE ar.StudentID = ? AND s.SubjectID = ?
                    ORDER BY s.SessionDate DESC, s.StartTime DESC
                ";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$student_id, $subject_id]);
                $records = $stmt->fetchAll();
                
                // Format the data to match the expected structure
                foreach ($records as $record) {
                    $date = date('Y-m-d', strtotime($record['SessionDate']));
                    $day = date('l', strtotime($record['SessionDate']));
                    $start_time = date('h:i A', strtotime($record['StartTime']));
                    $end_time = $record['EndTime'] ? date('h:i A', strtotime($record['EndTime'])) : 'Ongoing';
                    
                    $attendance_data[] = [
                        'date' => $date,
                        'day' => $day,
                        'time' => $start_time . ' - ' . $end_time,
                        'status' => strtolower($record['AttendanceStatus']),
                        'subject_name' => $subject_name
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching attendance data: " . $e->getMessage());
    // Keep empty arrays if there's an error
}

// Ensure variables are defined for metrics calculation
if (!isset($student_id) || !$student_id) {
    $student_id = 0;
}
if (!isset($subject_id) || !$subject_id) {
    $subject_id = 0;
}

$user_initials = strtoupper(substr($user_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - <?php echo htmlspecialchars($subject_name); ?> - Attendance Records</title>
    
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
    <link rel="stylesheet" href="../assets/css/attendance.css?v=2">
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
    <!-- Include Navbar -->
    <?php include '../assets/components/navbar.php'; ?>
    
    <!-- Include Sidebar -->
    <?php include '../assets/components/sidebar.php'; ?>

    <!-- Disabled Overlay -->
    <?php if (!$canViewAttendanceRecord): ?>
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
        <?php echo !$canViewAttendanceRecord ? 'opacity: 0.4 !important; pointer-events: none !important;' : ''; ?>
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
            
            <!-- Page Header -->
            <div class="attendance-header">
                <div class="d-flex align-items-center mb-4">
                    <button class="btn-back me-3" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div>
                        <h1 class="page-title"><?php echo htmlspecialchars($subject_name); ?></h1>
                        <p class="page-subtitle">Section <?php echo htmlspecialchars($current_subject['section']); ?> • <?php echo htmlspecialchars($current_subject['teacher']); ?></p>
                    </div>
            </div>

            <!-- Attendance Percentage Metrics Section -->
            <div class="percentage-section">
                <div class="percentage-container">
                    <div class="percentage-header">
                        <h3 class="percentage-title">Attendance Performance Metrics</h3>
                        <p class="percentage-subtitle">Visual breakdown of your attendance patterns</p>
                    </div>
                    <div class="percentage-grid">
                        <?php
                        // Get accurate attendance metrics from database
                        $total_classes = 0;
                        $present_count = 0;
                        $absent_count = 0;
                        $late_count = 0;
                        
                        try {
                            // Get all sessions for this subject (only up to current date)
                            $sessions_query = "
                                SELECT COUNT(*) as total_sessions
                                FROM attendancesessions 
                                WHERE SubjectID = ? AND SessionDate <= CURDATE()
                            ";
                            $stmt = $db->prepare($sessions_query);
                            $stmt->execute([$subject_id]);
                            $sessions_result = $stmt->fetch();
                            $total_classes = $sessions_result ? $sessions_result['total_sessions'] : 0;
                            
                            // Get attendance counts for this student
                            $attendance_query = "
                                SELECT 
                                    AttendanceStatus,
                                    COUNT(*) as count
                                FROM attendancerecords ar
                                JOIN attendancesessions s ON ar.SessionID = s.SessionID
                                WHERE ar.StudentID = ? AND s.SubjectID = ? AND s.SessionDate <= CURDATE()
                                GROUP BY AttendanceStatus
                            ";
                            $stmt = $db->prepare($attendance_query);
                            $stmt->execute([$student_id, $subject_id]);
                            $attendance_counts = $stmt->fetchAll();
                            
                            // Count each status
                            foreach ($attendance_counts as $count) {
                                switch(strtolower($count['AttendanceStatus'])) {
                                    case 'present':
                                        $present_count = $count['count'];
                                        break;
                                    case 'absent':
                                        $absent_count = $count['count'];
                                        break;
                                    case 'late':
                                        $late_count = $count['count'];
                                        break;
                                }
                            }
                            
                            // Calculate absent count for sessions without records
                            $recorded_sessions = $present_count + $absent_count + $late_count;
                            $unrecorded_absences = max(0, $total_classes - $recorded_sessions);
                            $absent_count += $unrecorded_absences;
                            
                        } catch (Exception $e) {
                            error_log("Error calculating attendance metrics: " . $e->getMessage());
                            // Fallback to current data if database query fails
                            $total_classes = count($attendance_data);
                            $present_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'present'));
                            $absent_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'absent'));
                            $late_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'late'));
                        }
                        
                        // Calculate percentages
                        $present_percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 1) : 0;
                        $absent_percentage = $total_classes > 0 ? round(($absent_count / $total_classes) * 100, 1) : 0;
                        $late_percentage = $total_classes > 0 ? round(($late_count / $total_classes) * 100, 1) : 0;
                        ?>
                        
                        <!-- Present Percentage -->
                        <div class="percentage-card present-card">
                            <div class="percentage-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div class="percentage-content">
                                <div class="percentage-label">Present</div>
                                <div class="percentage-value"><?php echo $present_percentage; ?>%</div>
                                <div class="percentage-bar-container">
                                    <div class="percentage-bar present-bar" style="width: <?php echo $present_percentage; ?>%"></div>
                                </div>
                                <div class="percentage-detail"><?php echo $present_count; ?> out of <?php echo $total_classes; ?> classes</div>
                            </div>
                        </div>
                        
                        <!-- Absent Percentage -->
                        <div class="percentage-card absent-card">
                            <div class="percentage-icon">
                                <i class="bi bi-x-circle-fill"></i>
                            </div>
                            <div class="percentage-content">
                                <div class="percentage-label">Absent</div>
                                <div class="percentage-value"><?php echo $absent_percentage; ?>%</div>
                                <div class="percentage-bar-container">
                                    <div class="percentage-bar absent-bar" style="width: <?php echo $absent_percentage; ?>%"></div>
                                </div>
                                <div class="percentage-detail"><?php echo $absent_count; ?> out of <?php echo $total_classes; ?> classes</div>
                            </div>
                        </div>
                        
                        <!-- Late Percentage -->
                        <div class="percentage-card late-card">
                            <div class="percentage-icon">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div class="percentage-content">
                                <div class="percentage-label">Late</div>
                                <div class="percentage-value"><?php echo $late_percentage; ?>%</div>
                                <div class="percentage-bar-container">
                                    <div class="percentage-bar late-bar" style="width: <?php echo $late_percentage; ?>%"></div>
                                </div>
                                <div class="percentage-detail"><?php echo $late_count; ?> out of <?php echo $total_classes; ?> classes</div>
                            </div>
                        </div>
                        
                        <!-- Overall Performance -->
                        <div class="percentage-card overall-card">
                            <div class="percentage-icon">
                                <i class="bi bi-trophy-fill"></i>
                            </div>
                            <div class="percentage-content">
                                <div class="percentage-label">Overall Score</div>
                                <div class="percentage-value"><?php echo $present_percentage; ?>%</div>
                                <div class="percentage-bar-container">
                                    <div class="percentage-bar overall-bar" style="width: <?php echo $present_percentage; ?>%"></div>
                                </div>
                                <div class="percentage-detail">Based on attendance rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance Summary -->
                    <div class="performance-summary">
                        <div class="summary-item">
                            <div class="summary-icon excellent">
                                <i class="bi bi-award-fill"></i>
                            </div>
                            <div class="summary-content">
                                <div class="summary-label">Performance Grade</div>
                                <div class="summary-value">
                                    <?php
                                    if ($present_percentage >= 90) {
                                        echo 'Excellent';
                                    } elseif ($present_percentage >= 80) {
                                        echo 'Good';
                                    } elseif ($present_percentage >= 70) {
                                        echo 'Fair';
                                    } else {
                                        echo 'Needs Improvement';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-container">
                    <div class="filter-header">
                        <h3 class="filter-title">Filter Attendance Records</h3>
                    </div>
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label class="filter-label">Date Range</label>
                            <div class="calendar-dropdown-container">
                                <div class="calendar-input-wrapper">
                                    <input type="text" id="dateRangeInput" class="calendar-input" placeholder="Select date" readonly>
                                    <button type="button" class="calendar-toggle" id="calendarToggle">
                                        <i class="bi bi-calendar3"></i>
                                    </button>
                                </div>
                                <div class="calendar-dropdown" id="calendarDropdown">
                                    <div class="calendar-header">
                                        <button type="button" class="calendar-nav" id="prevMonth">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <h4 class="calendar-title" id="calendarTitle">April 2026</h4>
                                        <button type="button" class="calendar-nav" id="nextMonth">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="calendar-grid">
                                        <div class="calendar-weekdays">
                                            <div class="calendar-weekday">Sun</div>
                                            <div class="calendar-weekday">Mon</div>
                                            <div class="calendar-weekday">Tue</div>
                                            <div class="calendar-weekday">Wed</div>
                                            <div class="calendar-weekday">Thu</div>
                                            <div class="calendar-weekday">Fri</div>
                                            <div class="calendar-weekday">Sat</div>
                                        </div>
                                        <div class="calendar-days" id="calendarDays">
                                            <!-- Days will be generated by JavaScript -->
                                        </div>
                                    </div>
                                    <div class="calendar-footer">
                                        <button type="button" class="calendar-btn calendar-btn-clear" id="clearDateRange">Clear</button>
                                        <button type="button" class="calendar-btn calendar-btn-today" id="todayDateRange">Today</button>
                                    </div>
                                </div>
                            </div>
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
                        <h3 class="records-title">Attendance Records</h3>
                        <div class="records-count">
                            <span id="recordCount"><?php echo count($attendance_data); ?></span> records found
                        </div>
                    </div>
                    
                    <?php if (count($attendance_data) > 0): ?>
                    <div class="attendance-list" id="attendanceList">
                        <?php foreach ($attendance_data as $index => $record): ?>
                            <div class="attendance-record" data-date="<?php echo $record['date']; ?>" data-status="<?php echo $record['status']; ?>" data-month="<?php echo date('Y-m', strtotime($record['date'])); ?>">
                                <div class="record-date">
                                    <div class="date-day"><?php echo date('d', strtotime($record['date'])); ?></div>
                                    <div class="date-month"><?php echo date('M', strtotime($record['date'])); ?></div>
                                </div>
                                <div class="record-details">
                                    <div class="record-info">
                                        <h4 class="record-date-full"><?php echo $record['date']; ?> • <?php echo $record['day']; ?></h4>
                                        <p class="record-time"><?php echo $record['time']; ?></p>
                                        <p class="record-subject"><?php echo htmlspecialchars($record['subject_name']); ?></p>
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
                    <?php endif; ?>
                    
                    <!-- Empty State -->
                    <div class="empty-records" id="emptyRecords" style="display: none;">
                        <div class="empty-icon">
                            <i class="bi bi-calendar-x" id="emptyIcon"></i>
                        </div>
                        <h3 class="empty-title" id="emptyTitle">No attendance records found</h3>
                        <p class="empty-message" id="emptyMessage">Try adjusting your filters or check back later for new records.</p>
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
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/attendance.js?v=<?php echo time(); ?>"></script>
    
    <?php if (!$canViewAttendanceRecord): ?>
    <script>
    // Handle sidebar expansion and overlay positioning using clip-path
    function updateOverlayPosition() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.attendance-disabled-overlay');
        
        if (sidebar && overlay) {
            const isExpanded = sidebar.classList.contains('expanded');
            const isDesktop = window.innerWidth > 768;
            
            if (isDesktop) {
                if (isExpanded) {
                    overlay.style.clipPath = 'polygon(250px 0, 100% 0, 100% 100%, 250px 100%)';
                } else {
                    overlay.style.clipPath = 'polygon(70px 0, 100% 0, 100% 100%, 70px 100%)';
                }
            } else {
                overlay.style.clipPath = 'polygon(0 0, 100% 0, 100% 100%, 0 100%)';
            }
        }
    }
    
    // Monitor sidebar state changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                updateOverlayPosition();
            }
        });
    });
    
    // Start observing sidebar class changes
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        observer.observe(sidebar, { attributes: true });
    }
    
    // Update on window resize
    window.addEventListener('resize', updateOverlayPosition);
    
    // Initial positioning
    document.addEventListener('DOMContentLoaded', updateOverlayPosition);
    </script>
    <?php endif; ?>
</body>
</html>
