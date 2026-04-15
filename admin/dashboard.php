<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Start session
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/admin/admin_login.php');
    exit();
}



// Database connection
require_once '../config/database.php';

try {
    // Get total users count
    $totalUsersQuery = "SELECT COUNT(*) as total_users FROM users WHERE AccountStatus = 'Active'";
    $totalUsersStmt = $db->prepare($totalUsersQuery);
    $totalUsersStmt->execute();
    $totalUsers = $totalUsersStmt->fetchColumn();
    
    // Get users by role
    $usersByRoleQuery = "SELECT Role, COUNT(*) as count FROM users WHERE AccountStatus = 'Active' GROUP BY Role";
    $usersByRoleStmt = $db->prepare($usersByRoleQuery);
    $usersByRoleStmt->execute();
    $usersByRole = $usersByRoleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total classes count
    $totalClassesQuery = "SELECT COUNT(*) as total_classes FROM subjects";
    $totalClassesStmt = $db->prepare($totalClassesQuery);
    $totalClassesStmt->execute();
    $totalClasses = $totalClassesStmt->fetchColumn();
    
    // Get total sessions count
    $totalSessionsQuery = "SELECT COUNT(*) as total_sessions FROM attendancesessions";
    $totalSessionsStmt = $db->prepare($totalSessionsQuery);
    $totalSessionsStmt->execute();
    $totalSessions = $totalSessionsStmt->fetchColumn();
    
    // Get recent sessions with teacher names
    $recentSessionsQuery = "
        SELECT 
            s.SessionID,
            sub.ClassName,
            u.first_name as FirstName,
            u.last_name as LastName,
            CONCAT(s.SessionDate, ' ', s.StartTime) as StartTime,
            CASE 
                WHEN s.EndTime IS NOT NULL THEN CONCAT(s.SessionDate, ' ', s.EndTime)
                ELSE NULL
            END as EndTime,
            s.Status
        FROM attendancesessions s
        JOIN subjects sub ON s.SubjectID = sub.SubjectID
        JOIN teachers t ON sub.TeacherID = t.TeacherID
        JOIN users u ON t.UserID = u.UserID
        ORDER BY s.SessionDate DESC, s.StartTime DESC
        LIMIT 5
    ";
    $recentSessionsStmt = $db->prepare($recentSessionsQuery);
    $recentSessionsStmt->execute();
    $recentSessions = $recentSessionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get new users (last 7 days)
    $newUsersQuery = "SELECT COUNT(*) as new_users_7_days FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active'";
    $newUsersStmt = $db->prepare($newUsersQuery);
    $newUsersStmt->execute();
    $newUsers7Days = $newUsersStmt->fetchColumn();
    
    // Get session completion rate
    $completionRateQuery = "SELECT ROUND(COUNT(CASE WHEN Status = 'Closed' THEN 1 END) * 100.0 / COUNT(*), 1) as completion_rate FROM attendancesessions";
    $completionRateStmt = $db->prepare($completionRateQuery);
    $completionRateStmt->execute();
    $completionRate = $completionRateStmt->fetchColumn();
    
    // Calculate trend percentages (comparing current period vs previous period)
    // Users trend (last 7 days vs previous 7 days)
    $usersTrendQuery = "
        SELECT 
            ROUND(
                ((SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active') -
                 (SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND CreatedAt < DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active')) /
                 NULLIF((SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND CreatedAt < DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active'), 0) * 100, 1
            ) as trend
    ";
    $usersTrendStmt = $db->prepare($usersTrendQuery);
    $usersTrendStmt->execute();
    $usersTrend = $usersTrendStmt->fetchColumn() ?: 0;
    
    // Classes trend (based on recent activity - sessions in last 30 days vs previous 30 days)
    $classesTrendQuery = "
        SELECT 
            ROUND(
                ((SELECT COUNT(DISTINCT s.SubjectID) FROM attendancesessions s WHERE s.SessionDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)) -
                 (SELECT COUNT(DISTINCT s.SubjectID) FROM attendancesessions s WHERE s.SessionDate >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND s.SessionDate < DATE_SUB(NOW(), INTERVAL 30 DAY))) /
                 NULLIF((SELECT COUNT(DISTINCT s.SubjectID) FROM attendancesessions s WHERE s.SessionDate >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND s.SessionDate < DATE_SUB(NOW(), INTERVAL 30 DAY)), 0) * 100, 1
            ) as trend
    ";
    $classesTrendStmt = $db->prepare($classesTrendQuery);
    $classesTrendStmt->execute();
    $classesTrend = $classesTrendStmt->fetchColumn() ?: 0;
    
    // Sessions trend (last 7 days vs previous 7 days)
    $sessionsTrendQuery = "
        SELECT 
            ROUND(
                ((SELECT COUNT(*) FROM attendancesessions WHERE SessionDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)) -
                 (SELECT COUNT(*) FROM attendancesessions WHERE SessionDate >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND SessionDate < DATE_SUB(NOW(), INTERVAL 7 DAY))) /
                 NULLIF((SELECT COUNT(*) FROM attendancesessions WHERE SessionDate >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND SessionDate < DATE_SUB(NOW(), INTERVAL 7 DAY)), 0) * 100, 1
            ) as trend
    ";
    $sessionsTrendStmt = $db->prepare($sessionsTrendQuery);
    $sessionsTrendStmt->execute();
    $sessionsTrend = $sessionsTrendStmt->fetchColumn() ?: 0;
    
    // New users trend (this week vs last week)
    $newUsersTrendQuery = "
        SELECT 
            ROUND(
                ((SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active') -
                 (SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND CreatedAt < DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active')) /
                 NULLIF((SELECT COUNT(*) FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND CreatedAt < DATE_SUB(NOW(), INTERVAL 7 DAY) AND AccountStatus = 'Active'), 0) * 100, 1
            ) as trend
    ";
    $newUsersTrendStmt = $db->prepare($newUsersTrendQuery);
    $newUsersTrendStmt->execute();
    $newUsersTrend = $newUsersTrendStmt->fetchColumn() ?: 0;
    
} catch(PDOException $e) {
    // Fallback to mock data if database fails
    error_log("Dashboard Database Error: " . $e->getMessage());
    
    // Default values for fallback
    $totalUsers = 0;
    $usersByRole = [];
    $totalClasses = 0;
    $totalSessions = 0;
    $newUsers7Days = 0;
    $completionRate = 0.0;
    $recentSessions = [];
    $usersTrend = 0;
    $classesTrend = 0;
    $sessionsTrend = 0;
    $newUsersTrend = 0;
}

// Registration trends temporarily removed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ClassTrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛡️</text></svg>">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=5">
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
    <!-- Include Navbar Component -->
    <?php include '../assets/components/navbar.php'; ?>
    
    <!-- Include Sidebar Component -->
    <?php include '../assets/components/sidebar.php'; ?>
    
    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-wrapper">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1 class="welcome-title">
                            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator'); ?>! 👋
                        </h1>
                        <p class="welcome-subtitle">Here's what's happening with your ClassTrack system today</p>
                    </div>
                    <div class="welcome-time">
                        <div class="time-display"><?php echo date('g:i A'); ?></div>
                        <div class="date-display"><?php echo date('l, F j, Y'); ?></div>
                    </div>
                </div>
            </div>
            
                        
            <!-- Statistics Overview -->
            <div class="stats-section">
                <h2 class="section-title">System Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card-modern">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper users">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="stat-trend <?php echo $usersTrend >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="bi bi-arrow-<?php echo $usersTrend >= 0 ? 'up' : 'down'; ?>"></i>
                                <span><?php echo ($usersTrend >= 0 ? '+' : '') . number_format($usersTrend, 1); ?>%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-details">
                            <?php foreach ($usersByRole as $role): ?>
                                <div class="role-stat">
                                    <div class="role-indicator <?php echo strtolower($role['Role']); ?>"></div>
                                    <span class="role-name"><?php echo $role['Role']; ?></span>
                                    <span class="role-count"><?php echo $role['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card-modern">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper classes">
                                <i class="bi bi-book-fill"></i>
                            </div>
                            <div class="stat-trend <?php echo $classesTrend >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="bi bi-arrow-<?php echo $classesTrend >= 0 ? 'up' : 'down'; ?>"></i>
                                <span><?php echo ($classesTrend >= 0 ? '+' : '') . number_format($classesTrend, 1); ?>%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($totalClasses); ?></div>
                            <div class="stat-label">Active Classes</div>
                        </div>
                        <div class="stat-details">
                            <div class="detail-item">
                                <i class="bi bi-calendar-check"></i>
                                <span>This semester</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-modern">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper sessions">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div class="stat-trend <?php echo $sessionsTrend >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="bi bi-arrow-<?php echo $sessionsTrend >= 0 ? 'up' : 'down'; ?>"></i>
                                <span><?php echo ($sessionsTrend >= 0 ? '+' : '') . number_format($sessionsTrend, 1); ?>%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($totalSessions); ?></div>
                            <div class="stat-label">Attendance Sessions</div>
                        </div>
                        <div class="stat-details">
                            <div class="detail-item">
                                <i class="bi bi-check-circle"></i>
                                <span><?php echo $completionRate; ?>% completion rate</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-modern">
                        <div class="stat-header">
                            <div class="stat-icon-wrapper growth">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                            </div>
                            <div class="stat-trend <?php echo $newUsersTrend >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="bi bi-arrow-<?php echo $newUsersTrend >= 0 ? 'up' : 'down'; ?>"></i>
                                <span><?php echo ($newUsersTrend >= 0 ? '+' : '') . number_format($newUsersTrend, 1); ?>%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($newUsers7Days); ?></div>
                            <div class="stat-label">New Users (7 days)</div>
                        </div>
                        <div class="stat-details">
                            <div class="detail-item">
                                <i class="bi bi-person-plus"></i>
                                <span>Strong growth</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js temporarily removed -->
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/admin_dashboard.js?v=3"></script>
    
    </body>
</html>