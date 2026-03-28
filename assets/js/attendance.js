// Attendance Page JavaScript - ClassTrack Student Dashboard

// Initialize the page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAttendancePage();
});

// Initialize Attendance Page
function initializeAttendancePage() {
    // Set up event listeners
    setupEventListeners();
    
    // Initialize filters
    initializeFilters();
    
    // Setup toast close button
    setupToastCloseButton();
    
    // Load attendance data (for future database integration)
    loadAttendanceData();
}

// Show Toast Notification using existing toast system
function showToast(type, message) {
    // Get existing toast elements
    const toast = document.getElementById('otp-toast');
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon svg');
    
    // Update toast content based on type
    if (type === 'success') {
        toastTitle.textContent = 'Success';
        toast.className = 'toast toast-success';
        // Update icon to checkmark for success
        toastIcon.innerHTML = `
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        `;
    } else if (type === 'error') {
        toastTitle.textContent = 'Error';
        toast.className = 'toast toast-error';
        // Update icon to X for error
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        `;
    } else if (type === 'info') {
        toastTitle.textContent = 'Info';
        toast.className = 'toast toast-info';
        // Update icon to info for info
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        `;
    }
    
    toastMessage.textContent = message;
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Show an error toast notification for joining class
function showJoinClassToast(type, message) {
    showToast(message, type);
}

// Generic toast function
function showToast(message, type = 'info') {
    // Get existing toast elements
    const toastContainer = document.getElementById('toast-container');
    const toast = document.getElementById('otp-toast');
    
    // Check if toast elements exist before trying to use them
    if (!toast || !toastContainer) {
        console.error('Toast elements not found');
        return;
    }
    
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon svg');
    
    // Update toast content based on type
    if (type === 'success') {
        toastTitle.textContent = 'Success';
        toast.className = 'toast toast-success';
        // Update icon to checkmark for success
        toastIcon.innerHTML = `
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        `;
    } else if (type === 'error') {
        toastTitle.textContent = 'Error';
        toast.className = 'toast toast-error';
        // Update icon to X for error
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        `;
    }
    
    toastMessage.textContent = message;
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-hide after 4 seconds for errors, 3 seconds for success
    const hideDelay = type === 'error' ? 4000 : 3000;
    setTimeout(() => {
        toast.classList.remove('show');
    }, hideDelay);
}


// Setup Event Listeners
function setupEventListeners() {
    // Add hover effects to attendance records
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    attendanceRecords.forEach(record => {
        record.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        record.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Setup filter change listeners
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Auto-apply filters on change
            if (this.value !== 'all') {
                applyFilters();
            }
        });
    });
}

// Initialize Filters
function initializeFilters() {
    // Get current month and set as default
    const currentMonth = new Date().toISOString().slice(0, 7);
    const monthFilter = document.getElementById('monthFilter');
    if (monthFilter) {
        // Check if current month option exists, if not, add it
        const currentMonthOption = Array.from(monthFilter.options).find(option => option.value === currentMonth);
        if (!currentMonthOption) {
            const option = new Option(new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' }), currentMonth);
            monthFilter.add(option, 1); // Add after "All Months"
        }
    }
}

// Apply Filters
function applyFilters() {
    const dateFilter = document.getElementById('dateFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const monthFilter = document.getElementById('monthFilter').value;
    
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    const attendanceList = document.getElementById('attendanceList');
    const emptyRecords = document.getElementById('emptyRecords');
    const recordCount = document.getElementById('recordCount');
    
    let visibleCount = 0;
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Set to start of day
    
    attendanceRecords.forEach(record => {
        let shouldShow = true;
        
        // Date filter
        if (dateFilter !== 'all') {
            const recordDate = new Date(record.dataset.date);
            recordDate.setHours(0, 0, 0, 0);
            
            switch(dateFilter) {
                case 'today':
                    shouldShow = shouldShow && recordDate.getTime() === today.getTime();
                    break;
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekStart.getDate() + 6);
                    shouldShow = shouldShow && recordDate >= weekStart && recordDate <= weekEnd;
                    break;
                case 'month':
                    shouldShow = shouldShow && recordDate.getMonth() === today.getMonth() && 
                                           recordDate.getFullYear() === today.getFullYear();
                    break;
                case 'custom':
                    // For custom range, we would need date pickers (future enhancement)
                    break;
            }
        }
        
        // Status filter
        if (statusFilter !== 'all') {
            shouldShow = shouldShow && record.dataset.status === statusFilter;
        }
        
        // Month filter
        if (monthFilter !== 'all') {
            shouldShow = shouldShow && record.dataset.month === monthFilter;
        }
        
        // Show or hide record
        if (shouldShow) {
            record.classList.remove('filtered-out');
            record.classList.add('filtered-in');
            visibleCount++;
        } else {
            record.classList.add('filtered-out');
            record.classList.remove('filtered-in');
        }
    });
    
    // Update record count
    recordCount.textContent = visibleCount;
    
    // Show/hide empty state
    if (visibleCount === 0) {
        attendanceList.style.display = 'none';
        emptyRecords.style.display = 'block';
    } else {
        attendanceList.style.display = 'flex';
        emptyRecords.style.display = 'none';
    }
}

// Clear Filters
function clearFilters() {
    // Reset all filter selects to 'all'
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.value = 'all';
    });
    
    // Show all records
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    const attendanceList = document.getElementById('attendanceList');
    const emptyRecords = document.getElementById('emptyRecords');
    const recordCount = document.getElementById('recordCount');
    
    attendanceRecords.forEach(record => {
        record.classList.remove('filtered-out', 'filtered-in');
    });
    
    // Update record count
    recordCount.textContent = attendanceRecords.length;
    
    // Show list, hide empty state
    attendanceList.style.display = 'flex';
    emptyRecords.style.display = 'none';
}

// Load Attendance Data (for future database integration)
function loadAttendanceData() {
    // This function will be used to load attendance data from the database
    // For now, we're using static HTML content
    
    // Future implementation:
    /*
    const subjectId = getUrlParameter('subject_id');
    if (subjectId) {
        fetch(`../api/get_attendance_records.php?subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderAttendanceRecords(data.records);
                    updateStatistics(data.statistics);
                } else {
                    showAttendanceToast('error', 'Failed to load attendance records');
                }
            })
            .catch(error => {
                console.error('Error loading attendance records:', error);
                showAttendanceToast('error', 'Error loading attendance records');
            });
    }
    */
}

// Render Attendance Records Dynamically (for future use)
function renderAttendanceRecords(records) {
    const attendanceList = document.getElementById('attendanceList');
    const emptyRecords = document.getElementById('emptyRecords');
    const recordCount = document.getElementById('recordCount');
    
    if (records.length === 0) {
        attendanceList.style.display = 'none';
        emptyRecords.style.display = 'block';
        recordCount.textContent = '0';
        return;
    }
    
    attendanceList.innerHTML = records.map((record, index) => `
        <div class="attendance-record" data-date="${record.date}" data-status="${record.status}" data-month="${record.date.substring(0, 7)}">
            <div class="record-date">
                <div class="date-day">${new Date(record.date).getDate()}</div>
                <div class="date-month">${new Date(record.date).toLocaleDateString('en-US', { month: 'short' })}</div>
            </div>
            <div class="record-details">
                <div class="record-info">
                    <h4 class="record-date-full">${record.date} • ${record.day}</h4>
                    <p class="record-time">${record.time}</p>
                    <p class="record-topic">${record.topic}</p>
                </div>
                <div class="record-status">
                    <span class="status-badge status-${record.status}">
                        ${getStatusIcon(record.status)} ${capitalizeFirst(record.status)}
                    </span>
                </div>
            </div>
        </div>
    `).join('');
    
    attendanceList.style.display = 'flex';
    emptyRecords.style.display = 'none';
    recordCount.textContent = records.length;
    
    // Re-setup event listeners for new records
    setupEventListeners();
}

// Update Statistics (for future use)
function updateStatistics(statistics) {
    // Update stat cards with real data
    const presentCount = document.querySelector('.stat-card:nth-child(1) .stat-number');
    const absentCount = document.querySelector('.stat-card:nth-child(2) .stat-number');
    const lateCount = document.querySelector('.stat-card:nth-child(3) .stat-number');
    const totalCount = document.querySelector('.stat-card:nth-child(4) .stat-number');
    
    if (presentCount) presentCount.textContent = statistics.present || 0;
    if (absentCount) absentCount.textContent = statistics.absent || 0;
    if (lateCount) lateCount.textContent = statistics.late || 0;
    if (totalCount) totalCount.textContent = statistics.total || 0;
}

// Helper Functions
function getStatusIcon(status) {
    switch(status) {
        case 'present':
            return '<i class="bi bi-check-circle me-1"></i>';
        case 'absent':
            return '<i class="bi bi-x-circle me-1"></i>';
        case 'late':
            return '<i class="bi bi-clock me-1"></i>';
        default:
            return '';
    }
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Setup Toast Close Button
function setupToastCloseButton() {
    const toastCloseBtn = document.getElementById('toastClose');
    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', function() {
            const toast = document.getElementById('otp-toast');
            if (toast) {
                toast.classList.remove('show');
            }
        });
    }
}

// Show Attendance Toast using existing toast system
function showAttendanceToast(type, message) {
    // Get existing toast elements
    const toastContainer = document.getElementById('toast-container');
    const toast = document.getElementById('otp-toast');
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon svg');
    
    // Update toast content based on type
    if (type === 'success') {
        toastTitle.textContent = 'Success';
        toast.className = 'toast toast-success';
        // Update icon to checkmark for success
        toastIcon.innerHTML = `
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        `;
    } else if (type === 'error') {
        toastTitle.textContent = 'Error';
        toast.className = 'toast toast-error';
        // Update icon to X for error
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        `;
    } else if (type === 'info') {
        toastTitle.textContent = 'Info';
        toast.className = 'toast toast-info';
        // Update icon to info for info
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        `;
    }
    
    toastMessage.textContent = message;
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Handle keyboard navigation
document.addEventListener('keydown', function(event) {
    // Press 'C' to clear filters
    if (event.key === 'c' || event.key === 'C') {
        if (!event.target.matches('input, textarea, select')) {
            clearFilters();
        }
    }
    
    // Press 'A' to apply filters
    if (event.key === 'a' || event.key === 'A') {
        if (!event.target.matches('input, textarea, select')) {
            applyFilters();
        }
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Refresh data when page becomes visible again
        // loadAttendanceData(); // Uncomment when database integration is ready
    }
});

// Export functions for global access
window.applyFilters = applyFilters;
window.clearFilters = clearFilters;
window.showAttendanceToast = showAttendanceToast;
window.renderAttendanceRecords = renderAttendanceRecords;
