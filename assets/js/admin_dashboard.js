/**
 * ClassTrack Admin Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Dynamic greeting logic for admin dashboard
    updateAdminGreeting();
    
    // Start real-time clock
    updateRealTimeClock();
    setInterval(updateRealTimeClock, 1000); // Update every second
});

// Dynamic greeting function for admin dashboard
function updateAdminGreeting() {
    const greetingElement = document.querySelector('.welcome-title');
    if (!greetingElement) return;

    // Get admin visit count from localStorage
    const visitKey = 'classtrack_admin_visit_count';
    let visitCount = localStorage.getItem(visitKey);
    
    if (visitCount === null) {
        // First time visiting admin dashboard
        localStorage.setItem(visitKey, '1');
        // Will show "Welcome" (first time)
    } else {
        // Returning admin user
        const newCount = parseInt(visitCount) + 1;
        localStorage.setItem(visitKey, newCount.toString());
        
        // Update greeting if returning user (2+ visits)
        if (newCount >= 2) {
            const adminUsername = greetingElement.textContent.match(/,\s*([^!]+)!/);
            if (adminUsername) {
                greetingElement.textContent = `Welcome back, ${adminUsername[1]}! 👋`;
            }
        }
    }
}

// Real-time clock function
function updateRealTimeClock() {
    const timeDisplay = document.querySelector('.time-display');
    const dateDisplay = document.querySelector('.date-display');
    
    if (timeDisplay && dateDisplay) {
        const now = new Date();
        
        // Format time (e.g., "10:41 AM")
        const timeOptions = { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        };
        const timeString = now.toLocaleTimeString('en-US', timeOptions);
        
        // Format date (e.g., "Tuesday, April 15, 2026")
        const dateOptions = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        
        // Update the display
        timeDisplay.textContent = timeString;
        dateDisplay.textContent = dateString;
    }
}

// Quick action functions
function showCreateUserModal() {
    showToast('info', 'User creation feature coming soon!');
}

function showCreateClassModal() {
    showToast('info', 'Class creation feature coming soon!');
}

function generateReports() {
    showToast('info', 'Reports generation feature coming soon!');
}

function showSystemSettings() {
    showToast('info', 'System settings feature coming soon!');
}

// Toast notification function
function showToast(type, message) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}
