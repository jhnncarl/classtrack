// Admin Settings JavaScript - ClassTrack Admin Settings Page

// Global variable to track profile photo changes
let hasProfilePhotoChanged = false;

// Global variable to store original form values
let originalFormValues = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminSettings();
});

function initializeAdminSettings() {
    // Check if user has editProfile permission before initializing form features
    checkEditProfilePermission().then(hasPermission => {
        if (!hasPermission) {
            console.log('Edit Profile permission denied - skipping form initialization');
            // Disable all form inputs and buttons
            disableProfileForm();
            return;
        }
        
        // User has permission, proceed with normal initialization
        console.log('Edit Profile permission granted - initializing form features');
        
        // Store original form values FIRST before any other initialization
        storeOriginalFormValues();
        
        // Initialize tab navigation
        initializeTabNavigation();
        
        // Initialize form validation
        initializeFormValidation();
        
        // Initialize password visibility toggle
        initializePasswordToggle();
        
        // Initialize save button (now that original values is stored)
        initializeSaveButton();
        
        // Initialize form change detection
        initializeFormChangeDetection();
    });
}

// Check editProfile permission from server
async function checkEditProfilePermission() {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=checkEditProfilePermission'
        });
        
        const result = await response.json();
        return result.success || false;
    } catch (error) {
        console.error('Error checking edit profile permission:', error);
        return false; // Default to denied on error
    }
}

// Disable profile form when permission is denied
function disableProfileForm() {
    // Let the CSS overlay handle all blocking - no need to disable individual elements
    console.log('Profile form disabled via CSS overlay - insufficient permissions');
}

// Store Original Form Values
function storeOriginalFormValues() {
    const formInputs = document.querySelectorAll('.form-control');
    
    originalFormValues = {
        username: document.getElementById('username')?.value || '',
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
    };
    
    console.log('Original admin form values stored:', originalFormValues);
}

// Initialize Tab Navigation
function initializeTabNavigation() {
    const tabButtons = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
}

// Initialize Form Validation
function initializeFormValidation() {
    const formInputs = document.querySelectorAll('.form-control');
    
    formInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Clear validation message on input and revalidate
            clearFieldValidation(this);
            validateField(this);
        });
    });
}

// Validate Individual Field
function validateField(field) {
    const fieldName = field.id;
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Basic validation rules
    switch(fieldName) {
        case 'username':
            if (value.length < 3) {
                isValid = false;
                message = 'Username must be at least 3 characters';
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                isValid = false;
                message = 'Only letters, numbers, and underscores allowed';
            }
            break;
            
        case 'currentPassword':
            if (value.length > 0 && value.length < 6) {
                isValid = false;
                message = 'Password must be at least 6 characters';
            } else if (value.length >= 6) {
                // Valid password length
                isValid = true;
            }
            // If field is empty, don't show validation error
            break;
            
        case 'newPassword':
            if (value.length > 0) {
                if (value.length < 6) {
                    isValid = false;
                    message = 'Password must be at least 6 characters';
                } else {
                    // Check password strength
                    updatePasswordStrength(value);
                    isValid = true;
                }
            }
            // If field is empty, don't show validation error
            break;
            
        case 'confirmPassword':
            const newPassword = document.getElementById('newPassword').value;
            if (value.length > 0 && value !== newPassword) {
                isValid = false;
                message = 'Passwords do not match';
            }
            break;
    }
    
    // Update field validation state
    updateFieldValidation(field, isValid, message);
    
    return isValid;
}

// Update Field Validation State
function updateFieldValidation(field, isValid, message) {
    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Find the correct parent element for the error message
    // If field is in an input-group, go up one more level to the column
    let parentElement = field.parentNode;
    if (parentElement.classList.contains('input-group')) {
        parentElement = parentElement.parentNode;
    }
    
    // Remove existing validation message
    const existingMessage = parentElement.querySelector('.form-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (!isValid) {
        field.classList.add('is-invalid');
        
        // Add error message below the input group
        const messageElement = document.createElement('div');
        messageElement.className = 'form-message error';
        messageElement.textContent = message;
        parentElement.appendChild(messageElement);
    } else if (field.value.trim().length > 0) {
        field.classList.add('is-valid');
    }
    
    // Update save button state
    updateSaveButtonState();
}

// Clear Field Validation
function clearFieldValidation(field) {
    field.classList.remove('is-valid', 'is-invalid');
    
    const existingMessage = field.parentNode.querySelector('.form-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Clear password strength indicator if it's a password field
    if (field.id === 'newPassword') {
        clearPasswordStrength();
    }
}

// Initialize Password Toggle
function initializePasswordToggle() {
    // Password visibility toggle is handled by onclick attributes in HTML
    // Additional initialization can be added here if needed
}

// Toggle Password Visibility
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const toggleButton = field.nextElementSibling;
    const icon = toggleButton.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Update Password Strength Indicator
function updatePasswordStrength(password) {
    // Create or find password strength indicator
    let strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) {
        strengthIndicator = document.createElement('div');
        strengthIndicator.id = 'passwordStrength';
        strengthIndicator.className = 'password-strength';
        
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength-bar';
        strengthIndicator.appendChild(strengthBar);
        
        const newPasswordField = document.getElementById('newPassword');
        newPasswordField.parentNode.appendChild(strengthIndicator);
    }
    
    const strengthBar = strengthIndicator.querySelector('.password-strength-bar');
    let strength = 'weak';
    
    // Calculate password strength
    if (password.length >= 8) {
        if (password.match(/[a-z]/) && password.match(/[A-Z]/) && password.match(/[0-9]/) && password.match(/[^a-zA-Z0-9]/)) {
            strength = 'strong';
        } else if (password.match(/[a-z]/) && password.match(/[A-Z]/) && password.match(/[0-9]/)) {
            strength = 'medium';
        }
    }
    
    // Update strength indicator
    strengthBar.className = 'password-strength-bar ' + strength;
}

// Clear Password Strength Indicator
function clearPasswordStrength() {
    const strengthIndicator = document.getElementById('passwordStrength');
    if (strengthIndicator) {
        strengthIndicator.remove();
    }
}

// Initialize Save Button
function initializeSaveButton() {
    const saveButton = document.getElementById('saveInfoBtn');
    if (saveButton) {
        // Ensure button starts disabled by default
        saveButton.disabled = true;
        // Set initial state (now that originalFormValues is populated)
        updateSaveButtonState();
        console.log('Admin save button initialized. Original values:', originalFormValues);
    }
}

// Update Save Button State
function updateSaveButtonState() {
    const saveButton = document.getElementById('saveInfoBtn');
    if (!saveButton) return;
    
    let hasChanges = false;
    let allValid = true;
    
    // Check each form field for changes
    const fieldsToCheck = ['username', 'currentPassword', 'newPassword', 'confirmPassword'];
    
    fieldsToCheck.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            const currentValue = field.value.trim();
            const originalValue = originalFormValues[fieldId] || '';
            
            // Check for changes in any field
            if (currentValue !== originalValue) {
                hasChanges = true;
                console.log(`Change detected in ${fieldId}: "${currentValue}" != "${originalValue}"`);
            }
            
            // Check validation status
            if (field.classList.contains('is-invalid')) {
                allValid = false;
            }
        }
    });
    
    // Additional validation for password fields
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const currentPassword = document.getElementById('currentPassword').value;
    
    if (newPassword.length > 0) {
        if (currentPassword.length === 0) {
            allValid = false;
        }
        if (confirmPassword !== newPassword) {
            allValid = false;
        }
    }
    
    // Check if profile photo has changed
    if (hasProfilePhotoChanged) {
        hasChanges = true;
    }
    
    // Enable/disable save button based on changes and validation
    const shouldEnable = hasChanges && allValid;
    console.log(`Admin save button state: hasChanges=${hasChanges}, allValid=${allValid}, shouldEnable=${shouldEnable}`);
    
    if (shouldEnable) {
        saveButton.disabled = false;
    } else {
        saveButton.disabled = true;
    }
}

// Initialize Form Change Detection
function initializeFormChangeDetection() {
    const formInputs = document.querySelectorAll('.form-control');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            updateSaveButtonState();
        });
        
        input.addEventListener('change', function() {
            updateSaveButtonState();
        });
    });
}

// Save Admin Information
function saveAdminInfo() {
    console.log('=== SAVE ADMIN INFO FUNCTION STARTED ===');
    
    const saveButton = document.getElementById('saveInfoBtn');
    const originalContent = saveButton.innerHTML;
    
    console.log('Admin save button found:', !!saveButton);
    
    // Show loading state
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="loading-spinner"></span> Saving...';
    
    // Check if we need to handle file upload along with form data
    const fileInput = document.getElementById('profilePhotoInput');
    const hasFileToUpload = hasProfilePhotoChanged && fileInput && fileInput.files[0];
    
    // Validate all fields first
    const formInputs = document.querySelectorAll('.form-control');
    let allValid = true;
    
    console.log('Validating admin form fields...');
    
    formInputs.forEach(input => {
        const isValid = validateField(input);
        console.log(`Field ${input.id} valid:`, isValid);
        if (!isValid) {
            allValid = false;
        }
    });
    
    console.log('All admin fields valid:', allValid);
    
    if (!allValid) {
        // Restore button state
        saveButton.disabled = false;
        saveButton.innerHTML = originalContent;
        showNotification('Please correct the errors in the form', 'error');
        return;
    }
    
    // Collect form data
    const formData = {
        action: 'update_admin_profile',
        username: document.getElementById('username')?.value || '',
        currentPassword: document.getElementById('currentPassword').value,
        newPassword: document.getElementById('newPassword').value,
        confirmPassword: document.getElementById('confirmPassword').value
    };
    
    console.log('Admin form data collected:', formData);
    
    // Handle password change if provided
    if (formData.newPassword) {
        console.log('Password change requested');
        if (formData.newPassword !== formData.confirmPassword) {
            console.log('Password mismatch error');
            saveButton.disabled = false;
            saveButton.innerHTML = originalContent;
            showNotification('New passwords do not match', 'error');
            return;
        }
        
        if (!formData.currentPassword) {
            console.log('Current password missing error');
            saveButton.disabled = false;
            saveButton.innerHTML = originalContent;
            showNotification('Current password is required to change password', 'error');
            return;
        }
    } else {
        console.log('No password change requested');
    }
    
    let requestBody;
    let headers = {};
    
    if (hasFileToUpload) {
        // Use FormData for file upload + form data
        requestBody = new FormData();
        Object.keys(formData).forEach(key => {
            requestBody.append(key, formData[key]);
        });
        requestBody.append('profilePicture', fileInput.files[0]);
        
        console.log('Using FormData for file upload');
    } else {
        // Use URLSearchParams for form data only
        requestBody = new URLSearchParams(formData);
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
        
        console.log('Using URLSearchParams for form data only');
    }
    
    console.log('Sending AJAX request to admin settings.php...');
    
    // Send AJAX request to PHP backend
    fetch('settings.php', {
        method: 'POST',
        headers: headers,
        body: requestBody
    })
    .then(response => {
        console.log('AJAX response received:', response);
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        return response.json();
    })
    .then(data => {
        console.log('JSON response parsed:', data);
        
        if (data.success) {
            console.log('Admin update successful');
            // Show success state
            saveButton.innerHTML = '<i class="bi bi-check-lg me-1"></i>Saved';
            saveButton.classList.remove('btn-primary');
            saveButton.classList.add('btn-success');
            
            showNotification(data.message || 'Admin profile information saved successfully!', 'success');
            
            // Update stored original values
            storeOriginalFormValues();
            
            // Refresh navbar profile picture if function exists
            if (typeof window.refreshNavbarProfile === 'function') {
                window.refreshNavbarProfile();
            }
            
            // Reset button after delay
            setTimeout(function() {
                saveButton.innerHTML = originalContent;
                saveButton.classList.remove('btn-success');
                saveButton.classList.add('btn-primary');
                updateSaveButtonState();
                
                // Reset profile photo changed flag after successful save
                hasProfilePhotoChanged = false;
                
                // Clear password fields after successful save
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                clearPasswordStrength();
                
                // Clear validation states for password fields
                const passwordFields = ['currentPassword', 'newPassword', 'confirmPassword'];
                passwordFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        clearFieldValidation(field);
                    }
                });
                
                // Auto refresh browser after successful update to show applied changes
                // Wait a bit longer to ensure server-side changes are fully processed
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }, 1500);
        } else {
            console.log('Admin update failed:', data.message);
            // Show error
            saveButton.disabled = false;
            saveButton.innerHTML = originalContent;
            showNotification(data.message || 'Error updating admin profile', 'error');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        console.error('Error details:', error.message);
        console.error('Error stack:', error.stack);
        saveButton.disabled = false;
        saveButton.innerHTML = originalContent;
        showNotification('Network error occurred. Please try again.', 'error');
    });
}

// Reset Form
function resetForm() {
    const formInputs = document.querySelectorAll('.form-control');
    
    // Reset to stored original values
    const resetValues = {
        ...originalFormValues,
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
    };
    
    // Apply original values and clear validation
    formInputs.forEach(input => {
        if (resetValues.hasOwnProperty(input.id)) {
            input.value = resetValues[input.id];
        }
        clearFieldValidation(input);
    });
    
    // Reset profile image to default
    resetProfileImage();
    
    // Reset profile photo changed flag
    hasProfilePhotoChanged = false;
    
    // Clear password strength indicator
    clearPasswordStrength();
    
    // Update save button state
    updateSaveButtonState();
    
    // Show notification
    showNotification('Form has been reset to original values', 'info');
    
    // Log reset action for debugging
    console.log('Admin form reset to original values:', resetValues);
}

// Change Profile Picture
function changeProfilePicture() {
    // Trigger file picker dialog
    const fileInput = document.getElementById('profilePhotoInput');
    if (fileInput) {
        fileInput.click();
    } else {
        // Fallback: Create file input if it doesn't exist
        const newFileInput = document.createElement('input');
        newFileInput.type = 'file';
        newFileInput.id = 'profilePhotoInput';
        newFileInput.accept = 'image/*';
        newFileInput.style.display = 'none';
        newFileInput.onchange = function(e) {
            handleProfilePhotoSelect(e.target);
        };
        document.body.appendChild(newFileInput);
        newFileInput.click();
    }
}

// Handle Profile Photo Selection
function handleProfilePhotoSelect(input) {
    const file = input.files[0];
    
    if (!file) {
        return; // User cancelled selection
    }
    
    // Validate file type
    const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!validImageTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'error');
        return;
    }
    
    // Validate file size (optional - 5MB limit)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        showNotification('Image file must be smaller than 5MB', 'error');
        return;
    }
    
    // Read and display image
    const reader = new FileReader();
    reader.onload = function(e) {
        const imageUrl = e.target.result;
        displayProfileImage(imageUrl);
        
        // Mark that profile photo has changed (but don't upload yet)
        hasProfilePhotoChanged = true;
        
        // Update save button state
        updateSaveButtonState();
        
        console.log('Admin profile photo selected and ready for upload:', {
            name: file.name,
            size: file.size,
            type: file.type
        });
    };
    
    reader.onerror = function() {
        showNotification('Failed to read selected image', 'error');
    };
    
    reader.readAsDataURL(file);
}

// Display Profile Image Preview
function displayProfileImage(imageUrl) {
    const preview = document.getElementById('profileImagePreview');
    const defaultIcon = document.getElementById('defaultAvatarIcon');
    
    if (preview && defaultIcon) {
        // Show image preview
        preview.src = imageUrl;
        preview.style.display = 'block';
        
        // Hide default icon
        defaultIcon.style.display = 'none';
    }
}

// Reset Profile Image to Default
function resetProfileImage() {
    const preview = document.getElementById('profileImagePreview');
    const defaultIcon = document.getElementById('defaultAvatarIcon');
    
    if (preview && defaultIcon) {
        // Clear image preview
        preview.src = '';
        preview.style.display = 'none';
        
        // Show default icon
        defaultIcon.style.display = 'block';
    }
}

// Go Back
function goBack() {
    // Navigate back to admin dashboard
    window.location.href = 'dashboard.php';
}

// Show Notification (Toast)
function showNotification(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast ${type === 'success' ? 'toast-success' : type === 'warning' ? 'toast-warning' : type === 'danger' ? 'toast-error' : 'toast-info'}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-icon">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : type === 'warning' ? 'bi-exclamation-triangle-fill' : type === 'danger' ? 'bi-x-circle-fill' : 'bi-info-circle-fill'}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : type === 'danger' ? 'Error' : 'Information'}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button type="button" class="toast-close" data-bs-dismiss="toast" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const saveButton = document.getElementById('saveInfoBtn');
        if (saveButton && !saveButton.disabled) {
            saveAdminInfo();
        }
    }
    
    // Escape to go back to admin dashboard
    if (e.key === 'Escape') {
        goBack();
    }
});

// Console log for debugging
console.log('ClassTrack Admin Settings Page initialized successfully!');
