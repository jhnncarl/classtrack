<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Teacher');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Teacher';

// Include database configuration
require_once '../config/database.php';

// Get teacher ID from session
$user_id = $_SESSION['user_id'];

// Get attendance sessions from database for this teacher
try {
    // Get TeacherID from teachers table
    $teacher_stmt = $db->prepare("SELECT t.TeacherID FROM teachers t JOIN users u ON t.UserID = u.UserID WHERE u.UserID = ? AND u.Role = 'Teacher'");
    $teacher_stmt->execute([$user_id]);
    $teacher_data = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
    $teacher_id = $teacher_data ? $teacher_data['TeacherID'] : null;
    
    if (!$teacher_id) {
        throw new Exception('Teacher not found');
    }
    
    // Query to get sessions with subject information and attendance statistics
    $query = "SELECT 
                s.SessionID,
                s.SubjectID,
                s.SessionDate,
                s.StartTime,
                s.EndTime,
                s.Status,
                sub.SubjectName,
                sub.SubjectCode,
                sub.ClassName,
                sub.SectionName,
                (SELECT COUNT(*) FROM enrollments e WHERE e.SubjectID = s.SubjectID) as total_students,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Present') as present_count,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Late') as late_count,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Absent') as absent_count
              FROM attendancesessions s
              JOIN subjects sub ON s.SubjectID = sub.SubjectID
              WHERE sub.TeacherID = ?
              ORDER BY s.SessionDate DESC, s.StartTime DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$teacher_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get subjects for this teacher
    $subject_query = "SELECT SubjectID, SubjectName, SubjectCode FROM subjects WHERE TeacherID = ? ORDER BY SubjectName";
    $subject_stmt = $db->prepare($subject_query);
    $subject_stmt->execute([$teacher_id]);
    $subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $sessions = [];
    $subjects = [];
} catch(Exception $e) {
    error_log("Error: " . $e->getMessage());
    $sessions = [];
    $subjects = [];
}

// Helper function to map database status to display status
function mapStatus($status) {
    return $status === 'Closed' ? 'Completed' : $status;
}

$teacher_name = $user_name;
$user_initials = strtoupper(substr($teacher_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Attendance Session History</title>
    
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
    <link rel="stylesheet" href="../assets/css/attendance_session_history.css">
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
        <div class="container-fluid session-history-container">
            
            <!-- Filter Attendance Sessions -->
            <div class="filter-section">
                <div class="filter-container">
                    <div class="filter-header">
                        <h3 class="filter-title">Filter Attendance Sessions</h3>
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
                            <label for="statusFilter" class="filter-label">Status</label>
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
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
            
            <!-- Sessions Section -->
            <div class="sessions-section">
                <div class="section-header">
                    <h3>Attendance Session History</h3>
                </div>

                <!-- Sessions List -->
                <div class="sessions-list" id="listView">
                    <?php if (empty($sessions)): ?>
                        <div class="empty-state-container">
                            <div class="empty-state-message">
                                <div class="empty-state-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <h3 class="empty-state-title">No Sessions Found</h3>
                                <p class="empty-state-description">No attendance sessions have been conducted yet. Go to your dashboard and start an attendance session for any of your classes to see session history.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                        <div class="session-item" data-subject-id="<?php echo $session['SubjectID']; ?>" data-status="<?php echo mapStatus($session['Status']); ?>" data-date="<?php echo $session['SessionDate']; ?>">
                            <div class="session-info">
                                <h4 class="session-subject"><?php echo htmlspecialchars($session['SubjectName']); ?></h4>
                                <p class="session-details">
                                    <i class="bi bi-calendar3"></i> <?php echo date('F d, Y', strtotime($session['SessionDate'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-clock"></i> <?php echo date('h:i A', strtotime($session['StartTime'])); ?> - <?php echo date('h:i A', strtotime($session['EndTime'])); ?>
                                </p>
                                <div class="session-stats">
                                    <span class="stat-badge present"><?php echo $session['present_count']; ?> Present</span>
                                    <span class="stat-badge late"><?php echo $session['late_count']; ?> Late</span>
                                    <span class="stat-badge absent"><?php echo $session['absent_count']; ?> Absent</span>
                                </div>
                            </div>
                            <div class="session-actions">
                                <span class="status-badge <?php echo strtolower(mapStatus($session['Status'])); ?>"><?php echo mapStatus($session['Status']); ?></span>
                                <button class="btn-view-details" onclick="viewSessionDetails(<?php echo $session['SessionID']; ?>)">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Empty state for filtered results -->
                        <div class="empty-state" style="display: none;" id="filteredEmptyState">
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

    <!-- Session Details Modal -->
    <div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-labelledby="sessionDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom">
            <div class="modal-content session-details-modal">
                <div class="modal-body p-0">
                    <!-- Header -->
                    <div class="session-details-header">
                        <h5 class="session-details-title" id="sessionDetailsModalLabel">Session Details</h5>
                        <span class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </span>
                    </div>

                    <!-- Modal Content -->
                    <div class="account-section">
                        <!-- Desktop & Tablet: Two Column Layout -->
                        <div class="row d-none d-md-flex">
                            <!-- Left Column: Session Details -->
                            <div class="col-md-6">
                                <div class="session-details-section">
                                    <h6 class="section-subtitle">Session Information</h6>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label>Subject</label>
                                            <span id="modalSubject">Advanced Web Development</span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Class</label>
                                            <span id="modalClass">BSIT 3A - Morning</span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Date</label>
                                            <span id="modalDate">April 8, 2026</span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Time</label>
                                            <span id="modalTime">9:00 AM - 10:00 AM</span>
                                        </div>
                                        <div class="detail-item">
                                            <label>Status</label>
                                            <span id="modalStatus" class="status-badge completed">Completed</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Attendance Visualization -->
                            <div class="col-md-6">
                                <div class="attendance-visualization-section">
                                    <h6 class="section-subtitle">Attendance Distribution</h6>
                                    <div class="circular-graph-container">
                                        <canvas id="attendanceChart" width="200" height="200"></canvas>
                                        <div class="chart-center-text">
                                            <div class="total-students" id="totalStudents">25</div>
                                            <div class="total-label">Total Students</div>
                                        </div>
                                    </div>
                                    <div class="attendance-indicators">
                                        <div class="indicator-item present">
                                            <div class="indicator-color"></div>
                                            <div class="indicator-text">
                                                <span class="indicator-label">Present</span>
                                                <span class="indicator-value" id="presentCount">22</span>
                                            </div>
                                            <div class="indicator-percentage" id="presentPercentage">88%</div>
                                        </div>
                                        <div class="indicator-item late">
                                            <div class="indicator-color"></div>
                                            <div class="indicator-text">
                                                <span class="indicator-label">Late</span>
                                                <span class="indicator-value" id="lateCount">2</span>
                                            </div>
                                            <div class="indicator-percentage" id="latePercentage">8%</div>
                                        </div>
                                        <div class="indicator-item absent">
                                            <div class="indicator-color"></div>
                                            <div class="indicator-text">
                                                <span class="indicator-label">Absent</span>
                                                <span class="indicator-value" id="absentCount">1</span>
                                            </div>
                                            <div class="indicator-percentage" id="absentPercentage">4%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile View: Stacked Layout -->
                        <div class="d-md-none">
                            <!-- Top Section: Attendance Rate -->
                            <div class="mobile-attendance-section">
                                <h6 class="section-subtitle">Attendance Rate</h6>
                                <div class="circular-graph-container">
                                    <canvas id="mobileAttendanceChart" width="180" height="180"></canvas>
                                    <div class="chart-center-text">
                                        <div class="total-students" id="mobileTotalStudents">25</div>
                                        <div class="total-label">Total Students</div>
                                    </div>
                                </div>
                                <div class="attendance-indicators">
                                    <div class="indicator-item present">
                                        <div class="indicator-color"></div>
                                        <div class="indicator-text">
                                            <span class="indicator-label">Present</span>
                                            <span class="indicator-value" id="mobilePresentCount">22</span>
                                        </div>
                                        <div class="indicator-percentage" id="mobilePresentPercentage">88%</div>
                                    </div>
                                    <div class="indicator-item late">
                                        <div class="indicator-color"></div>
                                        <div class="indicator-text">
                                            <span class="indicator-label">Late</span>
                                            <span class="indicator-value" id="mobileLateCount">2</span>
                                        </div>
                                        <div class="indicator-percentage" id="mobileLatePercentage">8%</div>
                                    </div>
                                    <div class="indicator-item absent">
                                        <div class="indicator-color"></div>
                                        <div class="indicator-text">
                                            <span class="indicator-label">Absent</span>
                                            <span class="indicator-value" id="mobileAbsentCount">1</span>
                                        </div>
                                        <div class="indicator-percentage" id="mobileAbsentPercentage">4%</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Section: Session Details -->
                            <div class="mobile-session-section">
                                <h6 class="section-subtitle">Session Details</h6>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <label>Subject</label>
                                        <span id="mobileSubject">Advanced Web Development</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Class</label>
                                        <span id="mobileClass">BSIT 3A - Morning</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Date</label>
                                        <span id="mobileDate">April 8, 2026</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Time</label>
                                        <span id="mobileTime">9:00 AM - 10:00 AM</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Status</label>
                                        <span id="mobileStatus" class="status-badge completed">Completed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js for circular graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/js/attendance_session_history.js"></script>
</body>
</html>
