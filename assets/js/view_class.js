// Class View Page JavaScript - ClassTrack Teacher Dashboard

// Toast notification function
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
        : type === 'error'
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
    
    toast.innerHTML = `
        <div class="toast-icon">
            ${iconSvg}
        </div>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</div>
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

// Navigation functions
function goBack() {
    window.location.href = 'dashboard.php';
}

function startAttendanceSession(classCode) {
    window.location.href = 'attendance_session.php?class=' + classCode;
}

function showNotAvailableNotification() {
    showToast('This feature is not yet available.', 'info');
}

function updateAttendanceIndicators(studentId) {
    // Fetch real attendance data from API
    fetch(`../api/get_attendance_stats.php?studentId=${studentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch attendance data');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                const data = result.data;
                
                // Update percentage text
                const presentText = document.querySelector('.circular-progress.present .percentage-text');
                const lateText = document.querySelector('.circular-progress.late .percentage-text');
                const absentText = document.querySelector('.circular-progress.absent .percentage-text');
                
                if (presentText) presentText.textContent = data.present + '%';
                if (lateText) lateText.textContent = data.late + '%';
                if (absentText) absentText.textContent = data.absent + '%';
                
                // Update circular progress rotation based on percentage
                updateCircularProgress('.circular-progress.present', data.present);
                updateCircularProgress('.circular-progress.late', data.late);
                updateCircularProgress('.circular-progress.absent', data.absent);
                
                console.log(`Attendance stats for student ${studentId}:`, data);
            } else {
                console.error('API Error:', result.message);
                // Fallback to dummy data if API fails
                useDummyAttendanceData();
            }
        })
        .catch(error => {
            console.error('Error fetching attendance data:', error);
            // Fallback to dummy data if network fails
            useDummyAttendanceData();
        });
}

function useDummyAttendanceData() {
    // Fallback dummy data
    const dummyData = {
        present: 75,
        late: 15,
        absent: 10
    };
    
    // Update percentage text
    const presentText = document.querySelector('.circular-progress.present .percentage-text');
    const lateText = document.querySelector('.circular-progress.late .percentage-text');
    const absentText = document.querySelector('.circular-progress.absent .percentage-text');
    
    if (presentText) presentText.textContent = dummyData.present + '%';
    if (lateText) lateText.textContent = dummyData.late + '%';
    if (absentText) absentText.textContent = dummyData.absent + '%';
    
    // Update circular progress rotation based on percentage
    updateCircularProgress('.circular-progress.present', dummyData.present);
    updateCircularProgress('.circular-progress.late', dummyData.late);
    updateCircularProgress('.circular-progress.absent', dummyData.absent);
}

function updateCircularProgress(selector, percentage) {
    const element = document.querySelector(selector);
    if (!element) return;
    
    // Handle 0% case - hide the progress bar
    if (percentage === 0) {
        element.style.setProperty('--rotation', '0deg');
        element.classList.add('zero-progress');
    } else if (percentage === 100) {
        // Handle 100% case - show full circle
        element.style.setProperty('--rotation', '360deg');
        element.classList.remove('zero-progress');
    } else {
        // Calculate proper rotation for partial progress
        // Start from top and rotate based on percentage
        const rotation = (percentage / 100) * 360;
        element.style.setProperty('--rotation', rotation + 'deg');
        element.classList.remove('zero-progress');
    }
}

function viewStudentProfile(studentId) {
    const student = studentData.find(s => s.StudentID == studentId);
    if (!student) {
        showToast('error', 'Student not found');
        return;
    }
    
    // Set profile avatar
    const avatarContainer = document.getElementById('modalProfileAvatar');
    if (student.ProfilePicture && student.ProfilePicture.includes('profile_' + student.UserID + '_')) {
        const profilePath = student.ProfilePicture.replace('uploads/', '../uploads/');
        avatarContainer.innerHTML = `<img src="${profilePath}" alt="Profile" class="profile-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                     <i class="bi bi-person-circle" style="display:none;"></i>`;
    } else {
        avatarContainer.innerHTML = '<i class="bi bi-person-circle"></i>';
    }
    
    // Set student number
    document.getElementById('modalStudentNumber').textContent = student.StudentNumber;
    
    // Update attendance indicators (for now, showing dummy data)
    updateAttendanceIndicators(student.StudentID);
    
    // Set full name
    document.getElementById('modalFullName').textContent = student.first_name + ' ' + student.last_name;
    
    // Set masked email (if available)
    if (student.Email) {
        const email = student.Email;
        const atIndex = email.indexOf('@');
        if (atIndex > 1) {
            const firstChar = email[0];
            const domain = email.substring(atIndex);
            const maskedMiddle = '*'.repeat(atIndex - 1);
            document.getElementById('modalEmail').textContent = firstChar + maskedMiddle + domain;
        } else {
            document.getElementById('modalEmail').textContent = email;
        }
    } else {
        document.getElementById('modalEmail').textContent = 'Not available';
    }
    
    // Set course and year level
    const course = student.Course ? student.Course.trim() : '';
    const yearLevel = student.YearLevel ? student.YearLevel.toString().trim() : '';
    document.getElementById('modalCourseYear').innerHTML = course + '&nbsp;-&nbsp;' + yearLevel;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));
    modal.show();
}

// Update Class Modal Functions
let originalFormData = {};
let formChanged = false;

function openUpdateClassModal() {
    // Populate form with current class data
    document.getElementById('updateSubjectName').value = currentClassData.title;
    
    // Parse section to get class name and section
    const sectionParts = currentClassData.section.split(' - ');
    document.getElementById('updateClassName').value = sectionParts[0] || '';
    document.getElementById('updateSection').value = sectionParts[1] || '';
    document.getElementById('updateSchedule').value = currentClassData.schedule;
    
    // Store original form data
    originalFormData = {
        subjectName: document.getElementById('updateSubjectName').value,
        className: document.getElementById('updateClassName').value,
        section: document.getElementById('updateSection').value,
        schedule: document.getElementById('updateSchedule').value
    };
    
    // Reset form changed state and disable save button
    formChanged = false;
    updateSaveButtonState();
    
    // Add event listeners for form changes
    addFormChangeListeners();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('updateClassModal'));
    modal.show();
}

function addFormChangeListeners() {
    const formInputs = document.querySelectorAll('#updateClassForm input');
    
    // Remove existing listeners to prevent duplicates
    formInputs.forEach(input => {
        input.removeEventListener('input', checkFormChanges);
        input.removeEventListener('change', checkFormChanges);
    });
    
    // Add new listeners
    formInputs.forEach(input => {
        input.addEventListener('input', checkFormChanges);
        input.addEventListener('change', checkFormChanges);
    });
}

function checkFormChanges() {
    const currentFormData = {
        subjectName: document.getElementById('updateSubjectName').value,
        className: document.getElementById('updateClassName').value,
        section: document.getElementById('updateSection').value,
        schedule: document.getElementById('updateSchedule').value
    };
    
    // Check if any field has changed
    formChanged = JSON.stringify(currentFormData) !== JSON.stringify(originalFormData);
    updateSaveButtonState();
}

function updateSaveButtonState() {
    const saveBtn = document.querySelector('#updateClassModal .btn-primary');
    if (saveBtn) {
        saveBtn.disabled = !formChanged;
    }
}

function updateClassDetails() {
    const form = document.getElementById('updateClassForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = {
        subjectId: currentClassData.subject_id,
        subjectName: formData.get('subjectName'),
        className: formData.get('className'),
        section: formData.get('section'),
        schedule: formData.get('schedule')
    };
    
    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;
    
    // Make AJAX call to update the database
    fetch('../api/update_subject.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Network response was not ok');
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showToast('Class details updated successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateClassModal'));
            modal.hide();
            
            // Refresh page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Failed to update class details');
        }
    })
    .catch(error => {
        console.error('Error updating class details:', error);
        showToast('error', error.message || 'Failed to update class details');
        
        // Reset button state on error
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
        updateSaveButtonState(); // Re-enable based on form state
    })
    .finally(() => {
        // Reset button text if not already reset
        if (saveBtn.textContent === 'Saving...') {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            updateSaveButtonState();
        }
    });
}

// Handle modal close to reset form state
document.addEventListener('DOMContentLoaded', function() {
    const updateClassModal = document.getElementById('updateClassModal');
    if (updateClassModal) {
        updateClassModal.addEventListener('hidden.bs.modal', function () {
            // Reset form changed state when modal is closed
            formChanged = false;
            updateSaveButtonState();
        });
    }
});

function copySubjectCode(subjectCode) {
    // Copy to clipboard
    navigator.clipboard.writeText(subjectCode).then(function() {
        showToast('success', 'Subject code copied: ' + subjectCode);
    }).catch(function(err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = subjectCode;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('success', 'Subject code copied: ' + subjectCode);
    });
}

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing tabs...');
    
    // Get all tabs and content
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');
    
    console.log('Found tabs:', tabs.length);
    console.log('Found contents:', contents.length);
    
    // Set first tab as active by default
    if (tabs.length > 0) {
        tabs[0].classList.add('active');
    }
    if (contents.length > 0) {
        contents[0].classList.add('active');
    }
    
    // Add click handlers to tabs
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            console.log('Tab clicked:', this.getAttribute('data-tab'));
            
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Remove active class from all content
            contents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Show corresponding content
            const tabName = this.getAttribute('data-tab');
            const targetContent = document.getElementById(tabName + '-content');
            
            if (targetContent) {
                targetContent.classList.add('active');
                console.log('Showing content for:', tabName);
                
                // Initialize search if People tab
                if (tabName === 'people') {
                    initializeStudentSearch();
                }
            } else {
                console.error('Content not found for:', tabName);
            }
        });
    });
    
    // Add event listener for confirmation button
    const confirmBtn = document.getElementById('confirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', confirmRemoveStudent);
    }
    
    // Add event listener for confirmation modal cancel button
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal) {
        confirmModal.addEventListener('hidden.bs.modal', function() {
            // Check if the removal was not completed (no success toast shown)
            // If the modal was closed without clicking "Remove", reopen manage students modal
            setTimeout(() => {
                const manageModal = new bootstrap.Modal(document.getElementById('manageStudentsModal'));
                manageModal.show();
            }, 10);
        });
    }
});


// Manage Students Modal Functions
function openManageStudentsModal() {
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('manageStudentsModal'));
    modal.show();
    
    // Initialize search functionality
    initializeManageStudentSearch();
}

function initializeManageStudentSearch() {
    const searchInput = document.getElementById('manageStudentSearch');
    if (searchInput && !searchInput.hasAttribute('data-manage-initialized')) {
        searchInput.setAttribute('data-manage-initialized', 'true');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentItems = document.querySelectorAll('.manage-student-item');
            
            studentItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
}

function openAddStudentModal() {
    // Close manage students modal first
    const manageModal = bootstrap.Modal.getInstance(document.getElementById('manageStudentsModal'));
    if (manageModal) {
        manageModal.hide();
    }
    
    // Clear form
    document.getElementById('studentEmail').value = '';
    
    // Show add student modal
    setTimeout(() => {
        const addModal = new bootstrap.Modal(document.getElementById('addStudentModal'));
        addModal.show();
    }, 300);
}

function addStudent() {
    const form = document.getElementById('addStudentForm');
    const emailInput = document.getElementById('studentEmail');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const email = emailInput.value.trim();
    
    if (!window.currentClassData || !window.currentClassData.subject_id) {
        showToast('Class data not available', 'error');
        return;
    }
    
    // Show loading state
    const addBtn = event.target;
    const originalText = addBtn.textContent;
    addBtn.textContent = 'Adding...';
    addBtn.disabled = true;
    
    // Make AJAX call to add student
    fetch('../api/add_student_to_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            subjectId: window.currentClassData.subject_id,
            email: email
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Network response was not ok');
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showToast('Student added successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            modal.hide();
            
            // Refresh page to show updated student list
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(result.message || 'Failed to add student');
        }
    })
    .catch(error => {
        console.error('Error adding student:', error);
        showToast(error.message || 'Failed to add student', 'error');
        
        // Reset button state on error
        addBtn.textContent = originalText;
        addBtn.disabled = false;
    })
    .finally(() => {
        // Reset button text if not already reset
        if (addBtn.textContent === 'Adding...') {
            addBtn.textContent = originalText;
            addBtn.disabled = false;
        }
    });
}

function removeStudent(studentId, studentName) {
    // Store the student data for use in confirmation
    window.pendingRemoval = {
        studentId: studentId,
        studentName: studentName
    };
    
    // Update modal content
    document.getElementById('confirmText').textContent = 
        `Are you sure you want to remove ${studentName} from this class?`;
    
    // Close the manage students modal first
    const manageStudentsModal = bootstrap.Modal.getInstance(document.getElementById('manageStudentsModal'));
    if (manageStudentsModal) {
        manageStudentsModal.hide();
    }
    
    // Show confirmation modal after a small delay
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }, 10);
}

// Function to actually remove the student after confirmation
function confirmRemoveStudent() {
    const { studentId, studentName } = window.pendingRemoval;
    
    // Close the confirmation modal
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
    confirmModal.hide();
    
    // Show loading state on the button
    const studentItem = document.querySelector(`.manage-student-item[data-id="${studentId}"]`);
    const removeBtn = studentItem.querySelector('.btn-remove-student');
    const originalText = removeBtn.textContent;
    removeBtn.textContent = 'Removing...';
    removeBtn.disabled = true;
    
    // Make AJAX call to remove student
    fetch('../api/remove_student_from_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            subjectId: window.currentClassData.subject_id,
            studentId: studentId
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Network response was not ok');
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            showToast('Student removed successfully!', 'success');
            
            // Remove student item from list with animation
            studentItem.style.transition = 'opacity 0.3s, transform 0.3s';
            studentItem.style.opacity = '0';
            studentItem.style.transform = 'translateX(20px)';
            
            setTimeout(() => {
                studentItem.remove();
                
                // Check if no students left
                const remainingStudents = document.querySelectorAll('.manage-student-item');
                if (remainingStudents.length === 0) {
                    // Show no students message
                    const listContainer = document.querySelector('.manage-students-list');
                    listContainer.innerHTML = `
                        <div class="no-students-message">
                            <i class="bi bi-person-x"></i>
                            <p>No students enrolled in this class yet.</p>
                        </div>
                    `;
                }
                
                // Reopen manage students modal after removal
                setTimeout(() => {
                    const manageModal = new bootstrap.Modal(document.getElementById('manageStudentsModal'));
                    manageModal.show();
                }, 10);
            }, 300);
        } else {
            throw new Error(result.message || 'Failed to remove student');
        }
    })
    .catch(error => {
        console.error('Error removing student:', error);
        showToast(error.message || 'Failed to remove student', 'error');
        
        // Reset button state on error
        removeBtn.textContent = originalText;
        removeBtn.disabled = false;
        
        // Reopen manage students modal on error
        setTimeout(() => {
            const manageModal = new bootstrap.Modal(document.getElementById('manageStudentsModal'));
            manageModal.show();
        }, 10);
    });
}

// Initialize student search functionality
function initializeStudentSearch() {
    const searchInput = document.getElementById('studentSearch');
    if (searchInput && !searchInput.hasAttribute('data-initialized')) {
        searchInput.setAttribute('data-initialized', 'true');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentItems = document.querySelectorAll('.student-item');
            
            studentItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
}


