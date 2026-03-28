<?php

require_once '../config/auth.php';

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'student_number' => $_POST['student_number'] ?? '',
        'year_level' => $_POST['year_level'] ?? '',
        'course' => $_POST['course'] ?? '',
        'role' => 'Student'
    ];
    
    // Validation
    $errors = [];
    
    if (empty($userData['first_name']) || empty($userData['last_name'])) {
        $errors[] = 'First name and last name are required';
    }
    
    if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($userData['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($userData['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif ($userData['password'] !== $userData['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($userData['student_number'])) {
        $errors[] = 'Student number is required';
    }
    
    if (empty($userData['year_level'])) {
        $errors[] = 'Year level is required';
    }
    
    if (empty($userData['course'])) {
        $errors[] = 'Course is required';
    }
    
    if (empty($errors)) {
        $result = $auth->register($userData);
        
        if ($result['success']) {
            $registration_success = true;
            $student_data = $result['data'] ?? [];
            $qr_code_data = $result['qr_code'] ?? '';
            $success_message = 'Registration successful! Your account has been created successfully.';
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassTrack - Student Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/student_registration.css?v=13">
    <link rel="stylesheet" href="../assets/css/toast.css?v=1">
    <link rel="stylesheet" href="../assets/css/qr_modal.css?v=17">
</head>
<body>
    <div class="registration-container">
        <div class="registration-form-container">
            <h2>Create Student Account</h2>
            <p class="subtitle">Fill in your information to get started with ClassTrack</p>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" style="display: none;">
                <?php echo $success_message; ?>
                <p><a href="../auth/login.php">Click here to login</a></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" style="display: none;">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="registration-form" novalidate>
                <!-- Two Column Layout -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first-name">First Name</label>
                        <input 
                            type="text" 
                            id="first-name" 
                            name="first_name" 
                            placeholder="Enter your first name" 
                            required
                            value="<?php echo isset($userData['first_name']) ? htmlspecialchars($userData['first_name']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="last-name">Last Name</label>
                        <input 
                            type="text" 
                            id="last-name" 
                            name="last_name" 
                            placeholder="Enter your last name" 
                            required
                            value="<?php echo isset($userData['last_name']) ? htmlspecialchars($userData['last_name']) : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email address" 
                            required
                            value="<?php echo isset($userData['email']) ? htmlspecialchars($userData['email']) : ''; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="student-number">Student Number</label>
                        <input 
                            type="text" 
                            id="student-number" 
                            name="student_number" 
                            placeholder="Enter your student number" 
                            required
                            value="<?php echo isset($userData['student_number']) ? htmlspecialchars($userData['student_number']) : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year-level">Year Level</label>
                        <select id="year-level" name="year_level" required>
                            <option value="">Select your year level</option>
                            <option value="1" <?php echo (isset($userData['year_level']) && $userData['year_level'] == '1') ? 'selected' : ''; ?>>First Year</option>
                            <option value="2" <?php echo (isset($userData['year_level']) && $userData['year_level'] == '2') ? 'selected' : ''; ?>>Second Year</option>
                            <option value="3" <?php echo (isset($userData['year_level']) && $userData['year_level'] == '3') ? 'selected' : ''; ?>>Third Year</option>
                            <option value="4" <?php echo (isset($userData['year_level']) && $userData['year_level'] == '4') ? 'selected' : ''; ?>>Fourth Year</option>
                            <option value="5" <?php echo (isset($userData['year_level']) && $userData['year_level'] == '5') ? 'selected' : ''; ?>>Fifth Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input 
                            type="text" 
                            id="course" 
                            name="course" 
                            placeholder="Enter your course (e.g., BSIT, BSCS, BSM)" 
                            required
                            value="<?php echo isset($userData['course']) ? htmlspecialchars($userData['course']) : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Create a strong password" 
                                required
                            >
                            <button type="button" class="password-toggle-btn" id="password-toggle">
                                <svg class="eye-icon" id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" id="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="confirm-password" 
                                name="confirm_password" 
                                placeholder="Confirm your password" 
                                required
                            >
                            <button type="button" class="password-toggle-btn" id="confirm-password-toggle">
                                <svg class="eye-icon" id="confirm-eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" id="confirm-eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="register-btn">Create Account</button>
                
                <p class="login-text">
                    Already have an account? <a href="../auth/login.php">Sign In</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- QR Code Success Modal -->
    <?php if (isset($registration_success) && $registration_success): ?>
    <div class="qr-success-modal" id="qrSuccessModal">
        <div class="modal-overlay" id="modalOverlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Registration Successful!</h3>
                <button class="close-btn" id="closeModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="success-content">
                    <div class="qr-section">
                        <div class="qr-container" id="qrContainer">
                            <?php if (!empty($qr_code_data)): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($qr_code_data); ?>" alt="Your QR Code" class="qr-code">
                                <script>
                                    console.log('QR Code data found and displayed');
                                    console.log('QR Code data length:', '<?php echo strlen($qr_code_data); ?>');
                                </script>
                            <?php else: ?>
                                <div class="qr-placeholder">
                                    <i class="bi bi-qr-code"></i>
                                </div>
                                <script>
                                    console.log('No QR Code data available');
                                    console.log('qr_code_data variable:', <?php echo var_export($qr_code_data, true); ?>);
                                </script>
                            <?php endif; ?>
                        </div>
                        <p class="qr-description">This is your unique QR code for attendance scanning</p>
                    </div>
                    
                    <div class="student-info-section">
                        <h4>Student Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Name:</label>
                                <span><?php echo htmlspecialchars(($student_data['first_name'] ?? '') . ' ' . ($student_data['last_name'] ?? '')); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Student Number:</label>
                                <span><?php echo htmlspecialchars($student_data['StudentNumber'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($student_data['Email'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Course:</label>
                                <span><?php echo htmlspecialchars($student_data['Course'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Year Level:</label>
                                <span><?php echo htmlspecialchars($student_data['year_level_text'] ?? ''); ?></span>
                            </div>
                        </div>
                        <script>
                            console.log('Student data:', <?php echo json_encode($student_data ?? []); ?>);
                        </script>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-download" id="downloadBtn" style="color: #060606;">
                    <i class="bi bi-download"></i> Download QR
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Toast Notification Component -->
    <?php require_once '../assets/components/toast.php'; ?>
    
    <script src="../assets/js/register.js?v=10"></script>
</body>
</html>
