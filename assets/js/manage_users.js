// Manage Users JavaScript - ClassTrack

document.addEventListener('DOMContentLoaded', function() {
    initializeManageUsers();
});

function initializeManageUsers() {
    // Initialize all components
    initializeTabs();
    initializeSearch();
    initializeSearchButtons();
    initializeActionButtons();
    initializePermissions();
    initializeResponsive();
    initializeTooltips();
    initializeConfirmationModal();
    initializeRegistrationModal();
    
    // Load real data from server
    loadAllUsers();
    loadTeachers();
    loadStudents();
    loadPendingUsers();
}

// Tab Navigation
function initializeTabs() {
    const tabs = document.querySelectorAll('#usersTabs .nav-link');
    const tabContents = document.querySelectorAll('#usersTabsContent .tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('show', 'active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const targetId = this.getAttribute('data-bs-target');
            const targetContent = document.querySelector(targetId);
            if (targetContent) {
                targetContent.classList.add('show', 'active');
            }
            
            // Update badge counts
            updateBadgeCounts();
        });
    });
}

// Search Functionality
function initializeSearch() {
    const searchInputs = [
        { id: 'searchAllUsers', tableId: 'allUsersTable' },
        { id: 'searchTeachers', tableId: 'teachersTable' },
        { id: 'searchStudents', tableId: 'studentsTable' }
    ];
    
    searchInputs.forEach(search => {
        const input = document.getElementById(search.id);
        const table = document.getElementById(search.tableId);
        
        if (input && table) {
            input.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr:not(.empty-state)');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });
}

// Search Functionality
function initializeSearchButtons() {
    const searchButtons = [
        'searchAllUsersBtn',
        'searchTeachersBtn', 
        'searchStudentsBtn'
    ];
    
    searchButtons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                const searchInputId = buttonId.replace('Btn', '');
                const searchInput = document.getElementById(searchInputId);
                if (searchInput) {
                    // Trigger search by focusing input and triggering any existing search logic
                    searchInput.focus();
                    searchInput.dispatchEvent(new Event('input'));
                }
            });
        }
    });
}

// Action Buttons
function initializeActionButtons() {
    // Add User Button
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            showAddUserModal();
        });
    }
    
    // Select All Checkboxes
    const selectAllCheckboxes = [
        'selectAllAll',
        'selectAllTeachers',
        'selectAllStudents',
        'selectAllPending'
    ];
    
    selectAllCheckboxes.forEach(checkboxId => {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                const tableId = this.id.replace('selectAll', '').toLowerCase();
                const targetTable = tableId === 'all' ? 'allUsersTable' : 
                                  tableId === 'teachers' ? 'teachersTable' :
                                  tableId === 'students' ? 'studentsTable' : 'pendingTable';
                
                const table = document.getElementById(targetTable);
                if (table) {
                    const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]:not(#' + this.id + ')');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                }
            });
        }
    });
    
    // Action button events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-action') || e.target.closest('.btn-action')) {
            const button = e.target.classList.contains('btn-action') ? e.target : e.target.closest('.btn-action');
            const action = button.dataset.action || 
                          (button.classList.contains('approve') ? 'approve' :
                           button.classList.contains('reject') ? 'reject' :
                           button.classList.contains('edit') ? 'edit' :
                           button.classList.contains('delete') ? 'delete' :
                           button.classList.contains('deactivate') ? 'deactivate' : '');
            
            if (action) {
                handleUserAction(action, button);
            }
        }
    });
    
    // Bulk actions for pending approvals
    const approveAllBtn = document.getElementById('approveAllBtn');
    const rejectAllBtn = document.getElementById('rejectAllBtn');
    
    if (approveAllBtn) {
        approveAllBtn.addEventListener('click', function() {
            bulkApproveUsers();
        });
    }
    
    if (rejectAllBtn) {
        rejectAllBtn.addEventListener('click', function() {
            bulkRejectUsers();
        });
    }
}

// Handle User Actions
async function handleUserAction(action, button) {
    const row = button.closest('tr');
    const userId = button.dataset.userId;
    const userName = row.querySelector('.user-name')?.textContent || 'User';
    
    switch(action) {
        case 'approve':
            await approveUser(userId, userName);
            break;
        case 'reject':
            showConfirmationModal(
                'Reject User',
                `Are you sure you want to reject ${userName}?`,
                async () => {
                    await rejectUser(userId, userName);
                }
            );
            break;
        case 'edit':
            showEditUserModal(userId);
            break;
        case 'delete':
            showConfirmationModal(
                'Delete User',
                `Are you sure you want to delete ${userName}? This action cannot be undone.`,
                async () => {
                    await deleteUser(userId, userName);
                }
            );
            break;
        case 'activate':
        case 'deactivate':
            const actionText = action === 'activate' ? 'activate' : 'deactivate';
            showConfirmationModal(
                `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} User`,
                `Are you sure you want to ${actionText} ${userName}?`,
                async () => {
                    await toggleUserStatus(userId, action);
                }
            );
            break;
    }
}

// User Management Functions
async function approveUser(userId, userName) {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approveUser&userId=${userId}`
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(`Approved ${userName}`, 'success');
            loadPendingUsers();
            loadAllUsers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error approving user:', error);
        showToast('Error approving user', 'error');
    }
}

async function rejectUser(userId, userName) {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=rejectUser&userId=${userId}`
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(`Rejected ${userName}`, 'success');
            loadPendingUsers();
            loadAllUsers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error rejecting user:', error);
        showToast('Error rejecting user', 'error');
    }
}

async function deleteUser(userId, userName) {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=deleteUser&userId=${userId}`
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(`Deleted ${userName}`, 'success');
            loadAllUsers();
            loadTeachers();
            loadStudents();
            loadPendingUsers();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('Error deleting user', 'error');
    }
}

async function toggleUserStatus(userId, action) {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=updateUser&userId=${userId}&userData=${JSON.stringify({account_status: action === 'activate' ? 'Active' : 'Inactive'})}`
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(`User ${action}d successfully`, 'success');
            loadAllUsers();
            loadTeachers();
            loadStudents();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error toggling user status:', error);
        showToast('Error updating user status', 'error');
    }
}

function showEditUserModal(userId) {
    // Fetch user data and populate modal
    fetchUserDetails(userId);
}

async function fetchUserDetails(userId) {
    try {
        const response = await fetch('../api/user_management_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=getUserDetails&userId=${userId}`
        });
        
        const user = await response.json();
        if (user.success) {
            populateEditModal(user.data);
            showEditModal();
        } else {
            showToast(user.message || 'Error loading user details', 'error');
        }
    } catch (error) {
        console.error('Error fetching user details:', error);
        showToast('Error loading user details', 'error');
    }
}

function populateEditModal(user) {
    // Set user ID
    document.getElementById('editUserId').value = user.UserID;
    
    // Personal Information
    document.getElementById('editFirstName').value = user.first_name || '';
    document.getElementById('editLastName').value = user.last_name || '';
    document.getElementById('editEmail').value = user.Email || '';
    
    // Role & Status
    document.getElementById('editRole').value = user.Role || 'Student';
    document.getElementById('editStatus').value = user.AccountStatus || 'Active';
    
    // Profile Image
    updateProfileImage(user.ProfilePicture);
    
    // Show/hide role-specific fields
    toggleRoleFields(user.Role);
    
    // Student specific fields
    if (user.Role === 'Student') {
        document.getElementById('editStudentNumber').value = user.StudentNumber || '';
        document.getElementById('editCourse').value = user.Course || '';
        document.getElementById('editYearLevel').value = user.YearLevel || '';
    }
    
    // Teacher specific fields
    if (user.Role === 'Teacher') {
        document.getElementById('editDepartment').value = user.Department || '';
    }
}

function updateProfileImage(imagePath) {
    const profileImg = document.getElementById('editProfileImage');
    const profileIcon = document.getElementById('editProfileIcon');
    
    if (imagePath && imagePath.trim() !== '') {
        const fullImagePath = imagePath.startsWith('http') ? 
            imagePath : 
            '/classtrack/' + imagePath.replace(/^\/+/, '');
        
        profileImg.src = fullImagePath;
        profileImg.style.display = 'block';
        profileIcon.style.display = 'none';
        
        // Handle image load errors
        profileImg.onerror = function() {
            profileImg.style.display = 'none';
            profileIcon.style.display = 'flex';
        };
    } else {
        profileImg.style.display = 'none';
        profileIcon.style.display = 'flex';
    }
}

function toggleRoleFields(role) {
    const studentFields = document.getElementById('studentFields');
    const teacherFields = document.getElementById('teacherFields');
    
    // Hide all role-specific fields first
    studentFields.style.display = 'none';
    teacherFields.style.display = 'none';
    
    // Show relevant fields based on role
    if (role === 'Student') {
        studentFields.style.display = 'block';
    } else if (role === 'Teacher') {
        teacherFields.style.display = 'block';
    }
}

function showEditModal() {
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
    
    // Initialize scrollbar auto-hide functionality
    initScrollbarAutoHide();
}

function initScrollbarAutoHide() {
    const modalBody = document.querySelector('#editUserModal .modal-body');
    if (!modalBody) return;
    
    let scrollTimeout;
    let scrollHandler, mouseEnterHandler, mouseLeaveHandler;
    
    // Function to show scrollbar
    function showScrollbar() {
        modalBody.style.setProperty('--scrollbar-thumb-opacity', '0.2');
    }
    
    // Function to hide scrollbar
    function hideScrollbar() {
        modalBody.style.setProperty('--scrollbar-thumb-opacity', '0');
    }
    
    // Show scrollbar on scroll
    scrollHandler = function() {
        showScrollbar();
        
        // Clear existing timeout
        clearTimeout(scrollTimeout);
        
        // Hide scrollbar after 1.5 seconds of inactivity
        scrollTimeout = setTimeout(() => {
            hideScrollbar();
        }, 1500);
    };
    
    // Show scrollbar on hover
    mouseEnterHandler = function() {
        showScrollbar();
    };
    
    // Hide scrollbar when mouse leaves
    mouseLeaveHandler = function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            hideScrollbar();
        }, 500);
    };
    
    // Add event listeners
    modalBody.addEventListener('scroll', scrollHandler);
    modalBody.addEventListener('mouseenter', mouseEnterHandler);
    modalBody.addEventListener('mouseleave', mouseLeaveHandler);
    
    // Initially hide scrollbar
    hideScrollbar();
    
    // Clean up event listeners when modal is hidden
    const modal = document.getElementById('editUserModal');
    modal.addEventListener('hidden.bs.modal', function() {
        clearTimeout(scrollTimeout);
        modalBody.removeEventListener('scroll', scrollHandler);
        modalBody.removeEventListener('mouseenter', mouseEnterHandler);
        modalBody.removeEventListener('mouseleave', mouseLeaveHandler);
        // Reset scrollbar opacity
        modalBody.style.setProperty('--scrollbar-thumb-opacity', '0.2');
    }, { once: true });
}

// Handle role change in edit modal
document.addEventListener('DOMContentLoaded', function() {
    const editRoleSelect = document.getElementById('editRole');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', function() {
            toggleRoleFields(this.value);
        });
    }
    
    // Handle save button click
    const saveUserBtn = document.getElementById('saveUserBtn');
    if (saveUserBtn) {
        saveUserBtn.addEventListener('click', function() {
            saveUserChanges();
        });
    }
});

async function saveUserChanges() {
    const form = document.getElementById('editUserForm');
    
    // Validate form
    if (!validateEditForm()) {
        return;
    }
    
    // Get form data and map to correct field names
    const formData = {
        userId: document.getElementById('editUserId').value,
        firstName: document.getElementById('editFirstName').value,
        lastName: document.getElementById('editLastName').value,
        email: document.getElementById('editEmail').value,
        role: document.getElementById('editRole').value,
        status: document.getElementById('editStatus').value,
        studentNumber: document.getElementById('editStudentNumber').value,
        course: document.getElementById('editCourse').value,
        yearLevel: document.getElementById('editYearLevel').value,
        department: document.getElementById('editDepartment').value
    };
    
    // Convert to URL-encoded string
    const urlEncodedData = new URLSearchParams({
        action: 'updateUser',
        userId: formData.userId,
        userData: JSON.stringify(formData)
    }).toString();
    
    try {
        // Show loading state
        const saveBtn = document.getElementById('saveUserBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
        saveBtn.disabled = true;
        
        const response = await fetch('../api/user_management_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: urlEncodedData
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('User information updated successfully', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            
            // Refresh user data
            loadAllUsers();
            loadTeachers();
            loadStudents();
        } else {
            showToast(result.message || 'Error updating user', 'error');
        }
    } catch (error) {
        console.error('Error saving user changes:', error);
        showToast('Error updating user information', 'error');
    } finally {
        // Restore button state
        const saveBtn = document.getElementById('saveUserBtn');
        saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Changes';
        saveBtn.disabled = false;
    }
}

function validateEditForm() {
    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const role = document.getElementById('editRole').value;
    
    // Basic validation
    if (!firstName) {
        showToast('First name is required', 'error');
        document.getElementById('editFirstName').focus();
        return false;
    }
    
    if (!lastName) {
        showToast('Last name is required', 'error');
        document.getElementById('editLastName').focus();
        return false;
    }
    
    if (!email) {
        showToast('Email address is required', 'error');
        document.getElementById('editEmail').focus();
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToast('Please enter a valid email address', 'error');
        document.getElementById('editEmail').focus();
        return false;
    }
    
    // Role-specific validation
    if (role === 'Student') {
        const studentNumber = document.getElementById('editStudentNumber').value.trim();
        if (!studentNumber) {
            showToast('Student number is required for students', 'error');
            document.getElementById('editStudentNumber').focus();
            return false;
        }
    }
    
    if (role === 'Teacher') {
        const department = document.getElementById('editDepartment').value.trim();
        if (!department) {
            showToast('Department is required for teachers', 'error');
            document.getElementById('editDepartment').focus();
            return false;
        }
    }
    
    return true;
}

// Bulk Actions
async function bulkApproveUsers() {
    const selectedUsers = getSelectedPendingUsers();
    if (selectedUsers.length === 0) {
        showToast('Please select users to approve', 'error');
        return;
    }
    
    showConfirmationModal(
        'Approve Users',
        `Are you sure you want to approve ${selectedUsers.length} user(s)?`,
        async () => {
            let successCount = 0;
            let errorCount = 0;
            
            for (const userId of selectedUsers) {
                try {
                    const response = await fetch('manage_users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=approveUser&userId=${userId}`
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (error) {
                    errorCount++;
                }
            }
            
            if (successCount > 0) {
                showToast(`Approved ${successCount} user(s) successfully`, 'success');
            }
            if (errorCount > 0) {
                showToast(`Failed to approve ${errorCount} user(s)`, 'error');
            }
            
            loadPendingUsers();
            loadAllUsers();
        }
    );
}

async function bulkRejectUsers() {
    const selectedUsers = getSelectedPendingUsers();
    if (selectedUsers.length === 0) {
        showToast('Please select users to reject', 'error');
        return;
    }
    
    showConfirmationModal(
        'Reject Users',
        `Are you sure you want to reject ${selectedUsers.length} user(s)?`,
        async () => {
            let successCount = 0;
            let errorCount = 0;
            
            for (const userId of selectedUsers) {
                try {
                    const response = await fetch('manage_users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=rejectUser&userId=${userId}`
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (error) {
                    errorCount++;
                }
            }
            
            if (successCount > 0) {
                showToast(`Rejected ${successCount} user(s) successfully`, 'success');
            }
            if (errorCount > 0) {
                showToast(`Failed to reject ${errorCount} user(s)`, 'error');
            }
            
            loadPendingUsers();
            loadAllUsers();
        }
    );
}

function getSelectedPendingUsers() {
    const checkboxes = document.querySelectorAll('#pendingTableBody input[type="checkbox"]:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function showAddUserModal() {
    const modal = document.getElementById('registrationModal');
    if (modal) {
        modal.classList.add('active');
    }
}

// RBAC/Permissions
function initializePermissions() {
    const roleSelector = document.getElementById('roleSelector');
    const permissionsPanel = document.getElementById('permissionsPanel');
    
    console.log('Initializing permissions...');
    console.log('Role selector found:', roleSelector);
    console.log('Permissions panel found:', permissionsPanel);
    
    if (roleSelector && permissionsPanel) {
        roleSelector.addEventListener('change', function() {
            const selectedRole = this.value;
            console.log('Role changed to:', selectedRole);
            
            if (selectedRole) {
                permissionsPanel.style.display = 'block';
                console.log('Permissions panel shown');
                loadRolePermissions(selectedRole);
            } else {
                permissionsPanel.style.display = 'none';
                console.log('Permissions panel hidden');
            }
        });
    } else {
        console.error('Role selector or permissions panel not found!');
    }
    
    // Permission toggle switches
    const permissionSwitches = document.querySelectorAll('.permission-item input[type="checkbox"]');
    permissionSwitches.forEach(switch_ => {
        switch_.addEventListener('change', function() {
            const permissionName = this.id;
            const isEnabled = this.checked;
            console.log(`Permission ${permissionName} ${isEnabled ? 'enabled' : 'disabled'}`);
        });
    });
    
    // Save permissions button
    const savePermissionsBtn = document.getElementById('savePermissionsBtn');
    if (savePermissionsBtn) {
        savePermissionsBtn.addEventListener('click', function() {
            const selectedRole = roleSelector.value;
            if (selectedRole) {
                showToast(`Permissions for ${selectedRole} saved successfully`, 'success');
            } else {
                showToast('Please select a role first', 'error');
            }
        });
    }
    
    // Reset permissions button
    const resetPermissionsBtn = document.getElementById('resetPermissionsBtn');
    if (resetPermissionsBtn) {
        resetPermissionsBtn.addEventListener('click', function() {
            const selectedRole = roleSelector.value;
            if (selectedRole) {
                showConfirmationModal(
                    'Reset Permissions',
                    `Reset permissions for ${selectedRole} to default?`,
                    () => {
                        loadRolePermissions(selectedRole);
                        showToast(`Permissions for ${selectedRole} reset to default`, 'info');
                    }
                );
            }
        });
    }
}

// Load Role Permissions
function loadRolePermissions(role) {
    console.log('Loading permissions for role:', role);
    
    // Hide all permission panels first
    const allPanels = ['teacherPermissions', 'studentPermissions', 'administratorPermissions'];
    allPanels.forEach(panelId => {
        const panel = document.getElementById(panelId);
        console.log('Panel found:', panelId, panel);
        if (panel) {
            panel.style.display = 'none';
        }
    });
    
    // Show the selected role's permission panel
    const selectedPanelId = role + 'Permissions';
    const selectedPanel = document.getElementById(selectedPanelId);
    console.log('Selected panel:', selectedPanelId, selectedPanel);
    if (selectedPanel) {
        selectedPanel.style.display = 'block';
        console.log('Panel displayed successfully');
    } else {
        console.error('Panel not found:', selectedPanelId);
    }
    
    // Set default permissions for each role
    const permissions = {
        teacher: {
            createClass: true,
            joinClass: false,
            manageClass: true,
            takeAttendance: true,
            viewReports: true,
            exportReports: true,
            editTeacherInfo: true,
            manageUsers: false,
            systemSettings: false
        },
        student: {
            student_createClass: false,
            student_joinClass: true,
            student_unenrollClass: true,
            student_viewAttendanceRecord: true,
            student_viewAttendanceHistory: true,
            student_editStudentInfo: true
        },
        administrator: {
            approveTeacherAccounts: true,
            rejectTeacherAccounts: true,
            createAdminUser: true,
            editAdminProfile: true
        }
    };
    
    const rolePermissions = permissions[role] || {};
    
    // Apply permissions to checkboxes
    Object.keys(rolePermissions).forEach(permission => {
        const checkbox = document.getElementById(permission);
        if (checkbox) {
            checkbox.checked = rolePermissions[permission];
            console.log('Set permission:', permission, rolePermissions[permission]);
        } else {
            console.log('Checkbox not found:', permission);
        }
    });
}

// Responsive Design
function initializeResponsive() {
    // Handle mobile menu
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (menuToggle && sidebar && mainContent) {
        menuToggle.addEventListener('click', function() {
            const isExpanded = sidebar.style.width === '250px';
            
            if (window.innerWidth <= 768) {
                // Mobile behavior
                if (isExpanded) {
                    sidebar.style.width = '0px';
                    mainContent.style.marginLeft = '0px';
                } else {
                    sidebar.style.width = '250px';
                    mainContent.style.marginLeft = '0px';
                }
            } else {
                // Desktop behavior
                if (isExpanded) {
                    sidebar.style.width = '70px';
                    mainContent.style.marginLeft = '70px';
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    sidebar.style.width = '250px';
                    mainContent.style.marginLeft = '250px';
                    localStorage.setItem('sidebarState', 'expanded');
                }
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            const savedState = localStorage.getItem('sidebarState');
            if (savedState === 'expanded') {
                sidebar.style.width = '250px';
                mainContent.style.marginLeft = '250px';
            } else {
                sidebar.style.width = '70px';
                mainContent.style.marginLeft = '70px';
            }
        } else {
            sidebar.style.width = '0px';
            mainContent.style.marginLeft = '0px';
        }
    });
}

// Tooltips
function initializeTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Load Users Data
async function loadAllUsers() {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=getAllUsers'
        });
        
        const users = await response.json();
        populateAllUsersTable(users);
        updateBadgeCount('#all-users-tab .badge', users.length);
    } catch (error) {
        console.error('Error loading all users:', error);
        showToast('Error loading users data', 'error');
    }
}

async function loadTeachers() {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=getTeachers'
        });
        
        const teachers = await response.json();
        populateTeachersTable(teachers);
        updateBadgeCount('#teachers-tab .badge', teachers.length);
    } catch (error) {
        console.error('Error loading teachers:', error);
        showToast('Error loading teachers data', 'error');
    }
}

async function loadStudents() {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=getStudents'
        });
        
        const students = await response.json();
        populateStudentsTable(students);
        updateBadgeCount('#students-tab .badge', students.length);
    } catch (error) {
        console.error('Error loading students:', error);
        showToast('Error loading students data', 'error');
    }
}

async function loadPendingUsers() {
    try {
        const response = await fetch('manage_users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=getPendingUsers'
        });
        
        const pendingUsers = await response.json();
        populatePendingTable(pendingUsers);
        updateBadgeCount('#pending-tab .badge', pendingUsers.length);
    } catch (error) {
        console.error('Error loading pending users:', error);
        showToast('Error loading pending users data', 'error');
    }
}

// Populate Tables
function populateAllUsersTable(users) {
    const tbody = document.getElementById('allUsersTableBody');
    if (!tbody) return;
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-state">
                <td colspan="5">
                    <div class="empty-state-content">
                        <i class="bi bi-people"></i>
                        <h4>No users found</h4>
                        <p>There are no users in the system yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map(user => {
        const imagePath = user.ProfilePicture && user.ProfilePicture.trim() !== '' ? 
            (user.ProfilePicture.startsWith('http') ? user.ProfilePicture : '/classtrack/' + user.ProfilePicture.replace(/^\/+/, '')) : 
            null;
        
        return `
        <tr data-user-id="${user.UserID}">
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        ${imagePath ? 
                            `<img src="${imagePath}" alt="${user.first_name} ${user.last_name}" class="rounded-circle" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <i class="bi bi-person-circle" style="display:none;"></i>` :
                            `<i class="bi bi-person-circle"></i>`
                        }
                    </div>
                    <div class="user-details">
                        <div class="user-name">${user.first_name} ${user.last_name}</div>
                        <div class="user-email">${user.Email}</div>
                        ${user.StudentNumber ? `<div class="user-id">ID: ${user.StudentNumber}</div>` : ''}
                    </div>
                </div>
            </td>
            <td><span class="badge role-badge ${getRoleBadgeClass(user.Role)}">${user.Role}</span></td>
            <td><span class="badge status-badge ${getStatusBadgeClass(user.AccountStatus)}">${user.AccountStatus}</span></td>
            <td>${formatDateTime(user.CreatedAt)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-action edit" data-user-id="${user.UserID}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-action deactivate ${user.AccountStatus === 'Active' ? 'btn-warning' : 'btn-success'}" 
                            data-user-id="${user.UserID}" 
                            data-action="${user.AccountStatus === 'Active' ? 'deactivate' : 'activate'}"
                            title="${user.AccountStatus === 'Active' ? 'Deactivate' : 'Activate'}">
                        <i class="bi bi-${user.AccountStatus === 'Active' ? 'pause' : 'play'}"></i>
                    </button>
                    <button class="btn btn-sm btn-action delete btn-danger" data-user-id="${user.UserID}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function populateTeachersTable(teachers) {
    const tbody = document.getElementById('teachersTableBody');
    if (!tbody) return;
    
    if (teachers.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-state">
                <td colspan="5">
                    <div class="empty-state-content">
                        <i class="bi bi-mortarboard"></i>
                        <h4>No teachers found</h4>
                        <p>There are no teachers in the system yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = teachers.map(teacher => {
        const imagePath = teacher.ProfilePicture && teacher.ProfilePicture.trim() !== '' ? 
            (teacher.ProfilePicture.startsWith('http') ? teacher.ProfilePicture : '/classtrack/' + teacher.ProfilePicture.replace(/^\/+/, '')) : 
            null;
        
        return `
        <tr data-user-id="${teacher.UserID}">
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        ${imagePath ? 
                            `<img src="${imagePath}" alt="${teacher.first_name} ${teacher.last_name}" class="rounded-circle" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <i class="bi bi-person-circle" style="display:none;"></i>` :
                            `<i class="bi bi-person-circle"></i>`
                        }
                    </div>
                    <div class="user-details">
                        <div class="user-name">${teacher.first_name} ${teacher.last_name}</div>
                        <div class="user-email">${teacher.Email}</div>
                    </div>
                </div>
            </td>
            <td>${teacher.Department || 'Not assigned'}</td>
            <td><span class="badge status-badge ${getStatusBadgeClass(teacher.AccountStatus)}">${teacher.AccountStatus}</span></td>
            <td>${teacher.ClassCount || 0} classes</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-action edit" data-user-id="${teacher.UserID}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-action delete btn-danger" data-user-id="${teacher.UserID}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function populateStudentsTable(students) {
    const tbody = document.getElementById('studentsTableBody');
    if (!tbody) return;
    
    if (students.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-state">
                <td colspan="5">
                    <div class="empty-state-content">
                        <i class="bi bi-book"></i>
                        <h4>No students found</h4>
                        <p>There are no students in the system yet.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = students.map(student => {
        const imagePath = student.ProfilePicture && student.ProfilePicture.trim() !== '' ? 
            (student.ProfilePicture.startsWith('http') ? student.ProfilePicture : '/classtrack/' + student.ProfilePicture.replace(/^\/+/, '')) : 
            null;
        
        return `
        <tr data-user-id="${student.UserID}">
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        ${imagePath ? 
                            `<img src="${imagePath}" alt="${student.first_name} ${student.last_name}" class="rounded-circle" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <i class="bi bi-person-circle" style="display:none;"></i>` :
                            `<i class="bi bi-person-circle"></i>`
                        }
                    </div>
                    <div class="user-details">
                        <div class="user-name">${student.first_name} ${student.last_name}</div>
                        <div class="user-email">${student.Email}</div>
                    </div>
                </div>
            </td>
            <td>${student.StudentNumber}</td>
            <td>${student.SectionName || `${student.Course} - Year ${student.YearLevel}`}</td>
            <td><span class="badge status-badge ${getStatusBadgeClass(student.AccountStatus)}">${student.AccountStatus}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-action edit" data-user-id="${student.UserID}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-action delete btn-danger" data-user-id="${student.UserID}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    }).join('');
}

function populatePendingTable(pendingUsers) {
    const tbody = document.getElementById('pendingTableBody');
    if (!tbody) return;
    
    if (pendingUsers.length === 0) {
        tbody.innerHTML = `
            <tr class="empty-state">
                <td colspan="6">
                    <div class="empty-state-content">
                        <i class="bi bi-clock"></i>
                        <h4>No pending approvals</h4>
                        <p>All user registrations have been processed.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = pendingUsers.map(user => `
        <tr data-user-id="${user.UserID}">
            <td><input type="checkbox" class="form-check-input user-checkbox" value="${user.UserID}"></td>
            <td>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name">${user.first_name} ${user.last_name}</div>
                    </div>
                </div>
            </td>
            <td><span class="badge role-badge ${getRoleBadgeClass(user.Role)}">${user.Role}</span></td>
            <td>${user.Email}</td>
            <td>${formatDateTime(user.CreatedAt)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-action approve btn-success" data-user-id="${user.UserID}" title="Approve">
                        <i class="bi bi-check-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-action reject btn-danger" data-user-id="${user.UserID}" title="Reject">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Update Badge Count
function updateBadgeCount(selector, count) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = count;
    }
}

// Update All Badge Counts
function updateBadgeCounts() {
    // This function is called when tabs are switched
    // The actual counts are updated when data is loaded
    // This function can be used for additional badge count updates if needed
}

// Confirmation Modal Management
let currentConfirmationCallback = null;

function showConfirmationModal(title, message, confirmCallback) {
    const modal = document.getElementById('confirmationModal');
    const titleElement = document.getElementById('confirmationModalLabel');
    const messageElement = document.querySelector('.confirmation-text');
    const confirmBtn = document.getElementById('confirmActionBtn');
    
    // Set modal content
    titleElement.textContent = title;
    messageElement.textContent = message;
    
    // Store callback
    currentConfirmationCallback = confirmCallback;
    
    // Show modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// Initialize confirmation modal event listeners
function initializeConfirmationModal() {
    const confirmBtn = document.getElementById('confirmActionBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (currentConfirmationCallback) {
                currentConfirmationCallback();
            }
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            if (modal) {
                modal.hide();
            }
            currentConfirmationCallback = null;
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            currentConfirmationCallback = null;
        });
    }
    
    // Clear callback when modal is hidden
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            currentConfirmationCallback = null;
        });
    }
}

// Registration Modal Management
function initializeRegistrationModal() {
    const modal = document.getElementById('registrationModal');
    const closeBtn = document.getElementById('closeModal');
    const modalOverlay = document.getElementById('modalOverlay');
    
    // Close modal when clicking close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            hideRegistrationModal();
        });
    }
    
    // Close modal when clicking overlay
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function() {
            hideRegistrationModal();
        });
    }
    
    // Role selection handlers
    const studentBtn = document.getElementById('studentBtn');
    const teacherBtn = document.getElementById('teacherBtn');
    const administratorBtn = document.getElementById('administratorBtn');
    
    if (studentBtn) {
        studentBtn.addEventListener('click', function() {
            handleRoleSelection('Student');
        });
    }
    
    if (teacherBtn) {
        teacherBtn.addEventListener('click', function() {
            handleRoleSelection('Teacher');
        });
    }
    
    if (administratorBtn) {
        administratorBtn.addEventListener('click', function() {
            handleRoleSelection('Administrator');
        });
    }
    
    // Form button handlers
    const backToRolesBtn = document.getElementById('backToRolesBtn');
    const createAccountBtn = document.getElementById('createAccountBtn');
    
    if (backToRolesBtn) {
        backToRolesBtn.addEventListener('click', function() {
            showRoleSelection();
        });
    }
    
    if (createAccountBtn) {
        createAccountBtn.addEventListener('click', function() {
            handleCreateAccount();
        });
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            hideRegistrationModal();
        }
    });
}

function hideRegistrationModal() {
    const modal = document.getElementById('registrationModal');
    if (modal) {
        modal.classList.remove('active');
        // Reset modal when hidden
        setTimeout(() => {
            resetModal();
        }, 300);
    }
}

function handleRoleSelection(role) {
    // Hide role selection and show form section
    const roleSelection = document.querySelector('.role-selection');
    const formSection = document.getElementById('formSection');
    const welcomeSection = document.querySelector('.welcome-section');
    const studentTeacherFields = document.getElementById('studentTeacherFields');
    const adminFields = document.getElementById('adminFields');
    const formTitle = document.querySelector('.form-title');
    const formDescription = document.querySelector('.form-description');
    
    // Hide role selection and welcome section
    if (roleSelection) roleSelection.style.display = 'none';
    if (welcomeSection) welcomeSection.style.display = 'none';
    
    // Show form section
    if (formSection) {
        formSection.style.display = 'block';
        formSection.style.animation = 'slideUp 0.4s ease-out';
    }
    
    // Clear all form fields first
    if (studentTeacherFields) studentTeacherFields.style.display = 'none';
    if (adminFields) adminFields.style.display = 'none';
    
    // Show appropriate form fields based on role
    if (role === 'Student' || role === 'Teacher') {
        if (studentTeacherFields) {
            studentTeacherFields.style.display = 'flex';
            studentTeacherFields.style.animation = 'slideUp 0.3s ease-out';
        }
        formTitle.textContent = `Create New ${role} Account`;
        formDescription.textContent = 'Please fill in the required information below';
    } else if (role === 'Administrator') {
        if (adminFields) {
            adminFields.style.display = 'flex';
            adminFields.style.animation = 'slideUp 0.3s ease-out';
        }
        formTitle.textContent = 'Create New Administrator Account';
        formDescription.textContent = 'Please provide a username for the administrator account';
    }
    
    // Store selected role for later use
    window.selectedRole = role;
}

function showRoleSelection() {
    // Show role selection and welcome section
    const roleSelection = document.querySelector('.role-selection');
    const formSection = document.getElementById('formSection');
    const welcomeSection = document.querySelector('.welcome-section');
    
    // Hide form section
    if (formSection) formSection.style.display = 'none';
    
    // Show role selection and welcome section
    if (roleSelection) {
        roleSelection.style.display = 'grid';
        roleSelection.style.animation = 'slideUp 0.3s ease-out';
    }
    if (welcomeSection) {
        welcomeSection.style.display = 'block';
        welcomeSection.style.animation = 'slideUp 0.3s ease-out';
    }
    
    // Clear form fields
    clearFormFields();
    
    // Clear selected role
    window.selectedRole = null;
}

function clearFormFields() {
    // Clear Student/Teacher fields
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    
    if (firstNameInput) firstNameInput.value = '';
    if (lastNameInput) lastNameInput.value = '';
    if (emailInput) emailInput.value = '';
    
    // Clear Admin fields
    const usernameInput = document.getElementById('username');
    if (usernameInput) usernameInput.value = '';
}

function handleCreateAccount() {
    const role = window.selectedRole;
    
    if (!role) {
        showToast('Please select an account type first', 'error');
        return;
    }
    
    let formData = {};
    let isValid = true;
    
    if (role === 'Student' || role === 'Teacher') {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        
        // Validation
        if (!firstName) {
            showToast('First name is required', 'error');
            document.getElementById('firstName').focus();
            isValid = false;
        } else if (!lastName) {
            showToast('Last name is required', 'error');
            document.getElementById('lastName').focus();
            isValid = false;
        } else if (!email) {
            showToast('Email address is required', 'error');
            document.getElementById('email').focus();
            isValid = false;
        } else if (!validateEmail(email)) {
            showToast('Please enter a valid email address', 'error');
            document.getElementById('email').focus();
            isValid = false;
        } else {
            formData = { firstName, lastName, email };
        }
    } else if (role === 'Administrator') {
        const username = document.getElementById('username').value.trim();
        
        if (!username) {
            showToast('Username is required', 'error');
            document.getElementById('username').focus();
            isValid = false;
        } else if (username.length < 3) {
            showToast('Username must be at least 3 characters long', 'error');
            document.getElementById('username').focus();
            isValid = false;
        } else {
            formData = { username };
        }
    }
    
    if (isValid) {
        // Show loading state
        const createBtn = document.getElementById('createAccountBtn');
        const originalText = createBtn.innerHTML;
        createBtn.innerHTML = '<i class="bi bi-spinner me-2"></i>Creating...';
        createBtn.disabled = true;
        
        // Prepare data for backend
        const requestData = {
            accountType: role,
            ...formData
        };
        
        // Send AJAX request to backend
        fetch('../api/register_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                
                // Show credentials modal only for admin accounts
                if (role === 'Administrator' && data.data && data.data.tempPassword) {
                    setTimeout(() => {
                        showCredentialsModal(data.data, role);
                    }, 1000);
                } else {
                    // For Student/Teacher accounts, just close modal and reset
                    setTimeout(() => {
                        hideRegistrationModal();
                        resetModal();
                    }, 1500);
                }
                
                // Refresh user lists if they exist
                if (typeof loadAllUsers === 'function') {
                    setTimeout(() => {
                        loadAllUsers();
                    }, 2000);
                }
            } else {
                showToast(data.message || 'Failed to create account', 'error');
            }
        })
        .catch(error => {
            console.error('Registration error:', error);
            showToast('An error occurred while creating the account', 'error');
        })
        .finally(() => {
            // Restore button state
            createBtn.innerHTML = originalText;
            createBtn.disabled = false;
        });
    }
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showCredentialsModal(data, accountType) {
    // Create credentials modal HTML
    const modalHTML = `
        <div class="modal fade" id="credentialsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>
                            Account Created Successfully
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            ${accountType} account has been created successfully!
                        </div>
                        
                        <div class="credentials-info">
                            <h6><i class="bi bi-key me-2"></i>Login Credentials:</h6>
                            <div class="credential-item">
                                <strong>${accountType === 'Administrator' ? 'Username:' : 'Email:'}</strong>
                                <span class="credential-value">${data.username || data.email}</span>
                            </div>
                            <div class="credential-item">
                                <strong>Temporary Password:</strong>
                                <span class="credential-value password">${data.tempPassword}</span>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPassword('${data.tempPassword}')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> This is a temporary password. The user must change it upon first login.
                        </div>
                        
                        ${accountType === 'Administrator' ? `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> For security reasons, admin credentials are not sent via email. Please save these credentials securely.
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="bi bi-check me-2"></i>Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing credentials modal if any
    const existingModal = document.getElementById('credentialsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('credentialsModal'));
    modal.show();
    
    // Remove modal from DOM when hidden
    document.getElementById('credentialsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
        hideRegistrationModal();
        resetModal();
    });
}

function copyPassword(password) {
    navigator.clipboard.writeText(password).then(function() {
        showToast('Password copied to clipboard', 'success');
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = password;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Password copied to clipboard', 'success');
    });
}

function resetModal() {
    // Reset to initial state
    showRoleSelection();
    clearFormFields();
    window.selectedRole = null;
}

// Toast Notification Helper
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        console.error('Toast container not found!');
        return;
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Create icon based on type
    let iconSvg;
    if (type === 'success') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
    } else if (type === 'info') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
    } else {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    }
    
    toast.innerHTML = `
        <div class="toast-icon">
            ${iconSvg}
        </div>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : type === 'info' ? 'Info' : 'Error'}</div>
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
    closeBtn.addEventListener('click', function() {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    });
    
    // Show toast with animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto hide after 4 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}

// Utility Functions
function formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function getStatusBadgeClass(status) {
    switch(status.toLowerCase()) {
        case 'active': return 'active';
        case 'inactive': return 'inactive';
        case 'pending': return 'pending';
        default: return '';
    }
}

function getRoleBadgeClass(role) {
    switch(role.toLowerCase()) {
        case 'teacher': return 'teacher';
        case 'student': return 'student';
        case 'administrator': return 'administrator';
        default: return '';
    }
}

// Export functions for global access
window.manageUsers = {
    showToast,
    formatDateTime,
    getStatusBadgeClass,
    getRoleBadgeClass,
    loadRolePermissions
};
