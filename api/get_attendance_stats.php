<?php
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get student ID and subject ID from query parameters
        $studentId = isset($_GET['studentId']) ? intval($_GET['studentId']) : 0;
        $subjectId = isset($_GET['subjectId']) ? intval($_GET['subjectId']) : 0;
        
        if ($studentId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
            exit();
        }
        
        if ($subjectId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
            exit();
        }
        
        // Get teacher's ID to verify ownership
        $teacherStmt = $db->prepare("SELECT t.TeacherID FROM teachers t JOIN users u ON t.UserID = u.UserID WHERE u.UserID = ? AND u.Role = 'Teacher'");
        $teacherStmt->execute([$_SESSION['user_id']]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Teacher not found']);
            exit();
        }
        
        // Verify that the student belongs to the specific subject taught by this teacher
        $verifyStmt = $db->prepare("
            SELECT e.StudentID, e.SubjectID 
            FROM enrollments e 
            JOIN subjects s ON e.SubjectID = s.SubjectID 
            WHERE e.StudentID = ? AND e.SubjectID = ? AND s.TeacherID = ?
        ");
        $verifyStmt->execute([$studentId, $subjectId, $teacher['TeacherID']]);
        
        if (!$verifyStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Student not found in this subject or access denied']);
            exit();
        }
        
        // Get attendance statistics for the student in the specific subject
        $statsStmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN ar.AttendanceStatus = 'Present' THEN 1 END) as present_count,
                COUNT(CASE WHEN ar.AttendanceStatus = 'Late' THEN 1 END) as late_count,
                COUNT(CASE WHEN ar.AttendanceStatus = 'Absent' THEN 1 END) as absent_count,
                COUNT(*) as total_sessions
            FROM attendancerecords ar
            JOIN attendancesessions asess ON ar.SessionID = asess.SessionID
            WHERE ar.StudentID = ? AND asess.SubjectID = ?
        ");
        
        $statsStmt->execute([$studentId, $subjectId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats && $stats['total_sessions'] > 0) {
            $present = round(($stats['present_count'] / $stats['total_sessions']) * 100);
            $late = round(($stats['late_count'] / $stats['total_sessions']) * 100);
            $absent = round(($stats['absent_count'] / $stats['total_sessions']) * 100);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'total_sessions' => $stats['total_sessions'],
                    'present_count' => $stats['present_count'],
                    'late_count' => $stats['late_count'],
                    'absent_count' => $stats['absent_count']
                ]
            ]);
        } else {
            // No attendance records found, return 0% for all
            echo json_encode([
                'success' => true,
                'data' => [
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'total_sessions' => 0,
                    'present_count' => 0,
                    'late_count' => 0,
                    'absent_count' => 0
                ]
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in get_attendance_stats.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("General error in get_attendance_stats.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
