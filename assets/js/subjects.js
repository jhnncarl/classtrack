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
            
            // Check if user has permission to unenroll
            if (!window.canUnenroll) {
                showToast('info', 'This feature is currently unavailable. Please contact your administrator to enable access.');
                return;
            }
            
            const className = this.getAttribute('data-class');
            const subjectId = this.getAttribute('data-subject-id');
            const classCard = this.closest('.class-card');
            
            document.getElementById('unenrollClassName').textContent = className;
            
            window.currentUnenrollData = {
                className: className,
                subjectId: subjectId,
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
                const { className, subjectId, classCard } = window.currentUnenrollData;
                
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Unenrolling...';
                
                // Make actual API call to unenroll
                fetch('../api/unenroll_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        subject_id: subjectId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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
                            
                            showToast('success', `Successfully unenrolled from ${className}`);
                        }, 300);
                    } else {
                        showToast('error', data.message || 'Failed to unenroll from class');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while unenrolling');
                })
                .finally(() => {
                    this.disabled = false;
                    this.innerHTML = 'Yes';
                    window.currentUnenrollData = null;
                });
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
function viewAttendanceHistory(subjectId, subjectName) {
    if (!navigator.onLine) {
        showConnectionToast('error', 'No internet connection. Please check your connection and try again.');
        return;
    }
    
    if (event && event.target) {
        const button = event.target.closest('.action-btn');
        if (button && navigator.onLine) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;
            
            setTimeout(() => {
                window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subjectName)}`;
            }, 500);
        } else {
            if (navigator.onLine) {
                window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subjectName)}`;
            }
        }
    } else {
        if (navigator.onLine) {
            window.location.href = `attendance.php?subject_id=${subjectId}&subject_name=${encodeURIComponent(subjectName)}`;
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


// Show Toast (Generic function)
function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }
    
    // Create toast element dynamically
    const toast = document.createElement('div');
    toast.className = 'toast';
    
    // Create toast icon
    const toastIcon = document.createElement('div');
    toastIcon.className = 'toast-icon';
    const iconSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    iconSvg.setAttribute('width', '24');
    iconSvg.setAttribute('height', '24');
    iconSvg.setAttribute('viewBox', '0 0 24 24');
    iconSvg.setAttribute('fill', 'none');
    iconSvg.setAttribute('stroke', 'currentColor');
    iconSvg.setAttribute('stroke-width', '2');
    iconSvg.setAttribute('stroke-linecap', 'round');
    iconSvg.setAttribute('stroke-linejoin', 'round');
    
    // Create toast content
    const toastContent = document.createElement('div');
    toastContent.className = 'toast-content';
    
    const toastTitle = document.createElement('div');
    toastTitle.className = 'toast-title';
    
    const toastMessage = document.createElement('div');
    toastMessage.className = 'toast-message';
    
    // Create close button
    const toastClose = document.createElement('button');
    toastClose.className = 'toast-close';
    toastClose.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`;
    toastClose.setAttribute('aria-label', 'Close');
    
    // Add close button functionality
    toastClose.addEventListener('click', function() {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    });
    
    // Set toast based on type
    switch(type) {
        case 'success':
            toastTitle.textContent = 'Success';
            toast.className = 'toast toast-success';
            iconSvg.innerHTML = `
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            `;
            break;
        case 'error':
            toastTitle.textContent = 'Error';
            toast.className = 'toast toast-error';
            iconSvg.innerHTML = `
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            `;
            break;
        case 'info':
            toastTitle.textContent = 'Info';
            toast.className = 'toast toast-info';
            iconSvg.innerHTML = `
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            `;
            break;
        default:
            toastTitle.textContent = 'Info';
            toast.className = 'toast toast-info';
            iconSvg.innerHTML = `
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            `;
    }
    
    toastMessage.textContent = message;
    
    // Build the toast structure
    toastIcon.appendChild(iconSvg);
    toastContent.appendChild(toastTitle);
    toastContent.appendChild(toastMessage);
    toast.appendChild(toastIcon);
    toast.appendChild(toastContent);
    toast.appendChild(toastClose);
    toastContainer.appendChild(toast);
    
    // Show toast with animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Remove toast after timeout
    const timeout = type === 'error' ? 4000 : (type === 'info' ? 5000 : 3000);
    setTimeout(() => {
        toast.classList.remove('show');
        // Remove element from DOM after animation
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, timeout);
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
window.initializeSubjectsPage = initializeSubjectsPage;
