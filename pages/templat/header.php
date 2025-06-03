<?php
// File: pages/templat/header.php

/**
 * ========================================================================
 * HARAP DIPERHATIKAN:
 * ========================================================================
 * File ini mengharapkan variabel berikut sudah didefinisikan
 * oleh file PHP yang meng-include file ini (misalnya: /index.php atau /pages/produk.php):
 *
 * 1. $categories (array): Array berisi daftar kategori produk.
 * 2. $base_url (string): URL dasar website (e.g., "http://localhost:8888").
 * 3. $project_folder (string): Nama folder proyek (e.g., "/newsedaya24apr2025").
 * ========================================================================
 */

// Validasi variabel yang diharapkan
if (!isset($categories) || !is_array($categories)) {
    $categories = []; // Default jika tidak ada
}
$base_url_header = $base_url ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']);
if (isset($project_folder)) {
    $project_folder_header = rtrim($project_folder, '/');
    if (!empty($project_folder_header) && $project_folder_header[0] !== '/') {
        $project_folder_header = '/' . $project_folder_header;
    }
} else {
    $project_folder_header = ''; // Fallback
}
if ($project_folder_header === '/') {
    $project_folder_header = '';
}

// Tentukan URL halaman saat ini dari SCRIPT_NAME (lebih andal dari PHP_SELF)
// $current_page_header = basename($_SERVER['SCRIPT_NAME']); // <-- Lebih baik dari PHP_SELF
// --- PERBAIKAN Logika Penentuan Halaman Aktif ---
// Dapatkan path relatif dari root web server
$current_script_path = $_SERVER['SCRIPT_NAME'];
// Jika file yang menginclude adalah index.php di root proyek
$is_home_header = (rtrim($current_script_path, '/') === rtrim($project_folder_header, '/') . '/index.php') || (rtrim($current_script_path, '/') === rtrim($project_folder_header, '/'));
// Jika file yang menginclude ada di dalam /pages/
$is_produk_page = strpos($current_script_path, $project_folder_header . '/pages/produk.php') !== false || strpos($current_script_path, $project_folder_header . '/pages/detail-produk.php') !== false;
$is_kontak_page = strpos($current_script_path, $project_folder_header . '/pages/kontak.php') !== false;
// --- Akhir Perbaikan Logika ---

// Buat URL root-relative untuk navigasi internal
$home_url_header = rtrim($project_folder_header, '/') . '/'; // Link ke root proyek (index.php)
$pages_base_header = rtrim($project_folder_header, '/') . '/pages'; // Base untuk halaman di 'pages'
$produk_url_header = $pages_base_header . '/produk.php';
$kontak_url_header = $pages_base_header . '/kontak.php';

// *** PERBAIKAN: Logo URL menggunakan $project_folder_header ***
// Asumsi logo ada di /images di dalam folder proyek

?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo htmlspecialchars($home_url_header); ?>">
            <span>Sedaya Furniture</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $is_home_header ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($home_url_header); ?>">Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $is_produk_page ? 'active' : ''; ?>" href="#" id="navbarProdukDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      Produk
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarProdukDropdown">
                      <li><a class="dropdown-item" href="<?php echo htmlspecialchars($produk_url_header); ?>">Semua Produk</a></li>
                      <?php if (!empty($categories)): ?>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($categories as $kategori): ?>
                          <li><a class="dropdown-item" href="<?php echo htmlspecialchars($produk_url_header); ?>?kategori=<?php echo $kategori['id']; ?>"><?php echo htmlspecialchars($kategori['nama']); ?></a></li>
                        <?php endforeach; ?>
                      <?php else: ?>
                         <li><span class="dropdown-item disabled text-muted">Kategori tidak tersedia</span></li>
                      <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $is_kontak_page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($kontak_url_header); ?>">Kontak</a>
                </li>
                </ul>
        </div>
    </div>
</nav>