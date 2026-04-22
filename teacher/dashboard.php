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

$teacher_name = $user_name;
$user_initials = strtoupper(substr($teacher_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - My Classes</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/teacher_dashboard.css">
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
        <div class="container-fluid dashboard-container">
            <!-- Class Cards Grid -->
            <div class="class-grid">
                <?php if (empty($subjects)): ?>
                    <!-- No subjects found -->
                    <div class="empty-state-container">
                        <div class="empty-state-message">
                            <div class="empty-state-icon">
                                <i class="bi bi-book"></i>
                            </div>
                            <h3 class="empty-state-title">No Classes Found</h3>
                            <p class="empty-state-description">You haven't created any classes yet. Click the ➕ (plus icon) in the navigation bar and select 'Create Class' to get started.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($subjects as $index => $subject): ?>
                        <?php 
                        // Define color classes for cards
                        $color_classes = ['blue', 'green', 'orange', 'purple', 'red', 'teal'];
                        $color_class = $color_classes[$index % count($color_classes)];
                        ?>
                        <div class="class-card">
                            <div class="card-header <?php echo $color_class; ?>">
                                <a href="class_view.php?class=<?php echo urlencode($subject['SubjectCode']); ?>" class="class-title-link">
                                    <h3 class="class-title"><?php echo htmlspecialchars($subject['SubjectName']); ?></h3>
                                </a>
                                <p class="class-section"><?php echo htmlspecialchars($subject['SectionName']); ?></p>
                            </div>
                            <div class="card-body">
                                <!-- Card body content without action buttons -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="../assets/js/teacher_dashboard.js?v=17"></script>
</body>
</html>
