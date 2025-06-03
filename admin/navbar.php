<?php
// File: admin/navbar.php (Diperbarui untuk bekerja dengan sidebar)
require_once 'session.php'; // Untuk akses session info

$admin_username = $_SESSION['username'] ?? 'Pengguna';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid">
        <button id="sidebarToggle" class="btn btn-link text-white me-2">
            <i class="bi bi-list fs-4"></i>
        </button>
        <a class="navbar-brand fw-bold d-none d-md-block" href="index.php">SEDAYA ADMIN</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="navbar-text text-white me-2 d-none d-lg-inline">
                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($admin_username); ?>
            </span>
            <a class="btn btn-outline-danger btn-sm d-none d-md-inline-block" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>
<div class="flash-container">
    <div class="container pt-3">
        <?php display_flash_message(); ?>
    </div>
</div>