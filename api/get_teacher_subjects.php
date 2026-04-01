<?php
// API endpoint to get teacher's subjects
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

    // Get teacher's subjects from database
    $stmt = $db->prepare("SELECT SubjectID, SubjectCode, SubjectName, ClassName, SectionName, Schedule FROM subjects WHERE TeacherID = ? ORDER BY SubjectName");
    $stmt->execute([$teacher_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_teacher_subjects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    error_log("General error in get_teacher_subjects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
