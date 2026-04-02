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

// Get JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['subjectId']) || !isset($data['studentId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$subjectId = $data['subjectId'];
$studentId = $data['studentId'];

// Get teacher's ID from session
$teacher_id = null;
try {
    $stmt = $db->prepare("SELECT t.TeacherID FROM teachers t JOIN users u ON t.UserID = u.UserID WHERE u.UserID = ? AND u.Role = 'Teacher'");
    $stmt->execute([$_SESSION['user_id']]);
    $teacher_data = $stmt->fetch();
    $teacher_id = $teacher_data ? $teacher_data['TeacherID'] : null;
} catch(PDOException $e) {
    error_log("Error getting teacher ID: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

if (!$teacher_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    exit();
}

// Verify that the subject belongs to this teacher
try {
    $stmt = $db->prepare("SELECT SubjectID FROM subjects WHERE SubjectID = ? AND TeacherID = ?");
    $stmt->execute([$subjectId, $teacher_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Subject not found or access denied']);
        exit();
    }
} catch(PDOException $e) {
    error_log("Error verifying subject ownership: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Check if enrollment exists
try {
    $stmt = $db->prepare("SELECT EnrollmentID FROM enrollments WHERE SubjectID = ? AND StudentID = ?");
    $stmt->execute([$subjectId, $studentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Student is not enrolled in this class']);
        exit();
    }
} catch(PDOException $e) {
    error_log("Error checking enrollment: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Remove student enrollment
try {
    $stmt = $db->prepare("DELETE FROM enrollments WHERE SubjectID = ? AND StudentID = ?");
    $stmt->execute([$subjectId, $studentId]);
    
    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Student removed successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove student']);
    }
    
} catch(PDOException $e) {
    error_log("Error removing student: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to remove student']);
    exit();
}
?>
