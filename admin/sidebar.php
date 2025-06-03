<?php
// File: admin/sidebar.php
require_once 'session.php'; // Untuk akses session info

$admin_username = $_SESSION['username'] ?? 'Pengguna';

// Fungsi untuk cek halaman aktif (jika belum ada)
if (!function_exists('isAdminPageActive')) {
    function isAdminPageActive($page_names) {
        $current = basename($_SERVER['SCRIPT_NAME']);
        if (is_array($page_names)) { return in_array($current, $page_names); }
        return $current === $page_names;
    }
}
?>

<!-- Sidebar -->
<div class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3 d-flex justify-content-between align-items-center">
        <a href="index.php" class="sidebar-brand text-white text-decoration-none fw-bold fs-4">SEDAYA ADMIN</a>
        <button id="sidebarToggleBtn" class="btn btn-link text-white d-md-none">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <div class="user-info p-3 border-top border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <i class="bi bi-person-circle fs-4 me-2"></i>
            <div>
                <div class="fw-bold"><?php echo htmlspecialchars($admin_username); ?></div>
                <small class="text-light">Administrator</small>
            </div>
        </div>
    </div>
    
    <ul class="sidebar-nav list-unstyled p-0">
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive('index.php') ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-house-door-fill me-2"></i> Beranda
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive(['kategori.php', 'kategori-detail.php']) ? 'active' : ''; ?>" href="kategori.php">
                <i class="bi bi-tags-fill me-2"></i> Kategori
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive(['produk.php', 'produk-detail.php', 'tambahproduk.php', 'lihatproduk.php']) ? 'active' : ''; ?>" href="produk.php">
                <i class="bi bi-box-seam-fill me-2"></i> Produk
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive('carrousel.php') ? 'active' : ''; ?>" href="carrousel.php">
                <i class="bi bi-images me-2"></i> Carrousel
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive('galery.php') ? 'active' : ''; ?>" href="galery.php">
                <i class="bi bi-collection-fill me-2"></i> Galeri
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive(['info_pages.php', 'info_pages_edit.php', 'info_pages_tambah.php']) ? 'active' : ''; ?>" href="info_pages.php">
                <i class="bi bi-file-earmark-text-fill me-2"></i> Halaman Info
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive(['user.php', 'edituser.php', 'tambahanggota.php']) ? 'active' : ''; ?>" href="user.php">
                <i class="bi bi-people-fill me-2"></i> User
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link <?php echo isAdminPageActive(['pesanan.php', 'pesanan_tambah.php', 'pesanan_detail.php']) ? 'active' : ''; ?>" href="pesanan.php">
                <i class="bi bi-receipt me-2"></i> Pesanan
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer p-3 mt-auto">
        <a class="btn btn-danger btn-sm w-100" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</div>

<!-- Overlay untuk menutup sidebar di tampilan mobile -->
<div class="sidebar-overlay"></div>