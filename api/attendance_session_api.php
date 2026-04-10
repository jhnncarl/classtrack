<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Get input - support both JSON and raw data for sendBeacon
$input = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawInput = file_get_contents('php://input');

// Debug logging
error_log("Attendance API Request - Content-Type: " . $contentType);
error_log("Attendance API Request - Raw Input: " . $rawInput);
error_log("Attendance API Request - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// Try to parse JSON regardless of Content-Type
if ($rawInput) {
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Attendance API Error - JSON parse error: " . json_last_error_msg());
        $input = null;
    }
}

if (!$input || !isset($input['action'])) {
    error_log("Attendance API Error - Invalid input or missing action");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$action = $input['action'];
error_log("Attendance API Action: " . $action);
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'update_status':
            $session_id = $input['session_id'] ?? null;
            $status = $input['status'] ?? null;
            
            if (!$session_id || !$status) {
                $response['message'] = 'Missing session ID or status';
                break;
            }
            
            // Update session status (Note: Status field only supports 'Active' and 'Closed' in DB)
            // For pause/resume, we'll keep the session as 'Active' and handle pause state in frontend
            if ($status === 'Closed') {
                $stmt = $db->prepare("UPDATE attendancesessions SET Status = 'Closed', EndTime = CURTIME() WHERE SessionID = ?");
                $stmt->execute([$session_id]);
            }
            
            $response['success'] = true;
            $response['message'] = 'Session status updated successfully';
            break;
            
        case 'close_session':
            $session_id = $input['session_id'] ?? null;
            
            error_log("Close Session Request - Session ID: " . $session_id);
            
            if (!$session_id) {
                $response['message'] = 'Missing session ID';
                error_log("Close Session Error - Missing session ID");
                break;
            }
            
            // Close the session
            $stmt = $db->prepare("UPDATE attendancesessions SET Status = 'Closed', EndTime = CURTIME() WHERE SessionID = ? AND Status = 'Active'");
            $stmt->execute([$session_id]);
            
            $rowCount = $stmt->rowCount();
            error_log("Close Session Result - Rows affected: " . $rowCount);
            
            if ($rowCount > 0) {
                $response['success'] = true;
                $response['message'] = 'Session closed successfully';
                error_log("Close Session Success - Session ID " . $session_id . " closed");
            } else {
                // Check if session exists and its current status
                $checkStmt = $db->prepare("SELECT Status, EndTime FROM attendancesessions WHERE SessionID = ?");
                $checkStmt->execute([$session_id]);
                $sessionData = $checkStmt->fetch();
                
                if ($sessionData) {
                    error_log("Close Session Info - Session exists with status: " . $sessionData['Status'] . ", EndTime: " . $sessionData['EndTime']);
                    $response['success'] = true;
                    $response['message'] = 'Session already closed';
                } else {
                    error_log("Close Session Error - Session not found");
                    $response['success'] = false;
                    $response['message'] = 'Session not found';
                }
            }
            break;
            
        case 'validate_student':
            $student_number = $input['student_number'] ?? null;
            $subject_id = $input['subject_id'] ?? null;
            
            error_log("Validate Student Request - Student Number: " . $student_number . ", Subject ID: " . $subject_id);
            
            if (!$student_number || !$subject_id) {
                $response['message'] = 'Missing student number or subject ID';
                break;
            }
            
            // Check if student exists and is enrolled in the subject
            $stmt = $db->prepare("
                SELECT s.StudentID, s.StudentNumber, s.Course, s.YearLevel,
                       u.first_name, u.last_name, u.Email, u.ProfilePicture
                FROM students s
                JOIN users u ON s.UserID = u.UserID
                JOIN enrollments e ON s.StudentID = e.StudentID
                WHERE s.StudentNumber = ? AND e.SubjectID = ?
            ");
            $stmt->execute([$student_number, $subject_id]);
            $student = $stmt->fetch();
            
            if ($student) {
                $response['success'] = true;
                $response['message'] = 'Student validated successfully';
                $response['student'] = [
                    'id' => $student['StudentID'],
                    'student_number' => $student['StudentNumber'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'course' => $student['Course'],
                    'year' => $student['YearLevel'] . ' Year',
                    'email' => $student['Email'],
                    'profile_picture' => $student['ProfilePicture']
                ];
                error_log("Validate Student Success - Found: " . $student['first_name'] . ' ' . $student['last_name']);
            } else {
                $response['success'] = false;
                $response['message'] = 'Student not found or not enrolled in this subject';
                error_log("Validate Student Error - Student not enrolled");
            }
            break;
            
        case 'record_attendance':
            $session_id = $input['session_id'] ?? null;
            $student_id = $input['student_id'] ?? null;
            $attendance_status = $input['attendance_status'] ?? 'Present';
            
            error_log("Record Attendance Request - Session ID: " . $session_id . ", Student ID: " . $student_id . ", Status: " . $attendance_status);
            
            if (!$session_id || !$student_id) {
                $response['message'] = 'Missing session ID or student ID';
                break;
            }
            
            // Check if session is still active
            $stmt = $db->prepare("SELECT Status FROM attendancesessions WHERE SessionID = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
            
            if (!$session) {
                $response['success'] = false;
                $response['message'] = 'Session not found';
                error_log("Record Attendance Error - Session not found: " . $session_id);
                break;
            }
            
            if ($session['Status'] !== 'Active') {
                $response['success'] = false;
                $response['message'] = 'Session is not active - scanning not allowed';
                error_log("Record Attendance Error - Session not active: " . $session['Status']);
                break;
            }
            
            // Check if attendance record already exists for this student and session
            $stmt = $db->prepare("SELECT RecordID FROM attendancerecords WHERE SessionID = ? AND StudentID = ?");
            $stmt->execute([$session_id, $student_id]);
            $existing_record = $stmt->fetch();
            
            if ($existing_record) {
                $response['success'] = false;
                $response['message'] = 'Attendance already recorded for this student';
                error_log("Record Attendance Error - Duplicate record for Student ID: " . $student_id);
            } else {
                // Create new attendance record
                $stmt = $db->prepare("
                    INSERT INTO attendancerecords (SessionID, StudentID, ScanTime, AttendanceStatus) 
                    VALUES (?, ?, NOW(), ?)
                ");
                $stmt->execute([$session_id, $student_id, $attendance_status]);
                
                if ($stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Attendance recorded successfully';
                    $response['record_id'] = $db->lastInsertId();
                    error_log("Record Attendance Success - Record ID: " . $db->lastInsertId());
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Failed to record attendance';
                    error_log("Record Attendance Error - Database insert failed");
                }
            }
            break;
            
        case 'mark_absent_students':
            $session_id = $input['session_id'] ?? null;
            
            error_log("Mark Absent Students Request - Session ID: " . $session_id);
            
            if (!$session_id) {
                $response['message'] = 'Missing session ID';
                break;
            }
            
            // Get session and subject information
            $stmt = $db->prepare("
                SELECT asess.SessionID, asess.SubjectID, sub.SubjectName 
                FROM attendancesessions asess
                JOIN subjects sub ON asess.SubjectID = sub.SubjectID
                WHERE asess.SessionID = ?
            ");
            $stmt->execute([$session_id]);
            $session_info = $stmt->fetch();
            
            if (!$session_info) {
                $response['success'] = false;
                $response['message'] = 'Session not found';
                break;
            }
            
            // Get all enrolled students for this subject
            $stmt = $db->prepare("
                SELECT DISTINCT s.StudentID, s.StudentNumber, u.first_name, u.last_name
                FROM students s
                JOIN users u ON s.UserID = u.UserID
                JOIN enrollments e ON s.StudentID = e.StudentID
                WHERE e.SubjectID = ?
            ");
            $stmt->execute([$session_info['SubjectID']]);
            $enrolled_students = $stmt->fetchAll();
            
            if (empty($enrolled_students)) {
                $response['success'] = true;
                $response['message'] = 'No students enrolled in this subject';
                $response['marked_absent'] = 0;
                break;
            }
            
            // Get students who already have attendance records for this session
            $enrolled_ids = array_column($enrolled_students, 'StudentID');
            $placeholders = str_repeat('?,', count($enrolled_ids) - 1) . '?';
            
            $stmt = $db->prepare("
                SELECT DISTINCT StudentID 
                FROM attendancerecords 
                WHERE SessionID = ? AND StudentID IN ($placeholders)
            ");
            $stmt->execute(array_merge([$session_id], $enrolled_ids));
            $recorded_students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Find students who don't have attendance records (mark as absent)
            $absent_students = array_diff($enrolled_ids, $recorded_students);
            $marked_count = 0;
            
            if (!empty($absent_students)) {
                // Insert absent records for students who didn't scan
                $values = [];
                $params = [];
                
                foreach ($absent_students as $student_id) {
                    $values[] = "(?, ?, NOW(), 'Absent')";
                    $params[] = $session_id;
                    $params[] = $student_id;
                }
                
                $sql = "
                    INSERT INTO attendancerecords (SessionID, StudentID, ScanTime, AttendanceStatus) 
                    VALUES " . implode(',', $values) . "
                ";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $marked_count = $stmt->rowCount();
                
                error_log("Mark Absent Students Success - Marked " . $marked_count . " students as absent for session " . $session_id);
            }
            
            $response['success'] = true;
            $response['message'] = "Successfully marked {$marked_count} students as absent";
            $response['marked_absent'] = $marked_count;
            $response['total_enrolled'] = count($enrolled_students);
            $response['already_recorded'] = count($recorded_students);
            break;
            
        default:
            $response['message'] = 'Unknown action';
            break;
    }
} catch (PDOException $e) {
    error_log("Database error in attendance_session_api.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
