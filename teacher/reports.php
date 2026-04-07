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

// Get attendance reports data
$reports = [];
if ($teacher_id && !empty($subjects)) {
    try {
        $subject_ids = array_column($subjects, 'SubjectID');
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        
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
                COUNT(a.RecordID) as total_students,
                SUM(CASE WHEN a.AttendanceStatus = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.AttendanceStatus = 'Late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.AttendanceStatus IS NULL THEN 1 ELSE 0 END) as absent_count
            FROM attendancesessions asess
            JOIN subjects s ON asess.SubjectID = s.SubjectID
            LEFT JOIN attendancerecords a ON asess.SessionID = a.SessionID
            WHERE asess.SubjectID IN ($placeholders)
            GROUP BY asess.SessionID, asess.SubjectID, asess.SessionDate, asess.StartTime, asess.EndTime, asess.Status,
                     s.SubjectCode, s.SubjectName, s.ClassName, s.SectionName
            ORDER BY asess.SessionDate DESC, asess.StartTime DESC
        ");
        $stmt->execute($subject_ids);
        $reports = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error getting reports: " . $e->getMessage());
    }
}

$teacher_name = $user_name;
$user_initials = strtoupper(substr($teacher_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Generated Reports</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/reports.css?v=1">
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
            
            <!-- Filter Attendance Records -->
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
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['SubjectID']; ?>">
                                    <?php echo htmlspecialchars($subject['SubjectName']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="monthFilter" class="filter-label">Month</label>
                            <select id="monthFilter" class="filter-select">
                                <option value="all">All Months</option>
                                <?php 
                                // Generate month options for the last 6 months
                                $months = [];
                                for ($i = 0; $i < 6; $i++) {
                                    $date = new DateTime();
                                    $date->modify("-$i month");
                                    $monthValue = $date->format('Y-m');
                                    $monthLabel = $date->format('F Y');
                                    $months[$monthValue] = $monthLabel;
                                }
                                foreach ($months as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
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
            
            <!-- Reports Table -->
            <div class="reports-section">
                <div class="section-header">
                    <h3>Attendance Records</h3>
                </div>

                <!-- Sessions List -->
                <div class="sessions-list">
                    <?php if (empty($reports)): ?>
                        <div class="empty-state-container">
                            <div class="empty-state-message">
                                <div class="empty-state-icon">
                                    <i class="bi bi-file-text"></i>
                                </div>
                                <h3 class="empty-state-title">No Reports Found</h3>
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
                                Generate Report
                            </button>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Empty state for filtered results -->
                        <div class="empty-state" style="display: none;">
                            <div class="empty-state-container">
                                <div class="empty-state-message">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <h3 class="empty-state-title">No Sessions Found</h3>
                                    <p class="empty-state-description">No attendance sessions match your current filter criteria. Try adjusting your filters to see different results.</p>
                                </div>
                            </div>
                        </div>
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
                            <div class="form-group">
                                <label for="filterStatus">Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Paused">Paused</option>
                                </select>
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
    <script src="../assets/js/reports.js?v=5"></script>
</body>
</html>
