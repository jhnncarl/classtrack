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


// Calendar functionality for Date Range
class DateRangeCalendar {
    constructor() {
        this.currentDate = new Date();
        this.selectedDate = null;
        this.calendarDropdown = document.getElementById('calendarDropdown');
        this.calendarToggle = document.getElementById('calendarToggle');
        this.dateRangeInput = document.getElementById('dateRangeInput');
        this.calendarTitle = document.getElementById('calendarTitle');
        this.calendarDays = document.getElementById('calendarDays');
        this.prevMonth = document.getElementById('prevMonth');
        this.nextMonth = document.getElementById('nextMonth');
        this.clearDateRange = document.getElementById('clearDateRange');
        this.todayDateRange = document.getElementById('todayDateRange');
        
        this.init();
    }
    
    init() {
        // Event listeners
        this.calendarToggle.addEventListener('click', () => this.toggleCalendar());
        this.prevMonth.addEventListener('click', () => this.navigateMonth(-1));
        this.nextMonth.addEventListener('click', () => this.navigateMonth(1));
        this.clearDateRange.addEventListener('click', () => this.clearSelection());
        this.todayDateRange.addEventListener('click', () => this.selectToday());
        
        // Close calendar when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.calendar-dropdown-container')) {
                this.closeCalendar();
            }
        });
        
        // Initialize calendar
        this.renderCalendar();
        this.updateInput();
    }
    
    toggleCalendar() {
        this.calendarDropdown.classList.toggle('active');
    }
    
    closeCalendar() {
        this.calendarDropdown.classList.remove('active');
    }
    
    navigateMonth(direction) {
        this.currentDate.setMonth(this.currentDate.getMonth() + direction);
        this.renderCalendar();
    }
    
    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // Update title
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];
        this.calendarTitle.textContent = `${monthNames[month]} ${year}`;
        
        // Clear days
        this.calendarDays.innerHTML = '';
        
        // Get first day of month and number of days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        // Add previous month's trailing days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dayElement = this.createDayElement(day, true, new Date(year, month - 1, day));
            this.calendarDays.appendChild(dayElement);
        }
        
        // Add current month's days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dayElement = this.createDayElement(day, false, date);
            this.calendarDays.appendChild(dayElement);
        }
        
        // Add next month's leading days
        const totalCells = this.calendarDays.children.length;
        const remainingCells = 42 - totalCells; // 6 rows * 7 days
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = this.createDayElement(day, true, new Date(year, month + 1, day));
            this.calendarDays.appendChild(dayElement);
        }
    }
    
    createDayElement(day, isOtherMonth, date) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        if (isOtherMonth) {
            dayElement.classList.add('other-month');
        }
        
        // Check if today
        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            dayElement.classList.add('today');
        }
        
        // Check if selected
        if (this.selectedDate && date.toDateString() === this.selectedDate.toDateString()) {
            dayElement.classList.add('selected');
        }
        
        // Check if has records
        if (this.hasRecordsForDate(date)) {
            dayElement.classList.add('has-records');
        }
        
        // Add click event
        dayElement.addEventListener('click', () => this.selectDate(date));
        
        return dayElement;
    }
    
    hasRecordsForDate(date) {
        // Check if there are attendance records for this date
        const dateString = date.toISOString().split('T')[0];
        const records = document.querySelectorAll('.attendance-record');
        
        for (let record of records) {
            if (record.dataset.date === dateString) {
                return true;
            }
        }
        return false;
    }
    
    selectDate(date) {
        this.selectedDate = date;
        this.currentDate = new Date(date);
        this.renderCalendar();
        this.updateInput();
        this.closeCalendar();
        
        // Trigger filter change
        this.triggerFilterChange();
    }
    
    selectToday() {
        this.selectDate(new Date());
    }
    
    clearSelection() {
        this.selectedDate = null;
        this.renderCalendar();
        this.updateInput();
        this.closeCalendar();
        
        // Trigger filter change
        this.triggerFilterChange();
    }
    
    updateInput() {
        if (this.dateRangeInput) {
            if (this.selectedDate) {
                const dateStr = this.selectedDate.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                this.dateRangeInput.value = dateStr;
                this.dateRangeInput.placeholder = 'Select date';
            } else {
                this.dateRangeInput.value = '';
                this.dateRangeInput.placeholder = 'Select date';
            }
        }
    }
    
    getSelectedDate() {
        return this.selectedDate;
    }
    
    triggerFilterChange() {
        // Trigger the filter application
        applyFilters();
    }
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
            // Auto-apply filters on any change
            applyFilters();
        });
    });
}

// Initialize Filters
function initializeFilters() {
    // Initialize record count with actual visible records on page load
    setTimeout(() => {
        applyFilters();
        
        // Check if there are no records initially and update empty state
        const attendanceRecords = document.querySelectorAll('.attendance-record');
        if (attendanceRecords.length === 0) {
            const emptyRecords = document.getElementById('emptyRecords');
            if (emptyRecords) {
                emptyRecords.style.display = 'block';
                updateEmptyState('all', 'all');
            }
        }
    }, 100);
}

function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const selectedDate = window.dateRangeCalendar ? window.dateRangeCalendar.getSelectedDate() : null;
    
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    const attendanceList = document.getElementById('attendanceList');
    const emptyRecords = document.getElementById('emptyRecords');
    const recordCount = document.getElementById('recordCount');
    
    let visibleCount = 0;
    
    attendanceRecords.forEach(record => {
        let shouldShow = true;
        
        // Date filter from calendar selection
        if (selectedDate) {
            const recordDate = new Date(record.dataset.date);
            const selectedDateOnly = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
            const recordDateOnly = new Date(recordDate.getFullYear(), recordDate.getMonth(), recordDate.getDate());
            
            shouldShow = shouldShow && recordDateOnly.getTime() === selectedDateOnly.getTime();
        }
        
        // Status filter
        if (statusFilter !== 'all') {
            shouldShow = shouldShow && record.dataset.status === statusFilter;
        }
        
        // Show or hide record
        if (shouldShow) {
            record.style.display = 'flex';
            visibleCount++;
        } else {
            record.style.display = 'none';
        }
    });
    
    // Update record count
    if (recordCount) recordCount.textContent = visibleCount;
    
    // Show/hide empty state
    if (visibleCount === 0) {
        if (attendanceList) attendanceList.style.display = 'none';
        if (emptyRecords) {
            emptyRecords.style.display = 'block';
            // Update empty state content based on active filters
            updateEmptyState(selectedDate, statusFilter);
        }
    } else {
        if (attendanceList) attendanceList.style.display = 'flex';
        if (emptyRecords) emptyRecords.style.display = 'none';
    }
}

// Clear Filters
function clearFilters() {
    // Reset all filter selects to 'all'
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.value = 'all';
    });
    
    // Clear calendar selection if it exists
    if (window.dateRangeCalendar) {
        window.dateRangeCalendar.clearSelection();
    }
    
    // Show all records
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    const attendanceList = document.getElementById('attendanceList');
    const emptyRecords = document.getElementById('emptyRecords');
    const recordCount = document.getElementById('recordCount');
    
    attendanceRecords.forEach(record => {
        record.style.display = 'flex';
    });
    
    // Update record count
    if (recordCount) recordCount.textContent = attendanceRecords.length;
    
    // Show list, hide empty state
    if (attendanceList) attendanceList.style.display = 'flex';
    if (emptyRecords) emptyRecords.style.display = 'none';
}

// Update empty state content based on active filters
function updateEmptyState(selectedDate, statusFilter) {
    const emptyIcon = document.getElementById('emptyIcon');
    const emptyTitle = document.getElementById('emptyTitle');
    const emptyMessage = document.getElementById('emptyMessage');
    
    if (!emptyIcon || !emptyTitle || !emptyMessage) return;
    
    let icon = 'bi-calendar-x';
    let title = 'No attendance records found';
    let message = 'Try adjusting your filters or check back later for new records.';
    
    // Check if date filter is active
    if (selectedDate) {
        const dateStr = selectedDate.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        icon = 'bi-calendar-day';
        title = `No attendance records for ${dateStr}`;
        message = `You don't have any attendance records scheduled for ${dateStr}.`;
    }
    // Check if status filter is active
    else if (statusFilter !== 'all') {
        switch(statusFilter) {
            case 'present':
                icon = 'bi-check-circle';
                title = 'No present attendance records';
                message = 'You don\'t have any present attendance records for the selected period.';
                break;
            case 'absent':
                icon = 'bi-x-circle';
                title = 'No absent attendance records';
                message = 'You don\'t have any absent attendance records for the selected period.';
                break;
            case 'late':
                icon = 'bi-clock';
                title = 'No late attendance records';
                message = 'You don\'t have any late attendance records for the selected period.';
                break;
        }
    }
    
    // Update the empty state content
    emptyIcon.className = `bi ${icon}`;
    emptyTitle.textContent = title;
    emptyMessage.textContent = message;
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
        // Page became visible again - could add refresh logic here if needed in future
    }
});


// Initialize calendar when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit to ensure all elements are loaded
    setTimeout(() => {
        if (!window.dateRangeCalendar) {
            window.dateRangeCalendar = new DateRangeCalendar();
        }
    }, 100);
});

// Export functions for global access
window.applyFilters = applyFilters;
window.clearFilters = clearFilters;
window.showAttendanceToast = showAttendanceToast;
window.renderAttendanceRecords = renderAttendanceRecords;
