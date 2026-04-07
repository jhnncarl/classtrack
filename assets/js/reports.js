document.addEventListener('DOMContentLoaded', function() {
    initializeAutoFilter();
});

function applyFilters() {
    const subjectFilter = document.getElementById('subjectFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    const subject = subjectFilter ? subjectFilter.value : '';
    const month = monthFilter ? monthFilter.value : '';
    
    const sessionItems = document.querySelectorAll('.session-item');
    let visibleCount = 0;
    
    sessionItems.forEach(item => {
        let showItem = true;
        const subjectName = item.querySelector('.session-subject').textContent.toLowerCase();
        const dateText = item.querySelector('.session-date').textContent;
        
        if (subject && subject !== 'all') {
            const selectedOption = document.querySelector(`#subjectFilter option[value="${subject}"]`);
            if (selectedOption) {
                const selectedSubjectText = selectedOption.textContent.toLowerCase();
                const selectedSubjectName = selectedSubjectText.split(' - ')[0].trim();
                
                if (!subjectName.includes(selectedSubjectName)) {
                    showItem = false;
                }
            }
        }
        
        if (month && month !== 'all' && showItem) {
            const itemDate = new Date(dateText);
            const itemMonth = itemDate.toISOString().slice(0, 7);
            if (itemMonth !== month) {
                showItem = false;
            }
        }
        
        item.style.display = showItem ? 'flex' : 'none';
        if (showItem) visibleCount++;
    });
    
    // Show/hide empty state message
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function clearFilters() {
    const subjectFilter = document.getElementById('subjectFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    if (subjectFilter) {
        subjectFilter.value = 'all';
    }
    
    if (monthFilter) {
        monthFilter.value = 'all';
    }
    
    const sessionItems = document.querySelectorAll('.session-item');
    sessionItems.forEach(item => {
        item.style.display = 'flex';
    });
}

function initializeAutoFilter() {
    const subjectFilter = document.getElementById('subjectFilter');
    const monthFilter = document.getElementById('monthFilter');
    
    if (subjectFilter) {
        subjectFilter.addEventListener('change', applyFilters);
    }
    
    if (monthFilter) {
        monthFilter.addEventListener('change', applyFilters);
    }
}

function generateReport(sessionId) {
    clearAllToasts();
    showToast('Preparing your report...', 'info');
    
    console.log('Generating report for session:', sessionId);
    
    fetch(`../api/generate_report_pdf.php?session_id=${sessionId}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `attendance_report_${sessionId}_${new Date().toISOString().split('T')[0]}.pdf`;
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (filenameMatch && filenameMatch[1]) {
                    filename = filenameMatch[1].replace(/['"]/g, '');
                }
            }
            
            return response.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            console.log('Blob size:', blob.size);
            console.log('Blob type:', blob.type);
            console.log('Filename:', filename);
            
            if (blob.size === 0) {
                throw new Error('Received empty PDF file');
            }
            
            clearAllToasts();
            showToast('Downloading PDF...', 'info');
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            setTimeout(() => {
                clearAllToasts();
                showToast('PDF report downloaded successfully!', 'success');
            }, 800);
        })
        .catch(error => {
            console.error('Error generating report:', error);
            clearAllToasts();
            showToast('Failed to generate report. Please try again.', 'error');
        });
}

function clearAllToasts() {
    const toastContainer = document.getElementById('toast-container');
    if (toastContainer) {
        toastContainer.innerHTML = '';
    }
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        console.error('Toast container not found!');
        return;
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let iconSvg;
    if (type === 'success') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
    } else if (type === 'info') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
    } else if (type === 'error') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    } else {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
    }
    
    toast.innerHTML = `
        <div class="toast-icon">
            ${iconSvg}
        </div>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : type === 'info' ? 'Info' : type === 'error' ? 'Error' : 'Warning'}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    `;
    
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        toast.classList.add('hide');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    });
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 4000);
}
