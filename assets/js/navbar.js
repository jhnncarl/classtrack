document.addEventListener('DOMContentLoaded', function() {
    const classCodeInput = document.getElementById('classCodeInput');
    const joinClassBtn = document.getElementById('joinClassBtn');
    const cancelBtn = document.querySelector('.btn-cancel');
    
    // Plus Dropdown Elements
    const plusDropdownToggle = document.getElementById('plusDropdownToggle');
    const plusDropdownMenu = document.getElementById('plusDropdownMenu');
    const createSubjectOption = document.getElementById('createSubjectOption');
    const joinClassOption = document.getElementById('joinClassOption');
    
    // Create Subject Modal Elements
    const agreementCheckbox = document.getElementById('agreementCheckbox');
    const continueBtn = document.getElementById('continueBtn');
    const createSubjectCancelBtn = document.querySelector('#createSubjectModal .btn-cancel');
    
    // Create Class Modal Elements
    const classNameInput = document.getElementById('classNameInput');
    const sectionInput = document.getElementById('sectionInput');
    const subjectInput = document.getElementById('subjectInput');
    const createClassBtn = document.getElementById('createClassBtn');
    const createClassCancelBtn = document.getElementById('createClassCancelBtn');
    
    // User role from PHP session
    let currentUserRole = window.currentUserRole || null; // Options: 'Student', 'Teacher', 'Administrator', null
    
    // Function to show toast notification
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        
        if (!toastContainer) {
            return;
        }
        
        // Determine toast type based on message content
        let toastType = type;
        if (message.includes('already enrolled') || message.includes('already joined')) {
            toastType = 'info';
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${toastType}`;
        
        // Create icon based on type
        let iconSvg;
        if (toastType === 'success') {
            iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        } else if (toastType === 'info') {
            iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        } else {
            iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        }
        
        toast.innerHTML = `
            <div class="toast-icon">
                ${iconSvg}
            </div>
            <div class="toast-content">
                <div class="toast-title">${toastType === 'success' ? 'Success' : toastType === 'info' ? 'Info' : 'Error'}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
        
        // Add to container
        toastContainer.appendChild(toast);
        
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
        }, 100);
    }
    
    // Create Subject Modal Functions
    function initializeCreateSubjectModal() {
        if (!agreementCheckbox || !continueBtn) return;
        
        // Initialize button state
        updateContinueButton();
        
        // Add checkbox change event listener
        agreementCheckbox.addEventListener('change', function() {
            updateContinueButton();
        });
        
        // Add continue button click event listener
        continueBtn.addEventListener('click', function() {
            if (agreementCheckbox.checked) {
                // Close Create Subject modal
                const modal = document.getElementById('createSubjectModal');
                if (modal) {
                    const bootstrapModal = bootstrap.Modal.getInstance(modal);
                    if (bootstrapModal) {
                        bootstrapModal.hide();
                    }
                }
                resetCreateSubjectModal();
                
                // Open Create Class modal
                setTimeout(() => {
                    const createClassModal = document.getElementById('createClassModal');
                    if (createClassModal) {
                        const newModal = new bootstrap.Modal(createClassModal);
                        newModal.show();
                    }
                    initializeCreateClassModal();
                }, 500);
            }
        });
        
        // Add cancel button click event listener
        if (createSubjectCancelBtn) {
            createSubjectCancelBtn.addEventListener('click', function() {
                resetCreateSubjectModal();
            });
        }
        
        // Add modal close event listener (X button or backdrop click)
        const modal = document.getElementById('createSubjectModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                resetCreateSubjectModal();
            });
        }
    }
    
    function updateContinueButton() {
        if (!agreementCheckbox || !continueBtn) return;
        
        const isChecked = agreementCheckbox.checked;
        
        if (isChecked) {
            // Enable Continue button
            continueBtn.style.color = '#1a73e8';
            continueBtn.style.opacity = '1';
            continueBtn.style.cursor = 'pointer';
            continueBtn.classList.remove('disabled');
            continueBtn.classList.add('enabled');
        } else {
            // Disable Continue button
            continueBtn.style.color = '#80868b';
            continueBtn.style.opacity = '0.6';
            continueBtn.style.cursor = 'not-allowed';
            continueBtn.classList.remove('enabled');
            continueBtn.classList.add('disabled');
        }
    }
    
    function resetCreateSubjectModal() {
        if (agreementCheckbox) {
            agreementCheckbox.checked = false;
        }
        updateContinueButton();
    }
    
    // Create Class Modal Functions
    function initializeCreateClassModal() {
        if (!classNameInput || !sectionInput || !subjectInput || !createClassBtn) return;
        
        // Initialize button state
        updateCreateClassButton();
        
        // Add input event listeners for real-time validation
        classNameInput.addEventListener('input', updateCreateClassButton);
        sectionInput.addEventListener('input', updateCreateClassButton);
        subjectInput.addEventListener('input', updateCreateClassButton);
        
        // Add Create button click event listener
        createClassBtn.addEventListener('click', function() {
            if (createClassBtn.classList.contains('enabled')) {
                // Show loading state
                createClassBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating..';
                createClassBtn.style.cursor = 'wait';
                createClassBtn.style.pointerEvents = 'none';
                createClassBtn.style.opacity = '0.7';
                createClassBtn.classList.remove('enabled');
                createClassBtn.classList.add('disabled');
                
                // Disable all inputs during creation
                classNameInput.disabled = true;
                sectionInput.disabled = true;
                subjectInput.disabled = true;
                
                // Get schedule input value
                const scheduleInput = document.getElementById('scheduleInput');
                const schedule = scheduleInput ? scheduleInput.value.trim() : '';
                
                // Prepare form data
                const classData = {
                    className: classNameInput.value.trim(),
                    section: sectionInput.value.trim(),
                    subject: subjectInput.value.trim(),
                    schedule: schedule
                };
                
                // Send AJAX request to create class
                fetch('/classtrack/api/create_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(classData)
                })
                .then(response => response.json())
                .then(data => {
                    
                    // Reset loading state
                    resetCreateClassModal();
                    
                    if (data.success) {
                        // Show success toast with subject code - split into two lines
                        showToast('Class created successfully! Class code: ' + data.data.subjectCode, 'success');
                        
                        // Close modal after a short delay
                        setTimeout(() => {
                            const modal = document.getElementById('createClassModal');
                            if (modal) {
                                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                                if (bootstrapModal) {
                                    bootstrapModal.hide();
                                }
                            }
                            
                            // Auto-reload teacher dashboard if we're on it
                            reloadTeacherDashboard();
                        }, 1000);
                    } else {
                        // Show error toast
                        showToast(data.message || 'Failed to create class', 'error');
                    }
                })
                .catch(error => {
                    resetCreateClassModal();
                    showToast('An error occurred. Please try again.', 'error');
                });
            }
        });
        
        // Add cancel button click event listener
        if (createClassCancelBtn) {
            createClassCancelBtn.addEventListener('click', function() {
                resetCreateClassModal();
            });
        }
        
        // Add modal close event listener
        const modal = document.getElementById('createClassModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                resetCreateClassModal();
            });
        }
    }
    
    function updateCreateClassButton() {
        if (!classNameInput || !sectionInput || !subjectInput || !createClassBtn) return;
        
        const className = classNameInput.value.trim();
        const section = sectionInput.value.trim();
        const subject = subjectInput.value.trim();
        
        const allFieldsFilled = className.length > 0 && section.length > 0 && subject.length > 0;
        
        if (allFieldsFilled) {
            // Enable Create button
            createClassBtn.style.color = '#1a73e8';
            createClassBtn.style.cursor = 'pointer';
            createClassBtn.style.opacity = '1';
            createClassBtn.classList.remove('disabled');
            createClassBtn.classList.add('enabled');
        } else {
            // Disable Create button
            createClassBtn.style.color = '#80868b';
            createClassBtn.style.cursor = 'not-allowed';
            createClassBtn.style.opacity = '0.6';
            createClassBtn.classList.remove('enabled');
            createClassBtn.classList.add('disabled');
        }
    }
    
    function resetCreateClassModal() {
        if (classNameInput) {
            classNameInput.value = '';
            classNameInput.disabled = false;
        }
        if (sectionInput) {
            sectionInput.value = '';
            sectionInput.disabled = false;
        }
        if (subjectInput) {
            subjectInput.value = '';
            subjectInput.disabled = false;
        }
        if (createClassBtn) {
            createClassBtn.innerHTML = 'Create';
            createClassBtn.style.pointerEvents = '';
            createClassBtn.style.cursor = '';
            createClassBtn.style.color = '';
            createClassBtn.style.opacity = '';
        }
        
        // Also reset schedule input if it exists
        const scheduleInput = document.getElementById('scheduleInput');
        if (scheduleInput) {
            scheduleInput.value = '';
            scheduleInput.disabled = false;
        }
        
        updateCreateClassButton();
    }
    
    // Plus Dropdown Functions
    function toggleDropdown() {
        const isOpen = plusDropdownMenu.classList.contains('show');
        
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }
    
    function openDropdown() {
        plusDropdownMenu.classList.add('show');
        plusDropdownToggle.classList.add('active');
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', handleOutsideClick);
        }, 100);
    }
    
    function closeDropdown() {
        plusDropdownMenu.classList.remove('show');
        plusDropdownToggle.classList.remove('active');
        document.removeEventListener('click', handleOutsideClick);
    }
    
    function handleOutsideClick(event) {
        if (!event.target.closest('.dropdown-plus')) {
            closeDropdown();
        }
    }
    
    // Role-based visibility function
    function updateDropdownVisibility() {
        if (currentUserRole === 'Student') {
            // Student Role: Only show Join Class
            if (createSubjectOption) createSubjectOption.classList.add('hidden');
            if (joinClassOption) joinClassOption.classList.remove('hidden');
        } else if (currentUserRole === 'Teacher') {
            // Teacher Role: Only show Create Class
            if (createSubjectOption) createSubjectOption.classList.remove('hidden');
            if (joinClassOption) joinClassOption.classList.add('hidden');
        } else if (currentUserRole === 'Administrator' || currentUserRole === null) {
            // Administrator or not logged in - hide both options
            if (createSubjectOption) createSubjectOption.classList.add('hidden');
            if (joinClassOption) joinClassOption.classList.add('hidden');
        }
    }
    
    // Plus dropdown event listeners
    if (plusDropdownToggle && plusDropdownMenu) {
        plusDropdownToggle.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            toggleDropdown();
        });
        
        // Use event delegation for dynamic dropdown items
        plusDropdownMenu.addEventListener('click', function(event) {
            const clickedItem = event.target.closest('.dropdown-item-plus');
            if (!clickedItem) return;
            
            closeDropdown();
            
            // Handle Create Subject/Class option
            if (clickedItem.id === 'createSubjectOption') {
                // Check createClass permission
                if (!window.canCreateClass) {
                    showToast('This feature is currently unavailable. Please contact your administrator to enable access.', 'info');
                    return;
                }
                
                // Trigger the Create Subject modal
                const modal = document.getElementById('createSubjectModal');
                if (modal) {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                }
            }
            
            // Handle Join Class option
            if (clickedItem.id === 'joinClassOption') {
                // Special handling for Student role - always show toast if permission disabled
                if (window.currentUserRole === 'Student' && !window.canJoinClass) {
                    showToast('This feature is currently unavailable. Please contact your administrator to enable access.', 'info');
                    return;
                }
                
                // For other roles, check permission normally
                if (window.currentUserRole !== 'Student' && !window.canJoinClass) {
                    showToast('This feature is currently unavailable. Please contact your administrator to enable access.', 'info');
                    return;
                }
                
                // Trigger the existing Join Class modal
                const modal = document.getElementById('joinClassModal');
                if (modal) {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                }
            }
        });
    }
        
        // Initialize dropdown visibility
        updateDropdownVisibility();
        
        // Initialize Create Subject modal
        initializeCreateSubjectModal();
    
    // Function to join class via API
    function joinClassViaAPI(classCode) {
        
        return fetch('/classtrack/api/join_class.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ classCode: classCode })
        })
        .then(response => response.json())
        .then(data => {
            return {
                success: data.success,
                message: data.message,
                classInfo: data.data
            };
        })
        .catch(() => ({
            success: false,
            message: 'Network error. Please try again.',
            classInfo: null
        }));
    }
    
    // Function to reset modal to original state
    function resetModal() {
        classCodeInput.value = '';
        validateClassCode();
    }
    
    // Function to set loading state
    function setLoadingState(isLoading) {
        if (isLoading) {
            joinClassBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Joining..';
            joinClassBtn.style.cursor = 'wait';
            joinClassBtn.style.pointerEvents = 'none';
            joinClassBtn.style.opacity = '0.7';
        } else {
            joinClassBtn.innerHTML = 'Join';
            joinClassBtn.style.cursor = 'pointer';
            joinClassBtn.style.pointerEvents = 'auto';
            joinClassBtn.style.opacity = '1';
        }
    }
    
    // Function to validate class code input
    function validateClassCode() {
        const inputValue = classCodeInput.value.trim();
        const isValid = inputValue.length >= 5 && inputValue.length <= 8 && /^[a-zA-Z0-9]+$/.test(inputValue);
        
        if (isValid) {
            // Enable the Join button
            joinClassBtn.classList.remove('disabled');
            joinClassBtn.classList.add('enabled');
        } else {
            // Disable the Join button
            joinClassBtn.classList.remove('enabled');
            joinClassBtn.classList.add('disabled');
        }
    }
    
    if (classCodeInput && joinClassBtn) {
        
        // Initial validation
        validateClassCode();
        
        classCodeInput.addEventListener('input', function() {
            validateClassCode();
        });
        classCodeInput.addEventListener('keyup', function() {
            validateClassCode();
        });
        
        // Add click event listener - make it work for span elements
        joinClassBtn.addEventListener('click', async function(e) {
            const inputValue = classCodeInput.value.trim();
            const isValid = inputValue.length >= 5 && inputValue.length <= 8 && /^[a-zA-Z0-9]+$/.test(inputValue);
            
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            // Prevent multiple clicks during loading
            if (joinClassBtn.innerHTML.includes('Joining..')) {
                e.preventDefault();
                return;
            }
            
            // Set loading state
            setLoadingState(true);
            
            try {
                // Join class via API
                const joinResult = await joinClassViaAPI(inputValue.toUpperCase());
                
                // Add 2-second delay before showing result
                setTimeout(() => {
                    // Reset loading state
                    setLoadingState(false);
                    
                    if (joinResult.success) {
                        // Show success toast with class details
                        const classInfo = joinResult.classInfo;
                        let successMessage = 'Successfully joined ' + classInfo.subjectName;
                        if (classInfo.className) {
                            successMessage += ' - ' + classInfo.className;
                        }
                        if (classInfo.sectionName) {
                            successMessage += ' (' + classInfo.sectionName + ')';
                        }
                        showToast(successMessage, 'success');
                        
                        // Close modal and reset after a short delay
                        setTimeout(() => {
                            const modal = document.getElementById('joinClassModal');
                            if (modal) {
                                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                                if (bootstrapModal) {
                                    bootstrapModal.hide();
                                }
                            }
                            resetModal();
                            
                            // Auto-reload subjects page if we're on it
                            reloadSubjectsPage();
                        }, 1500);
                    } else {
                        // Show error/info toast with server message
                        showToast(joinResult.message, 'error');
                    }
                }, 2000); // 2-second delay
                
            } catch (error) {
                
                // Add 2-second delay before showing error
                setTimeout(() => {
                    setLoadingState(false);
                    showToast('An error occurred. Please try again.', 'error');
                }, 2000);
            }
        });
        
        // Add event listener for Cancel button
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                resetModal();
            });
        }
        
        // Add event listener for modal close (X button or backdrop click)
        const modal = document.getElementById('joinClassModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                resetModal();
            });
        }
        
        // Initialize button state
        validateClassCode();
    }
    
    // Close dropdown when Escape key is pressed
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && plusDropdownMenu.classList.contains('show')) {
            closeDropdown();
        }
    });
    
    // Function to reload subjects page after joining a class
    function reloadSubjectsPage() {
        // Check if we're currently on the subjects page
        if (window.location.pathname.includes('/student/subjects.php') || 
            window.location.href.includes('/student/subjects.php')) {
            
            // Show progress bar
            showProgressBar();
            
            // Reload the page content via AJAX to show the newly joined class
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Update progress to half way
                updateProgressBar('loading-half');
                return response.text();
            })
            .then(html => {
                // Update progress to complete
                updateProgressBar('loading-complete');
                
                // Create a temporary DOM element to parse the response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Find the new class grid content
                const newClassGrid = tempDiv.querySelector('.class-grid');
                const currentClassGrid = document.querySelector('.class-grid');
                
                if (newClassGrid && currentClassGrid) {
                    // Replace the current class grid with the updated one
                    currentClassGrid.innerHTML = newClassGrid.innerHTML;
                    
                    // Reinitialize subjects page functionality
                    if (typeof initializeSubjectsPage === 'function') {
                        initializeSubjectsPage();
                    }
                }
                
                // Hide progress bar after a short delay
                setTimeout(() => {
                    hideProgressBar();
                }, 500);
            })
            .catch(error => {
                console.error('Error reloading subjects page:', error);
                // Hide progress bar
                hideProgressBar();
                // Fallback: reload the entire page if AJAX fails
                window.location.reload();
            });
        }
    }
    
    // Function to refresh navbar profile picture dynamically
    window.refreshNavbarProfile = function() {
        const navbarProfileBtn = document.getElementById('navbarProfileBtn');
        if (!navbarProfileBtn) return;
        
        // Fetch current profile path from session via AJAX
        fetch('../settings_page/settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_profile_path'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.profilePath) {
                // Update profile picture in navbar
                const profilePath = data.profilePath;
                const fullPath = '/classtrack/' + profilePath.replace(/^\/+/, '');
                
                // Check if current element has image or default icon
                const currentImg = navbarProfileBtn.querySelector('img');
                const currentIcon = navbarProfileBtn.querySelector('i.bi-person-circle');
                
                if (profilePath && profilePath.trim() !== '') {
                    if (currentImg) {
                        // Update existing image
                        currentImg.src = fullPath;
                        currentImg.style.display = 'block';
                    } else if (currentIcon) {
                        // Replace icon with image
                        currentIcon.style.display = 'none';
                        const img = document.createElement('img');
                        img.src = fullPath;
                        img.alt = 'Profile';
                        img.className = 'rounded-circle';
                        img.style.cssText = 'width: 40px; height: 40px; object-fit: cover; display: block; aspect-ratio: 1/1; border-radius: 50%;';
                        navbarProfileBtn.appendChild(img);
                    }
                } else {
                    // Show default icon
                    if (currentImg) {
                        currentImg.style.display = 'none';
                    }
                    if (currentIcon) {
                        currentIcon.style.display = 'block';
                    }
                }
            }
        })
        .catch(() => {
            // Silently handle profile refresh errors
        });
    };
});

// Progress Bar Functions (Global Access)
// Function to show progress bar
function showProgressBar() {
    const progressBar = document.getElementById('navbarProgressBar');
    
    if (progressBar) {
        progressBar.style.display = 'block';
        progressBar.className = 'navbar-progress-container loading';
        
        // Add body class for smooth padding transition
        document.body.classList.add('has-progress-bar');
        
        // Reset progress fill
        const progressFill = progressBar.querySelector('.navbar-progress-fill');
        if (progressFill) {
            progressFill.style.width = '0%';
        }
        
        // Start animation after a brief delay
        setTimeout(() => {
            updateProgressBar('loading');
        }, 100);
    }
}

// Function to update progress bar state
function updateProgressBar(state) {
    const progressBar = document.getElementById('navbarProgressBar');
    if (progressBar) {
        progressBar.className = 'navbar-progress-container ' + state;
    }
}

// Function to hide progress bar
function hideProgressBar() {
    const progressBar = document.getElementById('navbarProgressBar');
    if (progressBar) {
        progressBar.className = 'navbar-progress-container loading-complete';
        
        // Hide after animation completes
        setTimeout(() => {
            progressBar.style.display = 'none';
            progressBar.className = 'navbar-progress-container';
            // Remove body class for smooth padding transition
            document.body.classList.remove('has-progress-bar');
        }, 500);
    }
}

// Function to reload teacher dashboard after creating a class
function reloadTeacherDashboard() {
    // Check if we're currently on the teacher dashboard
    if (window.location.pathname.includes('/teacher/dashboard.php') || 
        window.location.href.includes('/teacher/dashboard.php')) {
        
        // Show progress bar
        showProgressBar();
        
        // Trigger immediate dashboard update using the existing dashboard instance
        if (window.teacherDashboard) {
            // Update progress to half way
            updateProgressBar('loading-half');
            
            // Trigger the dashboard update
            window.teacherDashboard.updateDashboard()
                .then(() => {
                    // Update progress to complete
                    updateProgressBar('loading-complete');
                    
                    // Hide progress bar after a short delay
                    setTimeout(() => {
                        hideProgressBar();
                    }, 500);
                })
                .catch(error => {
                    console.error('Error reloading teacher dashboard:', error);
                    // Hide progress bar
                    hideProgressBar();
                    // Fallback: reload the entire page if dashboard update fails
                    window.location.reload();
                });
        } else {
            // Fallback: use global update function if dashboard instance not available
            if (typeof updateDashboard === 'function') {
                updateProgressBar('loading-half');
                updateDashboard();
                
                setTimeout(() => {
                    updateProgressBar('loading-complete');
                    setTimeout(() => {
                        hideProgressBar();
                    }, 500);
                }, 1000);
            } else {
                // Final fallback: reload the entire page
                hideProgressBar();
                window.location.reload();
            }
        }
    }
}

