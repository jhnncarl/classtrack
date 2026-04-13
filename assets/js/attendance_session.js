// Attendance tracking variables
let presentCount = 0;
let absentCount = 0;
let lateCount = 0;
let totalStudents = 0;

// Global variables
let sessionId = null;
let subjectId = null;
let classCode = null;
let isOnline = navigator.onLine;
let hasInitialLoad = false; // Track if initial load has completed
let isFirstScan = true; // Track if this is the first scan

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

// Get session data from body data attributes
// Variables are already declared globally at the top of the file

// Initialize session data when DOM is loaded
function initializeSessionData() {
    const body = document.body;
    sessionId = body.dataset.sessionId || null;
    subjectId = body.dataset.subjectId || null;
    classCode = body.dataset.classCode || null;
    
    console.log('Session data initialized:', { sessionId, subjectId, classCode });
}

// Show attendance list view (always shows attendance list)
function toggleAttendanceList() {
    const studentDetailsView = document.getElementById('studentDetailsView');
    const attendanceListView = document.getElementById('attendanceListView');
    
    // Always show attendance list, hide student details
    studentDetailsView.classList.add('hidden');
    attendanceListView.classList.remove('hidden');
    
    // Update attendance table when showing the view
    updateAttendanceTable();
}

// Fast update attendance table without loading indicator (for seamless updates)
async function fastUpdateAttendanceTable() {
    const tableBody = document.getElementById('attendanceTableBody');
    const tableContainer = document.querySelector('.students-table-container');
    
    // Always show the table container to occupy full space
    tableContainer.style.display = 'flex';
    
    // Get attendance records from API without showing loading
    const attendanceRecords = await getAttendanceRecords();
    
    if (attendanceRecords.length === 0) {
        // Show empty message in table with proper centering
        tableBody.innerHTML = `
            <tr class="empty-message">
                <td colspan="4">
                    <i class="bi bi-people"></i>
                    No attendance records yet. Students will appear here when they scan their QR codes.
                </td>
            </tr>
        `;
    } else {
        // Clear existing table content
        tableBody.innerHTML = '';
        
        // Populate table with attendance records
        attendanceRecords.forEach(record => {
            const row = createAttendanceTableRow(record);
            tableBody.appendChild(row);
        });
    }
}

// Update attendance table with current records
async function updateAttendanceTable() {
    const tableBody = document.getElementById('attendanceTableBody');
    const tableContainer = document.querySelector('.students-table-container');
    
    // Always show the table container to occupy full space
    tableContainer.style.display = 'flex';
    
    // Show loading only for initial load or first scan
    const shouldShowLoading = !hasInitialLoad || isFirstScan;
    
    if (shouldShowLoading) {
        // Show loading message in table with proper centering
        tableBody.innerHTML = `
            <tr class="loading-message">
                <td colspan="4">
                    <i class="bi bi-arrow-repeat"></i>
                    Loading attendance records...
                </td>
            </tr>
        `;
    }
    
    // Get attendance records from API
    const attendanceRecords = await getAttendanceRecords();
    
    if (attendanceRecords.length === 0) {
        // Show empty message in table with proper centering
        tableBody.innerHTML = `
            <tr class="empty-message">
                <td colspan="4">
                    <i class="bi bi-people"></i>
                    No attendance records yet. Students will appear here when they scan their QR codes.
                </td>
            </tr>
        `;
    } else {
        // Clear existing table content
        tableBody.innerHTML = '';
        
        // Populate table with attendance records
        attendanceRecords.forEach(record => {
            const row = createAttendanceTableRow(record);
            tableBody.appendChild(row);
        });
    }
    
    // Update state tracking
    hasInitialLoad = true;
    if (isFirstScan) {
        isFirstScan = false;
    }
}

// Get attendance records from API
async function getAttendanceRecords() {
    try {
        if (!sessionId) {
            console.warn('No session ID available for fetching attendance records');
            return [];
        }
        
        let onlineRecords = [];
        
        // Try to fetch online records
        try {
            const response = await fetch('../api/attendance_session_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_attendance_list',
                    session_id: sessionId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Online attendance records fetched:', data.attendance_records);
                onlineRecords = data.attendance_records || [];
            } else {
                console.error('Failed to fetch attendance records:', data.message);
            }
        } catch (error) {
            console.error('Error fetching online attendance records:', error);
        }
        
        const offlineRecords = getOfflineRecords();
        
        if (offlineRecords.length > 0) {
            console.log('📱 Including offline records in table (offline records exist)');
            
            // Convert offline records to the same format as online records
            const formattedOfflineRecords = offlineRecords
                .filter(record => record.sessionId === sessionId && record.studentData)
                .map(record => ({
                    id: record.id, // Use offline record ID
                    studentNumber: record.studentData.student_number,
                    name: record.studentData.name,
                    course: record.studentData.course,
                    status: record.attendanceStatus || 'Present',
                    time: new Date(record.timestamp).toLocaleTimeString(),
                    isOffline: true // Flag to indicate this is an offline record
                }));
            
            console.log('Formatted offline records for table:', formattedOfflineRecords);
            
            // Merge online and offline records, avoiding duplicates
            const allRecords = [...onlineRecords];
            
            // Add offline records that aren't already in online records
            formattedOfflineRecords.forEach(offlineRecord => {
                const exists = allRecords.some(onlineRecord => 
                    onlineRecord.studentNumber === offlineRecord.studentNumber
                );
                if (!exists) {
                    allRecords.push(offlineRecord);
                }
            });
            
            console.log('Total records (online + offline):', allRecords);
            return allRecords;
        }
        
        return onlineRecords;
    } catch (error) {
        console.error('Error in getAttendanceRecords:', error);
        return [];
    }
}

// Create table row for attendance record
function createAttendanceTableRow(record) {
    const row = document.createElement('tr');
    
    const statusClass = record.status.toLowerCase();
    const statusBadge = `<span class="status-badge ${statusClass}">${record.status}</span>`;
    
    // Add offline indicator if this is an offline record
    const offlineIndicator = record.isOffline ? 
        '<i class="bi bi-wifi-off" title="Scanned offline" style="color: #dc3545; margin-left: 5px;"></i>' : '';
    
    row.innerHTML = `
        <td>${record.studentNumber}</td>
        <td>${record.name}${offlineIndicator}</td>
        <td>${record.course}</td>
        <td>${statusBadge}</td>
    `;
    
    return row;
}

// Add attendance record to table (call this when student scans)
async function addAttendanceRecord(studentData, status) {
    const record = {
        studentNumber: studentData.id || studentData.studentNumber,
        name: studentData.name,
        course: studentData.course,
        status: status
    };
    
    await updateAttendanceTable();
}

// Helper function to check if currently in attendance list view
function isInAttendanceListView() {
    const attendanceListView = document.getElementById('attendanceListView');
    return !attendanceListView.classList.contains('hidden');
}

// Show student details view (always shows student details)
function showStudentDetails() {
    const studentDetailsView = document.getElementById('studentDetailsView');
    const attendanceListView = document.getElementById('attendanceListView');
    
    // Always show student details, hide attendance list
    studentDetailsView.classList.remove('hidden');
    attendanceListView.classList.add('hidden');
}

// Navigation functions
function goBack(event) {
    // Prevent triggering from keyboard navigation (arrow keys, space, enter)
    if (event && (event.type === 'keydown' || event.type === 'keypress')) {
        console.log('goBack triggered by keyboard, ignoring');
        return false;
    }
    
    // Use the shared confirmation modal function
    showBackConfirmationModal();
    return false;
}

// Handle confirmation button click
document.getElementById('confirmLeaveBtn').addEventListener('click', function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmLeaveModal'));
    modal.hide();
    
    // Set flag to prevent double modal trigger
    window.isLeavingSession = true;
    
    // Close session before leaving
    closeSession();
    
    // Navigate back using history API for consistency, then redirect as fallback
    setTimeout(() => {
        // Try to go back in history first
        if (window.history && history.length > 1) {
            // Remove our state first to allow proper back navigation
            history.back();
            // Fallback redirect in case history back doesn't work
            setTimeout(() => {
                window.location.href = 'class_view.php?class=' + encodeURIComponent(classCode);
            }, 1000);
        } else {
            // Fallback to direct redirect
            window.location.href = 'class_view.php?class=' + encodeURIComponent(classCode);
        }
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
    
    // Try sessionStorage first, then localStorage as fallback
    let savedStartTime = sessionStorage.getItem('sessionStartTime');
    let savedSessionId = sessionStorage.getItem('currentSessionId');
    
    // Fallback to localStorage if sessionStorage is empty
    if (!savedStartTime && !savedSessionId) {
        console.log('SessionStorage empty, trying localStorage fallback...');
        savedStartTime = localStorage.getItem('sessionStartTime');
        savedSessionId = localStorage.getItem('currentSessionId');
        
        if (savedStartTime || savedSessionId) {
            console.log('Found data in localStorage, migrating to sessionStorage...');
            if (savedStartTime) sessionStorage.setItem('sessionStartTime', savedStartTime);
            if (savedSessionId) sessionStorage.setItem('currentSessionId', savedSessionId);
        }
    }
    
    console.log('SessionStorage data:');
    console.log('- savedStartTime:', savedStartTime);
    console.log('- savedSessionId:', savedSessionId);
    console.log('- current sessionId:', sessionId);
    console.log('- sessionId comparison:', savedSessionId == sessionId);
    
    // Also save to localStorage as backup
    if (savedStartTime) localStorage.setItem('sessionStartTime', savedStartTime);
    if (savedSessionId) localStorage.setItem('currentSessionId', savedSessionId);
    
    // Enhanced timer restoration logic
    let timerRestored = false;
    
    if (savedStartTime) {
        if (savedSessionId == sessionId) {
            // Perfect match - restore timer
            const startTime = parseInt(savedStartTime);
            const currentTime = Date.now();
            const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
            
            console.log('Timer calculation (from sessionStorage):');
            console.log('- startTime:', startTime);
            console.log('- currentTime:', currentTime);
            console.log('- elapsedSeconds:', elapsedSeconds);
            
            // Set timer to elapsed time
            sessionSeconds = elapsedSeconds;
            updateTimerDisplay();
            timerRestored = true;
            
            console.log('Timer restored from sessionStorage:', 
        String(Math.floor(elapsedSeconds / 3600)).padStart(2, '0') + ':' +
        String(Math.floor((elapsedSeconds % 3600) / 60)).padStart(2, '0') + ':' +
        String(elapsedSeconds % 60).padStart(2, '0')
    );
            console.log('Session timer restored from previous session (likely after refresh)');
        } else {
            // Session ID mismatch but we have a start time - this might be a reopened session
            console.log('Session ID mismatch detected, but start time exists');
            console.log('- savedSessionId:', savedSessionId);
            console.log('- current sessionId:', sessionId);
            
            if (savedSessionId) {
                console.log('This appears to be a reopened session - attempting to restore timer');
                const startTime = parseInt(savedStartTime);
                const currentTime = Date.now();
                const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                
                // Restore timer but update session ID
                sessionSeconds = elapsedSeconds;
                updateTimerDisplay();
                sessionStorage.setItem('currentSessionId', sessionId); // Update session ID
                localStorage.setItem('currentSessionId', sessionId); // Update session ID in localStorage too
                timerRestored = true;
                
                console.log('Timer restored for reopened session:', 
            String(Math.floor(elapsedSeconds / 3600)).padStart(2, '0') + ':' +
            String(Math.floor((elapsedSeconds % 3600) / 60)).padStart(2, '0') + ':' +
            String(elapsedSeconds % 60).padStart(2, '0')
        );
            }
        }
    }
    
    if (!timerRestored) {
        // Start new timer from 00:00:00 when session becomes active or reopened
        const activationStartTime = Date.now();
        sessionStorage.setItem('sessionStartTime', activationStartTime.toString());
        sessionStorage.setItem('currentSessionId', sessionId);
        localStorage.setItem('sessionStartTime', activationStartTime.toString());
        localStorage.setItem('currentSessionId', sessionId);
        sessionSeconds = 0; // Start from 00:00:00
        
        console.log('New session timer started from activation time:');
        console.log('- activationStartTime:', activationStartTime);
        console.log('- sessionSeconds:', sessionSeconds);
        console.log('- Saved sessionId:', sessionId);
        
        updateTimerDisplay();
    }
    
    sessionTimer = setInterval(() => {
        if (!isPaused) {
            sessionSeconds++;
            updateTimerDisplay();
            // Save current time periodically to both sessionStorage and localStorage
            if (sessionSeconds % 10 === 0) { // Save every 10 seconds
                const startTimeValue = (Date.now() - (sessionSeconds * 1000)).toString();
                sessionStorage.setItem('sessionStartTime', startTimeValue);
                localStorage.setItem('sessionStartTime', startTimeValue);
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
    console.log('📱 Calculated attendance status for offline mode:', attendanceStatus);
    
    console.log('Offline student validation successful:', offlineStudent);
    console.log('📱 About to store offline attendance with status:', attendanceStatus);
    
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
function storeOfflineAttendance(studentData, attendanceStatus) {
    console.log('📱 [OFFLINE STORAGE] Starting offline attendance storage');
    console.log('📱 [OFFLINE STORAGE] Input parameters:', {
        studentNumber: studentData.student_number,
        attendanceStatus: attendanceStatus,
        timestamp: Date.now()
    });
    
    // Create offline record
    const offlineRecord = {
        action: 'validate_and_record',
        student_id: studentData.student_number,
        student_number: studentData.student_number, // Keep both for compatibility
        studentData: studentData,
        attendance_status: attendanceStatus,
        attendanceStatus: attendanceStatus, // Store both formats
        timestamp: Date.now()
    };
    
    console.log('📱 [OFFLINE STORAGE] Created offline record:', {
        action: offlineRecord.action,
        student_id: offlineRecord.student_id,
        attendance_status: offlineRecord.attendance_status,
        attendanceStatus: offlineRecord.attendanceStatus,
        timestamp: offlineRecord.timestamp
    });
    
    // Store the record
    const success = storeOfflineRecord(offlineRecord);
    
    if (success) {
        console.log('✅ [OFFLINE STORAGE] Attendance stored offline successfully');
        showToast('Attendance stored offline - Will sync when online', 'info');
    } else {
        console.error('❌ [OFFLINE STORAGE] Failed to store attendance offline');
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
    const studentStatusBadge = document.getElementById('studentStatusBadge');
    const studentDetailsView = document.getElementById('studentDetailsView');
    
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
    
    // Update only the student details view content
    studentDetailsView.innerHTML = `
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
    
    // Use appropriate update method based on connectivity and loading state
    if (isOnline && hasInitialLoad) {
        fastUpdateAttendanceTable();
    } else {
        updateAttendanceTable();
    }
    
    // Only switch to student details view if user is not in attendance list view
    if (!isInAttendanceListView()) {
        showStudentDetails();
    }
    
    console.log('Student info updated, attendance_status:', student.attendance_status);
}


function resetStudentInfo() {
    const studentStatusBadge = document.getElementById('studentStatusBadge');
    const studentDetailsView = document.getElementById('studentDetailsView');
    
    // Reset status badge
    studentStatusBadge.className = 'student-status-badge';
    studentStatusBadge.innerHTML = `
        <i class="bi bi-person"></i>
        <span>Waiting for scan</span>
    `;
    
    // Reset student details view content
    studentDetailsView.innerHTML = `
        <div class="no-student-placeholder">
            <i class="bi bi-qr-code-scan"></i>
            <p>Student information will appear here</p>
            <small>After QR code is scanned</small>
        </div>
    `;
    
    // Only switch to student details view if user is not in attendance list view
    if (!isInAttendanceListView()) {
        showStudentDetails();
    }
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
    initializeSessionData(); // Initialize session data from data attributes FIRST
    
    // Check if session data is properly initialized
    if (!sessionId) {
        console.error('Session ID not found in data attributes');
        return;
    }
    
    // Initialize history API for consistent back navigation
    initializeHistoryHandling();
    
    // Prevent keyboard events on back button
    const backButton = document.querySelector('.btn-back-inline');
    if (backButton) {
        backButton.addEventListener('keydown', function(event) {
            // Prevent back button from responding to keyboard navigation
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowLeft') {
                event.preventDefault();
                event.stopPropagation();
                console.log('Keyboard navigation on back button prevented');
                return false;
            }
        });
    }
    
    loadGracePeriodSettings(); // Load grace period settings first
    initializeNetworkMonitoring(); // Initialize offline storage system
    initializeOfflineRecords(); // Load existing offline records
    startTimer(); // Now startTimer can access sessionId
    initializeAttendanceCounts();
    setupSessionCleanup();
});

// Initialize history API handling for consistent back navigation
function initializeHistoryHandling() {
    // Add a state to history stack to intercept back navigation
    if (window.history && history.pushState) {
        // Add initial state if not present
        if (!history.state || !history.state.attendanceSession) {
            history.pushState({ attendanceSession: true, sessionId: sessionId }, '', window.location.href);
            console.log('History state added for attendance session');
        }
        
        // Listen for popstate events (browser back/forward)
        window.addEventListener('popstate', function(event) {
            console.log('Popstate event detected:', event.state);
            
            // Check if this is our attendance session navigation
            if (!event.state || !event.state.attendanceSession) {
                // User is trying to navigate away from attendance session
                console.log('User attempting to leave attendance session');
                
                // Prevent double modal trigger if user is already leaving
                if (window.isLeavingSession) {
                    console.log('Already leaving session, allowing navigation');
                    return;
                }
                
                // Prevent the navigation and show confirmation modal
                event.preventDefault();
                
                // Push the state back to maintain our position
                history.pushState({ attendanceSession: true, sessionId: sessionId }, '', window.location.href);
                
                // Show confirmation modal
                showBackConfirmationModal();
                return false;
            }
        });
        
        // Handle page visibility changes (mobile app switching, tab switching)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !isSessionClosed) {
                console.log('Page became hidden - user may be navigating away');
                // Don't show modal immediately as this could be tab switching
                // This is mainly for logging and potential future enhancements
            }
        });
        // Sessions should only be closed when user explicitly ends them
        console.log('Session cleanup setup complete - sessions will only close when explicitly ended by user');
    }
}

// Show back confirmation modal (extracted from goBack for reuse)
function showBackConfirmationModal() {
    // Check if offline and show WiFi warning first
    if (!isOnline) {
        const wifiModal = new bootstrap.Modal(document.getElementById('wifiWarningModal'));
        wifiModal.show();
        return;
    }
    
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('confirmLeaveModal'));
    modal.show();
}

// Setup automatic session cleanup
function setupSessionCleanup() {
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
    
    // Recalculate from actual records to ensure accuracy
    setTimeout(() => {
        recalculateAttendanceCounts();
    }, 1000);
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

// Recalculate attendance counts from all records (online + offline)
async function recalculateAttendanceCounts() {
    console.log('📊 [STATS] Recalculating attendance counts from all records');
    
    try {
        // Get all attendance records (online + offline)
        const allRecords = await getAttendanceRecords();
        
        // Reset counts
        presentCount = 0;
        absentCount = 0;
        lateCount = 0;
        
        // Count by status
        allRecords.forEach(record => {
            const status = record.status ? record.status.toLowerCase() : 'present';
            
            switch (status) {
                case 'present':
                    presentCount++;
                    break;
                case 'late':
                    lateCount++;
                    break;
                case 'absent':
                    absentCount++;
                    break;
            }
        });
        
        console.log('📊 [STATS] Recalculated counts:', {
            present: presentCount,
            late: lateCount,
            absent: absentCount,
            total: allRecords.length
        });
        
        // Update display and save
        updateAttendanceDisplay();
        saveAttendanceCounts();
        
    } catch (error) {
        console.error('❌ [STATS] Error recalculating attendance counts:', error);
    }
}

// Mark student as present
function markPresent() {
    console.log('📊 [STATS] markPresent called - current counts:', { presentCount, lateCount, absentCount });
    
    presentCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
    
    console.log('📊 [STATS] markPresent completed - new counts:', { presentCount, lateCount, absentCount });
}

// Mark student as late
function markLate() {
    console.log('📊 [STATS] markLate called - current counts:', { presentCount, lateCount, absentCount });
    
    lateCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
    
    console.log('📊 [STATS] markLate completed - new counts:', { presentCount, lateCount, absentCount });
}

// Mark student as absent
function markAbsent() {
    console.log('📊 [STATS] markAbsent called - current counts:', { presentCount, lateCount, absentCount });
    
    if (presentCount > 0) {
        presentCount--;
    } else if (lateCount > 0) {
        lateCount--;
    }
    absentCount++;
    updateAttendanceDisplay();
    saveAttendanceCounts();
    
    console.log('📊 [STATS] markAbsent completed - new counts:', { presentCount, lateCount, absentCount });
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
    
    // Clear both sessionStorage and localStorage when session ends
    sessionStorage.removeItem('sessionStartTime');
    sessionStorage.removeItem('currentSessionId');
    sessionStorage.removeItem('attendanceCounts');
    
    localStorage.removeItem('sessionStartTime');
    localStorage.removeItem('currentSessionId');
    localStorage.removeItem('attendanceCounts');
    
    console.log('SessionStorage and localStorage cleared');
    
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

// Ensure session start time is initialized
function SessionStartTime() {
    let sessionStartTime = sessionStorage.getItem('sessionStartTime');
    
    if (!sessionStartTime) {
        console.log('Session start time missing - initializing now');
        const activationStartTime = Date.now();
        sessionStorage.setItem('sessionStartTime', activationStartTime.toString());
        sessionStorage.setItem('currentSessionId', sessionId);
        sessionStartTime = activationStartTime.toString();
        console.log('Session start time initialized:', new Date(parseInt(sessionStartTime)).toLocaleTimeString());
    }
    
    return parseInt(sessionStartTime);
}

// Calculate attendance status based on grace period
function calculateAttendanceStatus() {
    let attendanceStatus = 'Present';
    
    if (autoLateEnabled) {
        // Ensure session start time is initialized before calculation
        const sessionActivationTime = SessionStartTime();
        
        if (sessionActivationTime) {
            const currentTime = Date.now();
            const elapsedMinutes = Math.floor((currentTime - sessionActivationTime) / 60000);
            
            console.log('Grace period calculation:');
            console.log('- Session activation time:', new Date(sessionActivationTime).toLocaleTimeString());
            console.log('- Current time:', new Date(currentTime).toLocaleTimeString());
            console.log('- Elapsed minutes:', elapsedMinutes);
            console.log('- Grace period:', gracePeriod);
            
            if (elapsedMinutes >= gracePeriod) {
                attendanceStatus = 'Late';
                console.log('Student marked as LATE');
            } else {
                console.log('Student marked as PRESENT (within grace period)');
            }
        } else {
            console.warn('Session activation time still missing after initialization');
        }
    } else {
        console.log('Auto late marking is disabled');
    }
    
    return attendanceStatus;
}

// ==========================================
// OFFLINE STORAGE SYSTEM
// ==========================================

// Network connectivity detection
// isOnline variable is already declared globally at the top of the file
let offlineRecords = [];
let syncInProgress = false;
let syncRetryCount = 0;
const MAX_SYNC_RETRIES = 3;
let networkCheckInterval = null;
const NETWORK_CHECK_INTERVAL = 10000; // Check every 10 seconds (more frequent)
let isProcessingQR = false;
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
    
    // Update attendance list seamlessly when connection is restored
    if (hasInitialLoad) {
        fastUpdateAttendanceTable();
        // Recalculate stats when connection is restored
        recalculateAttendanceCounts();
    }
    
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
    
    // Recalculate stats when going offline to ensure consistency
    recalculateAttendanceCounts();
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
    console.log('🔄 [SYNC] Starting sync of offline records...');
    
    if (syncInProgress) {
        console.log('🔄 [SYNC] Sync already in progress, skipping');
        return;
    }
    
    // Check if current session is active
    if (!sessionId) {
        console.log('🔄 [SYNC] No active session - cannot sync offline records');
        showToast('No active session - offline records cannot be synced', 'warning');
        return;
    }
    
    const recordsToSync = getOfflineRecords();
    
    if (recordsToSync.length === 0) {
        console.log('🔄 [SYNC] No offline records to sync');
        return;
    }
    
    console.log('🔄 [SYNC] Found records to sync:', recordsToSync.length);
    console.log('🔄 [SYNC] Current session ID:', sessionId);
    
    // Log all records for debugging
    recordsToSync.forEach((record, index) => {
        console.log(`🔄 [SYNC] Record ${index + 1}:`, {
            id: record.id,
            action: record.action,
            student_id: record.student_id,
            attendance_status: record.attendance_status,
            attendanceStatus: record.attendanceStatus,
            sessionId: record.sessionId,
            timestamp: record.timestamp
        });
    });
    
    // Filter records that belong to current session
    const validRecords = recordsToSync.filter(record => 
        record.sessionId == sessionId || record.sessionId == sessionId.toString()
    );
    
    if (validRecords.length === 0) {
        console.log('🔄 [SYNC] No records belong to current session');
        showToast('No offline records for current session', 'info');
        return;
    }
    
    console.log('🔄 [SYNC] Valid records for current session:', validRecords.length);
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
        
        // Update attendance table to show synced data
        fastUpdateAttendanceTable();
        
        // Recalculate attendance counts after sync to ensure accuracy
        recalculateAttendanceCounts();
        
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
    console.log('🔄 [SYNC VALIDATE] Starting sync validation for record:', record.id);
    console.log('🔄 [SYNC VALIDATE] Record details:', {
        id: record.id,
        action: record.action,
        student_number: record.student_number,
        studentId: record.studentId,
        attendance_status: record.attendance_status,
        attendanceStatus: record.attendanceStatus,
        subjectId: subjectId
    });
    
    // Get student number from multiple possible locations
    const studentNumber = record.student_number || 
                          record.studentId || 
                          record.studentData?.student_number || 
                          record.studentData?.id;
    
    console.log('🔄 [SYNC VALIDATE] Extracted student number:', studentNumber);
    
    if (!studentNumber) {
        console.error('❌ [SYNC VALIDATE] No student number found in record:', record);
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
            console.log('✅ [SYNC VALIDATE] Student validated during sync:', data.student);
            
            // Update student data cache with server data to replace unknown values
            if (data.student && data.student.student_number) {
                studentDataCache[data.student.student_number] = {
                    name: data.student.name,
                    course: data.student.course,
                    year: data.student.year,
                    email: data.student.email,
                    profile_picture: data.student.profile_picture
                };
                console.log('🔄 [SYNC VALIDATE] Updated student data cache for:', data.student.student_number);
            }
            
            // CRITICAL: Extract attendance status with detailed logging
            console.log('🔄 [SYNC VALIDATE] Checking attendance status fields:');
            console.log('  - record.attendance_status:', record.attendance_status);
            console.log('  - record.attendanceStatus:', record.attendanceStatus);
            
            const attendanceStatus = record.attendance_status || record.attendanceStatus || 'Present';
            console.log('🔄 [SYNC VALIDATE] Final attendance status to sync:', attendanceStatus);
            
            // Student validated, now record attendance
            recordAttendanceOnline(data.student.id, attendanceStatus, record, records, index);
        } else {
            console.log('❌ [SYNC VALIDATE] Student validation failed during sync:', data.message);
            // Skip this record and move to next
            syncNextRecord(records, index + 1);
        }
    })
    .catch(error => {
        console.error('❌ [SYNC VALIDATE] Error during sync validation:', error);
        handleSyncError(record, records, index, error);
    });
}

// Sync record that only needs attendance recording
function syncAttendanceOnly(record, records, index) {
    console.log('🔄 [SYNC ONLY] Starting sync-only for record:', record.id);
    console.log('🔄 [SYNC ONLY] Record details:', {
        id: record.id,
        student_id: record.student_id,
        attendance_status: record.attendance_status,
        attendanceStatus: record.attendanceStatus
    });
    
    const attendanceStatus = record.attendance_status || record.attendanceStatus || 'Present';
    console.log('🔄 [SYNC ONLY] Final attendance status to sync:', attendanceStatus);
    
    recordAttendanceOnline(record.student_id, attendanceStatus, record, records, index);
}

// Record attendance online during sync
function recordAttendanceOnline(studentId, attendanceStatus, record, records, index) {
    console.log('🔄 [SYNC ONLINE] Starting online attendance recording');
    console.log('🔄 [SYNC ONLINE] Input parameters:', {
        studentId: studentId,
        attendanceStatus: attendanceStatus,
        recordId: record.id,
        sessionId: record.sessionId,
        originalTimestamp: record.timestamp,
        formattedTime: new Date(record.timestamp).toLocaleString()
    });
    
    const requestBody = {
        action: 'record_attendance',
        session_id: record.sessionId,
        student_id: studentId,
        attendance_status: attendanceStatus,
        scan_timestamp: record.timestamp // Send original scan timestamp to preserve status
    };
    
    console.log('🔄 [SYNC ONLINE] Request body to server:', requestBody);
    
    fetch('../api/attendance_session_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        console.log('🔄 [SYNC ONLINE] Server response received:', data);
        
        if (data.success) {
            console.log('✅ [SYNC ONLINE] Record synced successfully:', {
                recordId: record.id,
                serverRecordId: data.record_id,
                preservedStatus: attendanceStatus,
                message: data.message
            });
            
            // Update student data cache with server data to replace unknown values
            if (record.studentData && record.studentData.student_number) {
                studentDataCache[record.studentData.student_number] = {
                    name: record.studentData.name,
                    course: record.studentData.course,
                    year: record.studentData.year,
                    email: record.studentData.email,
                    profile_picture: record.studentData.profile_picture
                };
                console.log('🔄 [SYNC ONLINE] Updated student data cache for:', record.studentData.student_number);
            }
            
            // Remove the synced record
            removeOfflineRecord(record.id);
            // Update table to show the synced record
            fastUpdateAttendanceTable();
            // Move to next record
            syncNextRecord(records, index + 1);
        } else {
            console.log('❌ [SYNC ONLINE] Failed to sync record:', {
                recordId: record.id,
                attemptedStatus: attendanceStatus,
                errorMessage: data.message
            });
            // Check if it's a duplicate (already recorded)
            if (data.message.includes('already recorded')) {
                console.log('🔄 [SYNC ONLINE] Record already exists on server, removing offline copy');
                removeOfflineRecord(record.id);
                // Update table to reflect the removal
                fastUpdateAttendanceTable();
                syncNextRecord(records, index + 1);
            } else {
                handleSyncError(record, records, index, data.message);
            }
        }
    })
    .catch(error => {
        console.error('❌ [SYNC ONLINE] Error during sync recording:', error);
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
    console.log('📱 [STORAGE] Starting offline record storage');
    console.log('📱 [STORAGE] Input recordData:', recordData);
    console.log('📱 [STORAGE] Attendance status analysis:', {
        'recordData.attendance_status': recordData.attendance_status,
        'recordData.attendanceStatus': recordData.attendanceStatus,
        'finalStatus': recordData.attendance_status || recordData.attendanceStatus || 'Present'
    });
    
    try {
        // Get existing offline records
        const existingRecords = getOfflineRecords();
        console.log('📱 [STORAGE] Existing records count:', existingRecords.length);
        
        // Create new offline record with metadata
        const attendanceStatus = recordData.attendance_status || recordData.attendanceStatus || 'Present';
        console.log('📱 [STORAGE] Extracted attendanceStatus:', attendanceStatus);
        
        const offlineRecord = {
            id: generateOfflineRecordId(),
            timestamp: Date.now(),
            sessionId: recordData.session_id || sessionId,
            studentId: recordData.student_id,
            studentData: recordData.studentData || null,
            attendanceStatus: attendanceStatus,
            attendance_status: attendanceStatus, 
            action: recordData.action || 'record_attendance',
            retryCount: 0,
            synced: false
        };
        
        console.log('📱 [STORAGE] Final offline record created:', {
            id: offlineRecord.id,
            attendanceStatus: offlineRecord.attendanceStatus,
            attendance_status: offlineRecord.attendance_status,
            sessionId: offlineRecord.sessionId,
            studentId: offlineRecord.studentId,
            timestamp: offlineRecord.timestamp
        });
        
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
        console.log('📱 [RETRIEVE] Retrieved offline records:', records.length);
        
        // Log each record for debugging
        records.forEach((record, index) => {
            console.log(`📱 [RETRIEVE] Record ${index + 1}:`, {
                id: record.id,
                action: record.action,
                student_id: record.student_id,
                attendance_status: record.attendance_status,
                attendanceStatus: record.attendanceStatus,
                sessionId: record.sessionId,
                timestamp: record.timestamp
            });
        });
        
        return records;
    } catch (error) {
        console.error('❌ [RETRIEVE] Error retrieving offline records:', error);
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

// Test attendance status calculation for debugging
function testAttendanceStatusCalculation() {
    console.log('=== TESTING ATTENDANCE STATUS CALCULATION ===');
    
    // Save current settings
    const originalGracePeriod = gracePeriod;
    const originalAutoLateEnabled = autoLateEnabled;
    const originalSessionStartTime = sessionStorage.getItem('sessionStartTime');
    
    try {
        // Test 1: Session start time missing (should initialize and mark as Present)
        console.log('Test 1: Session start time missing');
        sessionStorage.removeItem('sessionStartTime');
        gracePeriod = 15;
        autoLateEnabled = true;
        
        const status1 = calculateAttendanceStatus();
        console.log('Result when session start time missing:', status1);
        console.log('Session start time after calculation:', sessionStorage.getItem('sessionStartTime'));
        
        // Test 2: Within grace period (should mark as Present)
        console.log('\nTest 2: Within grace period');
        const now = Date.now();
        const fiveMinutesAgo = now - (5 * 60 * 1000);
        sessionStorage.setItem('sessionStartTime', fiveMinutesAgo.toString());
        
        const status2 = calculateAttendanceStatus();
        console.log('Result within grace period:', status2);
        
        // Test 3: Beyond grace period (should mark as Late)
        console.log('\nTest 3: Beyond grace period');
        const twentyMinutesAgo = now - (20 * 60 * 1000);
        sessionStorage.setItem('sessionStartTime', twentyMinutesAgo.toString());
        
        const status3 = calculateAttendanceStatus();
        console.log('Result beyond grace period:', status3);
        
        // Test 4: Auto late disabled (should always mark as Present)
        console.log('\nTest 4: Auto late disabled');
        autoLateEnabled = false;
        
        const status4 = calculateAttendanceStatus();
        console.log('Result with auto late disabled:', status4);
        
        console.log('\n=== ATTENDANCE STATUS TEST COMPLETE ===');
        
    } finally {
        // Restore original settings
        gracePeriod = originalGracePeriod;
        autoLateEnabled = originalAutoLateEnabled;
        if (originalSessionStartTime) {
            sessionStorage.setItem('sessionStartTime', originalSessionStartTime);
        } else {
            sessionStorage.removeItem('sessionStartTime');
        }
    }
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
