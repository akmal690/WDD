<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (customer)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get current user ID (customer)
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current admin ID
function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Redirect if not logged in (customer)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: customer_login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        // Check if we're in admin directory
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit();
    }
}
?>

