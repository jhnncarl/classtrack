<?php
// RBAC Permissions API Endpoint
// Handles all AJAX requests for role-based permission management

require_once '../config/database.php';
require_once '../config/permissions.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    switch ($action) {
        case 'getRolePermissions':
            $role = $_POST['role'] ?? $_GET['role'] ?? '';
            echo json_encode(getRolePermissions($db, $role));
            break;
            
        case 'updateRolePermissions':
            $role = $_POST['role'] ?? '';
            $permissions = json_decode($_POST['permissions'] ?? '{}', true);
            echo json_encode(updateRolePermissions($db, $role, $permissions));
            break;
            
        case 'resetRolePermissions':
            $role = $_POST['role'] ?? '';
            echo json_encode(resetRolePermissions($db, $role));
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API Error: ' . $e->getMessage()
    ]);
}

// RBAC Functions

/**
 * Get role permissions
 * @param PDO $db
 * @param string $role
 * @return array
 */
function getRolePermissions($db, $role) {
    try {
        // Get permissions from role_permissions table
        $stmt = $db->prepare("SELECT * FROM role_permissions WHERE role = ?");
        $stmt->execute([ucfirst($role)]);
        $rolePermissions = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rolePermissions) {
            // Fallback to defaults if no role permissions exist
            $permissions = new Permissions();
            $rolePermissions = $permissions->getDefaultRolePermissions(ucfirst($role));
        } else {
            // Remove non-permission fields
            unset($rolePermissions['role'], $rolePermissions['updated_at']);
            // Convert boolean values
            foreach ($rolePermissions as $key => $value) {
                $rolePermissions[$key] = (bool) $value;
            }
        }
        
        // Get count of users with this role
        $stmt = $db->prepare("SELECT COUNT(*) as userCount FROM users WHERE Role = ?");
        $stmt->execute([ucfirst($role)]);
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['userCount'];
        
        return [
            'success' => true,
            'permissions' => $rolePermissions,
            'userCount' => $userCount,
            'role' => ucfirst($role)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error getting role permissions: ' . $e->getMessage()
        ];
    }
}

/**
 * Update role permissions
 * @param PDO $db
 * @param string $role
 * @param array $permissions
 * @return array
 */
function updateRolePermissions($db, $role, $permissions) {
    try {
        $permissionsObj = new Permissions();
        $result = $permissionsObj->updateRolePermissions(ucfirst($role), $permissions);
        
        if ($result) {
            // Get count of affected users
            $stmt = $db->prepare("SELECT COUNT(*) as userCount FROM users WHERE Role = ?");
            $stmt->execute([ucfirst($role)]);
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['userCount'];
            
            return [
                'success' => true,
                'message' => "Updated permissions for $role role was successfully.",
                'updatedCount' => $userCount,
                'totalUsers' => $userCount
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update role permissions'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error updating role permissions: ' . $e->getMessage()
        ];
    }
}

/**
 * Reset role permissions
 * @param PDO $db
 * @param string $role
 * @return array
 */
function resetRolePermissions($db, $role) {
    try {
        $permissionsObj = new Permissions();
        $result = $permissionsObj->resetRolePermissions(ucfirst($role));
        
        if ($result) {
            // Get count of affected users
            $stmt = $db->prepare("SELECT COUNT(*) as userCount FROM users WHERE Role = ?");
            $stmt->execute([ucfirst($role)]);
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['userCount'];
            
            return [
                'success' => true,
                'message' => "Reset permissions for $role role was successfully.",
                'resetCount' => $userCount,
                'totalUsers' => $userCount
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to reset role permissions'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error resetting role permissions: ' . $e->getMessage()
        ];
    }
}
?>
