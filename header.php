<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <header class="top">
        <nav>
            <a href="dashboard.php" <?= $current_page == 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="pets.php" <?= $current_page == 'pets.php' ? 'class="active"' : '' ?>>Pets</a>
            <a href="reminders.php" <?= $current_page == 'reminders.php' ? 'class="active"' : '' ?>>Reminders</a>
            <a href="appointments.php" <?= $current_page == 'appointments.php' ? 'class="active"' : '' ?>>Appointments</a>
            <a href="view_memories.php" <?= $current_page == 'view_memories.php' ? 'class="active"' : '' ?>>Memories</a>
            <a href="articles.php" <?= $current_page == 'articles.php' ? 'class="active"' : '' ?>>Articles</a>
            <a href="adoption.php" <?= $current_page == 'adoption.php' ? 'class="active"' : '' ?>>Adoption</a>
            <a href="feed_guidelines.php" <?= $current_page == 'feed_guidelines.php' ? 'class="active"' : '' ?>>Feed Guidelines</a>
            <a href="messages.php" <?= $current_page == 'messages.php' ? 'class="active"' : '' ?>>Messages</a>
            <a href="view_treatments.php" <?= $current_page == 'view_treatments.php' ? 'class="active"' : '' ?>>Treatments</a>
            <a href="profile.php" <?= $current_page == 'profile.php' ? 'class="active"' : '' ?>>Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>
    <main class="container">