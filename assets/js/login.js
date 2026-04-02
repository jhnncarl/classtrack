document.addEventListener('DOMContentLoaded', function() {
    console.log('Login.js loaded successfully'); // Debug
    console.log('Current URL:', window.location.pathname); // Debug
    
    // Prevent form resubmission confirmation dialog
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Dynamic greeting logic
    updateDynamicGreeting();
    
    // Check if there's an error message on the page and show toast
    const errorAlert = document.querySelector('.alert-error');
    console.log('Login - Error alert found:', errorAlert); // Debug
    if (errorAlert) {
        const errorText = errorAlert.textContent.trim();
        console.log('Login - Error text:', errorText); // Debug
        console.log('Login - Error text length:', errorText.length); // Debug
        
        // Only process if there's actual error text
        if (errorText && errorText.length > 0) {
            // Hide the PHP alert
            errorAlert.style.display = 'none';
            
            // Show the error toast notification with appropriate type
            const toastType = determineToastType(errorText);
            const toastTitle = getToastTitle(errorText);
            console.log('Login - Showing toast:', toastTitle, errorText, toastType); // Debug
            
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                showToast(toastTitle, errorText, toastType);
            }, 100);
        } else {
            console.log('Login - No error text found, hiding alert'); // Debug
        }
    } else {
        console.log('Login - No error alert found on page'); // Debug
    }
    
    // Modal elements
    const registrationModal = document.getElementById('registrationModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const signupLink = document.querySelector('.signup-link');
    const closeModalBtn = document.getElementById('closeModal');
    const studentBtn = document.getElementById('studentBtn');
    const teacherBtn = document.getElementById('teacherBtn');

    // Toast elements - use the standard toast container
    const toastContainer = document.getElementById('toast-container');
    console.log('Login - Toast container found:', toastContainer); // Debug

    // Form elements
    const loginForm = document.querySelector('.login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const signInBtn = document.getElementById('signInBtn');
    
    // Debug: Check if elements exist
    console.log('Login - Form found:', loginForm); // Debug
    console.log('Login - Email input found:', emailInput); // Debug
    console.log('Login - Password input found:', passwordInput); // Debug

    // Modal functionality
    function showModal() {
        if (registrationModal) {
            registrationModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function hideModal() {
        if (registrationModal) {
            registrationModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }

    // Show modal when signup link is clicked
    if (signupLink) {
        signupLink.addEventListener('click', function(e) {
            e.preventDefault();
            showModal();
        });
    }

    // Hide modal when close button is clicked
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', hideModal);
    }

    // Hide modal when overlay is clicked
    if (modalOverlay) {
        modalOverlay.addEventListener('click', hideModal);
    }

    // Handle student button click
    if (studentBtn) {
        studentBtn.addEventListener('click', function() {
            // Redirect to student registration page
            window.location.href = '../student/register.php';
        });
    }

    // Handle teacher button click
    if (teacherBtn) {
        teacherBtn.addEventListener('click', function() {
            // Redirect to teacher registration page
            window.location.href = '../teacher/register.php';
        });
    }

    // Hide modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && registrationModal && registrationModal.classList.contains('show')) {
            hideModal();
        }
    });

    // Helper functions for toast notifications
    function determineToastType(message) {
        if (message.includes('Account not found') || message.includes('check your email')) {
            return 'warning';
        } else if (message.includes('Invalid password') || message.includes('try again')) {
            return 'error';
        } else if (message.includes('pending approval') || message.includes('suspended') || message.includes('inactive')) {
            return 'warning';
        } else if (message.includes('Login failed') || message.includes('try again later')) {
            return 'error';
        } else {
            return 'error'; // Default to error type
        }
    }

    function getToastTitle(message) {
        if (message.includes('Account not found')) {
            return 'Account Not Found';
        } else if (message.includes('Invalid password')) {
            return 'Invalid Password';
        } else if (message.includes('pending approval')) {
            return 'Account Pending';
        } else if (message.includes('suspended')) {
            return 'Account Suspended';
        } else if (message.includes('inactive')) {
            return 'Account Inactive';
        } else if (message.includes('Login failed')) {
            return 'Login Failed';
        } else {
            return 'Error';
        }
    }

    // Toast functions
    function showToast(title, message, type = 'error') {
        console.log('showToast called with:', title, message, type); // Debug
        console.log('Toast container found:', toastContainer); // Debug
        
        if (!toastContainer) {
            console.error('Toast container not found!');
            return;
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        console.log('Created toast element with class:', toast.className);
        
        // Create icon based on type
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
        
        toast.innerHTML = `
            <div class="toast-icon">
                ${iconSvg}
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        `;
        
        console.log('Toast inner HTML set');
        
        // Add to container
        toastContainer.appendChild(toast);
        console.log('Toast added to container');
        
        // Add close functionality
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            toast.remove();
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
        
        // Show animation
        setTimeout(() => {
            toast.classList.add('show');
            console.log('Toast show class added');
        }, 100);
    }
    
    // Fallback function for showLoginToast (in case of old references)
    function showLoginToast(title, message, type = 'error') {
        showToast(title, message, type);
    }

    // Form validation and submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            console.log('Login - Form submitted'); // Debug
            console.log('Login - Email value:', emailInput ? emailInput.value : 'emailInput not found'); // Debug
            console.log('Login - Password value:', passwordInput ? (passwordInput.value ? '***' : 'empty') : 'passwordInput not found'); // Debug
            
            // Clear previous errors
            clearAllErrors();
            
            let isValid = true;
            
            // Validate Email
            if (emailInput && emailInput.value.trim() === '') {
                console.log('Login - Email validation failed: empty'); // Debug
                showCustomError(emailInput, 'Email is required');
                isValid = false;
            } else if (emailInput && !emailInput.value.trim().includes('@')) {
                console.log('Login - Email validation failed: invalid format'); // Debug
                showCustomError(emailInput, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Validate Password
            if (passwordInput && passwordInput.value.trim() === '') {
                console.log('Login - Password validation failed: empty'); // Debug
                showCustomError(passwordInput, 'Password is required');
                isValid = false;
            } else if (passwordInput && passwordInput.value.length < 6) {
                console.log('Login - Password validation failed: too short'); // Debug
                showCustomError(passwordInput, 'Password must be at least 6 characters');
                isValid = false;
            }
            
            console.log('Login - Validation result:', isValid); // Debug
            
            if (isValid) {
                console.log('Login - Form will submit to PHP'); // Debug
                
                // Change button to "Signing in..." state
                if (signInBtn) {
                    const originalText = signInBtn.textContent;
                    signInBtn.textContent = 'Signing in...';
                    signInBtn.disabled = true;
                    
                    // Reset button after 5 seconds in case of server issues
                    setTimeout(() => {
                        signInBtn.textContent = originalText;
                        signInBtn.disabled = false;
                    }, 5000);
                }
                
                // Allow the form to actually submit to PHP
                // The form will submit normally to the PHP backend
                return true;
            } else {
                console.log('Login - Form submission prevented due to validation errors'); // Debug
                // Only prevent submission if validation fails
                e.preventDefault();
            }
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    } else {
        console.log('Login - Form not found, event listener not attached'); // Debug
    }

    // Add input event listeners for real-time error clearing
    const inputs = [emailInput, passwordInput];
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function() {
                clearError(this);
            });
        }
    });

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
        
        const inputs = document.querySelectorAll('.form-group input');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }

    // Dynamic greeting function
    function updateDynamicGreeting() {
        const greetingElement = document.getElementById('greeting-text');
        if (!greetingElement) return;

        // Get visit count from localStorage
        const visitKey = 'classtrack_visit_count';
        let visitCount = localStorage.getItem(visitKey);
        
        if (visitCount === null) {
            // First time visiting
            localStorage.setItem(visitKey, '1');
            greetingElement.textContent = 'Welcome';
        } else {
            // Returning user
            const newCount = parseInt(visitCount) + 1;
            localStorage.setItem(visitKey, newCount.toString());
            
            // Show "Welcome back" for returning users (2+ visits)
            if (newCount >= 2) {
                greetingElement.textContent = 'Welcome back';
            } else {
                greetingElement.textContent = 'Welcome';
            }
        }
    }
});
