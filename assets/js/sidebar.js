// Simple Sidebar Toggle - ClassTrack Student Dashboard

// Immediately restore sidebar state to prevent flicker
(function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent && window.innerWidth > 768) {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'expanded') {
            // Set state immediately before DOMContentLoaded
            sidebar.classList.add('expanded');
            mainContent.style.marginLeft = '250px';
            // Update CSS variables
            document.documentElement.style.setProperty('--sidebar-width', '250px');
            document.documentElement.style.setProperty('--main-content-margin', '250px');
        } else {
            sidebar.classList.remove('expanded');
            mainContent.style.marginLeft = '70px';
            // Update CSS variables
            document.documentElement.style.setProperty('--sidebar-width', '70px');
            document.documentElement.style.setProperty('--main-content-margin', '70px');
        }
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar directly (no dynamic loading needed)
    initializeSidebar();
});

// Initialize sidebar directly (no dynamic loading needed)
// Sidebar is now included directly in the page

function initializeSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (!menuToggle || !sidebar) {
        // If elements don't exist yet, try again
        setTimeout(initializeSidebar, 100);
        return;
    }
    
    // Restore sidebar state from localStorage
    restoreSidebarState();
    
    // Set initial margin immediately
    setInitialMargin();
    
    // Add click event to burger icon
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Setup navigation menu items
    setupNavigation();
    
    // Setup Sign Out modal confirmation button
    setupSignOutModal();
    
    // Set active menu item based on current page
    setActiveMenuBasedOnCurrentPage();
}

// Restore sidebar state from localStorage
function restoreSidebarState() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Only restore state on desktop (not mobile)
    if (window.innerWidth > 768) {
        const savedState = localStorage.getItem('sidebarState');
        const mainContent = document.querySelector('.main-content');
        
        if (savedState === 'expanded') {
            sidebar.classList.add('expanded');
            // Immediately set the correct margin to prevent flicker
            if (mainContent) {
                mainContent.style.marginLeft = '250px';
            }
            // Update CSS variables
            document.documentElement.style.setProperty('--sidebar-width', '250px');
            document.documentElement.style.setProperty('--main-content-margin', '250px');
        } else {
            sidebar.classList.remove('expanded');
            // Immediately set the correct margin to prevent flicker
            if (mainContent) {
                mainContent.style.marginLeft = '70px';
            }
            // Update CSS variables
            document.documentElement.style.setProperty('--sidebar-width', '70px');
            document.documentElement.style.setProperty('--main-content-margin', '70px');
        }
    } else {
        // On mobile, always start collapsed
        sidebar.classList.remove('expanded');
        localStorage.setItem('sidebarState', 'collapsed');
        // Reset margin for mobile
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.style.marginLeft = '0';
        }
        // Update CSS variables for mobile
        document.documentElement.style.setProperty('--sidebar-width', '0px');
        document.documentElement.style.setProperty('--main-content-margin', '0px');
    }
}

// Set active menu item based on current page URL
function setActiveMenuBasedOnCurrentPage() {
    const currentPath = window.location.pathname;
    let activePage = 'dashboard'; // default
    
    if (currentPath.includes('attendance_history.php')) {
        activePage = 'attendance-history';
    } else if (currentPath.includes('subjects.php') || currentPath.includes('attendance.php')) {
        activePage = 'subjects';
    } else if (currentPath.includes('settings.php') || currentPath.includes('settings_page')) {
        activePage = 'settings';
    } else if (currentPath.includes('dashboard.php')) {
        activePage = 'dashboard';
    }
    
    setActiveMenuItem(activePage);
}

function setInitialMargin() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar || !body || !mainContent) return;
    
    // Set correct initial margin based on screen size and sidebar state
    if (window.innerWidth <= 768) {
        // Mobile - no margin
        body.style.marginLeft = '0';
        mainContent.style.marginLeft = '0';
    } else {
        // Desktop - margin based on sidebar state
        if (sidebar.classList.contains('expanded')) {
            body.style.marginLeft = '0'; // No body margin needed
            mainContent.style.marginLeft = '250px'; // Match expanded sidebar width
        } else {
            body.style.marginLeft = '0'; // No body margin needed
            mainContent.style.marginLeft = '70px'; // Match collapsed sidebar width
        }
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    const mainContent = document.querySelector('.main-content');
    const menuToggle = document.getElementById('menuToggle');
    
    if (!sidebar || !mainContent) return;
    
    // Add bounce animation to menu toggle
    if (menuToggle) {
        menuToggle.style.animation = 'menuBounce 0.5s ease';
        setTimeout(() => {
            menuToggle.style.animation = '';
        }, 500);
    }
    
    // Toggle expanded class
    const isExpanding = !sidebar.classList.contains('expanded');
    sidebar.classList.toggle('expanded');
    
    // Save state to localStorage
    localStorage.setItem('sidebarState', isExpanding ? 'expanded' : 'collapsed');
    
    // Update CSS variables
    if (isExpanding) {
        document.documentElement.style.setProperty('--sidebar-width', '250px');
        document.documentElement.style.setProperty('--main-content-margin', '250px');
    } else {
        document.documentElement.style.setProperty('--sidebar-width', '70px');
        document.documentElement.style.setProperty('--main-content-margin', '70px');
    }
    
    // Update body margin for desktop with synchronized timing
    if (window.innerWidth > 768) {
        if (isExpanding) {
            // Expanding - set new margin
            body.style.marginLeft = '0'; // No body margin needed
            mainContent.style.marginLeft = '250px'; // Match expanded sidebar width
        } else {
            // Collapsing - set new margin
            body.style.marginLeft = '0'; // No body margin needed
            mainContent.style.marginLeft = '70px'; // Match collapsed sidebar width
        }
    }
    
    // Handle mobile overlay
    handleMobileOverlay();
}

function handleMobileOverlay() {
    const sidebar = document.getElementById('sidebar');
    const isExpanded = sidebar.classList.contains('expanded');
    const isMobile = window.innerWidth <= 768;
    
    // Create or remove overlay for mobile
    let overlay = document.querySelector('.sidebar-overlay');
    
    if (isMobile && isExpanded) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1005;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(overlay);
            
            // Add click to close
            overlay.addEventListener('click', toggleSidebar);
        }
        setTimeout(() => overlay.style.opacity = '1', 10);
    } else if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }
}

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    
    if (!sidebar) return;
    
    // Always reset margin based on current state and screen size
    setInitialMargin();
    
    // Update overlay
    handleMobileOverlay();
});

// Set Active Menu Item in Sidebar
function setActiveMenuItem(pageName) {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-page') === pageName) {
            link.classList.add('active');
        }
    });
}

// Setup Navigation Menu Items
function setupNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    
    if (sidebarLinks.length === 0) {
        // If no links found, try again after a short delay
        setTimeout(setupNavigation, 100);
        return;
    }
    
    // Remove all existing event listeners to prevent duplicates
    sidebarLinks.forEach(link => {
        const newLink = link.cloneNode(true);
        link.parentNode.replaceChild(newLink, link);
    });
    
    // Add fresh event listeners
    const freshLinks = document.querySelectorAll('.sidebar-link');
    freshLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove preventDefault to allow normal navigation
            // This will enable full page reloads as requested
            const href = this.getAttribute('href');
            
            // Only handle special cases like logout
            if (this.getAttribute('data-page') === 'logout') {
                console.log('Logout link clicked'); // Debug
                e.preventDefault();
                // Show the Sign Out confirmation modal
                const signOutModal = new bootstrap.Modal(document.getElementById('signOutModal'));
                console.log('Sign Out modal element:', document.getElementById('signOutModal')); // Debug
                signOutModal.show();
            } else if (href === '#' || !href) {
                // Handle placeholder links
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page === 'attendance') {
                    alert('Attendance History page coming soon!');
                } else if (page === 'settings') {
                    alert('Settings page coming soon!');
                }
            }
            // For all other valid href links, allow normal navigation (full page reload)
        });
    });
}

// Setup Sign Out Modal
function setupSignOutModal() {
    console.log('Setting up Sign Out Modal'); // Debug
    const confirmSignOutBtn = document.getElementById('confirmSignOutBtn');
    console.log('Confirm Sign Out button found:', confirmSignOutBtn); // Debug
    
    if (confirmSignOutBtn) {
        confirmSignOutBtn.addEventListener('click', function() {
            console.log('Sign Out button clicked'); // Debug
            // Store original button content
            const originalContent = this.innerHTML;
            const originalText = this.textContent;
            
            // Show loading state 
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing Out...';
            this.disabled = true;
            
            console.log('Starting logout process'); // Debug
            
            // Simulate logout process (replace with actual logout logic)
            setTimeout(() => {
                console.log('Closing modal and showing success'); // Debug
                // Close the modal
                const signOutModal = bootstrap.Modal.getInstance(document.getElementById('signOutModal'));
                if (signOutModal) {
                    signOutModal.hide();
                }
                
                // Show success toast notification
                showNotification('You have been successfully signed out.', 'success', 'Sign Out Successful');
                
                // Wait for toast to show before redirecting
                setTimeout(() => {
                    
                    try {
                        // Redirect to PHP logout handler
                        console.log('Redirecting to PHP logout handler'); // Debug
                        window.location.href = '/classtrack/auth/logout.php';
                        
                    } catch (error) {
                        console.error('Redirect error:', error); // Debug
                        // Fallback: try direct login page
                        window.location.href = '/classtrack/auth/login.php';
                    }
                }, 1500); // Wait 1.5 seconds for user to see the toast
                
            }, 2000); // Simulate 2 second logout process
        });
    } else {
        console.log('Sign Out button not found'); // Debug
    }
}

// Show Notification (helper function)
function showNotification(message, type = 'info', title = null) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element using custom toast.css styles
    const toastId = 'toast-' + Date.now();
    const toastTitle = title || getDefaultToastTitle(type);
    const toastHtml = `
        <div id="${toastId}" class="toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-icon">
                ${getToastIcon(type)}
            </div>
            <div class="toast-content">
                <div class="toast-title">${toastTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button type="button" class="toast-close" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Show toast with animation
    const toastElement = document.getElementById(toastId);
    setTimeout(() => {
        toastElement.classList.add('show');
    }, 10);
    
    // Setup close button
    const closeBtn = toastElement.querySelector('.toast-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            hideToast(toastElement);
        });
    }
    
    // Auto-hide after 4 seconds for success messages, 3 seconds for others
    const autoHideTime = type === 'success' ? 4000 : 3000;
    setTimeout(() => {
        hideToast(toastElement);
    }, autoHideTime);
}

// Helper function to get default toast title based on type
function getDefaultToastTitle(type) {
    switch(type) {
        case 'success':
            return 'Success';
        case 'error':
            return 'Error';
        case 'warning':
            return 'Warning';
        case 'info':
        default:
            return 'Information';
    }
}

// Helper function to get toast icon based on type
function getToastIcon(type) {
    switch(type) {
        case 'success':
            return '<i class="bi bi-check-lg"></i>';
        case 'error':
            return '<i class="bi bi-x-lg"></i>';
        case 'warning':
            return '<i class="bi bi-exclamation-lg"></i>';
        case 'info':
        default:
            return '<i class="bi bi-info-lg"></i>';
    }
}

// Helper function to hide toast
function hideToast(toastElement) {
    if (toastElement && toastElement.parentNode) {
        toastElement.classList.remove('show');
        toastElement.classList.add('hide');
        setTimeout(() => {
            if (toastElement.parentNode) {
                toastElement.remove();
            }
        }, 300);
    }
}
