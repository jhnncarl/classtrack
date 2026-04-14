<?php
// Include database configuration
require_once '../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Helper function to map database status to display status
function mapStatus($status) {
    return $status === 'Closed' ? 'Completed' : $status;
}

// Check if session_id is provided
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session ID is required'
    ]);
    exit();
}

$session_id = intval($_GET['session_id']);

try {
    // Query to get session details with attendance statistics
    $query = "SELECT 
                s.SessionID,
                s.SubjectID,
                s.SessionDate,
                s.StartTime,
                s.EndTime,
                s.Status,
                sub.SubjectName,
                sub.SubjectCode,
                sub.ClassName,
                sub.SectionName,
                (SELECT COUNT(*) FROM enrollments e WHERE e.SubjectID = s.SubjectID) as total_students,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Present') as present_count,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Late') as late_count,
                (SELECT COUNT(*) FROM attendancerecords ar WHERE ar.SessionID = s.SessionID AND ar.AttendanceStatus = 'Absent') as absent_count
              FROM attendancesessions s
              JOIN subjects sub ON s.SubjectID = sub.SubjectID
              WHERE s.SessionID = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        // Ensure all counts are integers and handle null values
        $session['total_students'] = intval($session['total_students']);
        $session['present_count'] = intval($session['present_count']);
        $session['late_count'] = intval($session['late_count']);
        $session['absent_count'] = intval($session['absent_count']);
        
        // Map the status for display
        $session['Status'] = mapStatus($session['Status']);
        
        echo json_encode([
            'success' => true,
            'session' => $session
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Session not found'
        ]);
    }
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
