<?php
// API endpoint to get dashboard statistics
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Start session
session_start();

// Check if user is logged in as Teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Teacher') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../config/database.php';

try {
    // Get teacher's ID from users table
    $teacher_id = null;
    $stmt = $db->prepare("SELECT t.TeacherID FROM teachers t JOIN users u ON t.UserID = u.UserID WHERE u.UserID = ? AND u.Role = 'Teacher'");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_data = $stmt->fetch();
    $teacher_id = $teacher_data ? $teacher_data['TeacherID'] : null;

    if (!$teacher_id) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        exit();
    }

    // Get total classes
    $stmt = $db->prepare("SELECT COUNT(*) as total_classes FROM subjects WHERE TeacherID = ?");
    $stmt->execute([$teacher_id]);
    $total_classes = $stmt->fetch()['total_classes'];

    // Get total students (count unique students enrolled in teacher's subjects)
    $stmt = $db->prepare("SELECT COUNT(DISTINCT e.StudentID) as total_students 
                         FROM enrollments e 
                         JOIN subjects s ON e.SubjectID = s.SubjectID 
                         WHERE s.TeacherID = ?");
    $stmt->execute([$teacher_id]);
    $total_students = $stmt->fetch()['total_students'];

    // Get active attendance sessions today
    $stmt = $db->prepare("SELECT COUNT(*) as active_sessions 
                         FROM attendancesessions 
                         WHERE SubjectID IN (SELECT SubjectID FROM subjects WHERE TeacherID = ?) 
                         AND SessionDate = CURDATE() 
                         AND Status = 'Active'");
    $stmt->execute([$teacher_id]);
    $active_sessions = $stmt->fetch()['active_sessions'];

    // Return success response
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalClasses' => (int)$total_classes,
            'totalStudents' => (int)$total_students,
            'activeSessions' => (int)$active_sessions
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_dashboard_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log("General error in get_dashboard_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
