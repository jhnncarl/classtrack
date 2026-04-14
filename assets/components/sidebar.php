<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-container">
        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <?php
                // Get current user role from session
                $userRole = $_SESSION['user_role'] ?? null;
                
                // Student Menu
                if ($userRole === 'Student'):
                ?>
                    <!-- Dashboard -->
                    <li class="sidebar-item">
                        <a href="../student/dashboard.php" class="sidebar-link active" data-page="dashboard">
                            <i class="bi bi-house-door sidebar-icon"></i>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Subjects Enrolled -->
                    <li class="sidebar-item">
                        <a href="../student/subjects.php" class="sidebar-link" data-page="subjects">
                            <i class="bi bi-book sidebar-icon"></i>
                            <span class="sidebar-text">Subjects Enrolled</span>
                        </a>
                    </li>
                    
                    <!-- Attendance History -->
                    <li class="sidebar-item">
                        <a href="../student/attendance_history.php" class="sidebar-link" data-page="attendance-history">
                            <i class="bi bi-graph-up sidebar-icon"></i>
                            <span class="sidebar-text">Attendance History</span>
                        </a>
                    </li>
                    
                    <!-- Settings -->
                    <li class="sidebar-item">
                        <a href="../settings_page/settings.php" class="sidebar-link" data-page="settings">
                            <i class="bi bi-gear sidebar-icon"></i>
                            <span class="sidebar-text">Settings</span>
                        </a>
                    </li>
                
                <?php
                // Teacher Menu
                elseif ($userRole === 'Teacher'):
                ?>
                    <!-- My Class -->
                    <li class="sidebar-item">
                        <a href="../teacher/dashboard.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>" data-page="dashboard">
                            <i class="bi bi-people sidebar-icon"></i>
                            <span class="sidebar-text">My Class</span>
                        </a>
                    </li>
                    
                    <!-- Attendance Session History -->
                    <li class="sidebar-item">
                        <a href="../teacher/attendance_session_history.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'attendance_session_history.php' || (isset($_GET['page']) && $_GET['page'] === 'attendance-sessions')) ? 'active' : ''; ?>" data-page="attendance-sessions">
                            <i class="bi bi-clock-history sidebar-icon"></i>
                            <span class="sidebar-text">Attendance Sessions</span>
                        </a>
                    </li>
                    
                    <!-- Generated Reports -->
                    <li class="sidebar-item">
                        <a href="../teacher/reports.php" class="sidebar-link" data-page="reports">
                            <i class="bi bi-file-text sidebar-icon"></i>
                            <span class="sidebar-text">Generate Reports</span>
                        </a>
                    </li>
                    
                    <!-- Settings -->
                    <li class="sidebar-item">
                        <a href="../settings_page/settings.php" class="sidebar-link" data-page="settings">
                            <i class="bi bi-gear sidebar-icon"></i>
                            <span class="sidebar-text">Settings</span>
                        </a>
                    </li>
                
                <?php
                // Administrator Menu
                elseif ($userRole === 'Administrator'):
                ?>
                    <!-- Dashboard -->
                    <li class="sidebar-item">
                        <a href="../admin/dashboard.php" class="sidebar-link active" data-page="dashboard">
                            <i class="bi bi-speedometer2 sidebar-icon"></i>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Manage Users / Roles -->
                    <li class="sidebar-item">
                        <a href="../admin/users.php" class="sidebar-link" data-page="users">
                            <i class="bi bi-person-gear sidebar-icon"></i>
                            <span class="sidebar-text">Manage Users / Roles</span>
                        </a>
                    </li>
                    
                    <!-- Settings -->
                    <li class="sidebar-item">
                        <a href="../settings_page/settings.php" class="sidebar-link" data-page="settings">
                            <i class="bi bi-gear sidebar-icon"></i>
                            <span class="sidebar-text">Settings</span>
                        </a>
                    </li>
                
                <?php endif; ?>
                
                <!-- Log Out (Common for all roles) -->
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" data-page="logout">
                        <i class="bi bi-box-arrow-right sidebar-icon"></i>
                        <span class="sidebar-text">Sign Out</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Sign Out Confirmation Modal -->
<div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
        <div class="modal-content join-class-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
            <div class="modal-body p-0" style="padding: 0 !important;">
                <!-- Header -->
                <div class="join-class-header" style="padding: 32px 32px 24px 32px !important;">
                    <h5 class="join-class-title" id="signOutModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Sign Out</h5>
                </div>

                <!-- Confirmation Message Section -->
                <div class="account-section" style="padding: 0 32px 24px 32px !important;">
                    <div class="class-code-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important;">
                        <p class="class-code-description" style="color: #202124 !important; font-size: 16px !important; margin: 0 !important; line-height: 1.5 !important; font-family: 'Montserrat', sans-serif !important;">Are you sure you want to sign out?</p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                    <button type="button" class="btn-join" id="confirmSignOutBtn" style="background: #060606 !important; border: none !important; color: #ffffff !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: all 0.2s ease !important; font-family: 'Montserrat', sans-serif !important; white-space: nowrap !important; min-width: 120px !important; text-align: center !important;">Sign Out</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link Sidebar CSS -->
<link rel="stylesheet" href="../assets/css/sidebar.css">

<!-- Link Toast CSS -->
<link rel="stylesheet" href="../assets/css/toast.css">

<!-- Include Sidebar JavaScript -->
<script src="../assets/js/sidebar.js?v=2"></script>
