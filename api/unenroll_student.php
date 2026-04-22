<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Student') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../config/database.php';
require_once '../config/permissions.php';

// Get POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['subject_id']) || empty($data['subject_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Subject ID is required']);
    exit();
}

$subject_id = intval($data['subject_id']);
$user_id = $_SESSION['user_id'];

// Check if user has permission to unenroll from classes
$permissions = new Permissions();
if (!$permissions->hasPermission($user_id, 'student_unenrollClass')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This feature is currently unavailable. Please contact your administrator to enable access.']);
    exit();
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get student ID from user ID
    $student_query = "SELECT StudentID FROM students WHERE UserID = ?";
    $student_stmt = $db->prepare($student_query);
    $student_stmt->execute([$user_id]);
    $student_row = $student_stmt->fetch();
    
    if (!$student_row) {
        throw new Exception('Student record not found');
    }
    
    $student_id = $student_row['StudentID'];
    
    // Check if student is enrolled in the subject
    $check_enrollment_query = "SELECT EnrollmentID FROM enrollments WHERE StudentID = ? AND SubjectID = ?";
    $check_stmt = $db->prepare($check_enrollment_query);
    $check_stmt->execute([$student_id, $subject_id]);
    $check_result = $check_stmt->fetch();
    
    if (!$check_result) {
        throw new Exception('You are not enrolled in this subject');
    }
    
    // Delete enrollment record
    $unenroll_query = "DELETE FROM enrollments WHERE StudentID = ? AND SubjectID = ?";
    $unenroll_stmt = $db->prepare($unenroll_query);
    $unenroll_stmt->execute([$student_id, $subject_id]);
    
    $affected_rows = $unenroll_stmt->rowCount();
    
    if ($affected_rows === 0) {
        throw new Exception('No enrollment record found to delete');
    }
    
    // Commit transaction
    $db->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Successfully unenrolled from subject']);
    
} catch (PDOException $e) {
    // Rollback transaction
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Rollback transaction
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
