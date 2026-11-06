<?php
/**
 * Magaram Real Estate - Executive Admin Suite Header
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$activeTab = $activeTab ?? 'dashboard';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Admin Portal'); ?> | Magaram Executive</title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <img src="<?= BASE_URL; ?>/assets/img/magaram-logo.svg" alt="Magaram Crest" width="32" height="32">
            <div>
                <strong style="color: var(--text-cream); font-size: 0.95rem; letter-spacing: 0.05em;">MAGARAM</strong>
                <div style="font-size: 0.65rem; color: var(--gold-bright); letter-spacing: 0.15em;">EXECUTIVE SUITE</div>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="<?= BASE_URL; ?>/dashboard.php" class="sidebar-link <?= $activeTab === 'dashboard' ? 'active' : ''; ?>">
                <span>&#9638;</span> Dashboard
            </a>
            <a href="<?= BASE_URL; ?>/properties/index.php" class="sidebar-link <?= $activeTab === 'properties' ? 'active' : ''; ?>">
                <span>&#9751;</span> Properties
            </a>
            <a href="<?= BASE_URL; ?>/enquiries.php" class="sidebar-link <?= $activeTab === 'enquiries' ? 'active' : ''; ?>">
                <span>&#9993;</span> Enquiries & Leads
            </a>
            <a href="<?= BASE_URL; ?>/testimonials.php" class="sidebar-link <?= $activeTab === 'testimonials' ? 'active' : ''; ?>">
                <span>&#9733;</span> Testimonials
            </a>
            <a href="<?= BASE_URL; ?>/gallery.php" class="sidebar-link <?= $activeTab === 'gallery' ? 'active' : ''; ?>">
                <span>&#9734;</span> Gallery
            </a>
            <a href="<?= BASE_URL; ?>/settings.php" class="sidebar-link <?= $activeTab === 'settings' ? 'active' : ''; ?>">
                <span>&#9881;</span> Site Settings & Stats
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL; ?>/logout.php" style="color: #f87171;">
                Logout (<?= e($_SESSION['admin_user_name'] ?? 'Admin'); ?>)
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h2 style="font-size: 1.35rem; margin: 0; color: var(--text-cream);"><?= e($adminTitle ?? 'Executive Control Panel'); ?></h2>
            </div>
            <div>
                <a href="<?= BASE_URL; ?>/properties/add.php" class="btn btn-primary btn-sm">
                    + Add New Property
                </a>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?>" style="margin-bottom: 24px;">
                    <?= e($flash['message']); ?>
                </div>
            <?php endif; ?>
