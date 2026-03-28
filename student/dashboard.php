<?php
// Start session for user authentication
session_start();

// Check if user is logged in (redirect to login if not)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}

// Get student information from session or use default values for demo
$student_data = isset($_SESSION['student_data']) ? $_SESSION['student_data'] : null;

if ($student_data) {
    // Use separate first_name and last_name from database
    $first_name = $student_data['first_name'] ?? 'Demo';
    $last_name = $student_data['last_name'] ?? 'Student';
    $student_name = trim($first_name . ' ' . $last_name);
    $student_id = $student_data['StudentNumber'] ?? 'STU001234';
    $student_email = $student_data['Email'] ?? 'demo.student@classtrack.edu';
    $student_course = $student_data['Course'] ?? 'Bachelor of Science in Computer Science';
    $student_year = $student_data['YearLevel'] ?? 3;
} else {
    // Fallback to basic session data or defaults
    $first_name = isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Demo';
    $last_name = isset($_SESSION['user_last_name']) ? $_SESSION['user_last_name'] : 'Student';
    $student_name = trim($first_name . ' ' . $last_name);
    $student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 'STU001234';
    $student_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'demo.student@classtrack.edu';
    $student_course = 'Bachelor of Science in Computer Science';
    $student_year = 3;
}

// Convert year level to text format
$year_text = '';
switch($student_year) {
    case 1: $year_text = '1st Year'; break;
    case 2: $year_text = '2nd Year'; break;
    case 3: $year_text = '3rd Year'; break;
    case 4: $year_text = '4th Year'; break;
    case 5: $year_text = '5th Year'; break;
    default: $year_text = $student_year . 'th Year';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Student Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/student_dashboard.css">
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
        <div class="container-fluid">
            <!-- Student Information Section -->
            <div class="row">
                <!-- QR Code & Student Information Combined Card -->
                <div class="col-12">
                    <div class="info-card">
                        <div class="info-card-body">
                            <div class="row">
                                <!-- QR Code Section -->
                                <div class="col-lg-4 col-md-5 text-center qr-section">
                                    <?php
                                    $qr_code_path = '';
                                    if ($student_data && isset($student_data['QRCodePath'])) {
                                        $qr_code_path = '../' . $student_data['QRCodePath'];
                                    }
                                    ?>
                                    <?php if ($qr_code_path && file_exists(__DIR__ . '/../' . $student_data['QRCodePath'])): ?>
                                        <img src="<?php echo htmlspecialchars($qr_code_path); ?>" alt="Student QR Code" class="qr-code-image-large">
                                    <?php else: ?>
                                        <img src="../assets/images/pngimg.com - qr_code_PNG34.png" alt="Student QR Code" class="qr-code-image-large">
                                    <?php endif; ?>
                                    <p class="qr-description">Show this QR code for attendance scanning</p>
                                    <p class="fw-bold text-primary">Student ID: <?php echo htmlspecialchars($student_id); ?></p>
                                    <p class="fw-bold text-secondary"><?php echo htmlspecialchars($student_name); ?></p>
                                </div>

                                <!-- Student Information Section -->
                                <div class="col-lg-8 col-md-7">
                                    <div class="student-info-display">
                                        <div class="row">
                                            <!-- First Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="info-label">First Name</label>
                                                <p class="info-value"><?php echo htmlspecialchars($first_name); ?></p>
                                            </div>
                                            
                                            <!-- Last Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="info-label">Last Name</label>
                                                <p class="info-value"><?php echo htmlspecialchars($last_name); ?></p>
                                            </div>
                                            
                                            <!-- Email -->
                                            <div class="col-md-6 mb-3">
                                                <label class="info-label">Email</label>
                                                <p class="info-value"><?php echo htmlspecialchars($student_email); ?></p>
                                            </div>
                                            
                                            <!-- Course -->
                                            <div class="col-md-6 mb-3">
                                                <label class="info-label">Course</label>
                                                <p class="info-value"><?php echo htmlspecialchars($student_course); ?></p>
                                            </div>
                                            
                                            <!-- Year Level -->
                                            <div class="col-md-6 mb-3">
                                                <label class="info-label">Year Level</label>
                                                <p class="info-value"><?php echo htmlspecialchars($year_text); ?></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="form-actions">
                                                    <a href="../settings_page/settings.php" class="btn btn-outline-primary" style="background-color: #060606; border-color: #060606; color: white; border-radius: 6px;">
                                                        <i class="bi bi-pencil me-1"></i> Edit Profile
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success" style="background-color: #060606; border-color: #060606; color: white; border-radius: 6px;" onclick="downloadQRCode()">
                                                        <i class="bi bi-download me-1"></i> Download QR
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    <script>
        // Pass QR code path to JavaScript
        const qrCodePath = '<?php echo $qr_code_path; ?>';
        const studentId = '<?php echo htmlspecialchars($student_id); ?>';
    </script>
    <script src="../assets/js/dashboard.js?v=1"></script>
</body>
</html>
