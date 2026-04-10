// Attendance tracking variables
let presentCount = 0;
let absentCount = 0;
let lateCount = 0;
let totalStudents = 0;

// Grace period settings
let gracePeriod = 15; // Default 15 minutes
let autoLateEnabled = true;

// Attendance Session JavaScript - ClassTrack Teacher Dashboard

let sessionTimer = null;
let sessionSeconds = 0;
let isPaused = false;

// Navigation functions
function goBack() {
    // Show confirmation modal instead of alert
    const modal = new bootstrap.Modal(document.getElementById('confirmLeaveModal'));
    modal.show();
}

// Handle confirmation button click
document.getElementById('confirmLeaveBtn').addEventListener('click', function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmLeaveModal'));
    modal.hide();
    
    // Close session before leaving
    closeSession();
    
    // Redirect after a short delay to ensure session closure is processed
    setTimeout(() => {
        window.location.href = 'class_view.php?class=' + encodeURIComponent(classCode);
    }, 500);
});

// Timer functions
function startTimer() {
    console.log('=== startTimer called ===');
    console.log('Current sessionId:', sessionId);
    
    // Clean up any existing camera stream before starting timer (only if camera is active)
    try {
        const videoElement = document.getElementById('videoElement');
        if (videoElement && videoElement.srcObject) {
            const stream = videoElement.srcObject;
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            videoElement.srcObject = null;
            console.log('Camera stream cleaned up on timer start');
        }
    } catch (error) {
        console.log('Camera cleanup skipped:', error.message);
    }
    
    // Restore timer state from sessionStorage if exists
    const savedStartTime = sessionStorage.getItem('sessionStartTime');
    const savedSessionId = sessionStorage.getItem('currentSessionId');
    
    console.log('SessionStorage data:');
    console.log('- savedStartTime:', savedStartTime);
    console.log('- savedSessionId:', savedSessionId);
    console.log('- current sessionId:', sessionId);
    console.log('- sessionId comparison:', savedSessionId == sessionId);
    
    if (savedStartTime && savedSessionId == sessionId) {
        // Calculate elapsed time since session started
        const startTime = parseInt(savedStartTime);
        const currentTime = Date.now();
        const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
        
        console.log('Timer calculation:');
        console.log('- startTime:', startTime);
        console.log('- currentTime:', currentTime);
        console.log('- elapsedSeconds:', elapsedSeconds);
        
        // Set timer to elapsed time
        sessionSeconds = elapsedSeconds;
        updateTimerDisplay();
        
        console.log('Timer restored from sessionStorage:', 
    String(Math.floor(elapsedSeconds / 3600)).padStart(2, '0') + ':' +
    String(Math.floor((elapsedSeconds % 3600) / 60)).padStart(2, '0') + ':' +
    String(elapsedSeconds % 60).padStart(2, '0')
);
    } else {
        // Save new session start time
        sessionStorage.setItem('sessionStartTime', Date.now().toString());
        sessionStorage.setItem('currentSessionId', sessionId);
        console.log('New session timer started');
        console.log('- Saved startTime:', Date.now().toString());
        console.log('- Saved sessionId:', sessionId);
    }
    
    sessionTimer = setInterval(() => {
        if (!isPaused) {
            sessionSeconds++;
            updateTimerDisplay();
            // Save current time periodically
            if (sessionSeconds % 10 === 0) { // Save every 10 seconds
                sessionStorage.setItem('sessionStartTime', (Date.now() - (sessionSeconds * 1000)).toString());
            }
        }
    }, 1000);
    
    console.log('Timer started, current sessionSeconds:', sessionSeconds);
}

function updateTimerDisplay() {
    const hours = Math.floor(sessionSeconds / 3600);
    const minutes = Math.floor((sessionSeconds % 3600) / 60);
    const seconds = sessionSeconds % 60;
    
    const display = 
        String(hours).padStart(2, '0') + ':' +
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');
    
    document.getElementById('sessionTimer').textContent = display;
}

// Camera functions
function enableCamera() {
    const videoElement = document.getElementById('videoElement');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const enableBtn = document.getElementById('enableCameraBtn');
    const scannerStatus = document.getElementById('scannerStatus');
    
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                videoElement.srcObject = stream;
                videoElement.style.display = 'block';
                cameraPlaceholder.style.display = 'none';
                scannerOverlay.style.display = 'block';
                enableBtn.innerHTML = '<i class="bi bi-camera-video-off"></i> Disable Camera';
                enableBtn.onclick = disableCamera;
                scannerStatus.textContent = 'Camera active - Ready for scanning';
                showToast('Camera enabled successfully', 'success');
                
                // Ensure video plays with proper error handling
                videoElement.play().catch(function(error) {
                    if (error.name === 'AbortError') {
                        // Ignore AbortError as it's expected when stream changes
                        console.log('Video play interrupted - this is normal');
                    } else {
                        console.error('Video play failed:', error);
                    }
                });
                
                // Start QR code scanning
                startQRScanning(stream);
            })
            .catch(function(error) {
                console.error('Camera access denied:', error);
                showToast('Camera access denied. Please allow camera permissions.', 'error');
                scannerStatus.textContent = 'Camera access denied';
            });
    } else {
        showToast('Camera not supported on this device', 'error');
        scannerStatus.textContent = 'Camera not supported';
    }
}

function stopCamera() {
    const videoElement = document.getElementById('videoElement');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const enableBtn = document.getElementById('enableCameraBtn');
    
    // Stop camera stream
    if (videoElement.srcObject) {
        const stream = videoElement.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
        videoElement.srcObject = null;
        console.log('Camera stream stopped');
    }
    
    videoElement.style.display = 'none';
    cameraPlaceholder.style.display = 'flex';
    scannerOverlay.style.display = 'none';
    enableBtn.innerHTML = '<i class="bi bi-camera-video"></i> Enable Camera';
    enableBtn.onclick = enableCamera;
}

function disableCamera() {
    stopCamera();
    const scannerStatus = document.getElementById('scannerStatus');
    scannerStatus.textContent = 'Camera ready / waiting for scan';
    showToast('Camera disabled', 'info');
    
    // Stop QR code scanning
    stopQRScanning();
}

let qrScanner = null;
let scanInterval = null;
let isScanning = false;
let lastErrorSoundTime = 0;
let lastErrorNotificationTime = 0;
const ERROR_SOUND_COOLDOWN = 2000; // 2 seconds cooldown for sound
const ERROR_NOTIFICATION_COOLDOWN = 2000; // 2 seconds cooldown for notifications

// Audio context for sound generation
let audioContext = null;

// Initialize audio context
function initAudioContext() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
}

// Generate error sound
function playErrorSound() {
    console.log('playErrorSound called');
    const currentTime = Date.now();
    const timeSinceLastSound = currentTime - lastErrorSoundTime;
    
    if (timeSinceLastSound < ERROR_SOUND_COOLDOWN) {
        console.log('Error sound blocked by cooldown');
        return; // Prevent rapid triggering
    }
    
    lastErrorSoundTime = currentTime;
    initAudioContext();
    
    if (!audioContext) return;
    
    // Create oscillator for error tone
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    // Configure error sound (low frequency buzz)
    oscillator.type = 'sawtooth';
    oscillator.frequency.setValueAtTime(200, audioContext.currentTime); // Low frequency
    oscillator.frequency.setValueAtTime(150, audioContext.currentTime + 0.1); // Drop frequency
    
    // Configure volume envelope
    gainNode.gain.setValueAtTime(0, audioContext.currentTime);
    gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01); // Quick attack
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3); // Quick decay
    
    // Play sound
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.3);
}

// Generate success sound
function playSuccessSound() {
    console.log('playSuccessSound called');
    initAudioContext();
    
    if (!audioContext) return;
    
    // Create shining confirmation sound with harmonics
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    // Configure shining bright sound
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(1200, audioContext.currentTime); // Bright, shining frequency
    
    // Create sparkling envelope
    gainNode.gain.setValueAtTime(0, audioContext.currentTime);
    gainNode.gain.linearRampToValueAtTime(0.6, audioContext.currentTime + 0.005); // Very quick attack
    gainNode.gain.linearRampToValueAtTime(0.4, audioContext.currentTime + 0.05); // Slight dip for sparkle
    gainNode.gain.linearRampToValueAtTime(0.01, audioContext.currentTime + 0.25); // Smooth decay
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.3);
}

// Error toast notification
function showErrorToast() {
    const currentTime = Date.now();
    const timeSinceLastError = currentTime - lastErrorNotificationTime;
    
    // Check notification cooldown to prevent duplicates
    if (timeSinceLastError < ERROR_NOTIFICATION_COOLDOWN) {
        return; // Skip if notification was shown recently
    }
    
    lastErrorNotificationTime = currentTime;
    
    // Show error message
    showToast('Invalid QR code - Student not enrolled in this class', 'error');
}

function startQRScanning(stream) {
    const videoElement = document.getElementById('videoElement');
    
    isScanning = true;
    
    // Create canvas for QR code detection
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    scanInterval = setInterval(() => {
        if (!isScanning || videoElement.paused || videoElement.ended) return;
        
        // Set canvas dimensions to match video
        if (videoElement.videoWidth && videoElement.videoHeight) {
            canvas.width = videoElement.videoWidth;
            canvas.height = videoElement.videoHeight;
            
            // Draw video frame to canvas
            context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            // Get image data for QR detection
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            // Detect QR code
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });
            
            if (code) {
                // QR code detected - update frame position and size
                updateScannerFrame(code, canvas.width, canvas.height);
                processQRCode(code);
            } else {
                // No QR code detected - reset to center
                resetScannerFrame();
            }
        }
    }, 100); // Scan every 100ms
}

function updateScannerFrame(code, videoWidth, videoHeight) {
    const scannerFrame = document.querySelector('.scanner-frame');
    
    // Calculate relative position and size
    const relativeX = (code.location.topLeftCorner.x / videoWidth) * 100;
    const relativeY = (code.location.topLeftCorner.y / videoHeight) * 100;
    const relativeWidth = (code.location.width / videoWidth) * 100;
    const relativeHeight = (code.location.height / videoHeight) * 100;
    
    // Apply dynamic positioning
    scannerFrame.style.left = `${relativeX + relativeWidth/2}%`;
    scannerFrame.style.top = `${relativeY + relativeHeight/2}%`;
    scannerFrame.style.width = `${relativeWidth}%`;
    scannerFrame.style.height = `${relativeHeight}%`;
    scannerFrame.style.maxWidth = 'none';
    scannerFrame.style.aspectRatio = 'auto';
}

function resetScannerFrame() {
    const scannerFrame = document.querySelector('.scanner-frame');
    
    // Reset to center position
    scannerFrame.style.left = '50%';
    scannerFrame.style.top = '50%';
    scannerFrame.style.width = '70%';
    scannerFrame.style.height = 'auto';
    scannerFrame.style.maxWidth = '250px';
    scannerFrame.style.aspectRatio = '1';
}

function processQRCode(code) {
    // Prevent duplicate processing
    if (qrScanner === code.data) return;
    
    qrScanner = code.data;
    
    // Validate QR code format
    const isValidQRCode = validateQRCode(code.data);
    
    if (isValidQRCode) {
        // Valid QR code format - process attendance
        console.log('Valid QR Code detected:', code.data);
        simulateStudentCheckIn();
        
        // Reset after processing
        setTimeout(() => {
            qrScanner = null;
            resetStudentInfo();
        }, 5000);
    } else {
        // Invalid QR code format - play error sound and show toast
        console.log('Invalid QR Code detected:', code.data);
        playErrorSound();
        showErrorToast();
        
        // Reset after cooldown
        setTimeout(() => {
            qrScanner = null;
        }, 1500);
    }
}

// Validate QR code (replace with actual validation logic)
function validateQRCode(qrData) {
    try {
        // Parse JSON data from QR code
        const studentData = JSON.parse(qrData);
        
        // Check if QR data contains required fields
        if (studentData && studentData.student_number) {
            // Store student data for later use
            window.currentStudentData = studentData;
            return true; // Always return true, validation will be done server-side
        }
        
        return false;
    } catch (error) {
        // Invalid JSON format
        console.log('Invalid QR format:', error);
        return false;
    }
}

function stopQRScanning() {
    isScanning = false;
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    
    resetScannerFrame();
}

function pauseSession() {
    isPaused = !isPaused;
    const pauseBtn = document.querySelector('.btn-control.pause');
    const statusIndicator = document.querySelector('.status-indicator');
    
    // Update session status in database
    updateSessionStatus(isPaused ? 'Paused' : 'Active');
    
    if (isPaused) {
        pauseBtn.innerHTML = '<i class="bi bi-play-circle"></i> Resume Session';
        statusIndicator.classList.remove('active');
        statusIndicator.classList.add('paused');
        document.querySelector('.status-text').textContent = 'Session Paused';
        showToast('Session paused', 'info');
    } else {
        pauseBtn.innerHTML = '<i class="bi bi-pause-circle"></i> Pause Session';
        statusIndicator.classList.remove('paused');
        statusIndicator.classList.add('active');
        document.querySelector('.status-text').textContent = 'Session Active';
        showToast('Session resumed', 'info');
    }
}

function endSession() {
    // Show confirmation modal instead of browser confirm
    const modal = new bootstrap.Modal(document.getElementById('confirmEndSessionModal'));
    modal.show();
}

// Handle end session confirmation
document.getElementById('confirmEndSessionBtn').addEventListener('click', function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmEndSessionModal'));
    modal.hide();
    
    // Close session in database
    closeSession();
    
    clearInterval(sessionTimer);
    showToast('Session ended successfully', 'success');
    
    // Redirect after a short delay to ensure session closure is processed
    setTimeout(() => {
        window.location.href = 'class_view.php?class=' + encodeURIComponent(classCode);
    }, 1000);
});

// Simulate student check-ins
function simulateStudentCheckIn() {
    // Use the stored student data from QR scan
    const studentData = window.currentStudentData;
    
    if (!studentData || !studentData.student_number) {
        console.error('No student data available');
        return;
    }
    
    // Validate student and get information from server
    validateStudentWithServer(studentData.student_number);
}

// Validate student with server and get student information
function validateStudentWithServer(studentNumber) {
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'validate_student',
            student_number: studentNumber,
            subject_id: subjectId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Student is valid - record attendance in database
            recordAttendance(data.student.id, data.student);
        } else {
            // Student not found or not enrolled - play error sound
            playErrorSound();
            console.error('Student validation failed:', data.message);
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        // Network or server error - play error sound
        playErrorSound();
        console.error('Error validating student:', error);
        showToast('Error validating student', 'error');
    });
}

// Record attendance in database
function recordAttendance(studentId, studentData = null) {
    // Calculate attendance status based on grace period
    let attendanceStatus = 'Present';
    
    if (autoLateEnabled) {
        // Get session start time from sessionStorage
        const sessionStartTime = sessionStorage.getItem('sessionStartTime');
        if (sessionStartTime) {
            const currentTime = Date.now();
            const sessionStart = parseInt(sessionStartTime);
            const elapsedMinutes = Math.floor((currentTime - sessionStart) / 60000);
            
            console.log('Late detection calculation:');
            console.log('- Session start time:', new Date(sessionStart).toLocaleTimeString());
            console.log('- Current time:', new Date(currentTime).toLocaleTimeString());
            console.log('- Elapsed minutes:', elapsedMinutes);
            console.log('- Grace period:', gracePeriod);
            
            if (elapsedMinutes > gracePeriod) {
                attendanceStatus = 'Late';
                console.log('Student marked as LATE');
            } else {
                console.log('Student marked as PRESENT (within grace period)');
            }
        }
    }
    
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'record_attendance',
            session_id: sessionId,
            student_id: studentId,
            attendance_status: attendanceStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Record attendance response:', data);
        if (data.success) {
            // Attendance recorded successfully - play success sound, show toast, and update UI
            console.log('Playing success sound for successful recording');
            playSuccessSound();
            showToast('Attendance recorded successfully', 'success');
            
            // Update attendance counts based on status
            if (attendanceStatus === 'Late') {
                markLate();
            } else {
                markPresent();
            }
            
            // Update student info with attendance status
            if (studentData) {
                updateStudentInfo({
                    name: studentData.name,
                    id: studentData.student_number,
                    course: studentData.course,
                    year: studentData.year,
                    email: studentData.email,
                    profile_picture: studentData.profile_picture,
                    attendance_status: attendanceStatus
                });
            }
            
            console.log('Attendance recorded with ID:', data.record_id);
        } else {
            // Failed to record attendance - play error sound
            console.log('Playing error sound for failed recording:', data.message);
            playErrorSound();
            console.error('Failed to record attendance:', data.message);
            
            // Check if this is a duplicate scan and show as info instead of error
            if (data.message.includes('already recorded')) {
                showToast(data.message, 'info');
            } else {
                showToast(data.message, 'error');
            }
            
            // Reset student info since attendance wasn't recorded
            setTimeout(() => {
                resetStudentInfo();
            }, 2000);
        }
    })
    .catch(error => {
        // Network or server error - play error sound
        playErrorSound();
        console.error('Error recording attendance:', error);
        showToast('Error recording attendance', 'error');
        // Reset student info since attendance wasn't recorded
        setTimeout(() => {
            resetStudentInfo();
        }, 2000);
    });
}

function updateStudentInfo(student) {
    console.log('updateStudentInfo called with:', student);
    const studentInfoContent = document.getElementById('studentInfoContent');
    const studentStatusBadge = document.getElementById('studentStatusBadge');
    
    // Status badge with late detection
    const status = student.attendance_status || 'Present';
    let statusIcon, statusClass;
    
    if (status === 'Late') {
        statusIcon = 'bi-clock-history';
        statusClass = 'student-status-badge late';
    } else {
        statusIcon = 'bi-person-check';
        statusClass = 'student-status-badge present';
    }
    
    studentStatusBadge.className = statusClass;
    studentStatusBadge.innerHTML = `
        <i class="bi ${statusIcon}"></i>
        <span>${status}</span>
    `;
    
    // Student info content
    studentInfoContent.innerHTML = `
        <div class="student-details">
            <div class="student-avatar-section">
                <div class="student-avatar">
                    ${student.profile_picture 
                        ? `<img src="../${student.profile_picture}" alt="${student.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">` 
                        : '<i class="bi bi-person-circle"></i>'
                    }
                </div>
                <div class="student-basic-info">
                    <h4>${student.name}</h4>
                    <div class="student-id">ID: ${student.id}</div>
                </div>
            </div>
            <div class="student-meta">
                <div class="meta-item">
                    <span class="meta-label">Status</span>
                    <span class="meta-value ${status === 'Late' ? 'status-late' : 'status-present'}">${status}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Course</span>
                    <span class="meta-value">${student.course}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Year</span>
                    <span class="meta-value">${student.year}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Email</span>
                    <span class="meta-value">${student.email}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Time</span>
                    <span class="meta-value">${new Date().toLocaleTimeString()}</span>
                </div>
            </div>
        </div>
    `;
    
    console.log('Student info updated, attendance_status:', student.attendance_status);
}


function resetStudentInfo() {
    const studentInfoContent = document.getElementById('studentInfoContent');
    const studentStatusBadge = document.getElementById('studentStatusBadge');
    
    // Reset status badge
    studentStatusBadge.className = 'student-status-badge';
    studentStatusBadge.innerHTML = `
        <i class="bi bi-person"></i>
        <span>Waiting for scan</span>
    `;
    
    // Reset student info content
    studentInfoContent.innerHTML = `
        <div class="no-student-placeholder">
            <i class="bi bi-qr-code-scan"></i>
            <p>Student information will appear here</p>
            <small>After QR code is scanned</small>
        </div>
    `;
}


// Toast notification function
function showToast(message, type = 'success') {
    console.log('showToast called with message:', message, 'type:', type);
    
    const toastContainer = document.getElementById('toast-container');
    console.log('Toast container found:', !!toastContainer);
    
    if (!toastContainer) {
        console.error('Toast container not found!');
        return;
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    console.log('Created toast element with class:', toast.className);
    
    // Create icon based on type
    let iconSvg, toastTitle;
    
    if (type === 'success') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        toastTitle = 'Success';
    } else if (type === 'info') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        toastTitle = 'Info';
    } else {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        toastTitle = 'Error';
    }
    
    toast.innerHTML = `
        <div class="toast-icon">
            ${iconSvg}
        </div>
        <div class="toast-content">
            <div class="toast-title">${toastTitle}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    `;
    
    console.log('Toast inner HTML set');
    
    // Add to container
    toastContainer.appendChild(toast);
    console.log('Toast added to container');
    
    // Add close functionality
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        toast.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
    
    // Show animation
    setTimeout(() => {
        toast.classList.add('show');
        console.log('Toast show class added');
    }, 100);
}

// Initialize session
document.addEventListener('DOMContentLoaded', function() {
    loadGracePeriodSettings(); // Load grace period settings first
    startTimer();
    initializeAttendanceCounts();
    setupSessionCleanup();
});

// Setup automatic session cleanup
function setupSessionCleanup() {
    // Don't close session automatically - only close when user explicitly ends session
    // This prevents session closure on browser refresh/tab switching
    
    console.log('Session cleanup setup complete - sessions will only close when explicitly ended by user');
}

// Track session state
let isSessionClosed = false;
let sessionHiddenTime = null;

// Initialize attendance counts
function initializeAttendanceCounts() {
    totalStudents = parseInt(document.getElementById('lateCount').textContent);
    
    // Restore attendance counts from sessionStorage if exists
    const savedSessionId = sessionStorage.getItem('currentSessionId');
    const savedCounts = sessionStorage.getItem('attendanceCounts');
    
    if (savedCounts && savedSessionId == sessionId) {
        const counts = JSON.parse(savedCounts);
        presentCount = counts.present || 0;
        absentCount = counts.absent || 0;
        lateCount = counts.late || 0;
        
        console.log('Attendance counts restored:', { presentCount, absentCount, lateCount });
    } else {
        presentCount = 0;
        absentCount = 0;
        lateCount = 0;
        
        console.log('New attendance counts initialized');
    }
    
    updateAttendanceDisplay();
}

// Save attendance counts to sessionStorage
function saveAttendanceCounts() {
    const counts = {
        present: presentCount,
        absent: absentCount,
        late: lateCount
    };
    sessionStorage.setItem('attendanceCounts', JSON.stringify(counts));
}

// Update attendance display
function updateAttendanceDisplay() {
    document.getElementById('presentCount').textContent = presentCount;
    document.getElementById('absentCount').textContent = absentCount;
    document.getElementById('lateCount').textContent = lateCount;
}

// Mark student as present
function markPresent() {
    if (lateCount > 0) {
        lateCount--;
    } else if (absentCount > 0) {
        absentCount--;
    }
    presentCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
}

// Mark student as late
function markLate() {
    if (presentCount > 0) {
        presentCount--;
    } else if (absentCount > 0) {
        absentCount--;
    }
    lateCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
}

// Mark student as absent
function markAbsent() {
    if (presentCount > 0) {
        presentCount--;
    } else if (lateCount > 0) {
        lateCount--;
    }
    absentCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
}

// Session management functions
function updateSessionStatus(status) {
    if (!sessionId) return;
    
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_status',
            session_id: sessionId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to update session status:', data.message);
        }
    })
    .catch(error => {
        console.error('Error updating session status:', error);
    });
}

function markAbsentStudents() {
    console.log('markAbsentStudents called for session:', sessionId);
    
    if (!sessionId) {
        console.log('No session ID provided for marking absent students');
        return;
    }
    
    // Call API to mark absent students
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_absent_students',
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Mark absent students response:', data);
        if (data.success) {
            console.log(`Successfully marked ${data.marked_absent} students as absent`);
        } else {
            console.error('Failed to mark absent students:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking absent students:', error);
    });
}

function closeSession() {
    console.log('closeSession called');
    console.log('Session ID:', sessionId);
    console.log('Is session closed:', isSessionClosed);
    
    if (!sessionId || isSessionClosed) {
        console.log('Exiting closeSession - no session ID or already closed');
        return;
    }
    
    isSessionClosed = true;
    console.log('Marking session as closed');
    
    // First, mark absent students before closing the session
    markAbsentStudents();
    
    // Clear sessionStorage when session ends
    sessionStorage.removeItem('sessionStartTime');
    sessionStorage.removeItem('currentSessionId');
    sessionStorage.removeItem('attendanceCounts');
    console.log('SessionStorage cleared');
    
    // Use fetch for normal requests, sendBeacon for page unload
    const data = JSON.stringify({
        action: 'close_session',
        session_id: sessionId
    });
    
    console.log('Sending close request with data:', data);
    
    if (navigator.sendBeacon) {
        const success = navigator.sendBeacon('../api/attendance_session_api.php', data);
        console.log('SendBeacon result:', success);
    } else {
        console.log('SendBeacon not supported, using fetch fallback');
        // Fallback for older browsers
        fetch('../api/attendance_session_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            console.log('Close session response:', data);
            if (!data.success) {
                console.error('Failed to close session:', data.message);
            }
        })
        .catch(error => {
            console.error('Error closing session:', error);
        });
    }
}

// Grace Period Settings Functions
function showGracePeriodModal() {
    // Load current settings into modal
    document.getElementById('gracePeriodSelect').value = gracePeriod;
    document.getElementById('autoLateCheck').checked = autoLateEnabled;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('gracePeriodModal'));
    modal.show();
}

function saveGracePeriodSettings() {
    // Get values from modal
    const newGracePeriod = parseInt(document.getElementById('gracePeriodSelect').value);
    const newAutoLateEnabled = document.getElementById('autoLateCheck').checked;
    
    // Update settings
    gracePeriod = newGracePeriod;
    autoLateEnabled = newAutoLateEnabled;
    
    // Save to sessionStorage
    sessionStorage.setItem('gracePeriod', gracePeriod.toString());
    sessionStorage.setItem('autoLateEnabled', autoLateEnabled.toString());
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('gracePeriodModal'));
    modal.hide();
    
    // Show success message
    showToast('Settings saved successfully', 'success');
    
    console.log('Grace period settings updated:', { gracePeriod, autoLateEnabled });
}

// Load grace period settings from sessionStorage on initialization
function loadGracePeriodSettings() {
    const savedGracePeriod = sessionStorage.getItem('gracePeriod');
    const savedAutoLateEnabled = sessionStorage.getItem('autoLateEnabled');
    
    if (savedGracePeriod) {
        gracePeriod = parseInt(savedGracePeriod);
    }
    
    if (savedAutoLateEnabled) {
        autoLateEnabled = savedAutoLateEnabled === 'true';
    }
    
    console.log('Grace period settings loaded:', { gracePeriod, autoLateEnabled });
}
