<?php
session_start();

// Check if user is logged in (redirect to login if not)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Student');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student';

// Get subject information from URL parameter or default
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : 'WEBDEV101';
$subject_name = isset($_GET['subject_name']) ? $_GET['subject_name'] : 'Web Development';

// Mock subject data (in real implementation, this would come from database)
$subjects_data = [
    'WEBDEV101' => ['name' => 'Web Development', 'section' => 'CS-301', 'teacher' => 'Prof. Sarah Johnson'],
    'DATA201' => ['name' => 'Data Structures', 'section' => 'CS-201', 'teacher' => 'Dr. Michael Chen'],
    'DB302' => ['name' => 'Database Systems', 'section' => 'CS-302', 'teacher' => 'Prof. Emily Davis'],
    'ML401' => ['name' => 'Machine Learning', 'section' => 'CS-401', 'teacher' => 'Dr. Robert Wilson'],
    'MOB351' => ['name' => 'Mobile Development', 'section' => 'CS-351', 'teacher' => 'Prof. Lisa Anderson'],
    'NET251' => ['name' => 'Computer Networks', 'section' => 'CS-251', 'teacher' => 'Dr. James Martinez']
];

$current_subject = isset($subjects_data[$subject_id]) ? $subjects_data[$subject_id] : $subjects_data['WEBDEV101'];
$subject_name = $current_subject['name'];

// Mock attendance data (in real implementation, this would come from database)
$attendance_data = [
    [
        'date' => '2024-03-15',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-13',
        'day' => 'Wednesday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-11',
        'day' => 'Monday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'absent',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-08',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-06',
        'day' => 'Wednesday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'late',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-04',
        'day' => 'Monday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => $subject_name
    ],
    [
        'date' => '2024-03-01',
        'day' => 'Friday',
        'time' => '10:00 AM - 11:30 AM',
        'status' => 'present',
        'subject_name' => $subject_name
    ]
];

$user_initials = strtoupper(substr($user_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - <?php echo htmlspecialchars($subject_name); ?> - Attendance Records</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/attendance.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="stylesheet" href="../assets/css/join_button_fix.css">
    
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
                        // Calculate percentages
                        $total_classes = count($attendance_data);
                        $present_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'present'));
                        $absent_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'absent'));
                        $late_count = count(array_filter($attendance_data, fn($a) => $a['status'] === 'late'));
                        
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
                            <label for="dateFilter" class="filter-label">Date Range</label>
                            <select id="dateFilter" class="filter-select">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="custom">Custom Range</option>
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
                        <div class="filter-group">
                            <label for="monthFilter" class="filter-label">Month</label>
                            <select id="monthFilter" class="filter-select">
                                <option value="all">All Months</option>
                                <option value="2024-03">March 2024</option>
                                <option value="2024-02">February 2024</option>
                                <option value="2024-01">January 2024</option>
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
    <script src="../assets/js/attendance.js"></script>
</body>
</html>
