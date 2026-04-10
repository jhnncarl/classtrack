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
$subject_id = $_GET['subject_id'] ?? $_POST['subject_id'] ?? null;

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

    // Verify teacher owns this subject and get subject info
    $stmt = $db->prepare("
        SELECT s.SubjectID, s.SubjectCode, s.SubjectName, s.ClassName, s.SectionName,
               CONCAT(u.first_name, ' ', u.last_name) as TeacherName
        FROM subjects s
        JOIN teachers t ON s.TeacherID = t.TeacherID
        JOIN users u ON t.UserID = u.UserID
        WHERE s.SubjectID = ? AND s.TeacherID = ?
    ");
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
        
        // Calculate attendance percentage based on actual records only
        $total_records = $present_count + $late_count + $absent_count;
        $attendance_percentage = $total_records > 0 ? round((($present_count + $late_count) / $total_records) * 100, 2) : 0;

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

    // Calculate overall statistics
    $total_present = array_sum(array_column($student_stats, 'PresentCount'));
    $total_late = array_sum(array_column($student_stats, 'LateCount'));
    $total_absent = array_sum(array_column($student_stats, 'AbsentCount'));
    $overall_attendance_rate = $total_sessions > 0 ? round((($total_present + $total_late) / (count($student_stats) * $total_sessions)) * 100, 2) : 0;

    $summary = [
        'total_students' => count($student_stats),
        'total_present' => $total_present,
        'total_late' => $total_late,
        'total_absent' => $total_absent,
        'overall_attendance_rate' => $overall_attendance_rate
    ];

    // Generate PDF
    generateSubjectPDFReport($subject_info, $summary, $student_stats, $total_sessions);

} catch (PDOException $e) {
    error_log("Error generating subject PDF report: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit();
}

function generateSubjectPDFReport($subject_info, $summary, $student_stats, $total_sessions) {
    // Create PDF content
    $pdf_content = createSubjectPDFContent($subject_info, $summary, $student_stats, $total_sessions);
    
    // Generate filename with subject name and date
    $subject_name = preg_replace('/[^a-zA-Z0-9\s]/', '', $subject_info['SubjectName']);
    $subject_name = preg_replace('/\s+/', '_', $subject_name);
    $date = date('Y-m-d');
    $filename = "{$subject_name}_Subject_Attendance_Report_{$date}.pdf";
    
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

function createSubjectPDFContent($subject_info, $summary, $student_stats, $total_sessions) {
    // Create a very basic, working PDF
    $pdf = "%PDF-1.4\n";
    
    // Simple text content with minimal positioning
    $content = "BT\n/F1 10 Tf\n";
    
    // Header - Bigger and Bold title
    $content .= "30 750 Td\n/F2 16 Tf (ClassTrack Subject Attendance Report) Tj\n/F1 10 Tf\n";
    $content .= "0 -25 Td (Subject: " . $subject_info['SubjectName'] . ") Tj\n";
    $content .= "0 -12 Td (Subject Code: " . $subject_info['SubjectCode'] . ") Tj\n";
    $content .= "0 -12 Td (Class: " . $subject_info['ClassName'] . " - " . $subject_info['SectionName'] . ") Tj\n";
    $content .= "0 -12 Td (Teacher: " . $subject_info['TeacherName'] . ") Tj\n";
    $content .= "0 -12 Td (Report Generated: " . date('F d, Y') . ") Tj\n";
    $content .= "0 -20 Td (============================================================================================) Tj\n";
    
    // Summary Statistics
    $content .= "0 -20 Td\n/F2 12 Tf (ATTENDANCE SUMMARY) Tj\n/F1 10 Tf\n";
    $content .= "0 -15 Td (Total Sessions Conducted: " . $total_sessions . ") Tj\n";
    $content .= "0 -12 Td (Total Students: " . $summary['total_students'] . ") Tj\n";
    $content .= "0 -12 Td (Total Present: " . $summary['total_present'] . ") Tj\n";
    $content .= "0 -12 Td (Total Late: " . $summary['total_late'] . ") Tj\n";
    $content .= "0 -12 Td (Total Absent: " . $summary['total_absent'] . ") Tj\n";
    $content .= "0 -12 Td (Overall Attendance Rate: " . $summary['overall_attendance_rate'] . "%) Tj\n";
    $content .= "0 -20 Td (============================================================================================) Tj\n";
    
    // Student Details Header
    $content .= "0 -20 Td\n/F2 12 Tf (STUDENT ATTENDANCE DETAILS) Tj\n/F1 10 Tf\n";
    $content .= "0 -15 Td (--------------------------------------------------------------------------------------------) Tj\n";
    
    // Table Headers
    $content .= "0 -15 Td\n/F2 10 Tf (Student No.               Name                       Present   Late   Absent   Attendance %) Tj\n/F1 10 Tf\n";
    $content .= "0 -15 Td (--------------------------------------------------------------------------------------------) Tj\n";
    
    // Student data
    if (empty($student_stats)) {
        $content .= "0 -15 Td (No students enrolled in this subject.) Tj\n";
    } else {
        foreach ($student_stats as $student) {
            // Create properly spaced line
            $student_line = sprintf("%-18s %-31s %7d %7d %7d %10.2f%%", 
                substr($student['StudentNumber'], 0, 12),
                substr($student['StudentName'], 0, 25),
                $student['PresentCount'],
                $student['LateCount'],
                $student['AbsentCount'],
                $student['AttendancePercentage']
            );
            
            $content .= "0 -15 Td (" . $student_line . ") Tj\n";
        }
    }
    
    $content .= "0 -15 Td (--------------------------------------------------------------------------------------------) Tj\n";
    $content .= "ET\n";
    
    // Add signature field
    $content .= "BT\n/F1 10 Tf\n";
    $content .= "412 40 Td (___________________________) Tj\n";
    $content .= "0 -15 Td (     Teacher Signature) Tj\n";
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
    
    // Object 5: Font - Use Courier
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";
    
    // Object 6: Font - Use Courier-Bold
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
