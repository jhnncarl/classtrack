// Attendance History JavaScript - ClassTrack Student Dashboard

document.addEventListener('DOMContentLoaded', function() {
    initializeAttendanceHistory();
});

function initializeAttendanceHistory() {
    // Initialize modals
    initializeModals();
    
    // Initialize filter functionality
    initializeFilters();
    
    // Initialize animations
    initializeAnimations();
}

// Initialize modals (no join class modal - handled in navbar.js)
function initializeModals() {
    // Join Class modal functionality is now handled in navbar.js
    // Additional modals can be initialized here if needed
}

// Initialize filter functionality
function initializeFilters() {
    const statusFilter = document.getElementById('statusFilter');
    const subjectFilter = document.getElementById('subjectFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    // Add change event listeners to filters - auto-apply on change
    [statusFilter, subjectFilter, monthFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', function() {
                applyFilters();
            });
        }
    });
}

// Apply filters to attendance records
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    const subjectFilter = document.getElementById('subjectFilter')?.value || 'all';
    const monthFilter = document.getElementById('monthFilter')?.value || 'all';
    
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    let visibleCount = 0;
    const recordsToShow = [];
    const recordsToHide = [];
    
    // First pass: determine which records to show/hide
    attendanceRecords.forEach((record, index) => {
        let isVisible = true;
        
        // Check status filter
        if (statusFilter !== 'all') {
            const recordStatus = record.dataset.status;
            if (recordStatus !== statusFilter) {
                isVisible = false;
            }
        }
        
        // Check subject filter
        if (subjectFilter !== 'all') {
            const recordSubject = record.dataset.subject;
            if (recordSubject !== subjectFilter) {
                isVisible = false;
            }
        }
        
        // Check month filter
        if (monthFilter !== 'all') {
            const recordMonth = record.dataset.month;
            if (recordMonth !== monthFilter) {
                isVisible = false;
            }
        }
        
        if (isVisible) {
            recordsToShow.push({ record, index });
            visibleCount++;
        } else {
            recordsToHide.push({ record, index });
        }
    });
    
    // Hide records that don't match filters (with staggered animation)
    recordsToHide.forEach(({ record, index }) => {
        setTimeout(() => {
            record.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            record.style.opacity = '0';
            record.style.transform = 'scale(0.95) translateY(-10px)';
            
            setTimeout(() => {
                record.style.display = 'none';
            }, 300);
        }, index * 50); // Stagger animation
    });
    
    // Show records that match filters (with staggered animation)
    recordsToShow.forEach(({ record, index }) => {
        if (record.style.display === 'none') {
            record.style.display = 'flex';
            record.style.opacity = '0';
            record.style.transform = 'scale(0.95) translateY(10px)';
            
            setTimeout(() => {
                record.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                record.style.opacity = '1';
                record.style.transform = 'scale(1) translateY(0)';
            }, index * 50 + 100); // Stagger with delay
        } else {
            // Record is already visible, just ensure it's properly styled
            record.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            record.style.opacity = '1';
            record.style.transform = 'scale(1) translateY(0)';
        }
    });
    
    // Update record count with animation
    updateRecordCount(visibleCount);
    
    // Show empty state if no records (without animation)
    const emptyRecords = document.getElementById('emptyRecords');
    const attendanceList = document.getElementById('attendanceList');
    
    if (visibleCount === 0) {
        setTimeout(() => {
            attendanceList.style.display = 'none';
            emptyRecords.style.display = 'block';
        }, recordsToHide.length * 50);
    } else {
        if (emptyRecords.style.display !== 'none') {
            emptyRecords.style.display = 'none';
            attendanceList.style.display = 'flex';
            attendanceList.style.opacity = '1';
        }
    }
}

// Update record count display
function updateRecordCount(count) {
    const recordCountElement = document.getElementById('recordCount');
    if (recordCountElement) {
        // Add animation class
        recordCountElement.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        recordCountElement.style.transform = 'scale(1.2)';
        recordCountElement.style.color = '#1a73e8';
        
        setTimeout(() => {
            recordCountElement.textContent = count;
            recordCountElement.style.transform = 'scale(1)';
            recordCountElement.style.color = '#5f6368';
        }, 150);
    }
}

// Clear all filters
function clearFilters() {
    const statusFilter = document.getElementById('statusFilter');
    const subjectFilter = document.getElementById('subjectFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    // Reset all filters to 'all'
    if (statusFilter) statusFilter.value = 'all';
    if (subjectFilter) subjectFilter.value = 'all';
    if (monthFilter) monthFilter.value = 'all';
    
    // Apply filters to reset the view with animation
    setTimeout(() => {
        applyFilters();
    }, 100);
    
    // Filters cleared
}

// Initialize animations
function initializeAnimations() {
    // Animate percentage bars on page load
    setTimeout(() => {
        const percentageBars = document.querySelectorAll('.percentage-bar');
        percentageBars.forEach((bar, index) => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, index * 100); // Stagger bar animations
        });
    }, 500);
    
    // Animate attendance records on page load with staggered effect
    setTimeout(() => {
        const attendanceRecords = document.querySelectorAll('.attendance-record');
        attendanceRecords.forEach((record, index) => {
            record.style.opacity = '0';
            record.style.transform = 'translateY(20px)';
            record.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            
            setTimeout(() => {
                record.style.opacity = '1';
                record.style.transform = 'translateY(0)';
            }, index * 80); // Stagger record animations
        });
    }, 800);
    
    // Add hover effects to attendance records (removed)
    const attendanceRecords = document.querySelectorAll('.attendance-record');
    // Hover effects removed as requested
    
    // Animate filter cards on page load
    setTimeout(() => {
        const filterGroups = document.querySelectorAll('.filter-group');
        filterGroups.forEach((group, index) => {
            group.style.opacity = '0';
            group.style.transform = 'translateY(15px)';
            group.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            
            setTimeout(() => {
                group.style.opacity = '1';
                group.style.transform = 'translateY(0)';
            }, index * 100 + 200);
        });
    }, 300);
}



// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Refresh data when page becomes visible again
        // In a real implementation, this would fetch fresh data from the server
        console.log('Page became visible - refreshing attendance history data');
    }
});

// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize tooltips if any
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Console log for debugging
console.log('ClassTrack Attendance History page initialized successfully!');
