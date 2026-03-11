<?php
/**
 * PetCare - Main Entry Point
 * Redirects users to appropriate pages based on login status
 */

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Check user role and redirect accordingly
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: admin/dashboard.php");
                exit;
            case 'doctor':
                header("Location: doctor_dashboard.php");
                exit;
            default:
                header("Location: dashboard.php");
                exit;
        }
    } else {
        // Regular user
        header("Location: dashboard.php");
        exit;
    }
} else {
    // Not logged in - show login page
    header("Location: login.php");
    exit;
}
?>
