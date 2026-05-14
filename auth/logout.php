<?php
/**
 * ClassTrack Logout Handler
 * Destroys session and redirects to login page
 */

// Start session
session_start();

// Remember which login page to return to before destroying the session.
$isAdminSession = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) ||
                  !empty($_SESSION['admin_id']) ||
                  !empty($_SESSION['admin_username']);

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: ' . ($isAdminSession ? 'admin/admin_login.php' : 'login.php'));
exit;
?>
