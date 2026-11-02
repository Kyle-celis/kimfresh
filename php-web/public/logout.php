<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (isset($_SESSION['admin_id'])) {
    logActivity($conn, $_SESSION['admin_id'], "Admin logged out");
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
header("Location: login.php");
exit();
?>