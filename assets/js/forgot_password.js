document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggle = document.getElementById('password-toggle');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeOffIcon = document.getElementById('eye-off-icon');

    // OTP functionality
    const sendOtpBtn = document.getElementById('sendOtpBtn');
    const otpInput = document.getElementById('otp');
    const emailInput = document.getElementById('email');

    // Toast elements - use the standard toast container
    const toast = document.getElementById('otp-toast');
    const toastClose = document.getElementById('toastClose');

    // Form elements
    const forgotPasswordForm = document.querySelector('.forgot-password-form');

    // OTP countdown variables
    let countdownInterval;
    let countdownTime = 60;

    // Password visibility toggle
    if (passwordToggle && passwordInput && eyeIcon && eyeOffIcon) {
        passwordToggle.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, eyeIcon, eyeOffIcon);
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

    // Send OTP functionality
    if (sendOtpBtn) {
        sendOtpBtn.addEventListener('click', function() {
            // Prevent multiple clicks
            if (sendOtpBtn.disabled) {
                return;
            }
            
            // Validate email first
            if (!emailInput || emailInput.value.trim() === '') {
                showCustomError(emailInput, 'Email address is required');
                return;
            } else if (!isValidEmail(emailInput.value.trim())) {
                showCustomError(emailInput, 'Please enter a valid email address');
                return;
            }

            // Clear any existing error
            clearError(emailInput);

            // Disable button immediately and show loading state
            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';
            sendOtpBtn.classList.add('loading');

            // Send OTP via AJAX
            sendOTPRequest(emailInput.value.trim());
        });
    }

    function startOtpCountdown() {
        countdownTime = 60;
        // Button is already disabled from click handler
        updateOtpButton();

        countdownInterval = setInterval(function() {
            countdownTime--;
            updateOtpButton();

            if (countdownTime <= 0) {
                clearInterval(countdownInterval);
                sendOtpBtn.disabled = false;
                sendOtpBtn.textContent = 'Send';
            }
        }, 1000);
    }

    function updateOtpButton() {
        if (countdownTime > 0) {
            sendOtpBtn.textContent = `(${countdownTime}s)`;
        } else {
            sendOtpBtn.textContent = 'Send';
        }
    }

    function showToast() {
        if (toast) {
            // Remove any existing show/hide classes
            toast.classList.remove('show', 'hide');
            
            // Trigger reflow to restart animation
            void toast.offsetWidth;
            
            // Show the toast
            toast.classList.add('show');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideToast();
            }, 5000);
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

    // Form validation and submission
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous errors
            clearAllErrors();

            let isValid = true;

            // Validate Email
            if (emailInput && emailInput.value.trim() === '') {
                showCustomError(emailInput, 'Email address is required');
                isValid = false;
            } else if (emailInput && !isValidEmail(emailInput.value.trim())) {
                showCustomError(emailInput, 'Please enter a valid email address');
                isValid = false;
            }

            // Validate OTP
            if (otpInput && otpInput.value.trim() === '') {
                showCustomError(otpInput, 'OTP code is required');
                isValid = false;
            } else if (otpInput && otpInput.value.trim().length !== 6) {
                showCustomError(otpInput, 'OTP code must be 6 digits');
                isValid = false;
            } else if (otpInput && !/^\d{6}$/.test(otpInput.value.trim())) {
                showCustomError(otpInput, 'OTP code must contain only numbers');
                isValid = false;
            }

            // Validate Password
            if (passwordInput && passwordInput.value.trim() === '') {
                showCustomError(passwordInput, 'New password is required');
                isValid = false;
            } else if (passwordInput && passwordInput.value.length < 8) {
                showCustomError(passwordInput, 'Password must be at least 8 characters');
                isValid = false;
            } else if (passwordInput && !isStrongPassword(passwordInput.value)) {
                showCustomError(passwordInput, 'Password must contain uppercase, lowercase, and number');
                isValid = false;
            }

            if (isValid) {
                // Send reset password request via AJAX
                resetPasswordRequest(
                    emailInput.value.trim(),
                    otpInput.value.trim(),
                    passwordInput.value
                );
            }
        });
    }

    // Add input event listeners for real-time error clearing
    const inputs = [emailInput, otpInput, passwordInput];
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function() {
                clearError(this);
            });
        }
    });

    // OTP input - only allow numbers and limit to 6 digits
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 6 digits
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });

        // Auto-focus next field when 6 digits are entered
        otpInput.addEventListener('keyup', function(e) {
            if (this.value.length === 6) {
                // Move focus to password field
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
        });
    }

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
        
        const inputs = document.querySelectorAll('.form-group input, .otp-input-group input');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }

    function showResetSuccessFeedback() {
        const resetBtn = document.querySelector('.reset-btn');
        const originalText = 'Reset Password'; // Set the correct original text

        // Show loading state
        resetBtn.textContent = 'Resetting...';

        // Simulate loading (since this is UI only)
        setTimeout(() => {
            resetBtn.textContent = '✓ Password Reset!';
            resetBtn.style.backgroundColor = '#28a745';

            // Update toast content for success message
            updateToastMessage('Success', 'Password reset successfully! Redirecting to login.');
            
            // Show the toast
            showToast();

            // Reset button after showing success for 1.5 seconds
            setTimeout(() => {
                resetBtn.textContent = originalText;
                resetBtn.style.backgroundColor = '';
            }, 1500);

            // Redirect to login after 3 seconds
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        }, 1500);
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

    // AJAX Functions
    function sendOTPRequest(email) {
        const formData = new FormData();
        formData.append('action', 'send_otp');
        formData.append('email', email);

        fetch('forgot_password.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove loading class before starting countdown
                sendOtpBtn.classList.remove('loading');
                startOtpCountdown();
                updateToastMessage('Success', data.message);
                showToast();
            } else {
                // Reset button state on error
                sendOtpBtn.disabled = false;
                sendOtpBtn.textContent = 'Send';
                sendOtpBtn.classList.remove('loading');
                showCustomError(emailInput, data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Reset button state on error
            sendOtpBtn.disabled = false;
            sendOtpBtn.textContent = 'Send';
            sendOtpBtn.classList.remove('loading');
            showCustomError(emailInput, 'Failed to send OTP. Please try again.');
        });
    }

    function verifyOTPRequest(email, otp) {
        const formData = new FormData();
        formData.append('action', 'verify_otp');
        formData.append('email', email);
        formData.append('otp', otp);

        return fetch('forgot_password.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error:', error);
            return { success: false, message: 'OTP verification failed' };
        });
    }

    function resetPasswordRequest(email, otp, password) {
        const resetBtn = document.querySelector('.reset-btn');
        const originalText = resetBtn.textContent;

        // Show loading state
        resetBtn.textContent = 'Resetting...';
        resetBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('email', email);
        formData.append('otp', otp);
        formData.append('password', password);

        fetch('forgot_password.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showResetSuccessFeedback();
            } else {
                resetBtn.textContent = originalText;
                resetBtn.disabled = false;
                
                // Show error on appropriate field
                if (data.message.toLowerCase().includes('otp')) {
                    showCustomError(otpInput, data.message);
                } else {
                    showCustomError(passwordInput, data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resetBtn.textContent = originalText;
            resetBtn.disabled = false;
            showCustomError(passwordInput, 'Password reset failed. Please try again.');
        });
    }

    function checkOTPStatus(email) {
        const formData = new FormData();
        formData.append('action', 'check_otp_status');
        formData.append('email', email);

        return fetch('forgot_password.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error:', error);
            return { success: false, has_valid_otp: false, expiry_time: 0 };
        });
    }
});
