<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // Redirect to login if user is not logged in
    exit();
}

// Function to restrict access to admin-only pages
function adminOnly() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header("Location: user/dashboard.php"); // Redirect to user dashboard
        exit();
    }
}

// Function to restrict access to user-only pages
function userOnly() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 0) {
        header("Location: admin/admin_dashboard.php"); // Redirect to admin dashboard
        exit();
    }
}
?>
