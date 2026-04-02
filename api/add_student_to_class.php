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

if (!$data || !isset($data['subjectId']) || !isset($data['email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$subjectId = $data['subjectId'];
$email = $data['email'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

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

// Find student by email
try {
    $stmt = $db->prepare("
        SELECT s.StudentID, s.UserID, u.first_name, u.last_name 
        FROM students s 
        JOIN users u ON s.UserID = u.UserID 
        WHERE u.Email = ? AND u.Role = 'Student'
    ");
    $stmt->execute([$email]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No student found with this email address']);
        exit();
    }
} catch(PDOException $e) {
    error_log("Error finding student: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Check if student is already enrolled
try {
    $stmt = $db->prepare("SELECT EnrollmentID FROM enrollments WHERE SubjectID = ? AND StudentID = ?");
    $stmt->execute([$subjectId, $student['StudentID']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Student is already enrolled in this class']);
        exit();
    }
} catch(PDOException $e) {
    error_log("Error checking enrollment: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Enroll student
try {
    $stmt = $db->prepare("INSERT INTO enrollments (SubjectID, StudentID, DateEnrolled) VALUES (?, ?, CURDATE())");
    $stmt->execute([$subjectId, $student['StudentID']]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Student added successfully',
        'student' => [
            'id' => $student['StudentID'],
            'name' => $student['first_name'] . ' ' . $student['last_name']
        ]
    ]);
    
} catch(PDOException $e) {
    error_log("Error enrolling student: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to enroll student']);
    exit();
}
?>
