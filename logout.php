<?php
// Initialize the session
session_start();

// Unset all session variables
$_SESSION = [];

// If you want to kill the session cookie as well (highly recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to the login or home page
header("Location: login.php");
exit(); // Always use exit() after header redirects to prevent further script execution
?>
