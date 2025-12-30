<?php
require_once '../config/session.php';

// Clear admin session
unset($_SESSION['admin_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);
unset($_SESSION['full_name']);

session_destroy();
header('Location: login.php');
exit();
?>

