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
        error_log("Getting default permissions for role: $role");
        
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
        
        $result = $defaults[$role] ?? [];
        error_log("Default permissions result for $role: " . json_encode($result));
        
        return $result;
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
            // Validate and normalize role name
            $validRoles = ['Student', 'Teacher', 'Administrator'];
            $normalizedRole = ucfirst(strtolower($role));
            
            if (!in_array($normalizedRole, $validRoles)) {
                error_log("Invalid role: $role (normalized: $normalizedRole)");
                return false;
            }
            
            error_log("Attempting to update permissions for role: $normalizedRole");
            error_log("Permissions data: " . json_encode($permissions));
            
            // First try to update existing record
            $setParts = [];
            $values = [];
            
            foreach ($permissions as $permission => $value) {
                $setParts[] = "$permission = ?";
                $values[] = $value ? 1 : 0;
            }
            
            $values[] = $normalizedRole; // For WHERE clause
            
            $sql = "UPDATE role_permissions SET " . implode(', ', $setParts) . " WHERE role = ?";
            error_log("Update SQL: $sql");
            error_log("Update values: " . json_encode($values));
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);
            
            error_log("Update result: " . ($result ? 'true' : 'false'));
            error_log("Update row count: " . $stmt->rowCount());
            
            // If update affected 0 rows, try to insert new record
            if ($result && $stmt->rowCount() === 0) {
                error_log("No rows updated, attempting to insert new record");
                
                $insertSql = "INSERT INTO role_permissions (role, " . implode(', ', array_keys($permissions)) . ") VALUES (?, " . str_repeat('?,', count($permissions) - 1) . "?)";
                $insertValues = array_merge([$normalizedRole], array_map(function($value) { return $value ? 1 : 0; }, $permissions));
                
                error_log("Insert SQL: $insertSql");
                error_log("Insert values: " . json_encode($insertValues));
                
                $insertStmt = $this->db->prepare($insertSql);
                $result = $insertStmt->execute($insertValues);
                
                error_log("Insert result: " . ($result ? 'true' : 'false'));
            }
            
            return $result;
            
        } catch(PDOException $e) {
            error_log("Error updating permissions for role $role: " . $e->getMessage());
            error_log("PDO Error info: " . json_encode($e->errorInfo));
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
        error_log("RESET FUNCTION CALLED WITH ROLE: $role");
        
        try {
            // Validate and normalize role name
            $validRoles = ['Student', 'Teacher', 'Administrator'];
            $normalizedRole = ucfirst(strtolower($role));
            
            if (!in_array($normalizedRole, $validRoles)) {
                error_log("Invalid role for reset: $role (normalized: $normalizedRole)");
                return false;
            }
            
            error_log("Reset permissions - normalized role: $normalizedRole");
            
            // Delete existing permissions for this role
            $deleteSql = "DELETE FROM role_permissions WHERE role = ?";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteResult = $deleteStmt->execute([$normalizedRole]);
            
            error_log("Delete existing permissions result: " . ($deleteResult ? 'true' : 'false'));
            
            // Get actual columns from the database table
            $columnsQuery = "SHOW COLUMNS FROM role_permissions";
            $columnsStmt = $this->db->query($columnsQuery);
            $dbColumns = [];
            
            while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Field'] !== 'role' && $row['Field'] !== 'updated_at') {
                    $dbColumns[] = $row['Field'];
                }
            }
            
            error_log("Database columns: " . json_encode($dbColumns));
            
            // Get default permissions for this role
            $defaults = $this->getDefaultRolePermissions($normalizedRole);
            
            if (empty($defaults)) {
                error_log("No default permissions found for role: $normalizedRole");
                return false;
            }
            
            // Filter defaults to only include columns that exist in database
            $filteredDefaults = [];
            $filteredColumns = [];
            
            foreach ($dbColumns as $column) {
                if (isset($defaults[$column])) {
                    $filteredDefaults[$column] = $defaults[$column];
                    $filteredColumns[] = $column;
                }
            }
            
            error_log("Filtered defaults: " . json_encode($filteredDefaults));
            
            // Build INSERT query with only existing columns
            if (!empty($filteredColumns)) {
                $insertSql = "INSERT INTO role_permissions (role, " . implode(', ', $filteredColumns) . ") VALUES (?, " . str_repeat('?,', count($filteredColumns) - 1) . "?)";
                $insertValues = array_merge([$normalizedRole], array_values($filteredDefaults));
                
                error_log("Insert SQL: $insertSql");
                error_log("Insert values: " . json_encode($insertValues));
                
                $insertStmt = $this->db->prepare($insertSql);
                $insertResult = $insertStmt->execute($insertValues);
                
                error_log("Insert result: " . ($insertResult ? 'true' : 'false'));
            } else {
                error_log("No valid columns to insert");
                $insertResult = false;
            }
            
            return $insertResult;
            
        } catch(PDOException $e) {
            error_log("Error resetting permissions for role $role: " . $e->getMessage());
            error_log("PDO Error info: " . json_encode($e->errorInfo));
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
