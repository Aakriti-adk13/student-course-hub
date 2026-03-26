<?php
/**
 * templetes/admin-header.php
 * 
 * Admin area header template
 * Required variables: $pageTitle, $activePage
 */

// Ensure session is started for admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

// Get admin info if needed
$adminName = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> – Admin – <?= SITE_NAME ?></title>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    
    <!-- Font Awesome (optional, for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">

<div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-brand">
            <a href="<?= BASE_URL ?>/admin/dashboard.php">
                <span class="brand-icon">🎓</span>
                <span><?= SITE_NAME ?></span>
            </a>
        </div>
        
        <ul class="admin-nav-list">
            <li class="nav-section-label">Main</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" 
                   class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section-label">Content</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/programmes.php" 
                   class="<?= $activePage === 'programmes' ? 'active' : '' ?>">
                    <i class="fas fa-graduation-cap nav-icon"></i>
                    <span>Programmes</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/modules.php" 
                   class="<?= $activePage === 'modules' ? 'active' : '' ?>">
                    <i class="fas fa-book nav-icon"></i>
                    <span>Modules</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/staff.php" 
                   class="<?= $activePage === 'staff' ? 'active' : '' ?>">
                    <i class="fas fa-users nav-icon"></i>
                    <span>Staff</span>
                </a>
            </li>
            
            <li class="nav-section-label">Marketing</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/students.php" 
                   class="<?= $activePage === 'students' ? 'active' : '' ?>">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span>Mailing List</span>
                </a>
            </li>
        </ul>
        
        <div class="admin-sidebar-footer">
            <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-sm btn-outline" style="width:100%;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="admin-content">
        
        <!-- Top Bar -->
        <div class="admin-topbar">
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">
                <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
            </div>
            <div class="topbar-user">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($adminName) ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="admin-main">
