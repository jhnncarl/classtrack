<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    header('Location: ../auth/login.php');
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Teacher');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Teacher';

// Get teacher's ID from users table
$teacher_id = null;
try {
    $stmt = $db->prepare("SELECT t.TeacherID FROM teachers t JOIN users u ON t.UserID = u.UserID WHERE u.UserID = ? AND u.Role = 'Teacher'");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_data = $stmt->fetch();
    $teacher_id = $teacher_data ? $teacher_data['TeacherID'] : null;
} catch(PDOException $e) {
    error_log("Error getting teacher ID: " . $e->getMessage());
}

// Get teacher's subjects from database
$subjects = [];
if ($teacher_id) {
    try {
        $stmt = $db->prepare("SELECT SubjectID, SubjectCode, SubjectName, ClassName, SectionName FROM subjects WHERE TeacherID = ? ORDER BY SubjectName");
        $stmt->execute([$teacher_id]);
        $subjects = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting subjects: " . $e->getMessage());
    }
}

// Get filter parameters
$filter_subject = $_GET['subject'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Get attendance reports data
$reports = [];
if ($teacher_id && !empty($subjects)) {
    try {
        $subject_ids = array_column($subjects, 'SubjectID');
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        
        // Build WHERE conditions
        $where_conditions = ["asess.SubjectID IN ($placeholders)"];
        $params = $subject_ids;
        
        if (!empty($filter_subject)) {
            $where_conditions[] = "asess.SubjectID = ?";
            $params[] = $filter_subject;
        }
        
        if (!empty($filter_date_from)) {
            $where_conditions[] = "asess.SessionDate >= ?";
            $params[] = $filter_date_from;
        }
        
        if (!empty($filter_date_to)) {
            $where_conditions[] = "asess.SessionDate <= ?";
            $params[] = $filter_date_to;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $db->prepare("
            SELECT 
                asess.SessionID,
                asess.SubjectID,
                asess.SessionDate,
                asess.StartTime,
                asess.EndTime,
                asess.Status,
                s.SubjectCode,
                s.SubjectName,
                s.ClassName,
                s.SectionName,
                COUNT(DISTINCT ar.RecordID) as present_count,
                0 as late_count,
                COUNT(DISTINCT ar.RecordID) as total_students
            FROM attendancesessions asess
            LEFT JOIN attendancerecords ar ON asess.SessionID = ar.SessionID AND ar.AttendanceStatus IN ('Present', 'Late')
            LEFT JOIN subjects s ON asess.SubjectID = s.SubjectID
            WHERE $where_clause
            GROUP BY asess.SessionID, asess.SubjectID, asess.SessionDate, asess.StartTime, asess.EndTime, asess.Status,
                     s.SubjectCode, s.SubjectName, s.ClassName, s.SectionName
            ORDER BY asess.SessionDate DESC, asess.StartTime DESC
        ");
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting reports: " . $e->getMessage());
    }
}

// Handle AJAX filter requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter_reports') {
    header('Content-Type: application/json');
    
    $filter_subject = $_POST['subject'] ?? '';
    $filter_date_from = $_POST['date_from'] ?? '';
    $filter_date_to = $_POST['date_to'] ?? '';
    
    $reports = [];
    if ($teacher_id && !empty($subjects)) {
        try {
            $subject_ids = array_column($subjects, 'SubjectID');
            $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
            
            // Build WHERE conditions
            $where_conditions = ["asess.SubjectID IN ($placeholders)"];
            $params = $subject_ids;
            
            if (!empty($filter_subject)) {
                $where_conditions[] = "asess.SubjectID = ?";
                $params[] = $filter_subject;
            }
            
            if (!empty($filter_date_from)) {
                $where_conditions[] = "asess.SessionDate >= ?";
                $params[] = $filter_date_from;
            }
            
            if (!empty($filter_date_to)) {
                $where_conditions[] = "asess.SessionDate <= ?";
                $params[] = $filter_date_to;
            }
            
            if (!empty($filter_status)) {
                $where_conditions[] = "asess.Status = ?";
                $params[] = $filter_status;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $stmt = $db->prepare("
                SELECT 
                    asess.SessionID,
                    asess.SubjectID,
                    asess.SessionDate,
                    asess.StartTime,
                    asess.EndTime,
                    asess.Status,
                    s.SubjectCode,
                    s.SubjectName,
                    s.ClassName,
                    s.SectionName,
                    COUNT(DISTINCT ar.RecordID) as present_count,
                    0 as late_count,
                    COUNT(DISTINCT ar.RecordID) as total_students
                FROM attendancesessions asess
                LEFT JOIN attendancerecords ar ON asess.SessionID = ar.SessionID AND ar.AttendanceStatus IN ('Present', 'Late')
                LEFT JOIN subjects s ON asess.SubjectID = s.SubjectID
                WHERE $where_clause
                GROUP BY asess.SessionID, asess.SubjectID, asess.SessionDate, asess.StartTime, asess.EndTime, asess.Status,
                         s.SubjectCode, s.SubjectName, s.ClassName, s.SectionName
                ORDER BY asess.SessionDate DESC, asess.StartTime DESC
            ");
            $stmt->execute($params);
            $reports = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'reports' => $reports]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No subjects found']);
    }
    exit;
}

$teacher_name = $user_name;
$user_initials = strtoupper(substr($teacher_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Attendance Reports</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/reports.css?v=4">
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

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container-fluid reports-container">
            
            <!-- Subject Selection -->
            <div class="subject-selection-section">
                <div class="selection-container">
                    <div class="selection-header">
                        <h3 class="selection-title">Select Subject for Report</h3>
                    </div>
                    <div class="selection-controls">
                        <div class="selection-group">
                            <label for="subjectSelect" class="selection-label">Choose Subject</label>
                            <select id="subjectSelect" class="selection-select">
                                <option value="">-- Select a Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['SubjectID']; ?>">
                                    <?php echo htmlspecialchars($subject['SubjectName'] . ' - ' . $subject['SubjectCode'] . ' (' . $subject['ClassName'] . ' - ' . $subject['SectionName'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn-generate-summary" onclick="generateSubjectReport()" disabled>
                            <i class="bi bi-bar-chart-line me-2"></i>Generate Subject Report
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Subject Report Results -->
            <div class="subject-report-section" id="subjectReportSection" style="display: none;">
                <div class="report-container">
                    <!-- Report content will be loaded here -->
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
                            <label for="filterSubject" class="filter-label">Subject</label>
                            <select class="filter-select" id="filterSubject">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['SubjectID']; ?>">
                                    <?php echo htmlspecialchars($subject['SubjectName'] . ' - ' . $subject['SubjectCode']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filterDateFrom" class="filter-label">From Date</label>
                            <input type="date" class="form-control" id="filterDateFrom">
                        </div>
                        <div class="filter-group">
                            <label for="filterDateTo" class="filter-label">To Date</label>
                            <input type="date" class="form-control" id="filterDateTo">
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
            <div class="reports-section">
                <div class="section-header">
                    <h3>Attendance Records</h3>
                    <p class="section-subtitle">Generate reports for specific attendance sessions</p>
                </div>

                <!-- Sessions List -->
                <div class="sessions-list">
                    <?php if (empty($reports)): ?>
                        <div class="empty-state-container">
                            <div class="empty-state-message">
                                <div class="empty-state-icon">
                                    <i class="bi bi-file-text"></i>
                                </div>
                                <h3 class="empty-state-title">No Sessions Found</h3>
                                <p class="empty-state-description">No attendance sessions have been conducted yet. Go to your dashboard and start an attendance session for any of your classes to generate reports.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                        <div class="session-item">
                            <div class="session-info">
                                <h4 class="session-subject"><?php echo htmlspecialchars($report['SubjectName']); ?></h4>
                                <p class="session-date"><?php echo date('F d, Y', strtotime($report['SessionDate'])); ?></p>
                            </div>
                            <button class="btn-generate-report" onclick="generateReport(<?php echo $report['SessionID']; ?>)">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Session Report
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">
                        <i class="bi bi-funnel"></i> Filter Reports
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="form-section">
                            <div class="form-group">
                                <label for="filterSubject">Subject</label>
                                <select class="form-select" id="filterSubject">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['SubjectID']; ?>">
                                        <?php echo htmlspecialchars($subject['SubjectName'] . ' - ' . $subject['SubjectCode']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filterDateFrom">Date From</label>
                                <input type="date" class="form-control" id="filterDateFrom">
                            </div>
                            <div class="form-group">
                                <label for="filterDateTo">Date To</label>
                                <input type="date" class="form-control" id="filterDateTo">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/js/reports.js?v=7"></script>
</body>
</html>
