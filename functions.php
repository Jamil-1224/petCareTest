<?php
// functions.php

// Prevent multiple inclusions
if (defined('FUNCTIONS_LOADED')) {
    return;
}
define('FUNCTIONS_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!is_logged_in()) {
            header("Location: login.php");
            exit;
        }
    }
}

if (!function_exists('esc')) {
    function esc($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('upload_image')) {
    function upload_image($file)
    {
        // returns filename or null
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        if (!in_array($file['type'], $allowed)) return null;
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $target = __DIR__ . '/uploads/' . $fname;
        if (!move_uploaded_file($file['tmp_name'], $target)) return null;
        return 'uploads/' . $fname;
    }
}
