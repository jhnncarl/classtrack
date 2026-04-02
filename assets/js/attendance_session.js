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
    window.location.href = 'class_view.php?class=<?php echo $classCode; ?>';
});

// Timer functions
function startTimer() {
    sessionTimer = setInterval(() => {
        if (!isPaused) {
            sessionSeconds++;
            updateTimerDisplay();
        }
    }, 1000);
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

function disableCamera() {
    const videoElement = document.getElementById('videoElement');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const enableBtn = document.getElementById('enableCameraBtn');
    const scannerStatus = document.getElementById('scannerStatus');
    
    // Stop camera stream
    if (videoElement.srcObject) {
        const stream = videoElement.srcObject;
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop());
        videoElement.srcObject = null;
    }
    
    videoElement.style.display = 'none';
    cameraPlaceholder.style.display = 'flex';
    scannerOverlay.style.display = 'none';
    enableBtn.innerHTML = '<i class="bi bi-camera-video"></i> Enable Camera';
    enableBtn.onclick = enableCamera;
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
    const currentTime = Date.now();
    const timeSinceLastSound = currentTime - lastErrorSoundTime;
    
    if (timeSinceLastSound < ERROR_SOUND_COOLDOWN) {
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
    
    // Simulate validation (replace with actual validation logic)
    const isValidQRCode = validateQRCode(code.data);
    
    if (isValidQRCode) {
        // Valid QR code - process attendance
        console.log('Valid QR Code detected:', code.data);
        playSuccessSound();
        simulateStudentCheckIn();
        
        // Reset after processing
        setTimeout(() => {
            qrScanner = null;
        }, 2000);
    } else {
        // Invalid QR code - play error sound and show toast
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
            // Simulate validation - replace with actual database checks
            const validStudentNumbers = [
                '2022-31425',
                '2022-31559', 
                '2022-94362',
                '2022-28791',
                '2022-45328',
                '2022-67419',
                '2022-89234',
                '2022-12847'
            ];
            
            return validStudentNumbers.includes(studentData.student_number);
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
    
    clearInterval(sessionTimer);
    showToast('Session ended successfully', 'success');
    setTimeout(() => {
        window.location.href = 'class_view.php?class=<?php echo $classCode; ?>';
    }, 2000);
});

// Simulate student check-ins
function simulateStudentCheckIn() {
    const students = [
        { name: 'John Smith', id: '2022-31425', course: 'Computer Science', year: '3rd Year', email: 'john.smith@university.edu' },
        { name: 'Jane Doe', id: '2022-31559', course: 'Computer Science', year: '3rd Year', email: 'jane.doe@university.edu' },
        { name: 'Mike Johnson', id: '2022-94362', course: 'Computer Science', year: '3rd Year', email: 'mike.johnson@university.edu' },
        { name: 'Sarah Williams', id: '2022-28791', course: 'Computer Science', year: '3rd Year', email: 'sarah.williams@university.edu' },
        { name: 'David Brown', id: '2022-45328', course: 'Computer Science', year: '3rd Year', email: 'david.brown@university.edu' },
        { name: 'Emily Davis', id: '2022-67419', course: 'Computer Science', year: '3rd Year', email: 'emily.davis@university.edu' },
        { name: 'Chris Wilson', id: '2022-89234', course: 'Computer Science', year: '3rd Year', email: 'chris.wilson@university.edu' },
        { name: 'Lisa Anderson', id: '2022-12847', course: 'Computer Science', year: '3rd Year', email: 'lisa.anderson@university.edu' }
    ];
    
    const randomStudent = students[Math.floor(Math.random() * students.length)];
    
    // Update student information section
    updateStudentInfo(randomStudent);
    
    // Automatically reset student info after displaying
    setTimeout(() => {
        resetStudentInfo();
    }, 3000);
}

function updateStudentInfo(student) {
    const studentInfoContent = document.getElementById('studentInfoContent');
    const studentStatusBadge = document.getElementById('studentStatusBadge');
    
    // Update status badge
    studentStatusBadge.className = 'student-status-badge scanned';
    studentStatusBadge.innerHTML = `
        <i class="bi bi-person-check"></i>
        <span>Student Scanned</span>
    `;
    
    // Update student info content
    studentInfoContent.innerHTML = `
        <div class="student-details">
            <div class="student-avatar-section">
                <div class="student-avatar"><i class="bi bi-person-circle"></i></div>
                <div class="student-basic-info">
                    <h4>${student.name}</h4>
                    <div class="student-id">ID: ${student.id}</div>
                </div>
            </div>
            <div class="student-meta">
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
    const iconSvg = type === 'success' 
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    
    toast.innerHTML = `
        <div class="toast-icon">
            ${iconSvg}
        </div>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : 'Error'}</div>
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
    startTimer();
});
