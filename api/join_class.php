<?php
// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Require authentication and database
require_once '../config/auth.php';
require_once '../config/database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Debug: Log incoming request
    error_log("Join class API called");
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Current user role: " . $auth->getUserRole());
    
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        $response['message'] = 'Please log in to join a class';
        error_log("User not logged in");
        echo json_encode($response);
        exit;
    }
    
    // Check if user is a student (NOT teacher or admin)
    if ($auth->getUserRole() !== 'Student') {
        $response['message'] = 'Only students can join classes';
        error_log("User is not a student - Role: " . $auth->getUserRole());
        echo json_encode($response);
        exit;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    error_log("Received data: " . print_r($data, true));
    
    if (!$data) {
        $response['message'] = 'Invalid request data';
        error_log("Invalid request data received");
        echo json_encode($response);
        exit;
    }

    // Validate class code
    if (empty(trim($data['classCode']))) {
        $response['message'] = 'Class code is required';
        echo json_encode($response);
        exit;
    }

    $classCode = strtoupper(trim($data['classCode']));
    
    // Validate class code format (5-8 characters, alphanumeric)
    if (!preg_match('/^[A-Z0-9]{5,8}$/', $classCode)) {
        $response['message'] = 'Invalid class code format';
        echo json_encode($response);
        exit;
    }

    // Get current user's StudentID
    $userId = $_SESSION['user_id'];
    error_log("Joining class - User ID: " . $userId);
    
    $db = (new Database())->getConnection();
    
    // Get StudentID from students table
    $stmt = $db->prepare("SELECT StudentID FROM students WHERE UserID = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();
    
    error_log("Student query result: " . print_r($student, true));
    
    if (!$student) {
        error_log("Student account not found for UserID: " . $userId);
        $response['message'] = 'Student account not found';
        echo json_encode($response);
        exit;
    }
    
    $studentId = $student['StudentID'];
    error_log("Student ID found: " . $studentId);
    
    // Check if subject exists with the given class code
    error_log("Searching for subject with code: " . $classCode);
    
    $stmt = $db->prepare("
        SELECT s.SubjectID, s.SubjectName, s.SubjectCode, s.ClassName, s.SectionName, s.TeacherID,
               u.first_name, u.last_name
        FROM subjects s
        JOIN teachers t ON s.TeacherID = t.TeacherID
        JOIN users u ON t.UserID = u.UserID
        WHERE s.SubjectCode = ?
    ");
    $stmt->execute([$classCode]);
    $subject = $stmt->fetch();
    
    error_log("Subject query result: " . ($subject ? 'FOUND' : 'NOT FOUND'));
    if ($subject) {
        error_log("Found subject: " . print_r($subject, true));
    }
    
    if (!$subject) {
        $response['message'] = 'Invalid class code. No class found with this code.';
        error_log("Subject not found with code: " . $classCode);
        echo json_encode($response);
        exit;
    }
    
    $subjectId = $subject['SubjectID'];
    
    // Check if student is already enrolled in this subject
    error_log("Checking if student $studentId is already enrolled in subject $subjectId");
    
    $stmt = $db->prepare("SELECT EnrollmentID FROM enrollments WHERE StudentID = ? AND SubjectID = ?");
    $stmt->execute([$studentId, $subjectId]);
    $existingEnrollment = $stmt->fetch();
    
    if ($existingEnrollment) {
        $response['success'] = false;
        $response['message'] = 'You are already enrolled in this subject';
        error_log("Student already enrolled in subject");
        echo json_encode($response);
        exit;
    }
    
    // Enroll the student in the subject
    $stmt = $db->prepare("
        INSERT INTO enrollments (StudentID, SubjectID, DateEnrolled) 
        VALUES (?, ?, CURDATE())
    ");
    
    error_log("Executing enrollment insert with params: " . $studentId . ", " . $subjectId);
    
    $result = $stmt->execute([$studentId, $subjectId]);
    
    error_log("Enrollment execution result: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("PDO Error info: " . print_r($stmt->errorInfo(), true));
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Successfully joined the class!';
        $response['data'] = [
            'subjectId' => $subjectId,
            'subjectCode' => $subject['SubjectCode'],
            'subjectName' => $subject['SubjectName'],
            'className' => $subject['ClassName'],
            'sectionName' => $subject['SectionName'],
            'teacherName' => $subject['first_name'] . ' ' . $subject['last_name'],
            'studentName' => $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']
        ];
        error_log("Student successfully enrolled in subject ID: " . $subjectId);
    } else {
        $response['message'] = 'Failed to join class';
        error_log("Failed to enroll student - PDO Error: " . print_r($stmt->errorInfo(), true));
    }
    
} catch (Exception $e) {
    error_log("Error joining class: " . $e->getMessage());
    $response['message'] = 'An error occurred while joining the class';
}

// Return response
echo json_encode($response);
?>
