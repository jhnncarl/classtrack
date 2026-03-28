document.addEventListener('DOMContentLoaded', function() {
    // Detect if we're on teacher registration page by checking the URL
    const isTeacherRegistration = window.location.pathname.includes('teacher/register.php');
    
    // Prevent form resubmission confirmation dialog
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Toast elements - use the standard toast container (declare first)
    const toast = document.getElementById('otp-toast');
    const toastClose = document.getElementById('toastClose');
    
    // Check if there's a success message on the page and show toast
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        const successText = successAlert.textContent.trim();
        if (successText.includes('successfully')) {
            // Hide the PHP alert
            successAlert.style.display = 'none';
            // Show the toast notification and auto-hide after 3 seconds
            showToast('Success', successText.replace('Click here to login', '').trim(), 'success');
            
            // Show appropriate modal after successful registration
            setTimeout(() => {
                if (isTeacherRegistration) {
                    // Show approval modal for teacher registration
                    showApprovalModal();
                } else {
                    // Show QR modal for student registration
                    showQRModal();
                }
            }, 100);
        }
    }
    
    // Check if there's an error message on the page and show toast
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert) {
        const errorText = errorAlert.textContent.trim();
        // Hide the PHP alert
        errorAlert.style.display = 'none';
        // Show the error toast notification instead
        showToast('Error', errorText, 'error');
    }
    
    // Password toggle functionality
    const passwordToggle = document.getElementById('password-toggle');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');

    const confirmPasswordToggle = document.getElementById('confirm-password-toggle');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const confirmEyeIcon = document.getElementById('confirm-eye-icon');
    const confirmEyeOffIcon = document.getElementById('confirm-eye-off-icon');

    // Password visibility toggle for password field
    if (passwordToggle && passwordInput && eyeIcon && eyeOffIcon) {
        passwordToggle.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, eyeIcon, eyeOffIcon);
        });
    }

    // Password visibility toggle for confirm password field
    if (confirmPasswordToggle && confirmPasswordInput && confirmEyeIcon && confirmEyeOffIcon) {
        confirmPasswordToggle.addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordInput, confirmEyeIcon, confirmEyeOffIcon);
        });
    }

    function togglePasswordVisibility(input, eyeIcon, eyeOffIcon) {
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.style.display = 'none';
            eyeOffIcon.style.display = 'block';
        } else {
            input.type = 'password';
            eyeIcon.style.display = 'block';
            eyeOffIcon.style.display = 'none';
        }
    }

    // Toast close functionality
    if (toastClose) {
        toastClose.addEventListener('click', function() {
            hideToast();
        });
    }

    // Toast functions
    function showToast(title, message, type = 'success', keepVisible = false) {
        if (toast) {
            // Update toast content
            updateToastMessage(title, message);
            
            // Update toast type
            toast.className = `toast toast-${type}`;
            
            // Update icon based on type
            updateToastIcon(type);
            
            // Remove any existing show/hide classes
            toast.classList.remove('show', 'hide');
            
            // Trigger reflow to restart animation
            void toast.offsetWidth;
            
            // Show the toast
            toast.classList.add('show');
            
            // Only auto-hide if keepVisible is false
            if (!keepVisible) {
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    hideToast();
                }, 3000);
            }
        }
    }

    function hideToast() {
        if (toast && toast.classList.contains('show')) {
            toast.classList.remove('show');
            toast.classList.add('hide');
            
            // Remove hide class after animation completes
            setTimeout(() => {
                toast.classList.remove('hide');
            }, 300);
        }
    }

    function updateToastMessage(title, message) {
        if (toast) {
            const titleElement = toast.querySelector('.toast-title');
            const messageElement = toast.querySelector('.toast-message');
            
            if (titleElement) {
                titleElement.textContent = title;
            }
            
            if (messageElement) {
                messageElement.textContent = message;
            }
        }
    }

    function updateToastIcon(type) {
        if (toast) {
            const iconContainer = toast.querySelector('.toast-icon');
            
            if (iconContainer) {
                let iconSvg = '';
                
                switch(type) {
                    case 'success':
                        iconSvg = `
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        `;
                        break;
                    case 'error':
                        iconSvg = `
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        `;
                        break;
                    case 'warning':
                        iconSvg = `
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        `;
                        break;
                    default:
                        iconSvg = `
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        `;
                }
                
                iconContainer.innerHTML = iconSvg;
            }
        }
    }

    // Registration toast function
    function showRegistrationToast(title, message, type = 'success', keepVisible = false) {
        showToast(title, message, type, keepVisible);
    }

    // Function to show QR modal
    function showQRModal() {
        if (qrModal) {
            console.log('Showing QR modal after successful registration...');
            qrModal.classList.add('show');
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
            console.log('QR modal should now be visible');
        } else {
            console.log('QR Modal not found - registration may not have been successful');
        }
    }

    // Function to show approval modal (for teacher registration)
    function showApprovalModal() {
        if (approvalModal) {
            console.log('Showing approval modal after successful registration...');
            approvalModal.classList.add('show');
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
            console.log('Approval modal should now be visible');
        } else {
            console.log('Approval Modal not found - registration may not have been successful');
        }
    }

    // Form validation
    const registrationForm = document.querySelector('.registration-form');
    const firstNameInput = document.getElementById('first-name');
    const lastNameInput = document.getElementById('last-name');
    const emailInput = document.getElementById('email');
    
    // Conditional fields based on registration type
    const studentNumberInput = document.getElementById('student-number');
    const yearLevelSelect = document.getElementById('year-level');
    const courseInput = document.getElementById('course');
    const departmentInput = document.getElementById('department');

    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            // Clear previous errors
            clearAllErrors();
            
            let isValid = true;
            
            // Validate First Name
            if (firstNameInput && firstNameInput.value.trim() === '') {
                showCustomError(firstNameInput, 'First name is required');
                isValid = false;
            } else if (firstNameInput && firstNameInput.value.trim().length < 2) {
                showCustomError(firstNameInput, 'First name must be at least 2 characters');
                isValid = false;
            }
            
            // Validate Last Name
            if (lastNameInput && lastNameInput.value.trim() === '') {
                showCustomError(lastNameInput, 'Last name is required');
                isValid = false;
            } else if (lastNameInput && lastNameInput.value.trim().length < 2) {
                showCustomError(lastNameInput, 'Last name must be at least 2 characters');
                isValid = false;
            }
            
            // Validate Email
            if (emailInput && emailInput.value.trim() === '') {
                showCustomError(emailInput, 'Email address is required');
                isValid = false;
            } else if (emailInput && !isValidEmail(emailInput.value.trim())) {
                showCustomError(emailInput, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Student-specific validation
            if (!isTeacherRegistration) {
                // Validate Student Number
                if (studentNumberInput && studentNumberInput.value.trim() === '') {
                    showCustomError(studentNumberInput, 'Student number is required');
                    isValid = false;
                } else if (studentNumberInput && studentNumberInput.value.trim().length < 4) {
                    showCustomError(studentNumberInput, 'Student number must be at least 4 characters');
                    isValid = false;
                }
                
                // Validate Year Level
                if (yearLevelSelect && yearLevelSelect.value === '') {
                    showCustomError(yearLevelSelect, 'Please select your year level');
                    isValid = false;
                }
                
                // Validate Course
                if (courseInput && courseInput.value.trim() === '') {
                    showCustomError(courseInput, 'Course is required');
                    isValid = false;
                } else if (courseInput && courseInput.value.trim().length < 2) {
                    showCustomError(courseInput, 'Course must be at least 2 characters');
                    isValid = false;
                }
            }
            
            // Teacher-specific validation
            if (isTeacherRegistration) {
                // Validate Department
                if (departmentInput && departmentInput.value.trim() === '') {
                    showCustomError(departmentInput, 'Department is required');
                    isValid = false;
                } else if (departmentInput && departmentInput.value.trim().length < 2) {
                    showCustomError(departmentInput, 'Department must be at least 2 characters');
                    isValid = false;
                }
            }
            
            // Validate Password
            if (passwordInput && passwordInput.value.trim() === '') {
                showCustomError(passwordInput, 'Password is required');
                isValid = false;
            } else if (passwordInput && passwordInput.value.length < 8) {
                showCustomError(passwordInput, 'Password must be at least 8 characters');
                isValid = false;
            } else if (passwordInput && !isStrongPassword(passwordInput.value)) {
                showCustomError(passwordInput, 'Password must contain uppercase letters, lowercase letters, and numbers');
                isValid = false;
            }
            
            // Validate Confirm Password
            if (confirmPasswordInput && confirmPasswordInput.value.trim() === '') {
                showCustomError(confirmPasswordInput, 'Please confirm your password');
                isValid = false;
            } else if (passwordInput && confirmPasswordInput && passwordInput.value !== confirmPasswordInput.value) {
                showCustomError(confirmPasswordInput, 'Passwords do not match');
                isValid = false;
            }
            
            if (isValid) {
                // Show loading state before form submission
                const registerBtn = document.querySelector('.register-btn');
                
                // Show loading state with spinner
                registerBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #ffffff; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px; vertical-align: middle;"></span>Creating Account...';
                registerBtn.disabled = true;
                
                // Allow form to submit after a short delay to show loading state
                setTimeout(() => {
                    // Submit the form programmatically
                    registrationForm.submit();
                }, 500);
                
                // Prevent immediate submission to allow loading state to show
                e.preventDefault();
            } else {
                // Only prevent submission if validation fails
                e.preventDefault();
            }
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    }

    // Add input event listeners for real-time error clearing
    const inputs = [firstNameInput, lastNameInput, emailInput, passwordInput, confirmPasswordInput];
    
    // Add conditional inputs
    if (!isTeacherRegistration) {
        inputs.push(studentNumberInput, yearLevelSelect, courseInput);
    } else {
        inputs.push(departmentInput);
    }
    
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function() {
                clearError(this);
            });
        }
    });

    // Email validation helper
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Password strength validation
    function isStrongPassword(password) {
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        return hasUpperCase && hasLowerCase && hasNumbers;
    }

    // Error handling functions
    function showCustomError(input, message) {
        clearError(input); // Clear any existing error
        
        input.style.borderColor = '#dc3545';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message show';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
    }

    function clearError(input) {
        input.style.borderColor = '';
        
        const errorMessage = input.parentNode.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }

    function clearAllErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => error.remove());
        
        const inputs = document.querySelectorAll('.form-group input, .form-group select');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }

    // QR Modal functionality
    const qrModal = document.getElementById('qrSuccessModal');
    const closeModalBtn = document.getElementById('closeModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const cancelBtn = document.getElementById('cancelBtn');
    const downloadBtn = document.getElementById('downloadBtn');

    // Approval Modal functionality
    const approvalModal = document.getElementById('approvalModal');
    const approvalModalOverlay = document.getElementById('approvalModalOverlay');
    const approvalModalBtn = document.getElementById('approvalModalBtn');

    console.log('QR Modal elements:', {
        qrModal: qrModal,
        closeModalBtn: closeModalBtn,
        modalOverlay: modalOverlay,
        cancelBtn: cancelBtn,
        downloadBtn: downloadBtn
    });

    console.log('Approval Modal elements:', {
        approvalModal: approvalModal,
        approvalModalOverlay: approvalModalOverlay,
        approvalModalBtn: approvalModalBtn
    });

    // Modal display will now be triggered only after successful registration

    // Close modal functions
    function closeModal() {
        if (qrModal) {
            qrModal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Redirect to login page without hiding toast (toast will auto-hide if needed)
            setTimeout(() => {
                window.location.href = '../auth/login.php';
            }, 300);
        }
    }

    // Close approval modal function
    function closeApprovalModal() {
        if (approvalModal) {
            approvalModal.classList.remove('show');
            document.body.style.overflow = '';
            
            // Redirect to login page
            setTimeout(() => {
                window.location.href = '../auth/login.php';
            }, 300);
        }
    }

    // Event listeners for closing modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    // Event listeners for approval modal
    if (approvalModalBtn) {
        approvalModalBtn.addEventListener('click', closeApprovalModal);
    }

    if (approvalModalOverlay) {
        approvalModalOverlay.addEventListener('click', closeApprovalModal);
    }

    // Download QR code functionality
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            console.log('Download button clicked');
            const qrImage = document.querySelector('.qr-code');
            console.log('QR Image element:', qrImage);
            
            if (qrImage && qrImage.src) {
                console.log('QR Image src found:', qrImage.src.substring(0, 100) + '...');
                
                // Get student number from the modal
                const studentNumberElement = document.querySelector('.info-item:nth-child(2) span');
                const studentNumber = studentNumberElement ? studentNumberElement.textContent.trim() : 'Unknown';
                
                // Create filename with student number
                const filename = `Student_${studentNumber}.png`;
                
                // Create a temporary link element for download
                const link = document.createElement('a');
                link.href = qrImage.src;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show success toast
                showToast('Success', 'QR code downloaded successfully!', 'success');
                
                // Close modal and redirect to login after download
                setTimeout(() => {
                    closeModal();
                    // Redirect to login page
                    setTimeout(() => {
                        window.location.href = '../auth/login.php';
                    }, 300);
                }, 1000);
            } else {
                console.log('No QR code image found for download');
                // Show error toast if no QR code is available
                showToast('Error', 'QR code not available for download', 'error');
            }
        });
    } else {
        console.log('Download button not found');
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (qrModal && qrModal.classList.contains('show')) {
                closeModal();
            } else if (approvalModal && approvalModal.classList.contains('show')) {
                closeApprovalModal();
            }
        }
    });

    // Prevent modal content from closing when clicked
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Prevent approval modal content from closing when clicked
    const approvalModalContent = document.querySelector('.approval-modal-content');
    if (approvalModalContent) {
        approvalModalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

// Add slide animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
