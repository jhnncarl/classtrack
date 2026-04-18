<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Start session
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../config/database.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'getUserDetails':
            $userId = $_POST['userId'] ?? 0;
            echo json_encode(getUserDetails($db, $userId));
            exit;
            
        case 'updateUser':
            $userId = $_POST['userId'] ?? 0;
            $userData = json_decode($_POST['userData'] ?? '[]', true) ?? [];
            echo json_encode(updateUser($db, $userId, $userData));
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Function to get user details
function getUserDetails($db, $userId) {
    try {
        $stmt = $db->prepare("SELECT u.UserID, u.first_name, u.last_name, u.Email, u.Role, u.AccountStatus, u.CreatedAt, u.ProfilePicture,
                t.Department,
                s.StudentNumber, s.Course, s.YearLevel
                FROM users u
                LEFT JOIN teachers t ON u.UserID = t.UserID
                LEFT JOIN students s ON u.UserID = s.UserID
                WHERE u.UserID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return ['success' => true, 'data' => $user];
        } else {
            return ['success' => false, 'message' => 'User not found'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error fetching user details: ' . $e->getMessage()];
    }
}

// Function to update user
function updateUser($db, $userId, $userData) {
    try {
        $db->beginTransaction();
        
        // Update users table
        $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, Email = ?, Role = ?, AccountStatus = ? 
                              WHERE UserID = ?");
        $stmt->execute([
            $userData['firstName'],
            $userData['lastName'],
            $userData['email'],
            $userData['role'],
            $userData['status'],
            $userId
        ]);
        
        // Update role-specific information
        if ($userData['role'] === 'Teacher') {
            // Update teacher department
            $stmt = $db->prepare("UPDATE teachers SET Department = ? WHERE UserID = ?");
            $stmt->execute([$userData['department'], $userId]);
        } elseif ($userData['role'] === 'Student') {
            // Update student information
            $stmt = $db->prepare("UPDATE students SET StudentNumber = ?, Course = ?, YearLevel = ? 
                                  WHERE UserID = ?");
            $stmt->execute([
                $userData['studentNumber'],
                $userData['course'],
                $userData['yearLevel'],
                $userId
            ]);
        }
        
        $db->commit();
        return ['success' => true, 'message' => 'User updated successfully'];
    } catch(PDOException $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()];
    }
}
?>
