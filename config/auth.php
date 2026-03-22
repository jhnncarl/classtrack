<?php
/**
 * Authentication System for ClassTrack
 * Handles user login, registration, and session management
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Register a new user
     * @param array $userData
     * @return array
     */
    public function register($userData) {
        try {
            // Validate email
            if ($this->getUserByEmail($userData['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (Name, Email, PasswordHash, Role, AccountStatus) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $fullName = $userData['first_name'] . ' ' . $userData['last_name'];
            
            $stmt->execute([
                $fullName,
                $userData['email'],
                $hashedPassword,
                $userData['role'],
                'Pending' // New accounts need approval (except students)
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create role-specific record
            if ($userData['role'] === 'Student') {
                $this->createStudentRecord($userId, $userData);
                // Auto-approve students
                $this->updateAccountStatus($userId, 'Active');
            } elseif ($userData['role'] === 'Teacher') {
                $this->createTeacherRecord($userId, $userData);
            }
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            // Get user by email
            $sql = "SELECT * FROM users WHERE Email = ? AND AccountStatus = 'Active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['PasswordHash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Set session
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['logged_in'] = true;
            
            // Get role-specific data
            if ($user['Role'] === 'Student') {
                $_SESSION['student_data'] = $this->getStudentData($user['UserID']);
            } elseif ($user['Role'] === 'Teacher') {
                $_SESSION['teacher_data'] = $this->getTeacherData($user['UserID']);
            }
            
            return ['success' => true, 'role' => $user['Role']];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user role
     * @return string|null
     */
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        header('Location: ../auth/login.php');
        exit;
    }
    
    /**
     * Require login to access page
     * @param string $requiredRole
     */
    public function requireLogin($requiredRole = null) {
        if (!$this->isLoggedIn()) {
            header('Location: ../auth/login.php');
            exit;
        }
        
        if ($requiredRole && $this->getUserRole() !== $requiredRole) {
            // Redirect based on current role
            $role = $this->getUserRole();
            if ($role === 'Student') {
                header('Location: ../student/dashboard.php');
            } elseif ($role === 'Teacher') {
                header('Location: ../teacher/dashboard.php');
            } elseif ($role === 'Administrator') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../auth/login.php');
            }
            exit;
        }
    }
    
    /**
     * Get user by email
     * @param string $email
     * @return array|null
     */
    private function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE Email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create student record
     * @param int $userId
     * @param array $userData
     */
    private function createStudentRecord($userId, $userData) {
        $sql = "INSERT INTO students (UserID, StudentNumber, Course, YearLevel) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $userData['student_number'] ?? '',
            $userData['course'] ?? '',
            $userData['year_level'] ?? 1
        ]);
    }
    
    /**
     * Create teacher record
     * @param int $userId
     * @param array $userData
     */
    private function createTeacherRecord($userId, $userData) {
        $sql = "INSERT INTO teachers (UserID, Department) 
                VALUES (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            $userData['department'] ?? ''
        ]);
    }
    
    /**
     * Update account status
     * @param int $userId
     * @param string $status
     */
    private function updateAccountStatus($userId, $status) {
        $sql = "UPDATE users SET AccountStatus = ? WHERE UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $userId]);
    }
    
    /**
     * Get student data
     * @param int $userId
     * @return array|null
     */
    private function getStudentData($userId) {
        $sql = "SELECT s.*, u.Name, u.Email 
                FROM students s 
                JOIN users u ON s.UserID = u.UserID 
                WHERE s.UserID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get teacher data
     * @param int $userId
     * @return array|null
     */
    private function getTeacherData($userId) {
        $sql = "SELECT t.*, u.Name, u.Email 
                FROM teachers t 
                JOIN users u ON t.UserID = u.UserID 
                WHERE t.UserID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
}

// Create global auth instance
$auth = new Auth();
?>
