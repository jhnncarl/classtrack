<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    header('Location: ../auth/login.php');
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get subject code from URL parameter
$subjectCode = $_GET['class'] ?? '';

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Teacher');

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

// Get subject data from database
$currentClass = null;
if ($teacher_id && $subjectCode) {
    try {
        $stmt = $db->prepare("SELECT SubjectID, SubjectCode, SubjectName, ClassName, SectionName, Schedule FROM subjects WHERE TeacherID = ? AND SubjectCode = ?");
        $stmt->execute([$teacher_id, $subjectCode]);
        $subject_data = $stmt->fetch();
        
        if ($subject_data) {
            // Convert to expected format
            $currentClass = [
                'subject_id' => $subject_data['SubjectID'],
                'title' => $subject_data['SubjectName'],
                'section' => $subject_data['ClassName'] . ' - ' . $subject_data['SectionName'],
                'students' => 0, // Will be updated after we get enrolled students
                'schedule' => $subject_data['Schedule'] ?? 'Not specified'
            ];
        }
    } catch(PDOException $e) {
        error_log("Error getting subject data: " . $e->getMessage());
    }
}

// Get enrolled students for this subject
$students = [];
if ($currentClass && $subjectCode) {
    try {
        $stmt = $db->prepare("
            SELECT s.StudentID, s.StudentNumber, s.Course, s.YearLevel, s.UserID,
                   u.first_name, u.last_name, u.ProfilePicture, u.Email
            FROM enrollments e
            JOIN students s ON e.StudentID = s.StudentID
            JOIN users u ON s.UserID = u.UserID
            WHERE e.SubjectID = ?
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$currentClass['subject_id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting enrolled students: " . $e->getMessage());
        // Fallback to empty array if query fails
        $students = [];
    }
}

// Update student count in currentClass
if ($currentClass) {
    $currentClass['students'] = count($students);
}

// Pass student data to JavaScript
$studentDataJson = json_encode($students);

if (!$currentClass) {
    // Redirect back to dashboard if class not found
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - <?php echo htmlspecialchars($currentClass['title']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/view_class.css?v=47">
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
        <div class="class-view-container">
            <!-- Navigation Tabs -->
            <div class="nav-tabs-section">
                <div class="nav-tabs">
                    <button class="nav-tab active" data-tab="stream">
                        <i class="bi bi-house-door"></i>
                        Session
                    </button>
                    <button class="nav-tab" data-tab="people">
                        <i class="bi bi-people"></i>
                        People
                    </button>
                    <button class="nav-tab" data-tab="settings">
                        <i class="bi bi-settings"></i>
                        Settings
                    </button>
                </div>
            </div>

            <!-- Class Header -->
            <div class="class-header">
                <div class="class-info">
                    <h1 class="class-title"><?php echo htmlspecialchars($currentClass['title']); ?></h1>
                    <div class="class-details">
                        <span class="class-section"><?php echo htmlspecialchars($currentClass['section']); ?></span>
                        <span class="separator">•</span>
                        <span class="class-students"><?php echo $currentClass['students']; ?> <?php echo ($currentClass['students'] === 1) ? 'Student' : 'Students'; ?></span>
                    </div>
                </div>
                <div class="class-actions">
                    <div class="subject-code-display" onclick="copySubjectCode('<?php echo $subjectCode; ?>')">
                        <i class="bi bi-clipboard"></i>
                        <?php echo htmlspecialchars($subjectCode); ?>
                    </div>
                </div>
            </div>

            <!-- Stream Tab Content -->
            <div class="tab-content" id="stream-content">
                <!-- Class Schedule Card -->
                <div class="schedule-card">
                    <div class="card-header-section">
                        <i class="bi bi-calendar3"></i>
                        <h3>Class Schedule</h3>
                    </div>
                    <div class="schedule-details">
                        <div class="schedule-item">
                            <i class="bi bi-clock"></i>
                            <span><?php echo htmlspecialchars($currentClass['schedule']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Start Session Button -->
                <div class="session-section">
                    <button class="btn-session" type="button" onclick="startAttendanceSession('<?php echo $subjectCode; ?>')">
                        <i class="bi bi-play-circle"></i>
                        Start Session
                    </button>
                </div>
            </div>

            <!-- People Tab Content -->
            <div class="tab-content" id="people-content">
                <!-- Students Section -->
                <div class="students-section">
                    <div class="section-header">
                        <h3>Enrolled Students</h3>
                        <div class="student-stats">
                            <span class="stat-item">
                                <i class="bi bi-people"></i>
                                <span><?php echo count($students ?? []); ?> <?php echo (count($students ?? []) === 1) ? 'Student' : 'Students'; ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Search students..." id="studentSearch">
                    </div>

                    <!-- Students List -->
                    <div class="students-list">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                            <div class="student-item" data-name="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name']); ?>">
                                <div class="student-info">
                                    <div class="student-avatar">
                                    <?php 
                                    // Check if ProfilePicture exists and belongs to this student (not teacher)
                                    $hasValidProfile = !empty($student['ProfilePicture']) && 
                                                     strpos($student['ProfilePicture'], 'profile_' . $student['UserID'] . '_') !== false;
                                    ?>
                                    <?php if ($hasValidProfile): ?>
                                        <?php 
                                        // Fix the path to point to root uploads directory from teacher subdirectory
                                        $profilePath = $student['ProfilePicture'];
                                        // Ensure path starts with ../uploads/ from teacher subdirectory
                                        if (strpos($profilePath, '../uploads/') !== 0) {
                                            $profilePath = str_replace('uploads/', '../uploads/', $profilePath);
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profilePath); ?>" alt="Profile" class="profile-img" 
                                             onerror="console.error('Failed to load image for student <?php echo $student['StudentID']; ?> (User ID: <?php echo $student['UserID']; ?>):', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <i class="bi bi-person-circle" style="display:none;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-person-circle"></i>
                                    <?php endif; ?>
                                </div>
                                    <div class="student-details">
                                        <h4 class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                        <p class="student-id"><?php echo htmlspecialchars($student['StudentNumber']); ?></p>
                                    </div>
                                </div>
                                <div class="student-actions">
                                    <!-- Desktop/Tablet View -->
                                    <button class="btn-small btn-view-profile d-none d-md-flex" onclick="viewStudentProfile('<?php echo htmlspecialchars($student['StudentID']); ?>')">
                                        <i class="bi bi-person"></i>
                                        View Profile
                                    </button>
                                    
                                    <!-- Mobile View -->
                                    <div class="dropdown d-md-none">
                                        <button class="btn-dots" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="viewStudentProfile('<?php echo htmlspecialchars($student['StudentID']); ?>'); return false;">
                                                    <i class="bi bi-person"></i>
                                                    View Profile
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-students-message">
                                <i class="bi bi-person-x"></i>
                                <p>No students enrolled in this class yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Settings Tab Content -->
            <div class="tab-content" id="settings-content">
                <div class="settings-section">
                    <div class="section-header">
                        <h3>Class Settings</h3>
                    </div>
                    <div class="settings-list">
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>Class Information</h4>
                                <p>Update class details, schedule, and room information</p>
                            </div>
                            <div class="setting-actions">
                                <button class="btn-primary btn-small" onclick="openUpdateClassModal()">
                                    <i class="bi bi-pencil"></i>
                                    Edit
                                </button>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>Attendance Settings</h4>
                                <p>Configure attendance rules and notifications</p>
                            </div>
                            <div class="setting-actions">
                                <button class="btn-primary btn-small" onclick="showNotAvailableNotification()">
                                    <i class="bi bi-gear"></i>
                                    Configure
                                </button>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>Student Management</h4>
                                <p>Add or remove students from the class</p>
                            </div>
                            <div class="setting-actions">
                                <button class="btn-primary btn-small" onclick="openManageStudentsModal()">
                                    <i class="bi bi-people"></i>
                                    Manage
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1" aria-labelledby="studentProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentProfileModalLabel">Student Profile</h5>
                </div>
                <div class="modal-body">
                    <div class="profile-header">
                        <div class="profile-avatar-container">
                            <div id="modalProfileAvatar" class="profile-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                        </div>
                        <div id="modalStudentNumber" class="student-number"></div>
                        <div class="attendance-indicators">
                            <div class="indicator-item">
                                <div class="circular-progress present">
                                    <span class="percentage-text">0%</span>
                                </div>
                                <span class="indicator-label">Present</span>
                            </div>
                            <div class="indicator-item">
                                <div class="circular-progress late">
                                    <span class="percentage-text">0%</span>
                                </div>
                                <span class="indicator-label">Late</span>
                            </div>
                            <div class="indicator-item">
                                <div class="circular-progress absent">
                                    <span class="percentage-text">0%</span>
                                </div>
                                <span class="indicator-label">Absent</span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-info">
                        <div class="info-item">
                            <label>Full Name:</label>
                            <span id="modalFullName"></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span id="modalEmail"></span>
                        </div>
                        <div class="info-item">
                            <label>Course & Year Level: </label>
                            <span id="modalCourseYear"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Class Modal -->
    <div class="modal fade" id="updateClassModal" tabindex="-1" aria-labelledby="updateClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateClassModalLabel">Update Class Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateClassForm">
                        <div class="form-section">
                            <div class="form-group">
                                <label for="updateSubjectName">Subject Name</label>
                                <input type="text" id="updateSubjectName" name="subjectName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="updateClassName">Class Name</label>
                                <input type="text" id="updateClassName" name="className" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="updateSection">Section</label>
                                <input type="text" id="updateSection" name="section" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="updateSchedule">Schedule</label>
                                <input type="text" id="updateSchedule" name="schedule" class="form-control" placeholder="e.g., MWF 8:00-9:00 AM" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-primary" onclick="updateClassDetails()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Students Modal -->
    <div class="modal fade" id="manageStudentsModal" tabindex="-1" aria-labelledby="manageStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageStudentsModalLabel">Manage Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Search and Add Section -->
                    <div class="manage-students-header">
                        <div class="search-add-container">
                            <div class="search-bar">
                                <i class="bi bi-search"></i>
                                <input type="text" placeholder="Search students..." id="manageStudentSearch">
                            </div>
                            <button class="btn-add-student" onclick="openAddStudentModal()">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Students List -->
                    <div class="manage-students-list">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                            <div class="manage-student-item" data-name="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name']); ?>" data-id="<?php echo $student['StudentID']; ?>">
                                <div class="student-info">
                                    <div class="student-avatar">
                                    <?php 
                                    $hasValidProfile = !empty($student['ProfilePicture']) && 
                                                     strpos($student['ProfilePicture'], 'profile_' . $student['UserID'] . '_') !== false;
                                    ?>
                                    <?php if ($hasValidProfile): ?>
                                        <?php 
                                        $profilePath = $student['ProfilePicture'];
                                        if (strpos($profilePath, '../uploads/') !== 0) {
                                            $profilePath = str_replace('uploads/', '../uploads/', $profilePath);
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($profilePath); ?>" alt="Profile" class="profile-img" 
                                             onerror="console.error('Failed to load image for student <?php echo $student['StudentID']; ?> (User ID: <?php echo $student['UserID']; ?>):', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <i class="bi bi-person-circle" style="display:none;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-person-circle"></i>
                                    <?php endif; ?>
                                </div>
                                    <div class="student-details">
                                        <h4 class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                        <p class="student-id"><?php echo htmlspecialchars($student['StudentNumber']); ?></p>
                                    </div>
                                </div>
                                <div class="student-actions">
                                    <!-- Desktop/Tablet View -->
                                    <button class="btn-remove-student d-none d-md-flex" onclick="removeStudent('<?php echo htmlspecialchars($student['StudentID']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                        <i class="bi bi-person-dash"></i>
                                        Remove
                                    </button>
                                    
                                    <!-- Mobile View -->
                                    <div class="dropdown d-md-none dropdown-end">
                                        <button class="btn-dots" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="removeStudent('<?php echo htmlspecialchars($student['StudentID']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>'); return false;">
                                                    <i class="bi bi-person-dash"></i>
                                                    Remove
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-students-message">
                                <i class="bi bi-person-x"></i>
                                <p>No students enrolled in this class yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="form-section">
                            <div class="form-group">
                                <label for="studentEmail">Student Email</label>
                                <input type="email" id="studentEmail" name="studentEmail" class="form-control" placeholder="Enter student's email address" required>
                                <small class="form-text text-muted">Enter the email address of the student you want to add to this class.</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-primary" onclick="addStudent()">Add Student</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
            <div class="modal-content join-class-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="join-class-header" style="padding: 32px 32px 24px 32px !important;">
                        <h5 class="join-class-title" id="confirmModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Remove Student</h5>
                    </div>

                    <!-- Confirmation Message Section -->
                    <div class="account-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="class-code-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important;">
                            <p class="class-code-description" id="confirmText" style="color: #202124 !important; font-size: 16px !important; margin: 0 !important; line-height: 1.5 !important; font-family: 'Montserrat', sans-serif !important;">Are you sure you want to remove this student from the class?</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                        <button type="button" class="btn-join" id="confirmBtn" style="background: #060606 !important; border: none !important; color: #ffffff !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: all 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/js/view_class.js?v=14"></script>
    
    <!-- Data for JavaScript -->
    <script>
        // Make data available to external JS file
        window.studentData = <?php echo $studentDataJson; ?>;
        window.currentClassData = <?php echo json_encode($currentClass); ?>;
    </script>
</body>
</html>
