<?php
/**
 * =====================================================
 * Logout Page - GDSS System
 * Handle user logout
 * =====================================================
 */
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with logout message
header('Location: index.php?logout=1');
exit();
?>