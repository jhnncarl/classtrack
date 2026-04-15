/**
 * ClassTrack Admin Login JavaScript
 * Handles form validation and notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.admin-login-form');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const signInBtn = document.getElementById('adminSignInBtn');
    const alertElements = document.querySelectorAll('.alert');

    // Show Toast Notification using existing toast system
    function showToast(message, type = 'error') {
        // Get or create toast container
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        // Set icon and title based on type
        let iconHtml = '';
        let title = '';
        if (type === 'success') {
            iconHtml = '<i class="bi bi-check-circle-fill"></i>';
            title = 'Success';
        } else if (type === 'error') {
            iconHtml = '<i class="bi bi-x-circle-fill"></i>';
            title = 'Error';
        } else if (type === 'info') {
            iconHtml = '<i class="bi bi-info-circle-fill"></i>';
            title = 'Info';
        } else if (type === 'warning') {
            iconHtml = '<i class="bi bi-exclamation-triangle-fill"></i>';
            title = 'Warning';
        }

        toast.innerHTML = `
            <div class="toast-icon">${iconHtml}</div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;

        // Add toast to container
        toastContainer.appendChild(toast);

        // Show toast with animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }, 5000);
    }

    // Show error messages using toast notifications
    function showError(message) {
        showToast(message, 'error');
    }

    // Validate username format
    function validateUsername(username) {
        // Username should be at least 3 characters, alphanumeric with underscore/hyphen allowed
        const usernameRegex = /^[a-zA-Z0-9_-]{3,}$/;
        return usernameRegex.test(username);
    }

    // Show field validation error
    function showFieldError(input, message) {
        input.classList.add('error');
        
        // Remove existing error message if any
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error message
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.cssText = `
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        `;
        input.parentNode.appendChild(errorElement);
    }

    // Clear field validation error
    function clearFieldError(input) {
        input.classList.remove('error');
        const errorElement = input.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    // Validate form
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        clearFieldError(usernameInput);
        clearFieldError(passwordInput);
        
        // Validate username
        const username = usernameInput.value.trim();
        if (!username) {
            showFieldError(usernameInput, 'Username is required');
            isValid = false;
        } else if (!validateUsername(username)) {
            showFieldError(usernameInput, 'Username must be at least 3 characters and contain only letters, numbers, underscore, or hyphen');
            isValid = false;
        }
        
        // Validate password
        const password = passwordInput.value;
        if (!password) {
            showFieldError(passwordInput, 'Password is required');
            isValid = false;
        } else if (password.length < 6) {
            showFieldError(passwordInput, 'Password must be at least 6 characters');
            isValid = false;
        }
        
        return isValid;
    }

    // Handle form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        const originalBtnContent = signInBtn.innerHTML;
        signInBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing In...';
        signInBtn.disabled = true;
        
        // Submit form
        setTimeout(() => {
            loginForm.submit();
        }, 500);
    });

    // Real-time validation
    usernameInput.addEventListener('blur', function() {
        const username = this.value.trim();
        if (username && !validateUsername(username)) {
            showFieldError(this, 'Username must be at least 3 characters and contain only letters, numbers, underscore, or hyphen');
        } else {
            clearFieldError(this);
        }
    });

    passwordInput.addEventListener('blur', function() {
        const password = this.value;
        if (password && password.length < 6) {
            showFieldError(this, 'Password must be at least 6 characters');
        } else {
            clearFieldError(this);
        }
    });

    // Clear errors on input
    usernameInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            clearFieldError(this);
        }
    });

    passwordInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            clearFieldError(this);
        }
    });

    // Handle Enter key in form fields
    usernameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            passwordInput.focus();
        }
    });

    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Show PHP error messages as toast notifications
    alertElements.forEach(function(alert) {
        if (alert.textContent.trim()) {
            showError(alert.textContent.trim());
            alert.style.display = 'none';
        }
    });

    // Add input focus effects
    const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentNode.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.classList.remove('focused');
        });
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Alt + U to focus username input
        if (e.altKey && e.key === 'u') {
            e.preventDefault();
            usernameInput.focus();
        }
        // Alt + P to focus password input
        if (e.altKey && e.key === 'p') {
            e.preventDefault();
            passwordInput.focus();
        }
        // Alt + S to submit form
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            if (document.activeElement === usernameInput || document.activeElement === passwordInput) {
                loginForm.dispatchEvent(new Event('submit'));
            }
        }
    });

    // Add visual feedback for successful validation
    function showSuccess(input) {
        input.classList.add('success');
        setTimeout(() => {
            input.classList.remove('success');
        }, 2000);
    }

    // Check for admin-specific usernames (optional enhancement)
    function isAdminUsername(username) {
        const adminPrefixes = ['admin', 'administrator', 'root', 'super'];
        const usernameLower = username.toLowerCase();
        return adminPrefixes.some(prefix => usernameLower.startsWith(prefix));
    }

    usernameInput.addEventListener('blur', function() {
        const username = this.value.trim();
        if (username && validateUsername(username) && !isAdminUsername(username)) {
            // Show warning for non-admin usernames (but don't block submission)
            console.log('Warning: This may not be an administrator username');
        }
    });

    // Initialize page
    console.log('Admin Login page loaded successfully');
});
