<?php
// Define absolute path to config directory
$configPath = __DIR__ . '/../../config/auth.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Fallback for different directory structures
    $altPath = dirname(dirname(__DIR__)) . '/config/auth.php';
    if (file_exists($altPath)) {
        require_once $altPath;
    }
}

// Check for admin session first
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isAdmin = $isAdminLoggedIn && isset($_SESSION['admin_role']);

// Get current user role (for regular users)
$userRole = null;
$isLoggedIn = false;

if (!$isAdminLoggedIn) {
    // Only load auth for non-admin sessions
    if (file_exists($configPath)) {
        require_once $configPath;
        $userRole = $auth->getUserRole();
        $isLoggedIn = $auth->isLoggedIn();
    }
    
    // Ensure profile path is available in session - fetch from database if needed
    if ($isLoggedIn && (!isset($_SESSION['user_profile_path']) || empty($_SESSION['user_profile_path']))) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("SELECT ProfilePicture FROM users WHERE UserID = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && !empty($user['ProfilePicture'])) {
                $_SESSION['user_profile_path'] = $user['ProfilePicture'];
            }
        } catch (Exception $e) {
            error_log("Error fetching profile path: " . $e->getMessage());
        }
    }
} else {
    // For admin sessions, fetch admin profile picture if not already in session
    if (!isset($_SESSION['admin_profile_path']) || empty($_SESSION['admin_profile_path'])) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("SELECT profile_pic FROM admins WHERE admin_id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if ($admin && !empty($admin['profile_pic'])) {
                $_SESSION['admin_profile_path'] = $admin['profile_pic'];
            }
        } catch (Exception $e) {
            error_log("Error fetching admin profile path: " . $e->getMessage());
        }
    }
}
?>
<!-- Top Navigation Bar -->
    <nav class="navbar navbar-light bg-white fixed-top shadow-sm">
        <div class="container-fluid h-100 ps-0 pe-2">
            <!-- Left Section - Menu Icon & Branding -->
            <div class="d-flex align-items-center">
                <!-- Hamburger Menu Icon -->
                <button class="btn-icon btn-icon-large" id="menuToggle">
                    <i class="bi bi-list"></i>
                </button>
                
                <!-- ClassTrack Logo -->
                <a class="navbar-brand d-flex align-items-center ms-3" href="#">
                    <i class="bi bi-qr-code-scan me-2"></i>
                    <span class="fw-bold">ClassTrack</span>
                </a>
            </div>

            <!-- Right Section - Action Items -->
            <div class="d-flex align-items-center">
                <?php if ($isLoggedIn && $userRole === 'Student'): ?>
                <!-- Plus Icon with Dropdown for Students -->
                <div class="dropdown-plus">
                    <button class="btn-icon btn-icon-large me-3" id="plusDropdownToggle">
                        <i class="bi bi-plus"></i>
                    </button>
                    <div class="dropdown-menu-plus" id="plusDropdownMenu">
                        <!-- Show only Join Class for Student role -->
                        <div class="dropdown-item-plus" id="joinClassOption" data-role="student">
                            <i class="bi bi-door-open me-2"></i>
                            <span>Join Class</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isLoggedIn && $userRole === 'Teacher'): ?>
                <!-- Plus Icon with Dropdown for Teachers -->
                <div class="dropdown-plus">
                    <button class="btn-icon btn-icon-large me-3" id="plusDropdownToggle">
                        <i class="bi bi-plus"></i>
                    </button>
                    <div class="dropdown-menu-plus" id="plusDropdownMenu">
                        <!-- Show only Create Subject for Teacher role -->
                        <div class="dropdown-item-plus" id="createSubjectOption" data-role="teacher">
                            <i class="bi bi-plus-circle me-2"></i>
                            <span>Create Subject</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isLoggedIn && $userRole === 'Administrator'): ?>
                <!-- Administrator role - No plus icon, only profile -->
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Admin role - Show admin-specific options if needed -->
                <?php endif; ?>

                <!-- Profile Picture -->
                <div class="position-relative">
                    <button class="btn-icon btn-icon-large" id="navbarProfileBtn">
                        <?php 
                        if ($isAdmin) {
                            // Admin users get profile picture or default admin icon
                            $adminProfilePath = $_SESSION['admin_profile_path'] ?? null;
                            if ($adminProfilePath && !empty($adminProfilePath)) {
                                // Ensure proper path resolution from web root with classtrack prefix
                                $fullPath = '/classtrack/' . ltrim($adminProfilePath, '/');
                                echo '<img src="' . htmlspecialchars($fullPath) . '" alt="Admin Profile" class="rounded-circle">';
                            } else {
                                echo '<i class="bi bi-shield-fill" style="font-size: 40px; color: #6c757d;"></i>';
                            }
                        } else {
                            // Regular users get profile picture or default icon
                            $profilePath = $_SESSION['user_profile_path'] ?? null;
                            if ($profilePath && !empty($profilePath)) {
                                // Ensure proper path resolution from web root with classtrack prefix
                                $fullPath = '/classtrack/' . ltrim($profilePath, '/');
                                echo '<img src="' . htmlspecialchars($fullPath) . '" alt="Profile" class="rounded-circle">';
                            } else {
                                echo '<i class="bi bi-person-circle" style="font-size: 40px; color: #9b9b9b;"></i>';
                            }
                        }
                        ?>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Join Class Modal -->
    <div class="modal fade" id="joinClassModal" tabindex="-1" aria-labelledby="joinClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
            <div class="modal-content join-class-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="join-class-header" style="padding: 32px 32px 24px 32px !important;">
                        <h5 class="join-class-title" id="joinClassModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Join class</h5>
                    </div>

                    <!-- Account Section -->
                    <div class="account-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="account-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important; display: flex !important; align-items: center !important; gap: 16px !important;">
                            <?php 
                            $profilePath = $_SESSION['user_profile_path'] ?? null;
                            if ($profilePath && !empty($profilePath)) {
                                // Ensure proper path resolution from web root with classtrack prefix
                                $fullPath = '/classtrack/' . ltrim($profilePath, '/');
                                echo '<img src="' . htmlspecialchars($fullPath) . '" alt="Profile" class="account-avatar rounded-circle">';
                            } else {
                                 echo '<i class="bi bi-person-circle" style="font-size: 40px; color: #9b9b9b;"></i>';
                            }
                            ?>
                            <div class="account-info" style="flex: 1 !important;">
                                <div class="account-name" style="font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 4px !important; font-family: 'Montserrat', sans-serif !important;">
                                    <?php 
                                    $displayName = 'Demo Student';
                                    if (isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name'])) {
                                        $displayName = htmlspecialchars($_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']);
                                    } elseif (isset($_SESSION['user_first_name'])) {
                                        $displayName = htmlspecialchars($_SESSION['user_first_name']);
                                    }
                                    echo $displayName;
                                    ?>
                                </div>
                                <div class="account-email" style="color: #5f6368 !important; font-size: 14px !important; font-family: 'Montserrat', sans-serif !important;">
                                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'demo.student@classtrack.edu'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Class Code Section -->
                    <div class="class-code-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="class-code-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important;">
                            <label class="class-code-label" style="display: block !important; font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 8px !important; font-family: 'Montserrat', sans-serif !important;">Class code</label>
                            <p class="class-code-description" style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 16px !important; line-height: 1.4 !important; font-family: 'Montserrat', sans-serif !important;">Ask your teacher for the class code, then enter it here.</p>
                            <input type="text" id="classCodeInput" class="class-code-input" placeholder="Class code" maxlength="8" style="width: 100% !important; height: 48px !important; border: 1px solid #dadce0 !important; border-radius: 6px !important; padding: 0 12px !important; font-size: 16px !important; font-family: 'Montserrat', sans-serif !important; transition: border-color 0.2s ease, box-shadow 0.2s ease !important; background: #ffffff !important;">
                        </div>
                    </div>

                    <!-- Help Information -->
                    <div class="help-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="help-title" style="font-weight: 500 !important; color: #202124 !important; font-size: 14px !important; margin-bottom: 12px !important; font-family: 'Montserrat', sans-serif !important;">To sign in with a class code</div>
                        <ul class="help-list" style="list-style: none !important; padding: 0 !important; margin: 0 !important;">
                            <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 8px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">Use an authorized account</li>
                            <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 8px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">Use a class code with 5-8 letters or numbers, and no spaces or symbols</li>
                        </ul>
                    </div>

                    <!-- Footer Actions -->
                    <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                        <button type="button" class="btn-cancel" id="createClassCancelBtn" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                        <span class="btn-join" id="joinClassBtn" style="background: transparent !important; border: none !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; font-family: 'Montserrat', sans-serif !important; transition: color 0.2s ease !important;">Join</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Subject Modal -->
    <div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
            <div class="modal-content create-subject-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="create-subject-header" style="padding: 32px 32px 24px 32px !important;">
                        <h5 class="create-subject-title" id="createSubjectModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Create Subject</h5>
                    </div>

                    <!-- Account Section -->
                    <div class="account-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="account-card" style="background: #f1f3f4 !important; border-radius: 12px !important; padding: 20px !important; display: flex !important; align-items: center !important; gap: 16px !important;">
                            <?php 
                            $profilePath = $_SESSION['user_profile_path'] ?? null;
                            if ($profilePath && !empty($profilePath)) {
                                // Ensure proper path resolution from web root with classtrack prefix
                                $fullPath = '/classtrack/' . ltrim($profilePath, '/');
                                echo '<img src="' . htmlspecialchars($fullPath) . '" alt="Profile" class="account-avatar rounded-circle">';
                            } else {
                                 echo '<i class="bi bi-person-circle" style="font-size: 40px; color: #9b9b9b;"></i>';
                            }
                            ?>
                            <div class="account-info" style="flex: 1 !important;">
                                <div class="account-name" style="font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 4px !important; font-family: 'Montserrat', sans-serif !important;">
                                    <?php 
                                    $displayName = 'Demo Teacher';
                                    if (isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name'])) {
                                        $displayName = htmlspecialchars($_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']);
                                    } elseif (isset($_SESSION['user_first_name'])) {
                                        $displayName = htmlspecialchars($_SESSION['user_first_name']);
                                    }
                                    echo $displayName;
                                    ?>
                                </div>
                                <div class="account-email" style="color: #5f6368 !important; font-size: 14px !important; font-family: 'Montserrat', sans-serif !important;">
                                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'demo.teacher@classtrack.edu'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="information-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="information-card" style="background: #fef7e0 !important; border-radius: 12px !important; padding: 20px !important; border-left: 4px solid #fbbc04 !important;">
                            <div class="information-title" style="font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 12px !important; font-family: 'Montserrat', sans-serif !important;">
                                <i class="bi bi-info-circle-fill me-2" style="color: #fbbc04 !important;"></i>
                                Important Information
                            </div>
                            <ul class="information-list" style="list-style: none !important; padding: 0 !important; margin: 0 !important;">
                                <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 8px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">You will be designated as the primary teacher for this subject</li>
                                <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 8px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">Students can join using the generated class code</li>
                                <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 8px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">You can monitor attendance and generate attendance reports</li>
                                <li style="color: #5f6368 !important; font-size: 14px !important; margin-bottom: 0px !important; padding-left: 20px !important; position: relative !important; font-family: 'Montserrat', sans-serif !important;">Subject creation requires administrator approval in some institutions</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Agreement Section -->
                    <div class="agreement-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="agreement-card" style="background: #f8f9fa !important; border-radius: 12px !important; padding: 20px !important;">
                            <label class="agreement-label" style="display: flex !important; align-items: flex-start !important; cursor: pointer !important; font-family: 'Montserrat', sans-serif !important;">
                                <input type="checkbox" id="agreementCheckbox" class="agreement-checkbox" style="margin-right: 12px !important; margin-top: 2px !important; width: 16px !important; height: 16px !important; cursor: pointer !important;">
                                <span style="color: #202124 !important; font-size: 14px !important; line-height: 1.4 !important; font-family: 'Montserrat', sans-serif !important;">
                                    I understand that I will be responsible for managing this subject, including student enrollment, attendance tracking, and maintaining accurate records. I agree to follow the institution's guidelines and policies.
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                        <button type="button" class="btn-cancel" id="createSubjectCancelBtn" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                        <span class="btn-continue" id="continueBtn" style="background: transparent !important; border: none !important; color: #80868b; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; font-family: 'Montserrat', sans-serif !important; transition: color 0.2s ease !important; cursor: not-allowed !important;">Continue</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Class Modal -->
    <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom" style="max-width: 560px !important;">
            <div class="modal-content create-class-modal" style="border: none !important; border-radius: 15px !important; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important; font-family: 'Montserrat', sans-serif !important; background: #ffffff !important; overflow: hidden !important;">
                <div class="modal-body p-0" style="padding: 0 !important;">
                    <!-- Header -->
                    <div class="create-class-header" style="padding: 32px 32px 24px 32px !important;">
                        <h5 class="create-class-title" id="createClassModalLabel" style="font-size: 26px !important; font-weight: 500 !important; color: #202124 !important; margin: 0 !important; font-family: 'Montserrat', sans-serif !important;">Create Class</h5>
                    </div>

                    <!-- Form Section -->
                    <div class="form-section" style="padding: 0 32px 24px 32px !important;">
                        <div class="form-card" style="background: #f8f9fa !important; border-radius: 12px !important; padding: 20px !important;">
                            <!-- Class Name Field -->
                            <div class="form-group mb-4" style="margin-bottom: 20px !important;">
                                <label class="form-label" style="display: block !important; font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 8px !important; font-family: 'Montserrat', sans-serif !important;">
                                    Class Name <span class="text-danger" style="color: #dc3545 !important;">*</span>
                                </label>
                                <input type="text" id="classNameInput" class="form-control class-name-input" placeholder="Enter class name" style="width: 100% !important; height: 48px !important; border: 1px solid #dadce0 !important; border-radius: 6px !important; padding: 0 12px !important; font-size: 16px !important; font-family: 'Montserrat', sans-serif !important; transition: border-color 0.2s ease, box-shadow 0.2s ease !important; background: #ffffff !important;">
                            </div>

                            <!-- Section Field -->
                            <div class="form-group mb-4" style="margin-bottom: 20px !important;">
                                <label class="form-label" style="display: block !important; font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 8px !important; font-family: 'Montserrat', sans-serif !important;">
                                    Section <span class="text-danger" style="color: #dc3545 !important;">*</span>
                                </label>
                                <input type="text" id="sectionInput" class="form-control section-input" placeholder="Enter section" style="width: 100% !important; height: 48px !important; border: 1px solid #dadce0 !important; border-radius: 6px !important; padding: 0 12px !important; font-size: 16px !important; font-family: 'Montserrat', sans-serif !important; transition: border-color 0.2s ease, box-shadow 0.2s ease !important; background: #ffffff !important;">
                            </div>

                            <!-- Subject Field -->
                            <div class="form-group mb-4" style="margin-bottom: 20px !important;">
                                <label class="form-label" style="display: block !important; font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 8px !important; font-family: 'Montserrat', sans-serif !important;">
                                    Subject <span class="text-danger" style="color: #dc3545 !important;">*</span>
                                </label>
                                <input type="text" id="subjectInput" class="form-control subject-input" placeholder="Enter subject" style="width: 100% !important; height: 48px !important; border: 1px solid #dadce0 !important; border-radius: 6px !important; padding: 0 12px !important; font-size: 16px !important; font-family: 'Montserrat', sans-serif !important; transition: border-color 0.2s ease, box-shadow 0.2s ease !important; background: #ffffff !important;">
                            </div>

                            <!-- Schedule Field -->
                            <div class="form-group mb-0" style="margin-bottom: 0 !important;">
                                <label class="form-label" style="display: block !important; font-weight: 500 !important; color: #202124 !important; font-size: 16px !important; margin-bottom: 8px !important; font-family: 'Montserrat', sans-serif !important;">
                                    Schedule
                                </label>
                                <input type="text" id="scheduleInput" class="form-control schedule-input" placeholder="e.g., Monday 9:00-10:00 AM" style="width: 100% !important; height: 48px !important; border: 1px solid #dadce0 !important; border-radius: 6px !important; padding: 0 12px !important; font-size: 16px !important; font-family: 'Montserrat', sans-serif !important; transition: border-color 0.2s ease, box-shadow 0.2s ease !important; background: #ffffff !important;">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="modal-footer-actions" style="padding: 16px 32px 32px 32px !important; display: flex !important; justify-content: flex-end !important; gap: 12px !important;">
                        <button type="button" class="btn-cancel" id="createClassCancelBtn" data-bs-dismiss="modal" style="background: transparent !important; border: none !important; color: #5f6368 !important; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; cursor: pointer !important; border-radius: 6px !important; transition: background-color 0.2s ease !important; font-family: 'Montserrat', sans-serif !important;">Cancel</button>
                        <button type="button" class="btn-create" id="createClassBtn" style="background: transparent !important; border: none !important; color: #80868b; font-size: 14px !important; font-weight: 500 !important; padding: 8px 24px !important; font-family: 'Montserrat', sans-serif !important; transition: color 0.2s ease !important; cursor: not-allowed !important;">Create</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass user role to JavaScript
        <?php if ($isAdmin): ?>
        window.currentUserRole = 'Administrator';
        window.isAdmin = true;
        <?php else: ?>
        window.currentUserRole = <?php echo json_encode($isLoggedIn ? $userRole : null); ?>;
        window.isAdmin = false;
        <?php endif; ?>
    </script>
    <link rel="stylesheet" href="/classtrack/assets/css/toast.css?v=4">
    <link rel="stylesheet" href="/classtrack/assets/css/navbar.css?v=23">
    <script src="/classtrack/assets/js/navbar.js"></script>
