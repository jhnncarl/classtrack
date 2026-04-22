// Teacher Dashboard JavaScript - ClassTrack Teacher Dashboard

class TeacherDashboard {
    constructor() {
        this.isUpdating = false;
        this.lastUpdate = null;
        this.init();
    }

    init() {
        console.log('🚀 Initializing Teacher Dashboard...');
        console.log('🚀 Current URL:', window.location.href);
        
        this.setupEventListeners();
        
        console.log('🚀 Teacher Dashboard initialization completed');
    }

    // Main update function - simplified for navbar auto-reload
    async updateDashboard() {
        if (this.isUpdating) {
            console.log('⏭️ Update already in progress, skipping...');
            return;
        }

        this.isUpdating = true;
        console.log('🔄 Starting dashboard update at:', new Date().toLocaleTimeString());

        try {
            // Update subjects/classes only
            console.log('📚 Updating subjects...');
            await this.updateSubjects();
            console.log('✅ Subjects updated successfully');
            
            this.lastUpdate = new Date();
            console.log('🎉 Dashboard updated successfully at:', this.lastUpdate.toLocaleTimeString());
            
        } catch (error) {
            console.error('❌ Error updating dashboard:', error);
            console.error('❌ Error details:', {
                message: error.message,
                stack: error.stack,
                timestamp: new Date().toISOString()
            });
            this.showError('Failed to update dashboard. Please refresh the page.');
        } finally {
            this.isUpdating = false;
            console.log('🏁 Update cycle completed, isUpdating set to false');
        }
    }

    // Update subjects/classes
    async updateSubjects() {
        try {
            console.log('📡 Making API call to ../api/get_teacher_subjects.php');
            const response = await fetch('../api/get_teacher_subjects.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('📡 API Response status:', response.status, response.statusText);
            console.log('📡 API Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('📡 API Response data:', data);
            
            if (data.success) {
                console.log('📡 API call successful, rendering subjects...');
                console.log('📡 Subjects data:', data.subjects);
                this.renderSubjects(data.subjects);
                console.log('📡 Subjects rendered successfully');
            } else {
                console.error('📡 API returned error:', data.message);
                throw new Error(data.message || 'Failed to fetch subjects');
            }
        } catch (error) {
            console.error('❌ Error in updateSubjects:', error);
            console.error('❌ Error details:', {
                message: error.message,
                stack: error.stack,
                timestamp: new Date().toISOString()
            });
            throw error;
        }
    }

    // Render subjects in the dashboard
    renderSubjects(subjects) {
        console.log('🎨 renderSubjects called with:', subjects);
        console.log('🎨 Number of subjects:', subjects.length);
        
        const classGrid = document.querySelector('.class-grid');
        console.log('🎨 Class grid element found:', !!classGrid);
        
        if (!classGrid) {
            console.error('❌ Class grid element not found!');
            return;
        }

        if (subjects.length === 0) {
            console.log('🎨 Rendering empty state');
            // Show empty state
            classGrid.innerHTML = `
                <div class="empty-state-container">
                    <div class="empty-state-message">
                        <div class="empty-state-icon">
                            <i class="bi bi-book"></i>
                        </div>
                        <h3 class="empty-state-title">No Classes Found</h3>
                        <p class="empty-state-description">You haven't created any classes yet. Click the ➕ (plus icon) in the navigation bar and select 'Create Class' to get started.</p>
                    </div>
                </div>
            `;
            console.log('🎨 Empty state rendered');
            return;
        }

        console.log('🎨 Rendering subject cards...');
        // Generate HTML for subjects
        const colorClasses = ['blue', 'green', 'orange', 'purple', 'red', 'teal'];
        let subjectsHTML = '';

        subjects.forEach((subject, index) => {
            console.log('🎨 Processing subject:', subject);
            const colorClass = colorClasses[index % colorClasses.length];
            subjectsHTML += `
                <div class="class-card" data-subject-id="${subject.SubjectID}">
                    <div class="card-header ${colorClass}">
                        <a href="class_view.php?class=${encodeURIComponent(subject.SubjectCode)}" class="class-title-link">
                            <h3 class="class-title">${this.escapeHtml(subject.SubjectName)}</h3>
                        </a>
                        <p class="class-section">${this.escapeHtml(subject.SectionName)}</p>
                    </div>
                    <div class="card-body">
                        <!-- Card body content -->
                    </div>
                </div>
            `;
        });

        console.log('🎨 Generated HTML length:', subjectsHTML.length);
        console.log('🎨 Setting innerHTML...');
        classGrid.innerHTML = subjectsHTML;
        console.log('🎨 innerHTML set successfully');
        
        // Add fade-in animation to new cards
        console.log('🎨 Adding animations...');
        this.animateNewCards();
        console.log('🎨 renderSubjects completed');
    }

    
    // Animate new cards
    animateNewCards() {
        const cards = document.querySelectorAll('.class-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Setup event listeners
    setupEventListeners() {
        // Manual refresh button (if exists)
        const refreshBtn = document.querySelector('.refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.updateDashboard();
            });
        }

        // Listen for custom update events (for navbar auto-reload)
        document.addEventListener('dashboard:update', () => {
            this.updateDashboard();
        });

        // Listen for subject creation/update
        document.addEventListener('subject:changed', () => {
            this.updateSubjects();
        });
    }

    // Show error message
    showError(message) {
        // Use toast notification if available
        if (typeof showToast === 'function') {
            showToast(message, 'error');
        } else {
            // Fallback to alert
            console.error(message);
        }
    }

    // Show success message
    showSuccess(message) {
        if (typeof showToast === 'function') {
            showToast(message, 'success');
        } else {
            console.log(message);
        }
    }

    // Utility function to escape HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Get update status
    getUpdateStatus() {
        return {
            isUpdating: this.isUpdating,
            lastUpdate: this.lastUpdate
        };
    }

    // Destroy dashboard instance
    destroy() {
        console.log('Teacher Dashboard destroyed');
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('📱 DOM Content Loaded - initializing dashboard...');
    
    // Create global dashboard instance
    window.teacherDashboard = new TeacherDashboard();
    
    // Make it available globally for navbar auto-reload
    window.updateDashboard = () => {
        console.log('🔄 Manual update triggered from global function');
        if (window.teacherDashboard) {
            window.teacherDashboard.updateDashboard();
        } else {
            console.error('❌ Teacher dashboard instance not found!');
        }
    };
    
    console.log('✅ Dashboard instance created and global function registered');
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (window.teacherDashboard) {
        window.teacherDashboard.destroy();
    }
});
