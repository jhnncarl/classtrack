document.addEventListener('DOMContentLoaded', function() {
    console.log('Navbar script loaded');
    
    const classCodeInput = document.getElementById('classCodeInput');
    const joinClassBtn = document.getElementById('joinClassBtn');
    const cancelBtn = document.querySelector('.btn-cancel');
    
    console.log('classCodeInput found:', !!classCodeInput);
    console.log('joinClassBtn found:', !!joinClassBtn);
    
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
        console.log('showToast called with message:', message, 'type:', type);
        
        const toastContainer = document.getElementById('toast-container');
        console.log('Toast container found:', !!toastContainer);
        
        if (!toastContainer) {
            console.error('Toast container not found!');
            return;
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        console.log('Created toast element with class:', toast.className);
        
        // Create icon based on type
        const iconSvg = type === 'success' 
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        
        toast.innerHTML = `
            <div class="toast-icon">
                ${iconSvg}
            </div>
            <div class="toast-content">
                <div class="toast-title">${type === 'success' ? 'Success' : 'Error'}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
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
        console.log('Create Subject modal reset to original state');
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
                
                console.log('Creating class with data:', classData);
                console.log('Sending request to: /classtrack/api/create_class.php');
                
                // Send AJAX request to create class
                fetch('/classtrack/api/create_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(classData)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
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
                        }, 1000);
                    } else {
                        // Show error toast
                        showToast(data.message || 'Failed to create class', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error creating class:', error);
                    console.error('Error details:', error.message);
                    console.error('Error stack:', error.stack);
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
        console.log('Create Class modal reset to original state');
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
            // Show only Join Class
            if (createSubjectOption) createSubjectOption.classList.add('hidden');
            if (joinClassOption) joinClassOption.classList.remove('hidden');
        } else if (currentUserRole === 'Teacher') {
            // Show only Create Subject
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
        
        // Create Subject option click handler
        if (createSubjectOption) {
            createSubjectOption.addEventListener('click', function() {
                closeDropdown();
                // Trigger the Create Subject modal
                const modal = document.getElementById('createSubjectModal');
                if (modal) {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                }
            });
        }
        
        // Join Class option click handler
        if (joinClassOption) {
            joinClassOption.addEventListener('click', function() {
                closeDropdown();
                // Trigger the existing Join Class modal
                const modal = document.getElementById('joinClassModal');
                if (modal) {
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                }
            });
        }
        
        // Initialize dropdown visibility
        updateDropdownVisibility();
        
        // Initialize Create Subject modal
        initializeCreateSubjectModal();
    }
    
    // Function to simulate join class process
    function simulateJoinClass(classCode) {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Temporary valid codes for testing
                const validCodes = ['ABC123', 'TEST456', 'CLASS78', 'DEMO90', 'JOIN12'];
                const isValid = validCodes.includes(classCode.toUpperCase());
                resolve(isValid);
            }, 2000); // 2 second delay to show loading
        });
    }
    
    // Function to reset modal to original state
    function resetModal() {
        classCodeInput.value = '';
        validateClassCode();
        console.log('Modal reset to original state');
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
        
        console.log('Input value:', inputValue);
        console.log('Input length:', inputValue.length);
        console.log('Regex test result:', /^[a-zA-Z0-9]+$/.test(inputValue));
        console.log('Is valid:', isValid);
        console.log('Button element:', joinClassBtn);
        
        if (isValid) {
            // Enable the Join button
            joinClassBtn.classList.remove('disabled');
            joinClassBtn.classList.add('enabled');
            console.log('Join button enabled');
            console.log('Button classes after enable:', joinClassBtn.className);
            console.log('Button computed style cursor:', getComputedStyle(joinClassBtn).cursor);
            console.log('Button computed style pointer-events:', getComputedStyle(joinClassBtn).pointerEvents);
        } else {
            // Disable the Join button
            joinClassBtn.classList.remove('enabled');
            joinClassBtn.classList.add('disabled');
            console.log('Join button disabled');
            console.log('Button classes after disable:', joinClassBtn.className);
            console.log('Button computed style cursor:', getComputedStyle(joinClassBtn).cursor);
            console.log('Button computed style pointer-events:', getComputedStyle(joinClassBtn).pointerEvents);
        }
    }
    
    // Add event listener for input validation
    console.log('DEBUG: Checking for Join Class modal elements');
    console.log('DEBUG: classCodeInput found:', !!classCodeInput);
    console.log('DEBUG: joinClassBtn found:', !!joinClassBtn);
    console.log('DEBUG: currentUserRole:', currentUserRole);
    
    if (classCodeInput && joinClassBtn) {
        console.log('Elements found, adding event listener');
        console.log('joinClassBtn element:', joinClassBtn);
        console.log('joinClassBtn tagName:', joinClassBtn.tagName);
        console.log('joinClassBtn classes:', joinClassBtn.className);
        console.log('Initial input value:', classCodeInput.value);
        
        // Test validation function manually
        console.log('DEBUG: Testing validation with "ABC123"');
        classCodeInput.value = 'ABC123';
        validateClassCode();
        console.log('DEBUG: After setting ABC123, button classes:', joinClassBtn.className);
        console.log('DEBUG: Button color:', joinClassBtn.style.color);
        console.log('DEBUG: Button cursor:', joinClassBtn.style.cursor);
        
        // Clear the test value
        classCodeInput.value = '';
        
        // Initial validation
        validateClassCode();
        
        classCodeInput.addEventListener('input', function() {
            console.log('DEBUG: Input event triggered, value:', classCodeInput.value);
            validateClassCode();
        });
        classCodeInput.addEventListener('keyup', function() {
            console.log('DEBUG: Keyup event triggered, value:', classCodeInput.value);
            validateClassCode();
        });
        
        // Add click event listener - make it work for span elements
        joinClassBtn.addEventListener('click', async function(e) {
            const inputValue = classCodeInput.value.trim();
            const isValid = inputValue.length >= 5 && inputValue.length <= 8 && /^[a-zA-Z0-9]+$/.test(inputValue);
            
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Button is disabled - invalid input');
                return;
            }
            
            // Prevent multiple clicks during loading
            if (joinClassBtn.innerHTML.includes('Joining..')) {
                e.preventDefault();
                return;
            }
            
            console.log('Button is enabled - proceeding with join');
            
            // Set loading state
            setLoadingState(true);
            
            try {
                // Simulate join class process
                const isJoinSuccessful = await simulateJoinClass(inputValue.toUpperCase());
                
                // Reset loading state
                setLoadingState(false);
                
                if (isJoinSuccessful) {
                    // Show success toast
                    showToast('Successfully joined the class!', 'success');
                    
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
                    }, 1500);
                } else {
                    // Show error toast
                    showToast('Invalid class code. Please check and try again.', 'error');
                }
            } catch (error) {
                console.error('Error joining class:', error);
                setLoadingState(false);
                showToast('An error occurred. Please try again.', 'error');
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
    } else {
        console.log('Join Class modal elements not found - likely not a Student user');
    }
    
    // Close dropdown when Escape key is pressed
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && plusDropdownMenu.classList.contains('show')) {
            closeDropdown();
        }
    });
    
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
        .catch(error => {
            console.log('Could not refresh profile picture:', error);
        });
    };
});

