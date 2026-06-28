<?php
require_once 'db.php';

// Clear session
$_SESSION = [];
session_destroy();

// Delete the cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect with a goodbye message
header('Location: index.php?msg=logged_out');
exit();
?>
