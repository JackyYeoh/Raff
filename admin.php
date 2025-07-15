<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /raffle-demo/admin/admin-login.php');
    exit;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /raffle-demo/admin/admin-login.php');
    exit;
}

// If logged in, redirect to admin dashboard
header('Location: /raffle-demo/admin/dashboard.php');
exit;
?> 