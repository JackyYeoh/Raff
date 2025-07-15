<?php
define('BASE_URL', '/raffle-demo'); // Define the base URL for the project
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php'; // Include database connection

// Function to check if current page matches nav item
function isCurrentPage($page) {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    return $current_script === $page;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RaffLah! Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin-layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin-components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/enhanced-modals.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">RaffLah!</div>
        <nav class="sidebar-nav">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-item <?php echo isCurrentPage('dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/workflow-status.php" class="nav-item <?php echo isCurrentPage('workflow-status.php') ? 'active' : ''; ?>"><i class="fas fa-project-diagram"></i><span>Workflow</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/raffles.php" class="nav-item <?php echo isCurrentPage('raffles.php') ? 'active' : ''; ?>"><i class="fas fa-trophy"></i><span>Raffles</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/categories.php" class="nav-item <?php echo isCurrentPage('categories.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i><span>Categories</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/brands.php" class="nav-item <?php echo isCurrentPage('brands.php') ? 'active' : ''; ?>"><i class="fas fa-building"></i><span>Brands</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/banners.php" class="nav-item <?php echo isCurrentPage('banners.php') ? 'active' : ''; ?>"><i class="fas fa-images"></i><span>Banners</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="nav-item <?php echo isCurrentPage('users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/loyalty_management.php" class="nav-item <?php echo isCurrentPage('loyalty_management.php') ? 'active' : ''; ?>"><i class="fas fa-star"></i><span>Loyalty</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/stats.php" class="nav-item <?php echo isCurrentPage('stats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i><span>Stats</span></a>
            <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="nav-item <?php echo isCurrentPage('settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>Settings</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="<?php echo BASE_URL; ?>/admin.php?logout=1" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>
    <main class="main-content"> 