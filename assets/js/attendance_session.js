// Attendance tracking variables
let presentCount = 0;
let absentCount = 0;
let lateCount = 0;
let totalStudents = 0;

// Grace period settings
let gracePeriod = 15; // Default 15 minutes
let autoLateEnabled = true;

// Duplicate scan prevention
let recentScans = new Set(); // Track recent scans to prevent duplicates
const SCAN_COOLDOWN = 5000; // 5 seconds cooldown

// Student data cache for offline scans
let studentDataCache = {}; // Cache complete student data

// Attendance Session JavaScript - ClassTrack Teacher Dashboard

let sessionTimer = null;
let sessionSeconds = 0;
let isPaused = false;

// Navigation functions
function goBack() {
    // Check if offline and show WiFi warning first
    if (!isOnline) {
        const wifiModal = new bootstrap.Modal(document.getElementById('wifiWarningModal'));
        wifiModal.show();
        return;
    }
    
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
        // Valid QR code format - check for duplicates
        const studentData = window.currentStudentData;
        
        if (isDuplicateScan(studentData.student_number)) {
            playErrorSound();
            showToast('Attendance already recorded for this student', 'info');
            
            // Reset after cooldown
            setTimeout(() => {
                qrScanner = null;
            }, 1500);
            return;
        }
        
        // Add to recent scans to prevent duplicates
        addToRecentScans(studentData.student_number);
        
        // Process attendance
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

// Check for duplicate scan (both online and offline)
function isDuplicateScan(studentNumber) {
    console.log('Checking duplicate scan for student:', studentNumber);
    console.log('Session ID:', sessionId);
    
    const scanKey = `${sessionId}_${studentNumber}`;
    
    // Check recent scans (cooldown period)
    if (recentScans.has(scanKey)) {
        console.log('Duplicate scan detected in cooldown period:', studentNumber);
        return true;
    }
    
    // Check offline records
    const existingOfflineRecords = getOfflineRecords();
    console.log('Existing offline records:', existingOfflineRecords);
    
    const duplicateOffline = existingOfflineRecords.find(record => 
        (record.student_number === studentNumber || record.studentId === studentNumber) && 
        record.sessionId === sessionId
    );
    
    console.log('Duplicate offline record found:', duplicateOffline);
    
    if (duplicateOffline) {
        console.log('Duplicate offline scan detected:', studentNumber);
        return true;
    }
    
    console.log('No duplicate found for student:', studentNumber);
    return false;
}

// Add to recent scans with cooldown
function addToRecentScans(studentNumber) {
    const scanKey = `${sessionId}_${studentNumber}`;
    recentScans.add(scanKey);
    
    // Remove from recent scans after cooldown
    setTimeout(() => {
        recentScans.delete(scanKey);
    }, SCAN_COOLDOWN);
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
    // Check if offline and show WiFi warning first
    if (!isOnline) {
        const wifiModal = new bootstrap.Modal(document.getElementById('wifiWarningModal'));
        wifiModal.show();
        return;
    }
    
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

// Validate student with server and get information from server
function validateStudentWithServer(studentNumber) {
    console.log('🔍 validateStudentWithServer called');
    console.log('📡 Current isOnline status:', isOnline);
    console.log('🌐 navigator.onLine:', navigator.onLine);
    
    // Set QR processing flag to prevent network status changes
    isProcessingQR = true;
    
    // Check if offline first
    if (!isOnline) {
        console.log('📱 Offline mode - validating student locally');
        validateStudentOffline(studentNumber);
        // Clear flag after processing
        setTimeout(() => { isProcessingQR = false; }, 2000);
        return;
    }
    
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
            // Cache complete student data for future offline scans
            studentDataCache[data.student.student_number] = {
                name: data.student.name,
                course: data.student.course,
                year: data.student.year,
                email: data.student.email,
                profile_picture: data.student.profile_picture
            };
            
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
        // Network or server error - try offline mode
        console.log('Network error - falling back to offline mode');
        playErrorSound();
        validateStudentOffline(studentNumber);
    })
    .finally(() => {
        // Clear QR processing flag
        isProcessingQR = false;
    });
}

// Validate student in offline mode
function validateStudentOffline(studentNumber) {
    console.log('📱 validateStudentOffline called for:', studentNumber);
    console.log('📡 Current isOnline status:', isOnline);
    console.log('🌐 navigator.onLine:', navigator.onLine);
    console.log('🔄 QR processing flag:', isProcessingQR);
    
    // Get student data from QR code
    const studentData = window.currentStudentData;
    
    if (!studentData) {
        console.error('No student data available for offline validation');
        playErrorSound();
        showToast('No student data available', 'error');
        return;
    }
    
    // Get cached student data or use basic QR data
    const cachedStudent = studentDataCache[studentData.student_number];
    const offlineStudent = {
        id: null, // Will be set when synced
        student_number: studentData.student_number,
        name: studentData.full_name || `${studentData.first_name} ${studentData.last_name}`,
        course: cachedStudent?.course || studentData.course || 'Unknown',
        year: cachedStudent?.year || studentData.year || 'Unknown', 
        email: studentData.email || 'unknown@student.com',
        profile_picture: cachedStudent?.profile_picture || studentData.profile_picture || null
    };
    
    // Calculate attendance status based on grace period using helper function
    console.log('Offline mode - calculating attendance status...');
    const attendanceStatus = calculateAttendanceStatus();
    console.log('Calculated attendance status for offline mode:', attendanceStatus);
    
    console.log('Offline student validation successful:', offlineStudent);
    console.log('Calculated attendance status:', attendanceStatus);
    
    playSuccessSound();
    
    // Store attendance record offline (will show toast)
    storeOfflineAttendance(offlineStudent, attendanceStatus);
    
    // Update UI with offline student info
    updateStudentInfo({
        name: offlineStudent.name,
        id: offlineStudent.student_number,
        course: offlineStudent.course,
        year: offlineStudent.year,
        email: offlineStudent.email,
        profile_picture: offlineStudent.profile_picture,
        attendance_status: attendanceStatus // Use calculated status instead of hardcoded 'Present'
    });
    
    // Update attendance counts based on actual status
    if (attendanceStatus === 'Late') {
        markLate();
    } else {
        markPresent();
    }
}

// Store attendance record in offline mode
function storeOfflineAttendance(studentData, attendanceStatus = 'Present') {
    // Create offline record
    const offlineRecord = {
        action: 'validate_and_record',
        student_id: studentData.student_number,
        student_number: studentData.student_number, // Keep both for compatibility
        studentData: studentData,
        attendance_status: attendanceStatus,
        timestamp: Date.now()
    };
    
    // Store the record
    const success = storeOfflineRecord(offlineRecord);
    
    if (success) {
        console.log('Attendance stored offline successfully');
        showToast('Attendance stored offline - Will sync when online', 'info');
    } else {
        console.error('Failed to store attendance offline');
        showToast('Failed to store attendance offline', 'error');
    }
}

// Record attendance in database
function recordAttendance(studentId, studentData = null) {
    // Calculate attendance status based on grace period using helper function
    console.log('Online mode - calculating attendance status...');
    const attendanceStatus = calculateAttendanceStatus();
    console.log('Calculated attendance status for online mode:', attendanceStatus);
    
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
        // Network or server error - try to store offline
        console.log('Network error during recording - storing offline');
        playErrorSound();
        
        // Store attendance record offline
        const offlineRecord = {
            action: 'record_attendance',
            student_id: studentId,
            studentData: studentData,
            attendance_status: attendanceStatus,
            timestamp: Date.now()
        };
        
        const success = storeOfflineRecord(offlineRecord);
        
        if (success) {
            console.log('Attendance stored offline due to network failure');
            showToast('Network error - Attendance stored offline', 'warning');
            
            // Update UI since we have the student data
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
                
                // Update attendance counts based on status
                if (attendanceStatus === 'Late') {
                    markLate();
                } else {
                    markPresent();
                }
            }
        } else {
            console.error('Failed to store attendance offline:', error);
            showToast('Error recording attendance and failed to store offline', 'error');
            // Reset student info since nothing was stored
            setTimeout(() => {
                resetStudentInfo();
            }, 2000);
        }
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
    initializeNetworkMonitoring(); // Initialize offline storage system
    initializeOfflineRecords(); // Load existing offline records
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
    
    // Stop periodic network checks
    stopPeriodicNetworkCheck();
    
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

// ==========================================
// GRACE PERIOD HELPER FUNCTIONS
// ==========================================

// Calculate attendance status based on grace period
function calculateAttendanceStatus() {
    let attendanceStatus = 'Present';
    
    if (autoLateEnabled) {
        const sessionStartTime = sessionStorage.getItem('sessionStartTime');
        if (sessionStartTime) {
            const currentTime = Date.now();
            const sessionStart = parseInt(sessionStartTime);
            const elapsedMinutes = Math.floor((currentTime - sessionStart) / 60000);
            
            console.log('Grace period calculation:');
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
    
    return attendanceStatus;
}

// ==========================================
// OFFLINE STORAGE SYSTEM
// ==========================================

// Network connectivity detection
let isOnline = null; // Start with unknown state, will be determined by actual connectivity check
let offlineRecords = [];
let syncInProgress = false;
let syncRetryCount = 0;
const MAX_SYNC_RETRIES = 3;
let networkCheckInterval = null;
const NETWORK_CHECK_INTERVAL = 10000; // Check every 10 seconds (more frequent)
let isProcessingQR = false; // Flag to prevent network status changes during QR processing
let isInitialized = false; // Flag to track if initialization is complete

// Initialize network monitoring
function initializeNetworkMonitoring() {
    console.log('Initializing network monitoring...');
    console.log('Initial online status:', isOnline);
    
    // Set up event listeners for online/offline events
    window.addEventListener('online', handleConnectionRestored);
    window.addEventListener('offline', handleConnectionLost);
    
    // Set initial status based on browser's online status (no external checks)
    isOnline = navigator.onLine;
    updateConnectionStatus();
}

// Check actual connectivity by making a request to external resources
async function checkActualConnectivity() {
    console.log('=== Starting connectivity check ===');
    console.log('Current isOnline status:', isOnline);
    console.log('navigator.onLine:', navigator.onLine);
    console.log('QR processing flag:', isProcessingQR);
    
    // Skip connectivity check if QR is being processed to prevent status changes
    if (isProcessingQR) {
        console.log('⏸️ Skipping connectivity check - QR processing in progress');
        return;
    }
    
    // First check if browser reports offline - if so, we're definitely offline
    if (!navigator.onLine) {
        console.log('Browser reports offline - setting offline status');
        if (isOnline) {
            isOnline = false;
            handleConnectionLost();
        }
        return;
    }
    
    const wasOnline = isOnline;
    let actuallyOnline = false;
    
    try {
        // Test connectivity with multiple external endpoints to ensure real internet access
        // Use reliable external services that don't require CORS and respond quickly
        const testEndpoints = [
            'https://httpbin.org/status/200',
            'https://jsonplaceholder.typicode.com/posts/1',
            'https://reqres.in/api/users/1'
        ];
        
        // Test up to 2 endpoints to confirm connectivity
        let successCount = 0;
        const requiredSuccesses = 2; // Need 2 successful responses to confirm online
        
        for (let i = 0; i < Math.min(testEndpoints.length, requiredSuccesses); i++) {
            const endpoint = testEndpoints[i];
            console.log(`Testing endpoint ${i + 1}: ${endpoint}`);
            
            let timeoutId = null;
            try {
                const controller = new AbortController();
                timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout per request
                
                const response = await fetch(endpoint, {
                    method: 'GET',
                    cache: 'no-cache',
                    signal: controller.signal,
                    mode: 'no-cors' // Use no-cors to avoid CORS issues
                });
                
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                
                // With no-cors mode, we can't check response status, but if it doesn't throw, we have connectivity
                console.log(`Endpoint ${i + 1} responded successfully`);
                successCount++;
                
                if (successCount >= requiredSuccesses) {
                    break;
                }
                
            } catch (endpointError) {
                console.log(`Endpoint ${i + 1} failed:`, endpointError.message);
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
            }
        }
        
        // Also test our local API to ensure it's accessible (but don't rely on it for internet detection)
        try {
            const localController = new AbortController();
            const localTimeoutId = setTimeout(() => localController.abort(), 3000);
            
            const localResponse = await fetch('../api/attendance_session_api.php?action=ping', {
                method: 'GET',
                cache: 'no-cache',
                signal: localController.signal
            });
            
            clearTimeout(localTimeoutId);
            
            if (localResponse.ok) {
                console.log('Local API is accessible');
            }
        } catch (localError) {
            console.log('Local API not accessible:', localError.message);
            // If local API is not accessible, we might have bigger issues
        }
        
        actuallyOnline = successCount >= requiredSuccesses;
        console.log(`Successfully connected to ${successCount}/${requiredSuccesses} external endpoints`);
        console.log('Was online:', wasOnline);
        console.log('Actually online:', actuallyOnline);
        
        // Handle status changes
        if (wasOnline !== actuallyOnline) {
            if (actuallyOnline && !wasOnline) {
                // Going from offline to online - require confirmation with second check
                console.log('🔄 Potential online status detected - requiring confirmation...');
                
                // Wait a bit and check again to confirm it's not a fluke
                setTimeout(() => {
                    if (!isProcessingQR && navigator.onLine) {
                        checkActualConnectivity();
                    }
                }, 3000);
                return; // Don't update status yet
            } else if (!actuallyOnline && wasOnline) {
                // Going from online to offline - immediate update (safer)
                console.log('🔴 Internet connection lost');
                isOnline = false;
                handleConnectionLost();
                return;
            }
        }
        
        // If we get here, it means we're in a confirmed state
        if (actuallyOnline) {
            isOnline = true;
            console.log('✅ Internet connectivity confirmed - connection restored');
            updateConnectionStatus();
            
            // Only show toast and handle connection restoration if this is not the initial check
            if (isInitialized) {
                showToast('Connection restored', 'success');
                
                // Close WiFi warning modal if it's open
                const wifiModal = document.getElementById('wifiWarningModal');
                if (wifiModal) {
                    const modalInstance = bootstrap.Modal.getInstance(wifiModal);
                    if (modalInstance) {
                        modalInstance.hide();
                        console.log('WiFi warning modal closed due to connection restoration');
                    }
                }
                
                // Trigger sync if there are offline records (with delay to allow success toast to disappear)
                if (offlineRecords.length > 0 && !syncInProgress) {
                    console.log('Triggering sync for offline records:', offlineRecords.length);
                    // Delay sync to allow "Connection restored" toast to disappear first
                    setTimeout(() => {
                        syncOfflineRecords();
                    }, 5500); // 5.5 seconds (toast shows for 5 seconds + 500ms buffer)
                }
            }
        } else {
            console.log('❌ No internet connectivity - staying offline');
            if (wasOnline) {
                handleConnectionLost();
            } else {
                updateConnectionStatus();
            }
        }
        
        // Mark initialization as complete after first check
        if (!isInitialized) {
            isInitialized = true;
            console.log('Network monitoring initialization complete');
        }
        
    } catch (error) {
        console.log('❌ Connectivity check failed:', error.name, error.message);
        
        if (error.name === 'AbortError') {
            console.log('Request timed out - no internet connection');
        }
        
        isOnline = false;
        
        if (wasOnline !== isOnline) {
            console.log('🔴 Status changed from online to offline');
            handleConnectionLost();
        } else {
            console.log('🔴 Still offline');
            updateConnectionStatus();
        }
    }
    
    console.log('=== Connectivity check completed ===');
    console.log('Final isOnline status:', isOnline);
}

// Start periodic network checks
function startPeriodicNetworkCheck() {
    // Clear any existing interval
    if (networkCheckInterval) {
        clearInterval(networkCheckInterval);
    }
    
    // Set up periodic checks
    networkCheckInterval = setInterval(() => {
        checkActualConnectivity();
    }, NETWORK_CHECK_INTERVAL);
}

// Stop periodic network checks
function stopPeriodicNetworkCheck() {
    if (networkCheckInterval) {
        clearInterval(networkCheckInterval);
        networkCheckInterval = null;
    }
}

// Handle connection restored
function handleConnectionRestored() {
    console.log('🌐 Browser reports connection restored');
    console.log('Current isOnline status:', isOnline);
    console.log('navigator.onLine:', navigator.onLine);
    
    // Set online status based on browser report (no external checks to avoid console refresh)
    isOnline = true;
    updateConnectionStatus();
    showToast('Connection restored', 'success');
    
    // Close WiFi warning modal if it's open
    const wifiModal = document.getElementById('wifiWarningModal');
    if (wifiModal) {
        const modalInstance = bootstrap.Modal.getInstance(wifiModal);
        if (modalInstance) {
            modalInstance.hide();
            console.log('WiFi warning modal closed due to connection restoration');
        }
    }
    
    // Trigger sync if there are offline records (with delay to allow success toast to disappear)
    if (offlineRecords.length > 0 && !syncInProgress) {
        console.log('Triggering sync for offline records:', offlineRecords.length);
        // Delay sync to allow "Connection restored" toast to disappear first
        setTimeout(() => {
            syncOfflineRecords();
        }, 5500); // 5.5 seconds (toast shows for 5 seconds + 500ms buffer)
    }
}

// Handle connection lost
function handleConnectionLost() {
    console.log('Browser reports connection lost - entering offline mode');
    isOnline = false;
    updateConnectionStatus();
}

// Update connection status display
function updateConnectionStatus() {
    console.log('Updating connection status - isOnline:', isOnline);
    console.log('Initialization state:', isInitialized);
    
    // Update UI indicators
    const statusIndicator = document.getElementById('connectionStatus');
    if (statusIndicator) {
        if (isOnline === null) {
            // Still checking connectivity
            statusIndicator.className = 'connection-status checking';
            statusIndicator.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Checking...</span>';
        } else if (isOnline) {
            statusIndicator.className = 'connection-status online';
            statusIndicator.innerHTML = '<i class="bi bi-wifi"></i><span>Online</span>';
        } else {
            statusIndicator.className = 'connection-status offline';
            statusIndicator.innerHTML = '<i class="bi bi-wifi-off"></i><span>Offline</span>';
        }
    }
    
    // Log status change
    if (isOnline === null) {
        console.log('System is CHECKING connectivity...');
    } else if (isOnline) {
        console.log('System is ONLINE - normal operation mode');
        
        // Show initial online notification if this is the first time we're determining status
        // REMOVED: showToast('Connected to internet', 'success'); - duplicate notification
    } else {
        console.log('System is OFFLINE - attendance will be stored locally');
        
        // Show initial offline notification if this is the first time we're determining status
        if (!isInitialized) {
            showToast('No internet connection - Offline mode activated', 'warning');
        }
    }
}

// ==========================================
// SYNC MECHANISM
// ==========================================

// Sync offline records to server when connection is restored
function syncOfflineRecords() {
    console.log('Starting sync of offline records...');
    
    if (syncInProgress) {
        console.log('Sync already in progress, skipping');
        return;
    }
    
    // Check if current session is active
    if (!sessionId) {
        console.log('No active session - cannot sync offline records');
        showToast('No active session - offline records cannot be synced', 'warning');
        return;
    }
    
    const recordsToSync = getOfflineRecords();
    
    if (recordsToSync.length === 0) {
        console.log('No offline records to sync');
        return;
    }
    
    console.log('Found records to sync:', recordsToSync.length);
    console.log('Current session ID:', sessionId);
    
    // Filter records that belong to current session
    const validRecords = recordsToSync.filter(record => 
        record.sessionId == sessionId || record.sessionId == sessionId.toString()
    );
    
    if (validRecords.length === 0) {
        console.log('No records belong to current session');
        showToast('No offline records for current session', 'info');
        return;
    }
    
    console.log('Valid records for current session:', validRecords.length);
    syncInProgress = true;
    
    // Process records one by one
    syncNextRecord(validRecords, 0);
}

// Sync next record in the queue
function syncNextRecord(records, index) {
    if (index >= records.length) {
        console.log('All records synced successfully');
        syncInProgress = false;
        syncRetryCount = 0;
        showToast('All offline attendance synced successfully', 'success');
        return;
    }
    
    const record = records[index];
    console.log(`Syncing record ${index + 1}/${records.length}:`, record);
    
    // Determine the action based on record type
    if (record.action === 'validate_and_record') {
        // This record has both validation and attendance data
        syncValidateAndRecord(record, records, index);
    } else if (record.action === 'record_attendance') {
        // This record only has attendance data (validation was successful)
        syncAttendanceOnly(record, records, index);
    } else {
        console.log('Unknown record action:', record.action);
        // Skip this record and move to next
        syncNextRecord(records, index + 1);
    }
}

// Sync record that needs validation and attendance recording
function syncValidateAndRecord(record, records, index) {
    console.log('Syncing validate_and_record record:', record);
    console.log('student_number:', record.student_number);
    console.log('subjectId:', subjectId);
    
    // Get student number from multiple possible locations
    const studentNumber = record.student_number || 
                          record.studentId || 
                          record.studentData?.student_number || 
                          record.studentData?.id;
    
    console.log('Extracted student number:', studentNumber);
    console.log('Full studentData:', record.studentData);
    
    if (!studentNumber) {
        console.error('No student number found in record:', record);
        console.error('Available fields:', {
            student_number: record.student_number,
            studentId: record.studentId,
            studentData_student_number: record.studentData?.student_number,
            studentData_id: record.studentData?.id
        });
        // Skip this record and move to next
        syncNextRecord(records, index + 1);
        return;
    }
    
    // First validate the student
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
            // Student validated, now record attendance
            recordAttendanceOnline(data.student.id, record.attendance_status, record, records, index);
        } else {
            console.log('Student validation failed during sync:', data.message);
            // Skip this record and move to next
            syncNextRecord(records, index + 1);
        }
    })
    .catch(error => {
        console.error('Error during sync validation:', error);
        handleSyncError(record, records, index, error);
    });
}

// Sync record that only needs attendance recording
function syncAttendanceOnly(record, records, index) {
    recordAttendanceOnline(record.student_id, record.attendance_status, record, records, index);
}

// Record attendance online during sync
function recordAttendanceOnline(studentId, attendanceStatus, record, records, index) {
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'record_attendance',
            session_id: record.sessionId,
            student_id: studentId,
            attendance_status: attendanceStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Record synced successfully:', record.id);
            // Remove the synced record
            removeOfflineRecord(record.id);
            // Move to next record
            syncNextRecord(records, index + 1);
        } else {
            console.log('Failed to sync record:', data.message);
            // Check if it's a duplicate (already recorded)
            if (data.message.includes('already recorded')) {
                console.log('Record already exists on server, removing offline copy');
                removeOfflineRecord(record.id);
                syncNextRecord(records, index + 1);
            } else {
                handleSyncError(record, records, index, data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error during sync recording:', error);
        handleSyncError(record, records, index, error);
    });
}

// Handle sync errors with retry logic
function handleSyncError(record, records, index, error) {
    record.retryCount = (record.retryCount || 0) + 1;
    
    if (record.retryCount <= MAX_SYNC_RETRIES) {
        console.log(`Retrying sync for record ${record.id}, attempt ${record.retryCount}`);
        
        // Exponential backoff: wait longer between retries
        const retryDelay = Math.pow(2, record.retryCount) * 1000; // 1s, 2s, 4s
        
        setTimeout(() => {
            syncNextRecord(records, index); // Retry this record
        }, retryDelay);
    } else {
        console.log(`Max retries exceeded for record ${record.id}, skipping`);
        // Remove the failed record after max retries
        removeOfflineRecord(record.id);
        // Continue with next record
        syncNextRecord(records, index + 1);
    }
}

// ==========================================
// LOCAL STORAGE FUNCTIONS
// ==========================================

const OFFLINE_STORAGE_KEY = 'attendance_offline_records';

// Store attendance record locally when offline
function storeOfflineRecord(recordData) {
    console.log('Storing offline record:', recordData);
    
    try {
        // Get existing offline records
        const existingRecords = getOfflineRecords();
        
        // Create new offline record with metadata
        const offlineRecord = {
            id: generateOfflineRecordId(),
            timestamp: Date.now(),
            sessionId: recordData.session_id || sessionId,
            studentId: recordData.student_id,
            studentData: recordData.studentData || null,
            attendanceStatus: recordData.attendance_status || 'Present',
            action: recordData.action || 'record_attendance',
            retryCount: 0,
            synced: false
        };
        
        // Add to existing records
        existingRecords.push(offlineRecord);
        
        // Save to localStorage
        localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(existingRecords));
        
        // Update in-memory array
        offlineRecords = existingRecords;
        
        console.log('Offline record stored successfully. Total offline records:', offlineRecords.length);
        return true;
    } catch (error) {
        console.error('Error storing offline record:', error);
        return false;
    }
}

// Get all offline records from localStorage
function getOfflineRecords() {
    try {
        const stored = localStorage.getItem(OFFLINE_STORAGE_KEY);
        const records = stored ? JSON.parse(stored) : [];
        console.log('Retrieved offline records:', records.length);
        return records;
    } catch (error) {
        console.error('Error retrieving offline records:', error);
        return [];
    }
}

// Remove a specific offline record
function removeOfflineRecord(recordId) {
    try {
        const records = getOfflineRecords();
        const filteredRecords = records.filter(record => record.id !== recordId);
        
        localStorage.setItem(OFFLINE_STORAGE_KEY, JSON.stringify(filteredRecords));
        offlineRecords = filteredRecords;
        
        console.log('Offline record removed:', recordId);
        return true;
    } catch (error) {
        console.error('Error removing offline record:', error);
        return false;
    }
}

// Clear all offline records
function clearOfflineRecords() {
    try {
        localStorage.removeItem(OFFLINE_STORAGE_KEY);
        offlineRecords = [];
        console.log('All offline records cleared');
        return true;
    } catch (error) {
        console.error('Error clearing offline records:', error);
        return false;
    }
}

// Generate unique ID for offline records
function generateOfflineRecordId() {
    return 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Initialize offline records on page load
function initializeOfflineRecords() {
    offlineRecords = getOfflineRecords();
    console.log('Initialized offline records:', offlineRecords.length);
    
    if (offlineRecords.length > 0) {
        console.log('Found pending offline records that need syncing');
    }
}

// Debug function to examine offline records (remove after testing)
function debugOfflineRecords() {
    console.log('=== DEBUGGING OFFLINE RECORDS ===');
    const records = getOfflineRecords();
    console.log('Total records:', records.length);
    console.log('Current session ID:', sessionId);
    
    records.forEach((record, index) => {
        console.log(`Record ${index + 1}:`, {
            id: record.id,
            action: record.action,
            student_number: record.student_number,
            studentId: record.studentId,
            sessionId: record.sessionId,
            attendanceStatus: record.attendanceStatus,
            studentData: record.studentData,
            belongsToCurrentSession: record.sessionId == sessionId || record.sessionId == sessionId.toString()
        });
    });
    
    console.log('=== DEBUG COMPLETE ===');
}

// Clear offline records for testing
function clearOfflineRecordsForTesting() {
    if (confirm('This will clear all offline records. Are you sure?')) {
        clearOfflineRecords();
        showToast('All offline records cleared', 'info');
        console.log('Offline records cleared for testing');
    }
}

// Test function for Step 2 (remove after testing)
function testOfflineStorage() {
    console.log('=== TESTING OFFLINE STORAGE ===');
    
    // Test data
    const testRecord = {
        session_id: sessionId || 'test_session',
        student_id: 'test_student_123',
        studentData: {
            name: 'Test Student',
            id: '2022-12345',
            course: 'BSIT',
            year: '3 Year',
            email: 'test@student.com'
        },
        attendance_status: 'Present'
    };
    
    // Test storing
    console.log('1. Testing storeOfflineRecord...');
    const storeResult = storeOfflineRecord(testRecord);
    console.log('Store result:', storeResult);
    
    // Test retrieving
    console.log('2. Testing getOfflineRecords...');
    const records = getOfflineRecords();
    console.log('Retrieved records:', records);
    console.log('Number of records:', records.length);
    
    // Test removing
    if (records.length > 0) {
        console.log('3. Testing removeOfflineRecord...');
        const removeResult = removeOfflineRecord(records[0].id);
        console.log('Remove result:', removeResult);
        console.log('Records after removal:', getOfflineRecords());
    }
    
    // Test clearing
    console.log('4. Testing clearOfflineRecords...');
    const clearResult = clearOfflineRecords();
    console.log('Clear result:', clearResult);
    console.log('Final records:', getOfflineRecords());
    
    console.log('=== OFFLINE STORAGE TEST COMPLETE ===');
}

// Test connectivity function for debugging
function testConnectivity() {
    console.log('=== TESTING CONNECTIVITY ===');
    console.log('Current isOnline status:', isOnline);
    console.log('navigator.onLine:', navigator.onLine);
    
    // Force a connectivity check
    console.log('Forcing connectivity check...');
    checkActualConnectivity().then(() => {
        console.log('Connectivity check completed');
        console.log('Final isOnline status:', isOnline);
        
        // Test external endpoints manually
        console.log('Testing external endpoints manually...');
        testExternalEndpoints();
    });
}

// Test external endpoints individually
async function testExternalEndpoints() {
    const testEndpoints = [
        'https://httpbin.org/status/200',
        'https://jsonplaceholder.typicode.com/posts/1',
        'https://reqres.in/api/users/1'
    ];
    
    for (let i = 0; i < testEndpoints.length; i++) {
        const endpoint = testEndpoints[i];
        console.log(`Testing endpoint ${i + 1}: ${endpoint}`);
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);
            
            const response = await fetch(endpoint, {
                method: 'GET',
                cache: 'no-cache',
                signal: controller.signal,
                mode: 'no-cors'
            });
            
            clearTimeout(timeoutId);
            console.log(`✅ Endpoint ${i + 1} responded successfully`);
            
        } catch (error) {
            console.log(`❌ Endpoint ${i + 1} failed:`, error.message);
            clearTimeout(timeoutId);
        }
    }
    
    console.log('=== EXTERNAL ENDPOINT TEST COMPLETE ===');
}
