<?php
session_start();

// Clear doctor session variables
unset($_SESSION['doctor_id']);
unset($_SESSION['doctor_name']);
unset($_SESSION['doctor_username']);

// Redirect to login page
header('Location: doctor_login.php');
exit;
?>