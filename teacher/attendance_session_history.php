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

// Sample data for demonstration (not connected to database)
$sample_sessions = [
    [
        'SessionID' => 1,
        'SubjectID' => 101,
        'SubjectName' => 'Advanced Web Development',
        'SubjectCode' => 'CS-301',
        'ClassName' => 'BSIT 3A',
        'SectionName' => 'Morning',
        'SessionDate' => '2026-04-08',
        'StartTime' => '09:00:00',
        'EndTime' => '10:00:00',
        'Status' => 'Completed',
        'total_students' => 25,
        'present_count' => 22,
        'late_count' => 2,
        'absent_count' => 1
    ],
    [
        'SessionID' => 2,
        'SubjectID' => 102,
        'SubjectName' => 'Database Management Systems',
        'SubjectCode' => 'CS-302',
        'ClassName' => 'BSIT 3B',
        'SectionName' => 'Afternoon',
        'SessionDate' => '2026-04-07',
        'StartTime' => '13:00:00',
        'EndTime' => '14:00:00',
        'Status' => 'Completed',
        'total_students' => 30,
        'present_count' => 28,
        'late_count' => 1,
        'absent_count' => 1
    ],
    [
        'SessionID' => 3,
        'SubjectID' => 103,
        'SubjectName' => 'Software Engineering',
        'SubjectCode' => 'CS-303',
        'ClassName' => 'BSIT 4A',
        'SectionName' => 'Morning',
        'SessionDate' => '2026-04-06',
        'StartTime' => '10:00:00',
        'EndTime' => '11:00:00',
        'Status' => 'Active',
        'total_students' => 28,
        'present_count' => 15,
        'late_count' => 3,
        'absent_count' => 10
    ]
];

$sample_subjects = [
    ['SubjectID' => 101, 'SubjectName' => 'Advanced Web Development', 'SubjectCode' => 'CS-301'],
    ['SubjectID' => 102, 'SubjectName' => 'Database Management Systems', 'SubjectCode' => 'CS-302'],
    ['SubjectID' => 103, 'SubjectName' => 'Software Engineering', 'SubjectCode' => 'CS-303']
];

$teacher_name = $user_name;
$user_initials = strtoupper(substr($teacher_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Attendance Session History</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/attendance_session_history.css?v=38">
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
                                <?php foreach ($sample_subjects as $subject): ?>
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
                                <option value="Paused">Paused</option>
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
                    <?php if (empty($sample_sessions)): ?>
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
                        <?php foreach ($sample_sessions as $session): ?>
                        <div class="session-item" data-subject-id="<?php echo $session['SubjectID']; ?>" data-status="<?php echo $session['Status']; ?>" data-date="<?php echo $session['SessionDate']; ?>">
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
                                <span class="status-badge <?php echo strtolower($session['Status']); ?>"><?php echo $session['Status']; ?></span>
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
    <script src="../assets/js/attendance_session_history.js?v=7"></script>
</body>
</html>
