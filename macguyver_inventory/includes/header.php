<?php
if (!defined('BASE_URL')) define('BASE_URL', '/macguyver_inventory/');
require_once __DIR__ . '/functions.php';
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>MacGuyver Inventory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="<?= BASE_URL ?>assets/logo.png" alt="Logo" class="brand-logo">
    <div class="brand-text">
      <span class="brand-name">MacGuyver</span>
      <span class="brand-sub">Enterprises</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="<?= BASE_URL ?>dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
    </a>
    <div class="nav-section-label">INVENTORY MANAGEMENT</div>
    <a href="<?= BASE_URL ?>pages/items.php" class="nav-item <?= $currentPage==='items'?'active':'' ?>">
      <i class="fas fa-boxes"></i><span>Inventory Items</span>
    </a>
    <a href="<?= BASE_URL ?>pages/transactions.php" class="nav-item <?= $currentPage==='transactions'?'active':'' ?>">
      <i class="fas fa-exchange-alt"></i><span>Transactions</span>
    </a>
    <a href="<?= BASE_URL ?>pages/stock_in.php" class="nav-item <?= $currentPage==='stock_in'?'active':'' ?>">
      <i class="fas fa-arrow-circle-down"></i><span>Stock In</span>
    </a>
    <a href="<?= BASE_URL ?>pages/stock_out.php" class="nav-item <?= $currentPage==='stock_out'?'active':'' ?>">
      <i class="fas fa-arrow-circle-up"></i><span>Stock Out</span>
    </a>
    <div class="nav-section-label">MANAGEMENT</div>
    <a href="<?= BASE_URL ?>pages/categories.php" class="nav-item <?= $currentPage==='categories'?'active':'' ?>">
      <i class="fas fa-tag"></i><span>Categories</span>
    </a>
    <a href="<?= BASE_URL ?>pages/suppliers.php" class="nav-item <?= $currentPage==='suppliers'?'active':'' ?>">
      <i class="fas fa-truck"></i><span>Suppliers</span>
    </a>
    <a href="<?= BASE_URL ?>pages/reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
      <i class="fas fa-chart-bar"></i><span>Reports</span>
    </a>
    <?php if(isAdmin()): ?>
    <div class="nav-section-label">ADMIN</div>
    <a href="<?= BASE_URL ?>pages/users.php" class="nav-item <?= $currentPage==='users'?'active':'' ?>">
      <i class="fas fa-users"></i><span>Users</span>
    </a>
    <a href="<?= BASE_URL ?>pages/logs.php" class="nav-item <?= $currentPage==='logs'?'active':'' ?>">
      <i class="fas fa-history"></i><span>Activity Logs</span>
    </a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><i class="fas fa-user-circle"></i></div>
      <div class="user-details">
        <span class="user-name"><?= sanitize($currentUser['full_name']) ?></span>
        <span class="user-role"><?= ucfirst($currentUser['role']) ?></span>
      </div>
    </div>
    <a href="<?= BASE_URL ?>logout.php" class="logout-btn" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
  </div>
</aside>
<!-- Main Content -->
<div class="main-wrapper">
  <header class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <div class="topbar-title"><?= isset($pageTitle) ? sanitize($pageTitle) : 'Dashboard' ?></div>
    <div class="topbar-right">
      <span class="date-display"><i class="far fa-calendar-alt"></i> <?= date('M d, Y') ?></span>
    </div>
  </header>
  <main class="content-area">
