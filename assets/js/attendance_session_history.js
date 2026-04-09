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
    
    // Initialize scrollable behavior for sessions list
    initializeScrollableList();
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
    
    // Reinitialize scrollable list after filtering
    initializeScrollableList();
    
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
    
    // Reinitialize scrollable list after clearing filters
    initializeScrollableList();
    
    // Filters cleared without notification
}

function initializeScrollableList() {
    const sessionsList = document.getElementById('listView');
    if (!sessionsList) return;
    
    // Check if there are 5 or more session items
    const sessionItems = sessionsList.querySelectorAll('.session-item');
    
    if (sessionItems.length >= 5) {
        sessionsList.classList.add('scrollable');
        
        // Add scroll event listener for footer fade effect
        sessionsList.addEventListener('scroll', handleFooterFade);
        
        // Create footer fade element if it doesn't exist
        createFooterFade();
        
        // Initial fade check
        handleFooterFade.call(sessionsList);
    } else {
        // Remove scrollable class if less than 5 items
        sessionsList.classList.remove('scrollable');
        sessionsList.removeEventListener('scroll', handleFooterFade);
        removeFooterFade();
    }
}

function createFooterFade() {
    // Remove existing footer fade if any
    removeFooterFade();
    
    // Create footer fade element
    const footerFade = document.createElement('div');
    footerFade.className = 'footer-fade';
    footerFade.id = 'footer-fade';
    document.body.appendChild(footerFade);
}

function removeFooterFade() {
    const existingFooterFade = document.getElementById('footer-fade');
    if (existingFooterFade) {
        existingFooterFade.remove();
    }
}

function handleFooterFade() {
    const sessionsList = this;
    const scrollTop = sessionsList.scrollTop;
    const scrollHeight = sessionsList.scrollHeight;
    const clientHeight = sessionsList.clientHeight;
    
    // Check if there's more content below (not at bottom)
    const hasMoreContent = scrollTop < scrollHeight - clientHeight - 10;
    
    // Update footer fade visibility
    const footerFade = document.getElementById('footer-fade');
    if (footerFade) {
        if (hasMoreContent) {
            footerFade.classList.add('visible');
            document.documentElement.style.setProperty('--footer-fade-opacity', '1');
        } else {
            footerFade.classList.remove('visible');
            document.documentElement.style.setProperty('--footer-fade-opacity', '0');
        }
    }
}



// Session Details Modal Functions
async function viewSessionDetails(sessionId) {
    try {
        // Find session data from the database
        const sessionData = await findSessionData(sessionId);
        if (!sessionData) {
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
        
    } catch (error) {
        console.error('Error viewing session details:', error);
    }
}

async function findSessionData(sessionId) {
    try {
        // Fetch session details from server
        const response = await fetch(`../api/get_session_details.php?session_id=${sessionId}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch session details');
        }
        
        const data = await response.json();
        
        if (data.success) {
            return data.session;
        } else {
            throw new Error(data.message || 'Session not found');
        }
        
    } catch (error) {
        console.error('Error fetching session data:', error);
        return null;
    }
}

function updateModalContent(sessionData) {
    // Calculate percentages with division by zero protection
    const total = sessionData.total_students;
    let presentPercentage = 0;
    let latePercentage = 0;
    let absentPercentage = 0;
    
    if (total > 0) {
        presentPercentage = Math.round((sessionData.present_count / total) * 100);
        latePercentage = Math.round((sessionData.late_count / total) * 100);
        absentPercentage = Math.round((sessionData.absent_count / total) * 100);
    }
    
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
    
    // Handle division by zero and zero data
    let presentPercentage = 0;
    let latePercentage = 0;
    let absentPercentage = 0;
    
    if (total > 0) {
        presentPercentage = Math.round((sessionData.present_count / total) * 100);
        latePercentage = Math.round((sessionData.late_count / total) * 100);
        absentPercentage = Math.round((sessionData.absent_count / total) * 100);
    }
    
    // If all values are 0, show a default chart with equal parts
    if (presentPercentage === 0 && latePercentage === 0 && absentPercentage === 0) {
        presentPercentage = 33;
        latePercentage = 33;
        absentPercentage = 34;
    }
    
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

