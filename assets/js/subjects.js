// Subjects Page JavaScript - ClassTrack Student Dashboard

// Initialize the page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSubjectsPage();
});

// Initialize Subjects Page
function initializeSubjectsPage() {
    clearLoadingStates();
    initializeInternetMonitoring();
    setupEventListeners();
    setupToastCloseButton();
    setupUnenrollLinks();
}

// Initialize Internet Monitoring
function initializeInternetMonitoring() {
    updateConnectionStatus(navigator.onLine);
    
    window.addEventListener('online', function() {
        updateConnectionStatus(true);
        showConnectionToast('success', 'Internet connection restored');
    });
    
    window.addEventListener('offline', function() {
        updateConnectionStatus(false);
        showConnectionToast('error', 'Internet connection lost');
    });
    
    setInterval(checkInternetConnection, 5000);
}

// Update Connection Status
function updateConnectionStatus(isOnline) {
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        if (isOnline) {
            if (button.classList.contains('offline')) {
                button.classList.remove('offline');
                button.innerHTML = '<i class="bi bi-eye"></i>';
                button.disabled = false;
                button.style.cursor = 'pointer';
                button.style.pointerEvents = 'auto';
            }
        } else {
            if (!button.classList.contains('offline') && button.innerHTML.includes('bi-eye')) {
                button.classList.add('offline');
                button.innerHTML = '<i class="bi bi-wifi-off"></i>';
                button.disabled = true;
                button.style.cursor = 'not-allowed';
                button.style.pointerEvents = 'none';
            }
        }
    });
}

// Check Internet Connection
async function checkInternetConnection() {
    try {
        const response = await fetch('https://httpbin.org/get', {
            method: 'HEAD',
            mode: 'no-cors',
            cache: 'no-cache'
        });
        
        if (navigator.onLine) {
            updateConnectionStatus(true);
        }
    } catch (error) {
        updateConnectionStatus(false);
    }
}

// Show Connection Toast
function showConnectionToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.getElementById('otp-toast');
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon svg');
    
    if (type === 'success') {
        toastTitle.textContent = 'Connection Restored';
        toast.className = 'toast toast-success';
        toastIcon.innerHTML = `
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        `;
    } else if (type === 'error') {
        toastTitle.textContent = 'Connection Lost';
        toast.className = 'toast toast-error';
        toastIcon.innerHTML = `
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        `;
    }
    
    toastMessage.textContent = message;
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// Clear Loading States
function clearLoadingStates() {
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        if (button.innerHTML.includes('spinner-border')) {
            button.innerHTML = '<i class="bi bi-eye"></i>';
            button.disabled = false;
            button.style.cursor = 'pointer';
            button.style.pointerEvents = 'auto';
        }
    });
}

// Setup Event Listeners
function setupEventListeners() {
    const subjectCards = document.querySelectorAll('.class-card');
    subjectCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

// Setup Unenroll Links
function setupUnenrollLinks() {
    const unenrollLinks = document.querySelectorAll('.unenroll-link');
    
    unenrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const className = this.getAttribute('data-class');
            const classCard = this.closest('.class-card');
            
            document.getElementById('unenrollClassName').textContent = className;
            
            window.currentUnenrollData = {
                className: className,
                classCard: classCard
            };
            
            const modal = new bootstrap.Modal(document.getElementById('unenrollModal'));
            modal.show();
        });
    });
}

// Handle confirm unenroll button
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmUnenrollBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (window.currentUnenrollData) {
                const { className, classCard } = window.currentUnenrollData;
                
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Unenrolling...';
                
                setTimeout(() => {
                    classCard.style.transition = 'all 0.3s ease';
                    classCard.style.opacity = '0';
                    classCard.style.transform = 'scale(0.9)';
                    
                    setTimeout(() => {
                        classCard.remove();
                        
                        const remainingCards = document.querySelectorAll('.class-card');
                        if (remainingCards.length === 0) {
                            showEmptyState();
                        }
                        
                        const modal = bootstrap.Modal.getInstance(document.getElementById('unenrollModal'));
                        if (modal) modal.hide();
                        
                        showSuccessToast(`Successfully unenrolled from ${className}`);
                        
                        this.disabled = false;
                        this.innerHTML = 'Yes';
                        
                        window.currentUnenrollData = null;
                    }, 300);
                }, 1000);
            }
        });
    }
});

// Show Empty State
function showEmptyState() {
    const classGrid = document.querySelector('.class-grid');
    if (classGrid) {
        classGrid.innerHTML = `
            <div class="empty-state text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h3 class="mt-3 text-muted">No Classes Enrolled</h3>
                <p class="text-muted">You are not currently enrolled in any classes.</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh Page
                </button>
            </div>
        `;
    }
}

// View Attendance History
function viewAttendanceHistory(subjectId) {
    if (!navigator.onLine) {
        showConnectionToast('error', 'No internet connection. Please check your connection and try again.');
        return;
    }
    
    const subjectData = {
        'WEBDEV101': { name: 'Web Development', section: 'CS-301', teacher: 'Prof. Sarah Johnson' },
        'DATA201': { name: 'Data Structures', section: 'CS-201', teacher: 'Dr. Michael Chen' },
        'DB302': { name: 'Database Systems', section: 'CS-302', teacher: 'Prof. Emily Davis' },
        'ML401': { name: 'Machine Learning', section: 'CS-401', teacher: 'Dr. Robert Wilson' },
        'MOB351': { name: 'Mobile Development', section: 'CS-351', teacher: 'Prof. Lisa Anderson' },
        'NET251': { name: 'Computer Networks', section: 'CS-251', teacher: 'Dr. James Martinez' }
    };
    
    const subject = subjectData[subjectId] || subjectData['WEBDEV101'];
    
    if (event && event.target) {
        const button = event.target.closest('.action-btn');
        if (button && navigator.onLine) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;
            
            setTimeout(() => {
                window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subject.name)}`;
            }, 500);
        } else {
            if (navigator.onLine) {
                window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subject.name)}`;
            }
        }
    } else {
        if (navigator.onLine) {
            window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subject.name)}`;
        }
    }
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

// Show Success Toast
function showSuccessToast(message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.getElementById('otp-toast');
    
    if (!toast || !toastContainer) {
        console.error('Toast elements not found');
        return;
    }
    
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    
    toastTitle.textContent = 'Success';
    toastMessage.textContent = message;
    toast.className = 'toast toast-success';
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}




// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        clearLoadingStates();
    }
});

window.addEventListener('pageshow', function(event) {
    clearLoadingStates();
});

// Export functions for global access
window.viewAttendanceHistory = viewAttendanceHistory;
window.clearLoadingStates = clearLoadingStates;
window.initializeInternetMonitoring = initializeInternetMonitoring;
window.updateConnectionStatus = updateConnectionStatus;
window.checkInternetConnection = checkInternetConnection;
window.showConnectionToast = showConnectionToast;
