<?php
session_start();
require_once 'api/db_connect.php';

// Log the logout before destroying session
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    logUserActivity(
        $_SESSION['user_id'], 
        $_SESSION['username'], 
        'logout', 
        'success', 
        'Logged out'
    );
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>