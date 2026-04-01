<?php
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
    // Check if user is logged in and is a teacher
    if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'Teacher') {
        $response['message'] = 'Unauthorized access';
        echo json_encode($response);
        exit;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $response['message'] = 'Invalid request data';
        echo json_encode($response);
        exit;
    }

    // Validate required fields
    $requiredFields = ['className', 'section', 'subject'];
    foreach ($requiredFields as $field) {
        if (empty(trim($data[$field]))) {
            $response['message'] = ucfirst($field) . ' is required';
            echo json_encode($response);
            exit;
        }
    }

    // Get current user's TeacherID
    $userId = $_SESSION['user_id'];
    error_log("Creating class - User ID: " . $userId);
    
    $db = (new Database())->getConnection();
    
    // Get TeacherID from teachers table
    $stmt = $db->prepare("SELECT TeacherID FROM teachers WHERE UserID = ?");
    $stmt->execute([$userId]);
    $teacher = $stmt->fetch();
    
    error_log("Teacher query result: " . print_r($teacher, true));
    
    if (!$teacher) {
        error_log("Teacher account not found for UserID: " . $userId);
        $response['message'] = 'Teacher account not found';
        echo json_encode($response);
        exit;
    }
    
    $teacherId = $teacher['TeacherID'];
    error_log("Teacher ID found: " . $teacherId);
    
    // Generate unique subject code
    do {
        $subjectCode = generateSubjectCode();
        error_log("Generated subject code: " . $subjectCode);
        
        // Check if subject code already exists
        $stmt = $db->prepare("SELECT SubjectID FROM subjects WHERE SubjectCode = ?");
        $stmt->execute([$subjectCode]);
        $existing = $stmt->fetch();
        
        error_log("Subject code exists check: " . ($existing ? 'YES' : 'NO'));
        
    } while ($existing); // Keep generating until we find a unique code
    
    // Sanitize input data
    $className = trim($data['className']);
    $section = trim($data['section']);
    $subject = trim($data['subject']);
    $schedule = isset($data['schedule']) ? trim($data['schedule']) : null;
    
    error_log("Class data - Name: " . $className . ", Section: " . $section . ", Subject: " . $subject . ", Schedule: " . $schedule);
    
    // Insert new subject/class
    $stmt = $db->prepare("
        INSERT INTO subjects (SubjectCode, SubjectName, TeacherID, Schedule, ClassName, SectionName) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    error_log("Executing insert with params: " . $subjectCode . ", " . $subject . ", " . $teacherId . ", " . $schedule . ", " . $className . ", " . $section);
    
    $result = $stmt->execute([
        $subjectCode,
        $subject,
        $teacherId,
        $schedule,
        $className,
        $section
    ]);
    
    error_log("Insert execution result: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("PDO Error info: " . print_r($stmt->errorInfo(), true));
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Class created successfully';
        $response['data'] = [
            'subjectCode' => $subjectCode,
            'subjectId' => $db->lastInsertId()
        ];
        error_log("Class created successfully with ID: " . $db->lastInsertId());
    } else {
        $response['message'] = 'Failed to create class';
        error_log("Failed to create class - PDO Error: " . print_r($stmt->errorInfo(), true));
    }
    
} catch (Exception $e) {
    error_log("Error creating class: " . $e->getMessage());
    $response['message'] = 'An error occurred while creating the class';
}

// Return response
echo json_encode($response);

/**
 * Generate a unique subject code
 * Format: 5-8 characters (mix of lowercase letters and numbers)
 */
function generateSubjectCode() {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $length = rand(5, 8); // Random length between 5-8 characters
    
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}
?>
