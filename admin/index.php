<?php
// File: admin/index.php (Tidak diubah untuk menggunakan page-template.php)
require_once "session.php";
require_login();

// --- Path Koneksi ---
require_once __DIR__ . '/../config/koneksi.php';

// --- Include Utils (jika diperlukan) ---
if (!function_exists('log_error') && file_exists(__DIR__ . '/../includes/utils.php')) {
     require_once __DIR__ . '/../includes/utils.php';
}

global $con, $database_connection_error;

// Inisialisasi jumlah
$jumlahKategori = 0;
$jumlahProduk = 0;
$jumlahUser = 0;
$jumlahCarrousel = 0;
$jumlahGalery = 0;
$jumlahHalamanInfo = 0;
$error_fetch = null;

// --- Mengambil Data Summary ---
if (!isset($database_connection_error) || $database_connection_error === false) {
    if ($con instanceof mysqli) {
        // Query Kategori
        $queryKategori = mysqli_query($con, "SELECT COUNT(*) AS total FROM kategori");
        $jumlahKategori = ($queryKategori) ? mysqli_fetch_assoc($queryKategori)['total'] : 0;

        // Query Produk
        $queryProduk = mysqli_query($con, "SELECT COUNT(*) AS total FROM produk");
        $jumlahProduk = ($queryProduk) ? mysqli_fetch_assoc($queryProduk)['total'] : 0;

        // Query Users
        $queryUser = mysqli_query($con, "SELECT COUNT(*) AS total FROM users");
        $jumlahUser = ($queryUser) ? mysqli_fetch_assoc($queryUser)['total'] : 0;

        // Query Carrousel
        $queryCarrousel = mysqli_query($con, "SELECT COUNT(*) AS total FROM carrousel");
        $jumlahCarrousel = ($queryCarrousel) ? mysqli_fetch_assoc($queryCarrousel)['total'] : 0;

        // Query Galery
        $queryGalery = mysqli_query($con, "SELECT COUNT(*) AS total FROM galery");
        $jumlahGalery = ($queryGalery) ? mysqli_fetch_assoc($queryGalery)['total'] : 0;

        // Query Halaman Info
        $checkTableQuery = mysqli_query($con, "SHOW TABLES LIKE 'halaman_info'");
        if ($checkTableQuery && mysqli_num_rows($checkTableQuery) > 0) {
            $queryHalamanInfo = mysqli_query($con, "SELECT COUNT(*) AS total FROM halaman_info");
            if ($queryHalamanInfo) {
                 $jumlahHalamanInfo = mysqli_fetch_assoc($queryHalamanInfo)['total'];
            } else {
                 $error_msg = "Gagal query halaman_info: " . mysqli_error($con);
                 if (function_exists('log_error')) log_error("Admin Index - " . $error_msg);
                 $error_fetch = "Gagal memuat data Halaman Informasi.";
                 $jumlahHalamanInfo = 0;
            }
        } else {
             // Tidak set $error_fetch jika tabel memang belum ada, agar tidak menimpa error koneksi
             // $error_fetch = "Tabel 'halaman_info' tidak ditemukan.";
             if (function_exists('log_error')) log_error("Admin Index - Tabel halaman_info tidak ditemukan.");
             $jumlahHalamanInfo = 0;
        }

        mysqli_close($con); // Tutup koneksi

    } else {
        $error_msg = "Admin Index: Variabel koneksi \$con tidak valid.";
        if (function_exists('log_error')) log_error($error_msg);
        $error_fetch = 'Variabel koneksi database tidak valid.';
    }
} else {
    $error_msg = "Admin Index: Koneksi database gagal (dari config).";
    if (function_exists('log_error')) log_error($error_msg);
    $error_fetch = 'Koneksi database bermasalah.';
    // Set flash message jika fungsi tersedia dan belum ada pesan lain
    if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
        set_flash_message('error', $error_fetch);
    }
}

// --- Helper function untuk cek halaman aktif ---
if (!function_exists('is_admin_page_active')) {
    function is_admin_page_active($page_names) {
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        if (is_array($page_names)) {
            return in_array($current_script, $page_names);
        }
        return $current_script === $page_names;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sedaya Furniture</title>
    <link rel="icon" href="../images/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* PASTE CSS DARI page-template.php YANG SUDAH DIPERBAIKI KE SINI */
        /* Pastikan CSS di sini sama persis dengan CSS di page-template.php */
        :root {
            --sidebar-width: 260px;
            --topbar-height: 60px;
            --sidebar-bg: #212529;
            --sidebar-link-color: rgba(255, 255, 255, 0.7);
            --sidebar-link-hover-color: #ffffff;
            --sidebar-link-hover-bg: rgba(255, 255, 255, 0.1);
            --sidebar-link-active-bg: #0d6efd;
            --sidebar-link-active-color: #ffffff;
            --transition-speed: 0.3s;
        }
        body { padding-top: var(--topbar-height); background-color: #f8f9fa; overflow-x: hidden; }
        .page-wrapper { display: flex; min-height: calc(100vh - var(--topbar-height)); }
        .topbar { height: var(--topbar-height); background-color: var(--sidebar-bg); position: fixed; top: 0; right: 0; left: 0; z-index: 1030; display: flex; align-items: center; padding: 0 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); color: white; }
        .hamburger-btn { font-size: 1.5rem; color: white; cursor: pointer; background: none; border: none; padding: 0 0.5rem; margin-right: 1rem; }
        .brand-logo { font-weight: bold; font-size: 1.25rem; color: white; text-decoration: none; white-space: nowrap; }
        .user-menu { margin-left: auto; display: flex; align-items: center; }
        .user-info { color: white; margin-right: 1rem; display: flex; align-items: center; white-space: nowrap; }
        .user-info i { margin-right: 0.3rem; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: var(--sidebar-link-color); position: fixed; top: var(--topbar-height); bottom: 0; left: 0; z-index: 1020; overflow-y: auto; transition: transform var(--transition-speed) ease, width var(--transition-speed) ease; display: flex; flex-direction: column; }
        .sidebar.collapsed { transform: translateX(-100%); }
        .user-profile { padding: 1.25rem 1rem; border-bottom: 1px solid rgba(255,255,255,.1); }
        .user-profile i { font-size: 2rem; }
        .nav-title { padding: 0.75rem 1rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,.5); margin-top: 0.5rem; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--sidebar-link-color); text-decoration: none; transition: background-color var(--transition-speed), color var(--transition-speed); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-menu li a:hover { color: var(--sidebar-link-hover-color); background-color: var(--sidebar-link-hover-bg); }
        .sidebar-menu li a.active { color: var(--sidebar-link-active-color); background-color: var(--sidebar-link-active-bg); font-weight: 500; }
        .sidebar-menu li i { margin-right: 0.8rem; font-size: 1.1rem; width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,.1); margin-top: auto; }
        .main-content { flex-grow: 1; padding: 1.5rem; margin-left: var(--sidebar-width); transition: margin-left var(--transition-speed) ease; }
        .main-content.collapsed { margin-left: 0; }
        .sidebar-overlay { position: fixed; top: var(--topbar-height); left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 1019; opacity: 0; visibility: hidden; transition: opacity var(--transition-speed) ease, visibility var(--transition-speed) ease; }
        .sidebar-overlay.show { opacity: 1; visibility: visible; }
        .footer { padding: 1rem 0; text-align: center; border-top: 1px solid #dee2e6; background-color: #ffffff; margin-top: 2rem; margin-left: var(--sidebar-width); transition: margin-left var(--transition-speed) ease; }
        .footer.collapsed { margin-left: 0; }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(-100%); z-index: 1040; } .sidebar.expanded { transform: translateX(0); } .main-content { margin-left: 0 !important; } .footer { margin-left: 0 !important; } .user-info span { display: none; } }
        .flash-container { position: relative; z-index: 1050; padding: 0 1.5rem; margin-left: var(--sidebar-width); transition: margin-left var(--transition-speed) ease; }
        .flash-container.collapsed { margin-left: 0; }
        .flash-container .alert { margin-top: 1rem; }
        @media (max-width: 991.98px) { .flash-container { margin-left: 0 !important; } }

        /* Dashboard specific styles */
        .summary-box { border-radius: 10px; color: white; padding: 20px; height: 100%; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .summary-box:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); }
        .summary-box .icon i { font-size: 2.5rem; opacity: 0.8; }
        .summary-box .info { text-align: right; }
        .summary-box .info h3 { font-size: 1.2rem; margin-bottom: 5px; font-weight: 600; }
        .summary-box .info p { font-size: 1.8rem; margin-bottom: 8px; font-weight: bold; }
        .link-detail { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: color 0.2s ease; font-size: 0.9rem; }
        .link-detail:hover { color: white; text-decoration: underline; }
        .link-detail i { margin-right: 5px; }
        .summary-kategori { background: linear-gradient(135deg, #28a745, #218838); }
        .summary-produk { background: linear-gradient(135deg, #007bff, #0056b3); }
        .summary-user { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .summary-carrousel { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
        .summary-galery { background: linear-gradient(135deg, #17a2b8, #117a8b); }
        .summary-info-page { background: linear-gradient(135deg, #fd7e14, #e67e22); }
        h1.dashboard-title { color: #343a40; font-weight: bold; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="topbar">
        <button id="sidebarToggle" class="hamburger-btn" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <a href="index.php" class="brand-logo">SEDAYA ADMIN</a>
        <div class="user-menu">
            <div class="user-info d-none d-md-flex">
                <i class="bi bi-person-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger ms-2" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-sm-inline-block ms-1">Logout</span>
            </a>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="sidebar" id="sidebar">
             <div class="user-profile">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle me-2"></i>
                    <div>
                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
            </div>
            <div class="nav-title">Menu Navigasi</div>
            <ul class="sidebar-menu">
                 <li><a href="index.php" class="<?php echo is_admin_page_active('index.php') ? 'active' : ''; ?>"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a></li>
                <li><a href="kategori.php" class="<?php echo is_admin_page_active(['kategori.php', 'kategori-detail.php']) ? 'active' : ''; ?>"><i class="bi bi-tags-fill"></i><span>Kategori</span></a></li>
                <li><a href="produk.php" class="<?php echo is_admin_page_active(['produk.php', 'produk-detail.php', 'tambahproduk.php', 'lihatproduk.php']) ? 'active' : ''; ?>"><i class="bi bi-box-seam-fill"></i><span>Produk</span></a></li>
                <li><a href="carrousel.php" class="<?php echo is_admin_page_active('carrousel.php') ? 'active' : ''; ?>"><i class="bi bi-images"></i><span>Carrousel</span></a></li>
                <li><a href="galery.php" class="<?php echo is_admin_page_active('galery.php') ? 'active' : ''; ?>"><i class="bi bi-collection-fill"></i><span>Galeri</span></a></li>
                <li><a href="info_pages.php" class="<?php echo is_admin_page_active(['info_pages.php', 'info_pages_edit.php', 'info_pages_tambah.php']) ? 'active' : ''; ?>"><i class="bi bi-file-earmark-text-fill"></i><span>Halaman Info</span></a></li>
                <li><a href="user.php" class="<?php echo is_admin_page_active(['user.php', 'edituser.php', 'tambahanggota.php']) ? 'active' : ''; ?>"><i class="bi bi-people-fill"></i><span>User</span></a></li>
                <li><a href="pesanan.php" class="<?php echo is_admin_page_active(['pesanan.php', 'pesanan_tambah.php', 'pesanan_detail.php']) ? 'active' : ''; ?>"><i class="bi bi-receipt"></i><span>Pesanan</span></a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php" class="btn btn-danger w-100 btn-sm" onclick="return confirm('Apakah Anda yakin ingin logout?');">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>

        <div class="main-content" id="mainContent">
             <div class="flash-container" id="flashContainer">
                 <?php
                    // Pastikan fungsi display_flash_message ada sebelum memanggil
                    if (function_exists('display_flash_message')) {
                         display_flash_message();
                    }
                 ?>
            </div>

            <h1 class="dashboard-title"><i class="bi bi-speedometer2 me-2"></i> Dashboard Admin</h1>

            <?php // Tampilkan error fetch data (jika ada dan bukan error koneksi yg sudah jadi flash)
            if ($error_fetch && !(isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'error')): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($error_fetch); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-kategori">
                        <div class="icon"><i class="bi bi-tags-fill"></i></div>
                        <div class="info">
                            <h3>Kategori</h3>
                            <p><?php echo $jumlahKategori; ?></p>
                            <a href="kategori.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-produk">
                        <div class="icon"><i class="bi bi-box-seam-fill"></i></div>
                        <div class="info">
                            <h3>Produk</h3>
                            <p><?php echo $jumlahProduk; ?></p>
                            <a href="produk.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                 <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-user">
                        <div class="icon"><i class="bi bi-people-fill"></i></div>
                        <div class="info">
                            <h3>Pengguna</h3>
                            <p><?php echo $jumlahUser; ?></p>
                            <a href="user.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-carrousel">
                        <div class="icon"><i class="bi bi-images"></i></div>
                        <div class="info">
                            <h3>Carrousel</h3>
                            <p><?php echo $jumlahCarrousel; ?></p>
                            <a href="carrousel.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                 <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-galery">
                        <div class="icon"><i class="bi bi-collection-fill"></i></div>
                        <div class="info">
                            <h3>Galeri</h3>
                            <p><?php echo $jumlahGalery; ?></p>
                            <a href="galery.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="summary-box summary-info-page">
                        <div class="icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div class="info">
                            <h3>Info Pages</h3>
                            <p><?php echo $jumlahHalamanInfo; ?></p>
                            <a href="info_pages.php" class="link-detail"><i class="bi bi-arrow-right-circle-fill"></i> Lihat Detail</a>
                        </div>
                    </div>
                </div>
                 </div> </div> </div> <div class="sidebar-overlay" id="sidebarOverlay"></div>

     <footer class="footer" id="pageFooter">
         <div class="container-fluid text-center">
            <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
        </div>
     </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PASTE JAVASCRIPT DARI page-template.php YANG SUDAH DIPERBAIKI KE SINI
        // Pastikan JavaScript di sini sama persis dengan JS di page-template.php
         document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const pageFooter = document.getElementById('pageFooter'); // Get footer
            const flashContainer = document.getElementById('flashContainer'); // Get flash container

            if (!sidebar || !mainContent || !sidebarToggle || !sidebarOverlay || !pageFooter || !flashContainer) {
                console.error("Sidebar layout elements not found!");
                return;
            }

            const mobileBreakpoint = 991.98;

            function isMobileView() { return window.innerWidth <= mobileBreakpoint; }

            function setSidebarState(isCollapsed) {
                 if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('collapsed');
                    pageFooter.classList.add('collapsed');
                    flashContainer.classList.add('collapsed');
                    sidebarOverlay.classList.remove('show');
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('collapsed');
                    pageFooter.classList.remove('collapsed');
                    flashContainer.classList.remove('collapsed');
                    if (isMobileView()) {
                        sidebarOverlay.classList.add('show');
                    }
                    localStorage.setItem('sidebarState', 'expanded');
                }
                sidebar.classList.toggle('expanded', !isCollapsed);
            }

            function toggleSidebar() {
                const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');
                setSidebarState(!isCurrentlyCollapsed);
            }

            let initialStateCollapsed;
            const savedState = localStorage.getItem('sidebarState');

            if (isMobileView()) {
                initialStateCollapsed = true;
            } else {
                initialStateCollapsed = savedState === 'collapsed';
            }
             // Hapus transisi sementara untuk set state awal
             sidebar.style.transition = 'none';
             mainContent.style.transition = 'none';
             pageFooter.style.transition = 'none';
             flashContainer.style.transition = 'none';
             sidebarOverlay.style.transition = 'none';

             setSidebarState(initialStateCollapsed);

             // Kembalikan transisi setelah sedikit delay
             setTimeout(() => {
                 sidebar.style.transition = 'transform var(--transition-speed) ease, width var(--transition-speed) ease';
                 mainContent.style.transition = 'margin-left var(--transition-speed) ease';
                 pageFooter.style.transition = 'margin-left var(--transition-speed) ease';
                 flashContainer.style.transition = 'margin-left var(--transition-speed) ease';
                 sidebarOverlay.style.transition = 'opacity var(--transition-speed) ease, visibility var(--transition-speed) ease';
             }, 50);


            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', () => {
                 if (!sidebar.classList.contains('collapsed')) { setSidebarState(true); }
            });

            let currentViewIsMobile = isMobileView();
            window.addEventListener('resize', () => {
                const nowIsMobile = isMobileView();
                if (nowIsMobile !== currentViewIsMobile) {
                    if (nowIsMobile) {
                        setSidebarState(true);
                    } else {
                        const savedStateOnDesktop = localStorage.getItem('sidebarState');
                        setSidebarState(savedStateOnDesktop === 'collapsed');
                    }
                    currentViewIsMobile = nowIsMobile;
                }
                 if (!nowIsMobile) { sidebarOverlay.classList.remove('show'); }
            });
        });
    </script>
</body>
</html>