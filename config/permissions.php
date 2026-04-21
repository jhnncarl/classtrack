<?php
/**
 * Permission Management System for ClassTrack
 * Handles user permissions and role-based access control
 */

require_once 'database.php';

class Permissions {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Get default permissions for a specific role
     * @param string $role
     * @return array
     */
    public function getDefaultRolePermissions($role) {
        $defaults = [
            'Teacher' => [
                'createClass' => true,                     // Create Class – True
                'joinClass' => false,                      // Join Class – False
                'manageClass' => true,                     // Manage Class – True
                'takeAttendance' => true,                  // Take Attendance – True
                'viewReports' => true,                     // View Reports – True
                'exportReports' => true,                   // Export Reports – True
                'editProfile' => true,                     // Edit Teacher Information – True
                'student_unenrollClass' => false,          // Not specified, default to False
                'student_viewAttendanceRecord' => false,   // Not specified, default to False
                'student_viewAttendanceHistory' => false,  // Not specified, default to False
                'approveTeacherAccounts' => false,         // Not specified, default to False
                'rejectTeacherAccounts' => false,         // Not specified, default to False
                'createAdminUser' => false,                // Not specified, default to False
                'editAdminProfile' => false                // Not specified, default to False
            ],
            'Student' => [
                'createClass' => false,                    // Create Class – False
                'joinClass' => true,                       // Join Class – True
                'manageClass' => false,                    // Not specified, default to False
                'takeAttendance' => false,                 // Not specified, default to False
                'viewReports' => false,                    // Not specified, default to False
                'exportReports' => false,                  // Not specified, default to False
                'editProfile' => true,                     // Edit Student Information – True
                'student_unenrollClass' => true,           // Unenroll from Class – True
                'student_viewAttendanceRecord' => true,    // View Attendance Record – True
                'student_viewAttendanceHistory' => true,   // View Attendance History – True
                'approveTeacherAccounts' => false,         // Not specified, default to False
                'rejectTeacherAccounts' => false,         // Not specified, default to False
                'createAdminUser' => false,                // Not specified, default to False
                'editAdminProfile' => false                // Not specified, default to False
            ],
            'Administrator' => [
                'createClass' => true,                     // All permissions set to True
                'joinClass' => true,
                'manageClass' => true,
                'takeAttendance' => true,
                'viewReports' => true,
                'exportReports' => true,
                'editProfile' => true,
                'student_unenrollClass' => true,
                'student_viewAttendanceRecord' => true,
                'student_viewAttendanceHistory' => true,
                'approveTeacherAccounts' => true,          // Approve Teacher Accounts – True
                'rejectTeacherAccounts' => true,          // Reject Teacher Accounts – True
                'createAdminUser' => true,                 // Create Admin User – True
                'editAdminProfile' => true                 // Edit Admin Profile – True
            ]
        ];
        
        return $defaults[$role] ?? [];
    }
    
    /**
     * Get user permissions (role-based)
     * @param int $userId
     * @return array
     */
    public function getUserPermissions($userId) {
        try {
            // Get user role first
            $userRole = $this->getUserRole($userId);
            
            // Get permissions from role_permissions table
            $sql = "SELECT * FROM role_permissions WHERE role = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userRole]);
            
            $permissions = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$permissions) {
                // If no role permissions exist, return defaults
                return $this->getDefaultRolePermissions($userRole);
            }
            
            // Remove non-permission fields
            unset($permissions['role'], $permissions['updated_at']);
            
            // Convert boolean values
            foreach ($permissions as $key => $value) {
                $permissions[$key] = (bool) $value;
            }
            
            return $permissions;
            
        } catch(PDOException $e) {
            error_log("Error getting permissions for user $userId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has specific permission (role-based)
     * @param int $userId
     * @param string $permission
     * @return bool
     */
    public function hasPermission($userId, $permission) {
        try {
            // Get user role and check permission in one query
            $sql = "SELECT rp.$permission 
                    FROM users u 
                    JOIN role_permissions rp ON u.Role = rp.role 
                    WHERE u.UserID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            $result = $stmt->fetchColumn();
            
            if ($result === false) {
                // No permission record found, check role defaults
                $userRole = $this->getUserRole($userId);
                $defaults = $this->getDefaultRolePermissions($userRole);
                return $defaults[$permission] ?? false;
            }
            
            return (bool) $result;
            
        } catch(PDOException $e) {
            error_log("Error checking permission $permission for user $userId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update role permissions
     * @param string $role
     * @param array $permissions
     * @return bool
     */
    public function updateRolePermissions($role, $permissions) {
        try {
            // Build update query
            $setParts = [];
            $values = [];
            
            foreach ($permissions as $permission => $value) {
                $setParts[] = "$permission = ?";
                $values[] = $value ? 1 : 0;
            }
            
            $values[] = $role; // For WHERE clause
            
            $sql = "UPDATE role_permissions SET " . implode(', ', $setParts) . " WHERE role = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($values);
            
        } catch(PDOException $e) {
            error_log("Error updating permissions for role $role: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user role from users table
     * @param int $userId
     * @return string
     */
    private function getUserRole($userId) {
        try {
            $sql = "SELECT Role FROM users WHERE UserID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            $role = $stmt->fetchColumn();
            return $role ?: 'Student'; // Default to Student if not found
            
        } catch(PDOException $e) {
            error_log("Error getting role for user $userId: " . $e->getMessage());
            return 'Student';
        }
    }
    
    /**
     * Reset role permissions to defaults
     * @param string $role
     * @return bool
     */
    public function resetRolePermissions($role) {
        try {
            $defaults = $this->getDefaultRolePermissions($role);
            return $this->updateRolePermissions($role, $defaults);
            
        } catch(PDOException $e) {
            error_log("Error resetting permissions for role $role: " . $e->getMessage());
            return false;
        }
    }
}

// Helper functions removed - permissions are now role-based
// Users automatically inherit permissions from their role via role_permissions table

/**
 * Helper function to check user permission
 * @param int $userId
 * @param string $permission
 * @return bool
 */
function hasUserPermission($userId, $permission) {
    $permissions = new Permissions();
    return $permissions->hasPermission($userId, $permission);
}
?>
