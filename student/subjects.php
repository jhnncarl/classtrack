<?php
session_start();

// Check if user is logged in (redirect to login if not)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle AJAX request for auto-reload
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // Return only the class grid content for AJAX requests
    include '../config/database.php';
    include '../config/permissions.php';
    
    // Get user information from session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Get student's enrolled subjects with teacher information
    $enrolled_subjects = [];
    try {
        // First get the student ID from students table
        $student_query = "SELECT StudentID FROM students WHERE UserID = ?";
        $student_stmt = $db->prepare($student_query);
        $student_stmt->execute([$user_id]);
        $student_row = $student_stmt->fetch();
        
        if ($student_row) {
            $student_id = $student_row['StudentID'];
            
            // Get enrolled subjects with teacher information
            $subjects_query = "
                SELECT 
                    sub.SubjectID,
                    sub.SubjectCode,
                    sub.SubjectName,
                    sub.ClassName,
                    sub.SectionName,
                    sub.Schedule,
                    u.first_name as teacher_first_name,
                    u.last_name as teacher_last_name,
                    u.ProfilePicture as teacher_profile
                FROM enrollments e
                JOIN subjects sub ON e.SubjectID = sub.SubjectID
                JOIN teachers t ON sub.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                WHERE e.StudentID = ?
                ORDER BY sub.SubjectName
            ";
            
            $subjects_stmt = $db->prepare($subjects_query);
            $subjects_stmt->execute([$student_id]);
            $enrolled_subjects = $subjects_stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        // Log error but continue with empty array
        error_log("Error fetching enrolled subjects: " . $e->getMessage());
        $enrolled_subjects = [];
    }
    
    // Output only the class grid HTML
    ob_start();
    ?>
    <div class="class-grid">
        <?php if (!empty($enrolled_subjects)): ?>
            <?php 
            $color_classes = ['blue', 'green', 'orange', 'purple', 'red', 'teal'];
            $color_index = 0;
            
            foreach ($enrolled_subjects as $subject): 
                $color_class = $color_classes[$color_index % count($color_classes)];
                $color_index++;
                
                $teacher_name = trim($subject['teacher_first_name'] . ' ' . $subject['teacher_last_name']);
                $teacher_title = strpos($subject['teacher_first_name'], 'Dr.') === 0 || strpos($subject['teacher_last_name'], 'Dr.') === 0 ? 'Dr. ' : 'Prof. ';
                $teacher_display = $teacher_title . $teacher_name;
                
                $teacher_profile = !empty($subject['teacher_profile']) ? '../' . $subject['teacher_profile'] : 'https://picsum.photos/seed/teacher' . $subject['SubjectID'] . '/60/60';
                $section_display = !empty($subject['SectionName']) ? $subject['ClassName'] . ' - ' . $subject['SectionName'] : $subject['ClassName'];
            ?>
            <div class="class-card">
                <div class="card-header <?php echo $color_class; ?>">
                    <h3 class="class-title"><?php echo htmlspecialchars($subject['SubjectName']); ?></h3>
                    <p class="class-section"><?php echo htmlspecialchars($section_display); ?></p>
                    <p class="teacher-name"><?php echo htmlspecialchars($teacher_display); ?></p>
                    <img src="<?php echo htmlspecialchars($teacher_profile); ?>" alt="Teacher" class="teacher-profile">
                </div>
                <div class="card-body">
                    <div class="card-actions">
                        <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory(<?php echo $subject['SubjectID']; ?>, '<?php echo htmlspecialchars($subject['SubjectName']); ?>')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="dropdown more-options-dropdown">
                            <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item unenroll-link" href="#" data-subject-id="<?php echo $subject['SubjectID']; ?>" data-class="<?php echo htmlspecialchars($subject['SubjectName']); ?>">
                                    <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state text-center py-5" style="grid-column: 1 / -1;">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h3 class="mt-3 text-muted">No Classes Enrolled</h3>
                <p class="text-muted">You are not currently enrolled in any classes.</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh Page
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php
    echo ob_get_clean();
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../config/permissions.php';

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Student');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Student';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get student information from database
$student_name = $user_name;
$user_initials = strtoupper(substr($student_name, 0, 2));

// Check if user has permission to unenroll from classes
$permissions = new Permissions();
$canUnenroll = $permissions->hasPermission($user_id, 'student_unenrollClass');

// Get student's enrolled subjects with teacher information
$enrolled_subjects = [];
try {
    // First get the student ID from students table
    $student_query = "SELECT StudentID FROM students WHERE UserID = ?";
    $student_stmt = $db->prepare($student_query);
    $student_stmt->execute([$user_id]);
    $student_row = $student_stmt->fetch();
    
    if ($student_row) {
        $student_id = $student_row['StudentID'];
        
        // Get enrolled subjects with teacher information
        $subjects_query = "
            SELECT 
                sub.SubjectID,
                sub.SubjectCode,
                sub.SubjectName,
                sub.ClassName,
                sub.SectionName,
                sub.Schedule,
                u.first_name as teacher_first_name,
                u.last_name as teacher_last_name,
                u.ProfilePicture as teacher_profile
            FROM enrollments e
            JOIN subjects sub ON e.SubjectID = sub.SubjectID
            JOIN teachers t ON sub.TeacherID = t.TeacherID
            JOIN users u ON t.UserID = u.UserID
            WHERE e.StudentID = ?
            ORDER BY sub.SubjectName
        ";
        
        $subjects_stmt = $db->prepare($subjects_query);
        $subjects_stmt->execute([$student_id]);
        $enrolled_subjects = $subjects_stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    // Log error but continue with empty array
    error_log("Error fetching enrolled subjects: " . $e->getMessage());
    $enrolled_subjects = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - My Subjects</title>
    
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
    <link rel="stylesheet" href="../assets/css/subjects.css?v=12">
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
                <?php if (!empty($enrolled_subjects)): ?>
                    <?php 
                    $color_classes = ['blue', 'green', 'orange', 'purple', 'red', 'teal'];
                    $color_index = 0;
                    
                    foreach ($enrolled_subjects as $subject): 
                        $color_class = $color_classes[$color_index % count($color_classes)];
                        $color_index++;
                        
                        $teacher_name = trim($subject['teacher_first_name'] . ' ' . $subject['teacher_last_name']);
                        $teacher_title = strpos($subject['teacher_first_name'], 'Dr.') === 0 || strpos($subject['teacher_last_name'], 'Dr.') === 0 ? 'Dr. ' : 'Prof. ';
                        $teacher_display = $teacher_title . $teacher_name;
                        
                        $teacher_profile = !empty($subject['teacher_profile']) ? '../' . $subject['teacher_profile'] : 'https://picsum.photos/seed/teacher' . $subject['SubjectID'] . '/60/60';
                        $section_display = !empty($subject['SectionName']) ? $subject['ClassName'] . ' - ' . $subject['SectionName'] : $subject['ClassName'];
                    ?>
                    <div class="class-card">
                        <div class="card-header <?php echo $color_class; ?>">
                            <h3 class="class-title"><?php echo htmlspecialchars($subject['SubjectName']); ?></h3>
                            <p class="class-section"><?php echo htmlspecialchars($section_display); ?></p>
                            <p class="teacher-name"><?php echo htmlspecialchars($teacher_display); ?></p>
                            <img src="<?php echo htmlspecialchars($teacher_profile); ?>" alt="Teacher" class="teacher-profile">
                        </div>
                        <div class="card-body">
                            <div class="card-actions">
                                <button class="action-btn" title="View Attendance Record" onclick="viewAttendanceHistory(<?php echo $subject['SubjectID']; ?>, '<?php echo htmlspecialchars($subject['SubjectName']); ?>')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="dropdown more-options-dropdown">
                                    <button class="action-btn" title="More Options" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item unenroll-link" href="#" data-subject-id="<?php echo $subject['SubjectID']; ?>" data-class="<?php echo htmlspecialchars($subject['SubjectName']); ?>">
                                            <i class="bi bi-person-dash me-2"></i>Unenroll Class
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state text-center py-5" style="grid-column: 1 / -1;">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h3 class="mt-3 text-muted">No Classes Enrolled</h3>
                        <p class="text-muted">You are not currently enrolled in any classes.</p>
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh Page
                        </button>
                    </div>
                <?php endif; ?>
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
    <script>
        // Pass permission status to JavaScript
        window.canUnenroll = <?php echo $canUnenroll ? 'true' : 'false'; ?>;
    </script>
    <script src="../assets/js/subjects.js?v=1"></script>
</body>
</html>
