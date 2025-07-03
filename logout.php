<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isset($_SESSION['user_id'])) {
    clearUserSession($conn, $_SESSION['user_id']);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

$conn->close();

sleep(5);

header("Location: login.php");
exit;
?>