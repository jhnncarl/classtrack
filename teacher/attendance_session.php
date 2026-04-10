<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    header('Location: ../auth/login.php');
    exit();
}

// Get class code from URL parameter
$classCode = $_GET['class'] ?? '';

// Get user information from session
$user_name = isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name']) ? 
    $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] : 
    (isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : 'Teacher');

// Include database configuration
require_once '../config/database.php';

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
$session_id = null;
if ($teacher_id && $classCode) {
    try {
        $stmt = $db->prepare("SELECT SubjectID, SubjectCode, SubjectName, ClassName, SectionName, Schedule FROM subjects WHERE TeacherID = ? AND SubjectCode = ?");
        $stmt->execute([$teacher_id, $classCode]);
        $subject_data = $stmt->fetch();
        
        if ($subject_data) {
            // Get enrolled students count
            $stmt = $db->prepare("SELECT COUNT(*) as student_count FROM enrollments WHERE SubjectID = ?");
            $stmt->execute([$subject_data['SubjectID']]);
            $student_count = $stmt->fetch()['student_count'];
            
            // Check if there's an active session for today, if not create one
            $stmt = $db->prepare("SELECT SessionID FROM attendancesessions WHERE SubjectID = ? AND SessionDate = CURDATE() AND Status = 'Active'");
            $stmt->execute([$subject_data['SubjectID']]);
            $existing_session = $stmt->fetch();
            
            error_log("Session Check - SubjectID: " . $subject_data['SubjectID'] . ", Existing session: " . ($existing_session ? $existing_session['SessionID'] : 'None'));
            
            if (!$existing_session) {
                // Create new attendance session
                $stmt = $db->prepare("INSERT INTO attendancesessions (SubjectID, SessionDate, StartTime, Status) VALUES (?, CURDATE(), CURTIME(), 'Active')");
                $stmt->execute([$subject_data['SubjectID']]);
                $session_id = $db->lastInsertId();
                error_log("New session created with ID: " . $session_id);
            } else {
                $session_id = $existing_session['SessionID'];
                error_log("Using existing session with ID: " . $session_id);
            }
            
            // Convert to expected format
            $currentClass = [
                'title' => $subject_data['SubjectName'],
                'section' => $subject_data['ClassName'] . ' - ' . $subject_data['SectionName'],
                'students' => $student_count,
                'schedule' => $subject_data['Schedule'] ?? 'Not specified',
                'session_id' => $session_id
            ];
        }
    } catch(PDOException $e) {
        error_log("Error getting subject data: " . $e->getMessage());
    }
}
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
    <title>ClassTrack - Attendance Session</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Montserrat Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/attendance_session.css?v=18">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <!-- Session Container -->
    <div class="session-container">
        <!-- Header -->
        <header class="session-header">
            <div class="header-content">
                <div class="class-info">
                    <h1 class="session-title">
                        <button class="btn-back-inline" onclick="goBack()">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <?php echo htmlspecialchars($currentClass['title']); ?>
                    </h1>
                    <div class="session-details">
                        <span class="class-code"><?php echo htmlspecialchars($classCode); ?></span>
                        <span class="separator">•</span>
                        <span class="class-section"><?php echo htmlspecialchars($currentClass['section']); ?></span>
                        <span class="separator">•</span>
                        <span class="student-count"><?php echo $currentClass['students']; ?> <?php echo $currentClass['students'] == 1 ? 'Student' : 'Students'; ?></span>
                        <span class="separator">•</span>
                        <div class="connection-status-container">
                            <div id="connectionStatus" class="connection-status online">
                                <i class="bi bi-wifi"></i>
                                <span>Online</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn-settings-header" onclick="showGracePeriodModal()" title="Attendance Settings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>
        </header>

        <!-- Main Session Content -->
        <main class="session-main">
            <div class="session-layout">
                <!-- Left Column: Session Status & QR Code -->
                <div class="left-column">
                    <!-- Session Status -->
                    <section class="session-status">
                        <div class="status-card">
                            <div class="status-indicator active">
                                <div class="pulse-dot"></div>
                                <span class="status-text">Session Active</span>
                            </div>
                            <div class="session-time">
                                <i class="bi bi-clock"></i>
                                <span id="sessionTimer">00:00:00</span>
                            </div>
                        </div>
                    </section>

                    <!-- Attendance Camera Scanner Section -->
                    <section class="camera-scanner">
                        <div class="scanner-header">
                            <h3>Attendance Scanner</h3>
                        </div>
                        <div class="scanner-content">
                            <div class="camera-preview" id="cameraPreview">
                                <video id="videoElement" style="display: none;"></video>
                                <div class="scanner-overlay" id="scannerOverlay" style="display: none;">
                                    <div class="scanner-frame">
                                        <div class="scanner-line"></div>
                                    </div>
                                </div>
                                <div id="cameraPlaceholder">
                                    <i class="bi bi-camera-video-off"></i>
                                    <p>Camera preview will appear here</p>
                                </div>
                            </div>
                            <div class="scanner-instructions">
                                Use your camera to scan student QR codes
                            </div>
                            <button class="btn-enable-camera" id="enableCameraBtn" onclick="enableCamera()">
                                <i class="bi bi-camera-video"></i>
                                Enable Camera
                            </button>
                            <div class="scanner-status" id="scannerStatus">
                                Camera ready / waiting for scan
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Student Information -->
                <div class="right-column">
                    <section class="student-info-section">
                        <div class="student-info-card">
                            <div class="student-info-header">
                                <h3>Student Information</h3>
                                <div class="student-status-badge" id="studentStatusBadge">
                                    <i class="bi bi-person"></i>
                                    <span>Waiting for scan</span>
                                </div>
                            </div>
                            <div class="student-info-content" id="studentInfoContent">
                                <div class="no-student-placeholder">
                                    <i class="bi bi-qr-code-scan"></i>
                                    <p>Student information will appear here</p>
                                    <small>After QR code is scanned</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Session Controls -->
                    <section class="controls-section">
                        <div class="controls-card">
                            <div class="control-buttons">
                                <button class="btn-control pause" onclick="pauseSession()">
                                    <i class="bi bi-pause-circle"></i>
                                    Pause Session
                                </button>
                                <button class="btn-control end" onclick="endSession()">
                                    <i class="bi bi-stop-circle"></i>
                                    End Session
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Attendance Stats -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card present">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="presentCount">0</div>
                            <div class="stat-label">Present</div>
                        </div>
                    </div>
                    <div class="stat-card absent">
                        <div class="stat-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="absentCount">0</div>
                            <div class="stat-label">Absent</div>
                        </div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="lateCount"><?php echo $currentClass['students']; ?></div>
                            <div class="stat-label">Late</div>
                        </div>
                    </div>
                </div>
            </section>

            
            </main>
    </div>

    <!-- Include Toast Component -->
    <?php include '../assets/components/toast.php'; ?>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmLeaveModal" tabindex="-1" aria-labelledby="confirmLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmLeaveModalLabel">Leave Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to leave this session? The session will stop running.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmLeaveBtn" style="background: #060606; border-color: #060606;">Leave Session</button>
                </div>
            </div>
        </div>
    </div>

    <!-- End Session Confirmation Modal -->
    <div class="modal fade" id="confirmEndSessionModal" tabindex="-1" aria-labelledby="confirmEndSessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmEndSessionModalLabel">End Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="end-session-content">
                        <div class="end-session-icon">
                            <i class="bi bi-stop-circle"></i>
                        </div>
                        <h6>Are you sure you want to end this session?</h6>
                        <p class="text-muted">Ending the session will finalize all attendance records and stop the timer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmEndSessionBtn">
                        <i class="bi bi-stop-circle"></i> End Session
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- WiFi Connection Warning Modal -->
    <div class="modal fade" id="wifiWarningModal" tabindex="-1" aria-labelledby="wifiWarningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wifiWarningModalLabel">
                        <i class="bi bi-wifi-off"></i> No Internet Connection
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="warning-content">
                        <div class="warning-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <h6>You are currently offline</h6>
                        <p class="text-muted">Ending or leaving the session without an internet connection may cause data loss. Please connect to WiFi to ensure all attendance data is properly saved.</p>
                        <div class="connection-status">
                            <i class="bi bi-wifi-off"></i>
                            <span>Offline Mode</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grace Period Settings Modal -->
    <div class="modal fade" id="gracePeriodModal" tabindex="-1" aria-labelledby="gracePeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gracePeriodModalLabel">
                        <i class="bi bi-gear"></i> Attendance Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="settings-content">
                        <div class="setting-item">
                            <label for="gracePeriodSelect" class="form-label">
                                <i class="bi bi-clock"></i> Grace Period
                            </label>
                            <p class="text-muted small">Students will be marked "Present" if they scan within this time after session starts</p>
                            <select class="form-select" id="gracePeriodSelect">
                                <option value="5">5 minutes</option>
                                <option value="10">10 minutes</option>
                                <option value="15" selected>15 minutes</option>
                                <option value="20">20 minutes</option>
                                <option value="30">30 minutes</option>
                            </select>
                        </div>
                        <div class="setting-item mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoLateCheck" checked>
                                <label class="form-check-label" for="autoLateCheck">
                                    <i class="bi bi-clock-history"></i> Enable automatic late marking
                                </label>
                            </div>
                            <p class="text-muted small">Automatically mark students as "Late" after grace period</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveGracePeriodSettings()" style="background: #060606; border-color: #060606;">
                        <i class="bi bi-check-lg"></i> Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- QR Code Scanner Library -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Pass session data to JavaScript
        const sessionId = <?php echo json_encode($session_id); ?>;
        const subjectId = <?php echo json_encode($subject_data['SubjectID'] ?? null); ?>;
        const classCode = <?php echo json_encode($classCode); ?>;
    </script>
    <script src="../assets/js/attendance_session.js?v=43"></script>
</body>
</html>
