<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated and has the required role
 * @param string|null $required_role Role to check for
 * @return string|bool User role or false if not authenticated
 */
function checkAuth($required_role = null) {
    if (!isset($_SESSION['role'])) {
        header("Location: ../../index.php");
        exit();
    }

    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        header("Location: ../../index.php");
        exit();
    }

    return $_SESSION['role'];
}

/**
 * Log out the current user
 */
function logout() {
    session_unset();
    session_destroy();
    header("Location: ../../index.php");
    exit();
}
?> 