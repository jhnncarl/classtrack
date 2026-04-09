document.addEventListener('DOMContentLoaded', function() {
    initializeSubjectSelection();
    initializeFilters();
});

function initializeReportGeneration() {
    // Placeholder for report generation initialization if needed in the future
    console.log('Report generation initialized');
}

function initializeSubjectSelection() {
    const subjectSelect = document.getElementById('subjectSelect');
    const generateBtn = document.querySelector('.btn-generate-summary');
    
    if (subjectSelect && generateBtn) {
        subjectSelect.addEventListener('change', function() {
            generateBtn.disabled = !this.value;
        });
    }
}

function generateSubjectReport() {
    const subjectSelect = document.getElementById('subjectSelect');
    const subjectId = subjectSelect.value;
    
    if (!subjectId) {
        showToast('Please select a subject first', 'error');
        return;
    }
    
    clearAllToasts();
    showToast('Loading subject report...', 'info');
    
    fetch(`../api/get_subject_attendance_report.php?subject_id=${subjectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySubjectReport(data);
                clearAllToasts();
                showToast('Report loaded successfully!', 'success');
            } else {
                throw new Error(data.message || 'Failed to load report');
            }
        })
        .catch(error => {
            console.error('Error loading subject report:', error);
            clearAllToasts();
            showToast('Failed to load report. Please try again.', 'error');
        });
}

function displaySubjectReport(data) {
    const reportSection = document.getElementById('subjectReportSection');
    const reportContainer = reportSection.querySelector('.report-container');
    
    const subjectInfo = data.subject_info;
    const summary = data.summary;
    const students = data.student_stats;
    
    let html = `
        <div class="report-header">
            <div class="report-title">
                <h4>${subjectInfo.SubjectName} - Attendance Report</h4>
                <p class="text-muted">${subjectInfo.ClassName} - ${subjectInfo.SectionName} | ${subjectInfo.SubjectCode}</p>
            </div>
            <div class="report-actions">
                <button class="btn-download-pdf" onclick="downloadSubjectPDF(${subjectInfo.SubjectID})">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                </button>
            </div>
        </div>
        
        <div class="report-summary">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-value">${data.total_sessions}</div>
                    <div class="card-label">Total Sessions</div>
                </div>
                <div class="summary-card">
                    <div class="card-value">${summary.total_students}</div>
                    <div class="card-label">Total Students</div>
                </div>
                <div class="summary-card">
                    <div class="card-value">${summary.total_present}</div>
                    <div class="card-label">Total Present</div>
                </div>
                <div class="summary-card">
                    <div class="card-value">${summary.total_late}</div>
                    <div class="card-label">Total Late</div>
                </div>
                <div class="summary-card">
                    <div class="card-value">${summary.overall_attendance_rate}%</div>
                    <div class="card-label">Overall Attendance Rate</div>
                </div>
            </div>
        </div>
        
        <div class="students-table-container">
            <h5>Student Attendance Details</h5>
            <div class="students-table-wrapper">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    students.forEach(student => {
        const attendanceClass = student.AttendancePercentage >= 75 ? 'good' : 
                              student.AttendancePercentage >= 50 ? 'warning' : 'poor';
        
        html += `
            <tr>
                <td>${student.StudentNumber}</td>
                <td>${student.StudentName}</td>
                <td>${student.PresentCount}</td>
                <td>${student.LateCount}</td>
                <td>${student.AbsentCount}</td>
                <td><span class="attendance-badge ${attendanceClass}">${student.AttendancePercentage}%</span></td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    reportContainer.innerHTML = html;
    reportSection.style.display = 'block';
    
    // Smooth scroll to report
    reportSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function downloadSubjectPDF(subjectId) {
    window.location.href = `../api/generate_subject_report_pdf.php?subject_id=${subjectId}`;
}

function initializeFilters() {
    // Debug: Check if filter elements exist
    const filterSubject = document.getElementById('filterSubject');
    const filterDateFrom = document.getElementById('filterDateFrom');
    const filterDateTo = document.getElementById('filterDateTo');
    
    console.log('Filter elements found:', {
        filterSubject: !!filterSubject,
        filterDateFrom: !!filterDateFrom,
        filterDateTo: !!filterDateTo
    });
    
    // Set filter values from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    let hasUrlFilters = false;
    
    if (urlParams.get('subject') && filterSubject) {
        filterSubject.value = urlParams.get('subject');
        hasUrlFilters = true;
    }
    
    if (urlParams.get('date_from') && filterDateFrom) {
        filterDateFrom.value = urlParams.get('date_from');
        hasUrlFilters = true;
    }
    
    if (urlParams.get('date_to') && filterDateTo) {
        filterDateTo.value = urlParams.get('date_to');
        hasUrlFilters = true;
    }
    
    // Add event listeners for automatic filtering
    if (filterSubject) {
        filterSubject.addEventListener('change', function() {
            console.log('Subject filter changed');
            applyFilters();
        });
    }
    
    if (filterDateFrom) {
        filterDateFrom.addEventListener('change', function() {
            console.log('Date From filter changed');
            applyFilters();
        });
    }
    
    if (filterDateTo) {
        filterDateTo.addEventListener('change', function() {
            console.log('Date To filter changed');
            applyFilters();
        });
    }
    
    console.log('Filter event listeners added. Has URL filters:', hasUrlFilters);
    
    // If URL filters exist, apply them to show filtered data
    if (hasUrlFilters) {
        setTimeout(() => applyFilters(), 100); // Small delay to ensure DOM is ready
    }
}

function applyFilters() {
    console.log('applyFilters() called');
    
    const subject = document.getElementById('filterSubject').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    console.log('Filter values:', { subject, dateFrom, dateTo });
    
    // Send AJAX request
    const formData = new FormData();
    formData.append('action', 'filter_reports');
    if (subject) formData.append('subject', subject);
    if (dateFrom) formData.append('date_from', dateFrom);
    if (dateTo) formData.append('date_to', dateTo);
    
    console.log('Sending AJAX request...');
    
    fetch('reports.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            updateSessionsList(data.reports);
        } else {
            console.error('Filter error:', data.message || 'Failed to filter records');
        }
    })
    .catch(error => {
        console.error('Error filtering records:', error);
    });
}

function updateSessionsList(reports) {
    const sessionsList = document.querySelector('.sessions-list');
    
    if (!reports || reports.length === 0) {
        sessionsList.innerHTML = `
            <div class="empty-state-container">
                <div class="empty-state-message">
                    <div class="empty-state-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="empty-state-title">No Records Found</h3>
                    <p class="empty-state-description">No attendance records match your current filters. Try adjusting your filter criteria.</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    reports.forEach(report => {
        html += `
            <div class="session-item">
                <div class="session-info">
                    <h4 class="session-subject">${report.SubjectName}</h4>
                    <p class="session-date">${new Date(report.SessionDate).toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    })}</p>
                </div>
                <button class="btn-generate-report" onclick="generateReport(${report.SessionID})">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Session Report
                </button>
            </div>
        `;
    });
    
    sessionsList.innerHTML = html;
}

function clearFilters() {
    // Clear all filter inputs
    document.getElementById('filterSubject').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    
    // Send AJAX request to get all records
    const formData = new FormData();
    formData.append('action', 'filter_reports');
    
    fetch('reports.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateSessionsList(data.reports);
        } else {
            console.error('Clear error:', data.message || 'Failed to clear filters');
        }
    })
    .catch(error => {
        console.error('Error clearing filters:', error);
    });
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
