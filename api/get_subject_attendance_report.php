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

// Get subject ID from request
$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
    exit();
}

try {
    // Get teacher's ID to verify ownership
    $stmt = $db->prepare("SELECT TeacherID FROM teachers WHERE UserID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_data = $stmt->fetch();
    $teacher_id = $teacher_data ? $teacher_data['TeacherID'] : null;

    if (!$teacher_id) {
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit();
    }

    // Verify teacher owns this subject
    $stmt = $db->prepare("SELECT SubjectID, SubjectCode, SubjectName, ClassName, SectionName FROM subjects WHERE SubjectID = ? AND TeacherID = ?");
    $stmt->execute([$subject_id, $teacher_id]);
    $subject_info = $stmt->fetch();

    if (!$subject_info) {
        echo json_encode(['success' => false, 'message' => 'Subject not found or access denied']);
        exit();
    }

    // Get total sessions conducted for this subject
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_sessions 
        FROM attendancesessions 
        WHERE SubjectID = ? AND Status = 'Closed'
    ");
    $stmt->execute([$subject_id]);
    $total_sessions = $stmt->fetch()['total_sessions'];

    // Get all enrolled students for this subject
    $stmt = $db->prepare("
        SELECT DISTINCT
            s.StudentID,
            s.StudentNumber,
            st.Course,
            st.YearLevel,
            CONCAT(u.first_name, ' ', u.last_name) as StudentName,
            u.Email
        FROM enrollments e
        JOIN students s ON e.StudentID = s.StudentID
        JOIN users u ON s.UserID = u.UserID
        JOIN students st ON s.StudentID = st.StudentID
        WHERE e.SubjectID = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$subject_id]);
    $enrolled_students = $stmt->fetchAll();

    // Get attendance statistics for each student
    $student_stats = [];
    foreach ($enrolled_students as $student) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN ar.AttendanceStatus = 'Present' THEN 1 END) as present_count,
                COUNT(CASE WHEN ar.AttendanceStatus = 'Late' THEN 1 END) as late_count,
                COUNT(CASE WHEN ar.AttendanceStatus = 'Absent' THEN 1 END) as absent_count,
                COUNT(ar.RecordID) as total_attended
            FROM attendancerecords ar
            JOIN attendancesessions sess ON ar.SessionID = sess.SessionID
            WHERE ar.StudentID = ? AND sess.SubjectID = ? AND sess.Status = 'Closed'
        ");
        $stmt->execute([$student['StudentID'], $subject_id]);
        $attendance_data = $stmt->fetch();

        $present_count = (int)$attendance_data['present_count'];
        $late_count = (int)$attendance_data['late_count'];
        $absent_count = (int)$attendance_data['absent_count'];
        $total_attended = (int)$attendance_data['total_attended'];
        
        // Calculate attendance percentage based on actual attendance records
        $total_recorded = $present_count + $late_count + $absent_count;
        $attendance_percentage = $total_recorded > 0 ? round((($present_count + $late_count) / $total_recorded) * 100, 2) : 0;

        $student_stats[] = [
            'StudentID' => $student['StudentID'],
            'StudentNumber' => $student['StudentNumber'],
            'StudentName' => $student['StudentName'],
            'Email' => $student['Email'],
            'Course' => $student['Course'],
            'YearLevel' => $student['YearLevel'],
            'PresentCount' => $present_count,
            'LateCount' => $late_count,
            'TotalAttended' => $total_attended,
            'TotalSessions' => $total_sessions,
            'AttendancePercentage' => $attendance_percentage,
            'AbsentCount' => $absent_count
        ];
    }

    // Get session details for reference
    $stmt = $db->prepare("
        SELECT SessionID, SessionDate, StartTime, EndTime, Status
        FROM attendancesessions 
        WHERE SubjectID = ? 
        ORDER BY SessionDate DESC, StartTime DESC
    ");
    $stmt->execute([$subject_id]);
    $sessions = $stmt->fetchAll();

    // Calculate overall statistics
    $total_present = array_sum(array_column($student_stats, 'PresentCount'));
    $total_late = array_sum(array_column($student_stats, 'LateCount'));
    $total_absent = array_sum(array_column($student_stats, 'AbsentCount'));
    $overall_attendance_rate = $total_sessions > 0 ? round((($total_present + $total_late) / (count($student_stats) * $total_sessions)) * 100, 2) : 0;

    $response = [
        'success' => true,
        'subject_info' => $subject_info,
        'total_sessions' => $total_sessions,
        'student_stats' => $student_stats,
        'sessions' => $sessions,
        'summary' => [
            'total_students' => count($student_stats),
            'total_present' => $total_present,
            'total_late' => $total_late,
            'total_absent' => $total_absent,
            'overall_attendance_rate' => $overall_attendance_rate
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error getting subject attendance report: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
