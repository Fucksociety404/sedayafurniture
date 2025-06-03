<?php
// File: admin/page-template.php (Revised)
// Template untuk semua halaman admin

// Fungsi untuk membuat template header
function admin_header($title = 'Admin Panel', $extra_styles = '') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Sesuaikan path jika session.php tidak di root admin
    require_once 'session.php'; // Pastikan path ini benar
    require_login(); // Check login status

    $admin_username = $_SESSION['username'] ?? 'Pengguna';

    // Tentukan base path untuk aset (CSS, JS, gambar) relatif terhadap file ini
    // Jika page-template.php ada di /admin/, maka base path untuk gambar di /images/ adalah '../images/'
    $base_asset_path = '../'; // Sesuaikan jika struktur folder berbeda

    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Sedaya Furniture</title>
    <link rel="icon" href="<?php echo $base_asset_path; ?>images/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Core Styles for Sidebar Layout */
        :root {
            --sidebar-width: 260px; /* Sedikit lebih lebar */
            --topbar-height: 60px;
            --sidebar-bg: #212529; /* Dark */
            --sidebar-link-color: rgba(255, 255, 255, 0.7);
            --sidebar-link-hover-color: #ffffff;
            --sidebar-link-hover-bg: rgba(255, 255, 255, 0.1);
            --sidebar-link-active-bg: #0d6efd; /* Bootstrap Primary */
            --sidebar-link-active-color: #ffffff;
            --transition-speed: 0.3s;
        }

        body {
            padding-top: var(--topbar-height); /* Jarak untuk fixed topbar */
            background-color: #f8f9fa; /* Warna latar belakang konten */
            overflow-x: hidden; /* Cegah scroll horizontal */
        }

        .page-wrapper {
            display: flex;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* Topbar */
        .topbar {
            height: var(--topbar-height);
            background-color: var(--sidebar-bg);
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030; /* Di atas sidebar */
            display: flex;
            align-items: center;
            padding: 0 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: white;
        }

        .hamburger-btn {
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0 0.5rem;
            margin-right: 1rem;
        }

        .brand-logo {
            font-weight: bold;
            font-size: 1.25rem;
            color: white;
            text-decoration: none;
            white-space: nowrap;
        }

        .user-menu {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .user-info {
            color: white;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        .user-info i { margin-right: 0.3rem; }


        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: var(--sidebar-link-color);
            position: fixed;
            top: var(--topbar-height);
            bottom: 0;
            left: 0; /* Default tampil */
            z-index: 1020; /* Di bawah topbar */
            overflow-y: auto;
            transition: transform var(--transition-speed) ease, width var(--transition-speed) ease; /* Transisi transform */
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            transform: translateX(-100%); /* Geser ke kiri saat collapsed */
            /* Optional: bisa juga width: 0; tapi transform lebih halus */
        }

        .user-profile {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .user-profile i { font-size: 2rem; }

        .nav-title {
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,.5);
            margin-top: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1; /* Agar menu mengisi sisa ruang */
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--sidebar-link-color);
            text-decoration: none;
            transition: background-color var(--transition-speed), color var(--transition-speed);
            white-space: nowrap; /* Cegah teks wrap */
            overflow: hidden; /* Sembunyikan teks jika sidebar menyempit */
            text-overflow: ellipsis;
        }
        .sidebar-menu li a:hover {
            color: var(--sidebar-link-hover-color);
            background-color: var(--sidebar-link-hover-bg);
        }
        .sidebar-menu li a.active {
            color: var(--sidebar-link-active-color);
            background-color: var(--sidebar-link-active-bg);
            font-weight: 500;
        }
        .sidebar-menu li i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0; /* Icon tidak mengecil */
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.1);
            margin-top: auto; /* Dorong footer ke bawah */
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 1.5rem;
            margin-left: var(--sidebar-width); /* Default margin */
            transition: margin-left var(--transition-speed) ease;
        }

        .main-content.collapsed {
            margin-left: 0; /* Margin 0 saat sidebar collapsed */
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: var(--topbar-height);
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1019; /* Di bawah sidebar tapi di atas konten */
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-speed) ease, visibility var(--transition-speed) ease;
        }
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Footer */
        .footer {
             padding: 1rem 0;
             text-align: center;
             border-top: 1px solid #dee2e6;
             background-color: #ffffff; /* Atau #f8f9fa */
             margin-top: 2rem; /* Jarak dari konten */
             /* Pastikan footer tidak terpengaruh margin main-content */
             margin-left: var(--sidebar-width);
             transition: margin-left var(--transition-speed) ease;
         }
         .footer.collapsed {
              margin-left: 0;
          }


        /* Responsive: Aturan ini aktif di layar <= 991.98px */
        @media (max-width: 991.98px) {
             body {
                 /* Tidak perlu padding-top karena topbar fixed */
             }
             .sidebar {
                transform: translateX(-100%); /* Sembunyikan sidebar by default di mobile */
                z-index: 1040; /* Di atas overlay saat tampil */
             }
             .sidebar.expanded {
                transform: translateX(0); /* Tampilkan saat expanded */
             }
             .main-content {
                margin-left: 0 !important; /* Selalu margin 0 di mobile */
             }
             .footer {
                 margin-left: 0 !important; /* Selalu margin 0 di mobile */
             }
             /* Overlay hanya perlu di mobile */
             .sidebar-overlay.show {
                 /* Sudah diatur di style utama */
             }
             .user-info span {
                 display: none; /* Sembunyikan teks nama di mobile */
             }
        }
         /* Fix untuk container flash message agar tidak terdorong margin */
         .flash-container {
             position: relative; /* Atau sesuaikan agar tidak terpengaruh margin main-content */
             z-index: 1050; /* Pastikan di atas segalanya */
             padding: 0 1.5rem; /* Sesuaikan padding dengan main-content */
             margin-left: var(--sidebar-width); /* Ikuti margin main-content */
             transition: margin-left var(--transition-speed) ease;
          }
         .flash-container.collapsed {
              margin-left: 0;
         }
         .flash-container .alert {
             margin-top: 1rem; /* Jarak dari atas atau topbar */
         }
          @media (max-width: 991.98px) {
              .flash-container {
                  margin-left: 0 !important;
              }
          }

    </style>
    <?php echo $extra_styles; ?>
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
                <span><?php echo htmlspecialchars($admin_username); ?></span>
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
                        <div class="fw-bold"><?php echo htmlspecialchars($admin_username); ?></div>
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
            </div>

            <div class="nav-title">Menu Navigasi</div>

            <ul class="sidebar-menu">
                <?php
                // Helper function to check active page more easily
                function is_admin_page_active($page_names) {
                    $current_script = basename($_SERVER['SCRIPT_NAME']);
                    if (is_array($page_names)) {
                        return in_array($current_script, $page_names);
                    }
                    return $current_script === $page_names;
                }
                ?>
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
                 <?php display_flash_message(); // Display flash messages here ?>
            </div>
            <?php
}

// Fungsi untuk membuat template footer
function admin_footer() {
    ?>
            </div> </div> <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <footer class="footer" id="pageFooter">
         <div class="container-fluid text-center"> <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
        </div>
     </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const pageFooter = document.getElementById('pageFooter'); // Get footer
            const flashContainer = document.getElementById('flashContainer'); // Get flash container

            // --- Check if elements exist ---
            if (!sidebar || !mainContent || !sidebarToggle || !sidebarOverlay || !pageFooter || !flashContainer) {
                console.error("Sidebar layout elements not found!");
                return; // Stop execution if elements are missing
            }

            const mobileBreakpoint = 991.98; // Bootstrap's lg breakpoint upper limit

            // --- Function to check if on mobile view ---
            function isMobileView() {
                return window.innerWidth <= mobileBreakpoint;
            }

            // --- Function to set sidebar state ---
            function setSidebarState(isCollapsed) {
                if (isCollapsed) {
                    sidebar.classList.add('collapsed'); // Hide sidebar
                    mainContent.classList.add('collapsed'); // Expand content margin
                    pageFooter.classList.add('collapsed'); // Expand footer margin
                    flashContainer.classList.add('collapsed'); // Expand flash margin
                    sidebarOverlay.classList.remove('show'); // Hide overlay
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    sidebar.classList.remove('collapsed'); // Show sidebar
                    mainContent.classList.remove('collapsed'); // Normal content margin
                    pageFooter.classList.remove('collapsed'); // Normal footer margin
                    flashContainer.classList.remove('collapsed'); // Normal flash margin
                    if (isMobileView()) {
                        sidebarOverlay.classList.add('show'); // Show overlay only on mobile when expanded
                    }
                    localStorage.setItem('sidebarState', 'expanded');
                }
                // Add 'expanded' class for potential specific styling (optional)
                sidebar.classList.toggle('expanded', !isCollapsed);
            }

            // --- Function to toggle sidebar ---
            function toggleSidebar() {
                const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');
                setSidebarState(!isCurrentlyCollapsed); // Toggle the state
            }

            // --- Initial Setup ---
            let initialStateCollapsed;
            const savedState = localStorage.getItem('sidebarState');

            if (isMobileView()) {
                initialStateCollapsed = true; // Always start collapsed on mobile
            } else {
                // On desktop, use saved state or default to expanded
                initialStateCollapsed = savedState === 'collapsed';
            }
            setSidebarState(initialStateCollapsed); // Apply initial state without animation first

            // Add transition class after initial setup to avoid animation on load
            setTimeout(() => {
                 sidebar.style.transition = 'transform var(--transition-speed) ease, width var(--transition-speed) ease';
                 mainContent.style.transition = 'margin-left var(--transition-speed) ease';
                 pageFooter.style.transition = 'margin-left var(--transition-speed) ease';
                 flashContainer.style.transition = 'margin-left var(--transition-speed) ease';
                 sidebarOverlay.style.transition = 'opacity var(--transition-speed) ease, visibility var(--transition-speed) ease';
             }, 50);


            // --- Event Listeners ---
            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', () => {
                // Close sidebar when overlay is clicked (only relevant on mobile)
                 if (!sidebar.classList.contains('collapsed')) {
                     setSidebarState(true); // Collapse the sidebar
                 }
            });

            // --- Responsive Handling ---
            let currentViewIsMobile = isMobileView();
            window.addEventListener('resize', () => {
                const nowIsMobile = isMobileView();
                if (nowIsMobile !== currentViewIsMobile) {
                    // Viewport crossed the breakpoint
                    if (nowIsMobile) {
                        // Switched to Mobile: Always collapse sidebar
                        setSidebarState(true);
                    } else {
                        // Switched to Desktop: Restore saved state or default expanded
                        const savedStateOnDesktop = localStorage.getItem('sidebarState');
                        setSidebarState(savedStateOnDesktop === 'collapsed');
                    }
                    currentViewIsMobile = nowIsMobile;
                }

                // Ensure overlay is hidden on desktop view regardless of sidebar state
                 if (!nowIsMobile) {
                     sidebarOverlay.classList.remove('show');
                 }
            });
        });
    </script>
</body>
</html>
    <?php
}
?>