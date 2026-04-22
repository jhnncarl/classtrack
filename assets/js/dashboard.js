// Dashboard JavaScript - ClassTrack Student Dashboard

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Initialize current date
    updateCurrentDate();
    
    // Initialize QR section animations
    initializeQRAnimations();
    
    // Start auto-update for date/time
    setInterval(updateCurrentDate, 60000); // Update every minute
}

// Initialize QR section animations
function initializeQRAnimations() {
    // Watch for sidebar changes
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    animateQRSection(sidebar.classList.contains('expanded'));
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
        
        // Initial animation based on current state
        if (sidebar.classList.contains('expanded')) {
            animateQRSection(true);
        }
    }
}

// Animate QR section based on sidebar state
function animateQRSection(isExpanded) {
    const qrSection = document.querySelector('.qr-section');
    const qrImage = document.querySelector('.qr-code-image-large');
    
    if (qrSection && qrImage) {
        if (isExpanded) {
            // Sidebar expanded - subtle scale and slide effect
            qrSection.style.transform = 'scale(1.02) translateX(5px)';
            qrImage.style.transform = 'scale(1.05)';
            
            setTimeout(() => {
                qrSection.style.transform = 'scale(1) translateX(0)';
                qrImage.style.transform = 'scale(1)';
            }, 300);
        } else {
            // Sidebar collapsed - subtle scale effect
            qrSection.style.transform = 'scale(0.98) translateX(-5px)';
            qrImage.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                qrSection.style.transform = 'scale(1) translateX(0)';
                qrImage.style.transform = 'scale(1)';
            }, 300);
        }
    }
}

// Initialize modals directly (no dynamic loading needed)
// Modals are now included directly in the page

// Update current date
function updateCurrentDate() {
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const today = new Date();
        dateElement.textContent = today.toLocaleDateString('en-US', options);
    }
}

// View QR Code
function viewQRCode() {
    const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    modal.show();
}

// Download QR Code
function downloadQRCode() {
    // Use the actual QR code path if available, otherwise use the displayed image
    let qrImageUrl = null;
    
    // Check if we have a real QR code path from the database
    if (typeof qrCodePath !== 'undefined' && qrCodePath && qrCodePath !== '') {
        qrImageUrl = qrCodePath;
    } else {
        // Fallback to the displayed image
        const qrImage = document.querySelector('.qr-code-image-large');
        if (qrImage) {
            qrImageUrl = qrImage.src;
        }
    }
    
    if (qrImageUrl) {
        // Create a temporary link element for download
        const link = document.createElement('a');
        const filename = (typeof studentId !== 'undefined' && studentId) ? 
            `Student_${studentId}.png` : 'Student_QR.png';
        link.download = filename;
        link.href = qrImageUrl;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification('QR Code downloaded successfully!', 'success', 'Download Complete');
    } else {
        showNotification('QR Code not found', 'error', 'Not Found');
    }
}

// Print Profile
function printProfile() {
    window.print();
    showNotification('Print dialog opened', 'info', 'Print');
}

// Export Profile
function exportProfile() {
    // Get profile data from the plain text display
    const infoValues = document.querySelectorAll('.info-value');
    
    const profileData = {
        firstName: 'Demo',
        lastName: 'Student',
        studentId: '<?php echo htmlspecialchars($student_id); ?>',
        email: 'demo.student@classtrack.edu',
        course: 'Bachelor of Science in Computer Science',
        yearLevel: '3rd Year',
        contactNumber: '+63 912 345 6789',
        status: 'Active',
        address: '123 University Street, Campus City, Philippines'
    };
    
    // Create a downloadable JSON file
    const dataStr = JSON.stringify(profileData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
    
    const exportFileDefaultName = 'student_profile_' + profileData.studentId + '.json';
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
    
    showNotification('Profile exported successfully!', 'success', 'Export Complete');
}

// View Subjects
function viewSubjects() {
    showNotification('Opening subjects page...', 'info', 'Navigation');
    // In a real implementation, this would navigate to the subjects page
}

// View Attendance
function viewAttendance() {
    showNotification('Opening attendance history...', 'info', 'Navigation');
    // In a real implementation, this would navigate to the attendance page
}

// Show notification (toast)
function showNotification(message, type = 'info', title = null) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Generate default title based on type if not provided
    if (!title) {
        switch(type) {
            case 'success':
                title = 'Success';
                break;
            case 'warning':
                title = 'Warning';
                break;
            case 'danger':
                title = 'Error';
                break;
            case 'info':
            default:
                title = 'Information';
                break;
        }
    }
    
    // Create toast element with custom structure
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast toast-${type}">
            <div class="toast-icon">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : type === 'warning' ? 'bi-exclamation-triangle-fill' : type === 'danger' ? 'bi-x-circle-fill' : 'bi-info-circle-fill'}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="document.getElementById('${toastId}').remove()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Get toast element and show it
    const toastElement = document.getElementById(toastId);
    
    // Trigger show animation
    setTimeout(() => {
        toastElement.classList.add('show');
    }, 100);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toastElement.classList.remove('show');
        toastElement.classList.add('hide');
        
        // Remove element after animation
        setTimeout(() => {
            toastElement.remove();
        }, 300);
    }, 3000);
}

// Utility function to format time
function formatTime(date) {
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

// Utility function to format date
function formatDate(date) {
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}


// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Update date when page becomes visible again
        updateCurrentDate();
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
console.log('ClassTrack Student Dashboard initialized successfully!');
