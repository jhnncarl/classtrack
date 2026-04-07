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

// Get session ID from request
$session_id = $_GET['session_id'] ?? $_POST['session_id'] ?? null;

if (!$session_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit();
}

try {
    // Get session and subject information
    $stmt = $db->prepare("
        SELECT 
            asess.SessionID,
            asess.SubjectID,
            asess.SessionDate,
            asess.StartTime,
            asess.EndTime,
            asess.Status,
            s.SubjectCode,
            s.SubjectName,
            s.ClassName,
            s.SectionName,
            t.TeacherID,
            CONCAT(u.first_name, ' ', u.last_name) as TeacherName
        FROM attendancesessions asess
        JOIN subjects s ON asess.SubjectID = s.SubjectID
        JOIN teachers t ON s.TeacherID = t.TeacherID
        JOIN users u ON t.UserID = u.UserID
        WHERE asess.SessionID = ? AND t.UserID = ?
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session_info = $stmt->fetch();

    if (!$session_info) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session not found or access denied']);
        exit();
    }

    // Get present and late students for this session
    $stmt = $db->prepare("
        SELECT 
            ar.RecordID,
            ar.ScanTime,
            ar.AttendanceStatus,
            st.StudentID,
            st.StudentNumber,
            st.Course,
            st.YearLevel,
            CONCAT(u.first_name, ' ', u.last_name) as StudentName,
            u.Email
        FROM attendancerecords ar
        JOIN students st ON ar.StudentID = st.StudentID
        JOIN users u ON st.UserID = u.UserID
        WHERE ar.SessionID = ? AND ar.AttendanceStatus IN ('Present', 'Late')
        ORDER BY ar.ScanTime ASC
    ");
    $stmt->execute([$session_id]);
    $present_students = $stmt->fetchAll();

    // Generate PDF
    generatePDFReport($session_info, $present_students);

} catch (PDOException $e) {
    error_log("Error generating PDF report: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit();
}

function generatePDFReport($session_info, $present_students) {
    // Create PDF content
    $pdf_content = createPDFContent($session_info, $present_students);
    
    // Generate filename with subject name and date
    $subject_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $session_info['SubjectName']); // Remove special characters
    $subject_name = preg_replace('/\s+/', '_', $subject_name); // Replace spaces with underscores
    $date = date('Y-m-d', strtotime($session_info['SessionDate']));
    $filename = "{$subject_name}_Attendance_Report_{$date}.pdf";
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output PDF
    echo $pdf_content;
    exit();
}

function createPDFContent($session_info, $present_students) {
    // Create a very basic, working PDF
    $pdf = "%PDF-1.4\n";
    
    // Simple text content with minimal positioning
    $content = "BT\n/F1 10 Tf\n";
    
    // Header - Bigger and Bold title with narrow margin
    $content .= "30 750 Td\n/F2 16 Tf (ClassTrack Attendance Report) Tj\n/F1 10 Tf\n";
    $content .= "0 -25 Td (Subject: " . $session_info['SubjectName'] . ") Tj\n";
    $content .= "0 -12 Td (Class: " . $session_info['ClassName'] . " - " . $session_info['SectionName'] . ") Tj\n";
    $content .= "0 -12 Td (Date: " . date('F d, Y', strtotime($session_info['SessionDate'])) . ") Tj\n";
    $content .= "0 -12 Td (Time: " . date('h:i A', strtotime($session_info['StartTime'])) . ") Tj\n";
    $content .= "0 -12 Td (Teacher: " . $session_info['TeacherName'] . ") Tj\n";
    $content .= "0 -20 Td (ATTENDED STUDENTS: " . count($present_students) . "                                                         Room No. ______) Tj\n";
    $content .= "0 -15 Td (--------------------------------------------------------------------------------------------) Tj\n";
    
    // Headers
    $content .= "0 -15 Td\n/F2 10 Tf (Student Number              Name                           Email                      Status) Tj\n/F1 10 Tf\n";
    $content .= "0 -15 Td (--------------------------------------------------------------------------------------------) Tj\n";
    
    // Student data
    if (empty($present_students)) {
        $content .= "0 -15 Td (No students attended this session.) Tj\n";
    } else {
        foreach ($present_students as $student) {
            $status = ($student['AttendanceStatus'] === 'Late') ? 'Late' : 'Present';
            $email = $student['Email'] ?? 'N/A'; // Use student email or N/A if not available
            
            // Create properly spaced line - aligned to header fields
            $student_line = sprintf("%-21s %-26s %-35s %6s", 
                $student['StudentNumber'],
                $student['StudentName'],
                $email,
                $status
            );
            
            $content .= "0 -15 Td (" . $student_line . ") Tj\n";
        }
    }
    
    $content .= "ET\n";
    
    // Add signature field - position in bottom right corner
    $content .= "BT\n/F1 10 Tf\n";
    $content .= "412 60 Td (___________________________) Tj\n";
    $content .= "0 -15 Td (         Signature) Tj\n";
    $content .= "ET\n";
    
    // Build PDF with minimal structure
    $objects = [];
    
    // Object 1: Catalog
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    
    // Object 2: Pages
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    
    // Object 3: Page
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>";
    
    // Object 4: Content
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    
    // Object 5: Font - Use Courier (typewriter style)
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";
    
    // Object 6: Font - Use Courier-Bold for title
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>";
    
    // Build PDF
    $xref = [];
    foreach ($objects as $i => $obj) {
        $xref[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
    }
    
    // Cross-reference table
    $xref_start = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($xref as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    
    // Trailer
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref_start\n%%EOF";
    
    return $pdf;
}
?>
