// Attendance Session History JavaScript - ClassTrack

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializeSessionHistory();
});

function initializeSessionHistory() {
    // Set up event listeners
    setupEventListeners();
    
    // Check if there are any sessions initially
    const listItems = document.querySelectorAll('#listView .session-item');
    window.hasSessionsInitially = listItems.length > 0;
}

function setupEventListeners() {
    // Filter controls
    const subjectFilter = document.getElementById('subjectFilter');
    const statusFilter = document.getElementById('statusFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    if (subjectFilter) {
        subjectFilter.addEventListener('change', function() {
            // Auto-apply filter on change
            applyFilters();
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            // Auto-apply filter on change
            applyFilters();
        });
    }
    
    if (monthFilter) {
        monthFilter.addEventListener('change', function() {
            // Auto-apply filter on change
            applyFilters();
        });
    }
}


function applyFilters() {
    const subjectFilter = document.getElementById('subjectFilter');
    const statusFilter = document.getElementById('statusFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    const subjectValue = subjectFilter ? subjectFilter.value : 'all';
    const statusValue = statusFilter ? statusFilter.value : 'all';
    const monthValue = monthFilter ? monthFilter.value : 'all';
    
    // Get all session items
    const listItems = document.querySelectorAll('#listView .session-item');
    
    let hasVisibleItems = false;
    
    // Filter list view items
    listItems.forEach(item => {
        const matchesSubject = subjectValue === 'all' || item.dataset.subjectId === subjectValue;
        const matchesStatus = statusValue === 'all' || item.dataset.status === statusValue;
        const matchesMonth = monthValue === 'all' || item.dataset.date.startsWith(monthValue);
        
        if (matchesSubject && matchesStatus && matchesMonth) {
            item.style.display = 'flex';
            hasVisibleItems = true;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide empty state for filtered results
    const filteredEmptyState = document.getElementById('filteredEmptyState');
    const listEmptyState = document.querySelector('#listView .empty-state-container');
    
    if (!hasVisibleItems) {
        if (window.hasSessionsInitially) {
            // Sessions exist but none match filter - show filtered empty state
            if (filteredEmptyState) {
                filteredEmptyState.classList.add('show');
            }
            
            // Hide original empty state if it exists
            if (listEmptyState) {
                listEmptyState.parentElement.style.display = 'none';
            }
        } else {
            // No sessions at all - show original empty state
            if (filteredEmptyState) {
                filteredEmptyState.classList.remove('show');
            }
            
            if (listEmptyState) {
                listEmptyState.parentElement.style.display = 'block';
            }
        }
    } else {
        // Has visible items - hide all empty states
        if (filteredEmptyState) {
            filteredEmptyState.classList.remove('show');
        }
        
        // Hide original empty state if it exists
        if (listEmptyState) {
            listEmptyState.parentElement.style.display = 'none';
        }
    }
    
    // Filters applied without notification
}

function clearFilters() {
    const subjectFilter = document.getElementById('subjectFilter');
    const statusFilter = document.getElementById('statusFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    // Reset all filters to 'all'
    if (subjectFilter) subjectFilter.value = 'all';
    if (statusFilter) statusFilter.value = 'all';
    if (monthFilter) monthFilter.value = 'all';
    
    // Show all items
    const listItems = document.querySelectorAll('#listView .session-item');
    
    listItems.forEach(item => {
        item.style.display = 'flex';
    });
    
    // Hide filtered empty state
    const filteredEmptyState = document.getElementById('filteredEmptyState');
    if (filteredEmptyState) {
        filteredEmptyState.classList.remove('show');
    }
    
    // Show original empty state if it exists
    const listEmptyState = document.querySelector('#listView .empty-state-container');
    if (listEmptyState) {
        listEmptyState.parentElement.style.display = 'block';
    }
    
    // Filters cleared without notification
}


function showToast(message, type = 'info') {
    // Check if toast container exists
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-${getToastIcon(type)} me-2 text-${getToastColor(type)}"></i>
                <strong class="me-auto">ClassTrack</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
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

function getToastIcon(type) {
    const icons = {
        success: 'check-circle-fill',
        error: 'exclamation-triangle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    };
    return icons[type] || icons.info;
}

function getToastColor(type) {
    const colors = {
        success: 'success',
        error: 'danger',
        warning: 'warning',
        info: 'primary'
    };
    return colors[type] || colors.info;
}

// Session Details Modal Functions
function viewSessionDetails(sessionId) {
    // Find session data from the sample data
    const sessionData = findSessionData(sessionId);
    if (!sessionData) {
        showToast('Session data not found', 'error');
        return;
    }

    // Update modal content with session data
    updateModalContent(sessionData);
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('sessionDetailsModal'));
    modal.show();
    
    // Initialize charts after modal is shown
    modal._element.addEventListener('shown.bs.modal', function() {
        initializeCharts(sessionData);
    }, { once: true });
}

function findSessionData(sessionId) {
    // Sample session data (matching the PHP sample data)
    const sessions = [
        {
            'SessionID': 1,
            'SubjectID': 101,
            'SubjectName': 'Advanced Web Development',
            'SubjectCode': 'CS-301',
            'ClassName': 'BSIT 3A',
            'SectionName': 'Morning',
            'SessionDate': '2026-04-08',
            'StartTime': '09:00:00',
            'EndTime': '10:00:00',
            'Status': 'Completed',
            'total_students' : 25,
            'present_count' : 22,
            'late_count' : 2,
            'absent_count' : 1
        },
        {
            'SessionID': 2,
            'SubjectID': 102,
            'SubjectName': 'Database Management Systems',
            'SubjectCode': 'CS-302',
            'ClassName': 'BSIT 3B',
            'SectionName': 'Afternoon',
            'SessionDate': '2026-04-07',
            'StartTime': '13:00:00',
            'EndTime': '14:00:00',
            'Status': 'Completed',
            'total_students' : 30,
            'present_count' : 28,
            'late_count' : 1,
            'absent_count' : 1
        },
        {
            'SessionID': 3,
            'SubjectID': 103,
            'SubjectName': 'Software Engineering',
            'SubjectCode': 'CS-303',
            'ClassName': 'BSIT 4A',
            'SectionName': 'Morning',
            'SessionDate': '2026-04-06',
            'StartTime': '10:00:00',
            'EndTime': '11:00:00',
            'Status': 'Active',
            'total_students' : 28,
            'present_count' : 15,
            'late_count' : 3,
            'absent_count' : 10
        }
    ];
    
    return sessions.find(session => session.SessionID === sessionId);
}

function updateModalContent(sessionData) {
    // Calculate percentages
    const total = sessionData.total_students;
    const presentPercentage = Math.round((sessionData.present_count / total) * 100);
    const latePercentage = Math.round((sessionData.late_count / total) * 100);
    const absentPercentage = Math.round((sessionData.absent_count / total) * 100);
    
    // Update desktop view elements
    document.getElementById('modalSubject').textContent = sessionData.SubjectName;
    document.getElementById('modalClass').textContent = `${sessionData.ClassName} - ${sessionData.SectionName}`;
    document.getElementById('modalDate').textContent = formatDate(sessionData.SessionDate);
    document.getElementById('modalTime').textContent = `${formatTime(sessionData.StartTime)} - ${formatTime(sessionData.EndTime)}`;
    
    const modalStatus = document.getElementById('modalStatus');
    modalStatus.textContent = sessionData.Status;
    modalStatus.className = `status-badge ${sessionData.Status.toLowerCase()}`;
    
    // Update attendance indicators for desktop
    document.getElementById('totalStudents').textContent = total;
    document.getElementById('presentCount').textContent = sessionData.present_count;
    document.getElementById('presentPercentage').textContent = `${presentPercentage}%`;
    document.getElementById('lateCount').textContent = sessionData.late_count;
    document.getElementById('latePercentage').textContent = `${latePercentage}%`;
    document.getElementById('absentCount').textContent = sessionData.absent_count;
    document.getElementById('absentPercentage').textContent = `${absentPercentage}%`;
    
    // Update mobile view elements
    document.getElementById('mobileSubject').textContent = sessionData.SubjectName;
    document.getElementById('mobileClass').textContent = `${sessionData.ClassName} - ${sessionData.SectionName}`;
    document.getElementById('mobileDate').textContent = formatDate(sessionData.SessionDate);
    document.getElementById('mobileTime').textContent = `${formatTime(sessionData.StartTime)} - ${formatTime(sessionData.EndTime)}`;
    
    const mobileStatus = document.getElementById('mobileStatus');
    mobileStatus.textContent = sessionData.Status;
    mobileStatus.className = `status-badge ${sessionData.Status.toLowerCase()}`;
    
    // Update attendance indicators for mobile
    document.getElementById('mobileTotalStudents').textContent = total;
    document.getElementById('mobilePresentCount').textContent = sessionData.present_count;
    document.getElementById('mobilePresentPercentage').textContent = `${presentPercentage}%`;
    document.getElementById('mobileLateCount').textContent = sessionData.late_count;
    document.getElementById('mobileLatePercentage').textContent = `${latePercentage}%`;
    document.getElementById('mobileAbsentCount').textContent = sessionData.absent_count;
    document.getElementById('mobileAbsentPercentage').textContent = `${absentPercentage}%`;
}

function initializeCharts(sessionData) {
    const total = sessionData.total_students;
    const presentPercentage = Math.round((sessionData.present_count / total) * 100);
    const latePercentage = Math.round((sessionData.late_count / total) * 100);
    const absentPercentage = Math.round((sessionData.absent_count / total) * 100);
    
    // Destroy existing charts if they exist
    destroyExistingCharts();
    
    // Desktop chart
    const desktopCtx = document.getElementById('attendanceChart');
    if (desktopCtx) {
        window.desktopChart = new Chart(desktopCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [presentPercentage, latePercentage, absentPercentage],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Mobile chart
    const mobileCtx = document.getElementById('mobileAttendanceChart');
    if (mobileCtx) {
        window.mobileChart = new Chart(mobileCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [presentPercentage, latePercentage, absentPercentage],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    }
}

function destroyExistingCharts() {
    if (window.desktopChart) {
        window.desktopChart.destroy();
        window.desktopChart = null;
    }
    if (window.mobileChart) {
        window.mobileChart.destroy();
        window.mobileChart = null;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

