<?php
session_start();

/**
 * Admin Logout
 * Clears admin session and redirects to login page
 */

// Clear admin session
unset($_SESSION['admin']);

// Destroy session if no other data exists
if (empty($_SESSION)) {
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit;
?>


