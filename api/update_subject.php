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

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($input['subjectId']) || !isset($input['subjectName']) || 
            !isset($input['className']) || !isset($input['section']) || !isset($input['schedule'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        $subjectId = intval($input['subjectId']);
        $subjectName = trim($input['subjectName']);
        $className = trim($input['className']);
        $section = trim($input['section']);
        $schedule = trim($input['schedule']);
        
        // Validate inputs
        if (empty($subjectName) || empty($className) || empty($section) || empty($schedule)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
        
        // Verify that the subject belongs to this teacher
        $verifyStmt = $db->prepare("SELECT SubjectID FROM subjects WHERE SubjectID = ? AND TeacherID = ?");
        $verifyStmt->execute([$subjectId, $teacher['TeacherID']]);
        if (!$verifyStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Subject not found or access denied']);
            exit();
        }
        
        // Update the subject
        $updateStmt = $db->prepare("
            UPDATE subjects 
            SET SubjectName = ?, ClassName = ?, SectionName = ?, Schedule = ? 
            WHERE SubjectID = ? AND TeacherID = ?
        ");
        
        $result = $updateStmt->execute([$subjectName, $className, $section, $schedule, $subjectId, $teacher['TeacherID']]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Subject updated successfully',
                'data' => [
                    'subjectId' => $subjectId,
                    'subjectName' => $subjectName,
                    'className' => $className,
                    'section' => $section,
                    'schedule' => $schedule
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update subject']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in update_subject.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("General error in update_subject.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
