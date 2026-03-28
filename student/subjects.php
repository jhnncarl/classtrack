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

// For now, we'll use static data
// Later this will be replaced with database queries
$student_name = $user_name;
$user_initials = strtoupper(substr($student_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - My Subjects</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/subjects.css?v=2">
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
        <div class="container-fluid subjects-container">

            <!-- Class Cards Grid -->
            <div class="class-grid">
            <!-- Class Card 1 - Web Development -->
            <div class="class-card">
                <div class="card-header blue">
                    <h3 class="class-title">Web Development</h3>
                    <p class="class-section">CS-301</p>
                    <p class="teacher-name">Prof. Sarah Johnson</p>
                    <img src="https://picsum.photos/seed/teacher1/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('WEBDEV101')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Web Development">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card 2 - Data Structures -->
            <div class="class-card">
                <div class="card-header green">
                    <h3 class="class-title">Data Structures</h3>
                    <p class="class-section">CS-201</p>
                    <p class="teacher-name">Dr. Michael Chen</p>
                    <img src="https://picsum.photos/seed/teacher2/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('DATA201')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Data Structures">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card 3 - Database Systems -->
            <div class="class-card">
                <div class="card-header orange">
                    <h3 class="class-title">Database Systems</h3>
                    <p class="class-section">CS-302</p>
                    <p class="teacher-name">Prof. Emily Davis</p>
                    <img src="https://picsum.photos/seed/teacher3/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('DB302')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Database Systems">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card 4 - Machine Learning -->
            <div class="class-card">
                <div class="card-header purple">
                    <h3 class="class-title">Machine Learning</h3>
                    <p class="class-section">CS-401</p>
                    <p class="teacher-name">Dr. Robert Wilson</p>
                    <img src="https://picsum.photos/seed/teacher4/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('ML401')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Machine Learning">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card 5 - Mobile Development -->
            <div class="class-card">
                <div class="card-header red">
                    <h3 class="class-title">Mobile Development</h3>
                    <p class="class-section">CS-351</p>
                    <p class="teacher-name">Prof. Lisa Anderson</p>
                    <img src="https://picsum.photos/seed/teacher5/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('MOB351')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Mobile Development">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card 6 - Computer Networks -->
            <div class="class-card">
                <div class="card-header teal">
                    <h3 class="class-title">Computer Networks</h3>
                    <p class="class-section">CS-251</p>
                    <p class="teacher-name">Dr. James Martinez</p>
                    <img src="https://picsum.photos/seed/teacher6/60/60" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory('NET251')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-class="Computer Networks">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>
    
    <!-- Unenroll Confirmation Modal -->
    <div class="modal fade" id="unenrollModal" tabindex="-1" aria-labelledby="unenrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
            <div class="modal-content join-class-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="join-class-header" style="padding: 32px 32px 24px 32px !important;">
                        <h5 class="join-class-title" id="unenrollModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Confirm Unenrollment</h5>
                    </div>

                    <!-- Confirmation Message Section -->
                    <div class="account-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="class-code-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important;">
                            <p class="class-code-description" style="color: #202124 !important; font-size: 16px !important; margin: 0 !important; line-height: 1.5 !important; font-family: 'Montserrat', sans-serif !important;">Are you sure you want to unenroll from <strong id="unenrollClassName" style="color: #202124 !important;"></strong>?</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                        <button type="button" class="btn-join" id="confirmUnenrollBtn" style="background: #060606 !important; border: none !important; color: #ffffff !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: all 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Yes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/js/subjects.js"></script>
</body>
</html>
