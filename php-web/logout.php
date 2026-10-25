<?php
session_start();
require 'db.php';
require 'log_helper.php';

if (isset($_SESSION['admin_id'])) {
    logActivity($conn, $_SESSION['admin_id'], "Admin logged out");
}

session_destroy();
header("Location: login.php");
exit();
?>
