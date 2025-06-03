<?php
// File: /newsedaya24Apr2025/index.php (Frontend Utama - Diperbaiki)

// Tampilkan error untuk debugging (hapus atau set ke 0 di production)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// 1. Sertakan file utilitas dan koneksi dari root proyek
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/config/koneksi.php'; // <-- Gunakan koneksi dari root /config

// Variabel global $con dari config/koneksi.php
global $con;

// Inisialisasi variabel flag error koneksi
$database_connection_error = false;
if (!isset($con) || !$con instanceof mysqli) {
    $database_connection_error = true;
    log_error("Root Index - Koneksi database tidak valid (\$con dari config/koneksi.php).");
}

// --- Konfigurasi Dasar & SEO ---
$nama_toko = "Sedaya Furniture";
$tagline = "Custom Furniture Jepara";
$deskripsi_seo = "Temukan Indonesian furniture berkualitas ekspor langsung dari Jepara furniture manufacturer terpercaya. Kami spesialis dalam teak furniture Jepara, wood carving Jepara, dan berbagai Jepara wood furniture. Jelajahi koleksi lengkap kami, mulai dari indoor furniture Jepara seperti living room furniture Indonesia, bedroom set Jepara, dining table and chairs Indonesia, dan wooden cabinet Jepara, hingga outdoor furniture Indonesia termasuk garden furniture Jepara, patio set teak wood Indonesia, poolside furniture Jepara, dan weatherproof outdoor furniture. Kami juga menyediakan solid wood furniture online dan layanan custom furniture Jepara, made to order furniture Indonesia, memungkinkan Anda design your own furniture online atau memesan bespoke furniture Indonesia. Dapatkan solid teak dining table Indonesia, hand carved Jepara wood furniture, reclaimed teak furniture Jepara, Indonesian mahogany furniture, dan rattan furniture from Indonesia dengan desain minimalist teak furniture, modern Indonesian furniture, classic Jepara style furniture, hingga rustic wood furniture Indonesia. Kami berpengalaman melayani kebutuhan furniture for hotels Indonesia dan furniture for resorts Jepara. Percayakan kebutuhan Anda pada reliable furniture supplier Indonesia yang mengirim langsung dari direct from manufacturer furniture Indonesia. Kami melayani export furniture from Indonesia ke Australia, USA, Europe, etc. dengan informasi shipping furniture from Indonesia cost yang transparan. Dapatkan Jepara furniture price list export terbaik dari kami.";
$keywords_seo = "Jepara furniture, Indonesian furniture, Teak furniture Jepara, Wood carving Jepara, Furniture manufacturer Jepara, Export furniture from Indonesia, Jepara wood furniture, Central Java furniture, Indoor furniture Jepara, Living room furniture Indonesia, Bedroom set Jepara, Dining table and chairs Indonesia, Wooden cabinet Jepara, Solid wood furniture online, Outdoor furniture Indonesia, Garden furniture Jepara, Patio set teak wood Indonesia, Poolside furniture Jepara, Weatherproof outdoor furniture, Teak garden bench Indonesia, Custom furniture Jepara, Made to order furniture Indonesia, Design your own furniture Jepara, Custom wood furniture online, Bespoke furniture Indonesia, Teak outdoor furniture Jepara, Solid teak dining table Indonesia, Hand carved Jepara wood furniture, Reclaimed teak furniture Jepara, Indonesian mahogany furniture, Rattan furniture from Indonesia, Minimalist teak furniture, Modern Indonesian furniture, Classic Jepara style furniture, Rustic wood furniture Indonesia, Furniture for hotels Indonesia, Furniture for resorts Jepara, Buy furniture online from Indonesia, Order custom furniture Jepara online, Furniture export from Indonesia to Australia, USA, Europe etc., Shipping furniture from Indonesia cost, Reliable furniture supplier Indonesia, Direct from manufacturer furniture Indonesia, Jepara furniture price list export";

// --- Konfigurasi Path & URL ---
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$project_folder = ""; // <<< Nama folder proyek Anda
$project_root_url = rtrim($base_url, '/') . rtrim($project_folder, '/'); // Base URL proyek

// Canonical URL untuk halaman ini (root proyek)
$canonical_url = $project_root_url . '/';

// Base URL untuk halaman di dalam folder /pages/
$pages_url_base = $project_root_url . '/pages/';

// Base URL untuk aset di dalam /assets/ (root-relative)
$assets_url_base = rtrim($project_folder, '/') . '/assets/';

// Base URL untuk gambar di dalam /images/ (root-relative)
$images_url_base = rtrim($project_folder, '/') . '/images/';
$url_dir_carrousel = $images_url_base . "carrousel/";
$url_dir_produk_base = $images_url_base . "produk/";
$url_dir_galeri = $images_url_base . "galery/"; // Sesuaikan jika nama folder 'gallery'

// Lokasi File System Gambar (Path absolut server)
$fs_dir_images_root = __DIR__ . '/images';
$fs_dir_carrousel = $fs_dir_images_root . '/carrousel';
$fs_dir_produk_base = $fs_dir_images_root . '/produk';
$fs_dir_galeri = $fs_dir_images_root . '/galery'; // Sesuaikan jika nama folder 'gallery'

// Placeholder & Default OG Image (URL Absolut)
$default_placeholder_url = $project_root_url . "/images/placeholder.png";
$default_og_image_url = $project_root_url . "/images/og_image_default.png";
$og_image_url = $default_og_image_url; // Default

// --- Data Dinamis (Inisialisasi) ---
$carrousel_items = [];
$produk_unggulan = [];
$galeri_items_db = [];
$categories = [];

// Jalankan query hanya jika koneksi berhasil
if (!$database_connection_error) {
    // Ambil Kategori (untuk header/footer)
    $query_kategori_all = "SELECT id, nama FROM kategori ORDER BY nama ASC";
    $result_kategori_all = mysqli_query($con, $query_kategori_all);
    if ($result_kategori_all) {
        while ($kategori_data = mysqli_fetch_assoc($result_kategori_all)) { $categories[] = $kategori_data; }
        mysqli_free_result($result_kategori_all);
    } else { log_error("Root Index - Gagal fetch kategori all: " . mysqli_error($con)); }

    // --- Query untuk Carrousel ---
    $query_carrousel = "SELECT id, gambar, judul FROM carrousel ORDER BY id DESC";
    $result_carrousel = mysqli_query($con, $query_carrousel);
    if ($result_carrousel) {
        while ($item = mysqli_fetch_assoc($result_carrousel)) { $carrousel_items[] = $item; }
        mysqli_free_result($result_carrousel);
    } else { log_error("Root Index - Gagal fetch carrousel: " . mysqli_error($con)); }

    // --- Query untuk Produk Unggulan ---
    $limit_produk = 4;
    $query_produk = "SELECT id, nama, kode_produk, foto FROM produk WHERE status = 'aktif' ORDER BY RAND() LIMIT ?";
    $stmt_produk = $con->prepare($query_produk);
    if ($stmt_produk) {
        $stmt_produk->bind_param("i", $limit_produk);
        $stmt_produk->execute();
        $result_produk = $stmt_produk->get_result();
        while ($row = $result_produk->fetch_assoc()) { $produk_unggulan[] = $row; }
        $stmt_produk->close();
    } else { log_error("Root Index - Gagal prepare statement produk unggulan: " . $con->error); }

    // --- Ambil Gambar Galeri dari DB ---
    $limit_galeri = 6;
    $query_galeri = "SELECT id, gambar FROM galery ORDER BY id DESC LIMIT ?";
    $stmt_galeri = $con->prepare($query_galeri);
    if ($stmt_galeri) {
        $stmt_galeri->bind_param("i", $limit_galeri);
        $stmt_galeri->execute();
        $result_galeri = $stmt_galeri->get_result();
        while ($item_galeri = $result_galeri->fetch_assoc()) { $galeri_items_db[] = $item_galeri['gambar']; }
        $stmt_galeri->close();
    } else { log_error("Root Index - Gagal prepare statement galeri: " . $con->error); }

    // Tutup koneksi jika sudah tidak dipakai lagi
    // mysqli_close($con); // Pindahkan ke akhir script

} else {
    log_error("Root Index - Tidak dapat menjalankan query karena koneksi database gagal.");
    $categories = []; // Pastikan categories kosong jika koneksi gagal
}

// Tentukan OG Image (setelah query $carrousel_items selesai)
if (!empty($carrousel_items) && !empty($carrousel_items[0]['gambar'])) {
    $first_carousel_image_name = $carrousel_items[0]['gambar'];
    $fs_path_og = rtrim($fs_dir_carrousel, '/') . '/' . $first_carousel_image_name;
    if (file_exists($fs_path_og)) {
        // Gunakan URL root-relative yang sudah benar
        $og_image_url = $project_root_url . '/images/carrousel/' . $first_carousel_image_name;
    }
}

// Siapkan variabel untuk footer
$footer_categories = array_slice($categories, 0, 6);
// $pages_url_base sudah didefinisikan di atas

?>
<!DOCTYPE html>
<html lang="id">
<head>
       <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LHH00CCQR9"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LHH00CCQR9');
</script>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <title><?php echo htmlspecialchars($nama_toko . " - " . $tagline); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords_seo); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($nama_toko); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="<?php echo htmlspecialchars($nama_toko . " - " . $tagline); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars($nama_toko); ?>" />
    <meta property="og:locale" content="id_ID" />

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($nama_toko . " - " . $tagline); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_url); ?>">

    <link rel="icon" href="<?php echo htmlspecialchars($images_url_base . 'favicon.png'); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($images_url_base . 'apple-touch-icon.png'); ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assets_url_base . 'css/style.css'); ?>">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?php echo htmlspecialchars($nama_toko); ?>",
      "url": "<?php echo htmlspecialchars($canonical_url); ?>",
      "potentialAction": {
        "@type": "SearchAction",
        // Target search menggunakan $pages_url_base
        "target": "<?php echo htmlspecialchars($pages_url_base . 'produk.php?keyword={search_term_string}'); ?>",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FurnitureStore",
      "name": "<?php echo htmlspecialchars($nama_toko); ?>",
      "url": "<?php echo htmlspecialchars($canonical_url); ?>", // URL utama toko adalah root
      // Logo URL menggunakan $project_root_url
      "logo": "<?php echo htmlspecialchars($project_root_url . '/images/logo-sedaya-navbar.png'); ?>",
      "image": "<?php echo htmlspecialchars($og_image_url); ?>",
      "description": "<?php echo htmlspecialchars($deskripsi_seo); ?>",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Batealit, Jepara",
        "addressRegion": "Jawa Tengah",
        "addressCountry": "ID"
      },
      "telephone": "+6281567958549",
      "priceRange": "$$"
    }
    </script>

    <style>
        /* Style CSS tetap sama */
        body { padding-top: 70px; }
        .hero .carousel-item { position: relative; height: 70vh; min-height: 400px; background-color: #555; }
        .hero .carousel-item img { object-fit: cover; height: 100%; width: 100%; filter: brightness(0.5); }
        .carousel-caption-custom { position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white; z-index: 10; pointer-events: none; }
        .carousel-caption-custom h1 { font-size: calc(2rem + 1.8vw); font-weight: bold; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8); }
        .carousel-caption-custom p { font-size: calc(1rem + 0.3vw); text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7); margin-bottom: 2rem; max-width: 700px; }
        .carousel-caption-custom .btn { pointer-events: auto; }
        .carousel-indicators button { width: 12px; height: 12px; border-radius: 50%; margin: 0 5px; background-color: rgba(255, 255, 255, 0.5); border: 1px solid rgba(0,0,0,0.2); }
        .carousel-indicators .active { background-color: rgba(255, 255, 255, 0.9); }
        .carousel-control-prev, .carousel-control-next { z-index: 11; }
        .product-card .card-title { min-height: 3.2em; }
        .section-title { font-weight: bold; color: #343a40; margin-bottom: 2.5rem; }
        .section-title::after { content: ''; display: block; width: 60px; height: 3px; background-color: var(--accent-color); margin: 10px auto 0; }
    </style>
</head>
<body>
    <?php
    // Include header dari pages/templat
    // Variabel $categories, $base_url, $project_folder sudah didefinisikan
    include __DIR__ . '/pages/templat/header.php';
    ?>

    <header id="heroCarousel" class="carousel slide hero" data-bs-ride="carousel">
       <div class="carousel-indicators" style="z-index: 11;">
             <?php if (!empty($carrousel_items)): ?>
                <?php foreach ($carrousel_items as $index => $item): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo ($index == 0) ? 'active' : ''; ?>" aria-current="<?php echo ($index == 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            <?php else: ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <?php endif; ?>
        </div>

        <div class="carousel-inner h-100">
            <?php if (!empty($carrousel_items)): ?>
                <?php foreach ($carrousel_items as $index => $item): ?>
                    <?php
                        $gambar_carrousel_url = $default_placeholder_url; // Default placeholder absolut
                        $alt_carousel = "Sedaya Furniture Banner " . ($index + 1);
                        if (!empty($item['gambar'])) {
                            $gambar_filename = $item['gambar'];
                            $fs_path = rtrim($fs_dir_carrousel, '/') . '/' . $gambar_filename;
                            if (file_exists($fs_path)) {
                                // Gunakan URL root-relative yang sudah benar
                                $gambar_carrousel_url = $project_root_url . '/images/carrousel/' . $gambar_filename;
                                $alt_carousel = htmlspecialchars($item['judul'] ?: "Sedaya Furniture Banner " . ($index + 1));
                            } else {
                                 log_error("Root Index - File Carousel tidak ditemukan: " . $fs_path);
                            }
                        }
                    ?>
                    <div class="carousel-item h-100 <?php echo ($index == 0) ? 'active' : ''; ?>">
                         <img src="<?php echo htmlspecialchars($gambar_carrousel_url); ?>" class="d-block w-100" alt="<?php echo $alt_carousel; ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active h-100">
                    <img src="<?php echo htmlspecialchars($default_placeholder_url); ?>" class="d-block w-100" alt="Selamat Datang di <?php echo htmlspecialchars($nama_toko); ?>">
                </div>
            <?php endif; ?>
        </div>

        <div class="carousel-caption-custom">
            <h1 class="animate__animated animate__fadeInDown"><?php echo "Selamat Datang di " . htmlspecialchars($nama_toko); ?></h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s" style="text-shadow: 1px 1px 3px rgba(0,0,0,0.7); max-width: 700px;">
                <?php echo htmlspecialchars($tagline); ?>
            </p>
            <a href="<?php echo htmlspecialchars($pages_url_base . 'produk.php'); ?>" class="btn btn-primary btn-lg mt-3 animate__animated animate__fadeInUp animate__delay-2s">
                Jelajahi Koleksi Kami
            </a>
        </div>

        <?php if (count($carrousel_items) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" style="z-index: 11;">...</button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" style="z-index: 11;">...</button>
        <?php endif; ?>
    </header>

    <section id="produk-unggulan" class="py-5 bg-light">
         <div class="container">
            <h2 class="section-title text-center">Produk Unggulan</h2>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php if (!empty($produk_unggulan)): ?>
                    <?php foreach ($produk_unggulan as $produk): ?>
                        <?php
                            $kode_produk = $produk['kode_produk'];
                            $gambar_filename = $produk['foto'];
                            $gambar_url = $default_placeholder_url; // Default placeholder absolut
                            $alt_produk = "Placeholder untuk " . htmlspecialchars($produk['nama']);
                            // Link ke halaman detail produk menggunakan $pages_url_base
                            $detail_page_url = htmlspecialchars($pages_url_base . 'detail-produk.php?id=' . $produk['id']);

                            if (!empty($gambar_filename) && !empty($kode_produk)) {
                                $fs_path_produk = rtrim($fs_dir_produk_base, '/') . '/' . $kode_produk . "/" . $gambar_filename;
                                if (file_exists($fs_path_produk)) {
                                    // Gunakan URL root-relative yang sudah benar
                                    $gambar_url = $project_root_url . '/images/produk/' . $kode_produk . "/" . $gambar_filename;
                                    $alt_produk = htmlspecialchars($produk['nama']);
                                } else {
                                     log_error("Root Index - File Produk Unggulan tidak ditemukan: " . $fs_path_produk);
                                }
                            }
                        ?>
                        <div class="col d-flex align-items-stretch">
                            <div class="card product-card h-100">
                                <a href="<?php echo $detail_page_url; ?>">
                                     <img src="<?php echo htmlspecialchars($gambar_url); ?>" class="card-img-top" alt="<?php echo $alt_produk; ?>" loading="lazy">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h3 class="card-title h6">
                                        <a href="<?php echo $detail_page_url; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($produk['nama']); ?>
                                        </a>
                                    </h3>
                                    <a href="<?php echo $detail_page_url; ?>" class="btn btn-sm btn-outline-primary details-button mt-auto">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                     <?php if (!$database_connection_error): ?>
                        <p class="text-center col-12 text-muted">Produk unggulan akan segera hadir.</p>
                     <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="text-center mt-5">
                <a href="<?php echo htmlspecialchars($pages_url_base . 'produk.php'); ?>" class="btn btn-primary btn-lg">Lihat Semua Produk</a>
            </div>
        </div>
    </section>

    <section id="galeri" class="py-5">
     <div class="container">
        <h2 class="section-title text-center">Galeri Kami</h2>
        <div class="row row-cols-2 row-cols-md-3 g-3">
             <?php if (!empty($galeri_items_db)): ?>
                <?php foreach ($galeri_items_db as $index => $gambar_galeri_nama): ?>
                     <?php
                         $gambar_galeri_url = $default_placeholder_url; // Default placeholder absolut
                         $alt_galeri = "Galeri Sedaya Furniture " . ($index + 1);
                         if(!empty($gambar_galeri_nama)) {
                             $fs_path_galeri = rtrim($fs_dir_galeri, '/') . '/' . $gambar_galeri_nama;
                             if (file_exists($fs_path_galeri)) {
                                 // Gunakan URL root-relative yang sudah benar
                                 $gambar_galeri_url = $project_root_url . '/images/galery/' . $gambar_galeri_nama; // Sesuaikan 'galery'/'gallery'
                                 $alt_galeri = "Galeri " . htmlspecialchars(ucwords(str_replace(['-', '_'], ' ', pathinfo($gambar_galeri_nama, PATHINFO_FILENAME))));
                             } else {
                                 log_error("Root Index - File Galeri tidak ditemukan: " . $fs_path_galeri);
                             }
                         } else {
                              log_error("Root Index - Nama file Galeri kosong dari DB untuk item index " . $index);
                         }
                     ?>
                    <div class="col">
                         <div class="gallery-item shadow-sm">
                             <img src="<?php echo htmlspecialchars($gambar_galeri_url); ?>" class="img-fluid" alt="<?php echo $alt_galeri; ?>" loading="lazy">
                         </div>
                    </div>
                <?php endforeach; ?>
             <?php else: ?>
                 <?php if (!$database_connection_error): ?>
                    <p class="text-center col-12 text-muted">Galeri foto akan segera diperbarui.</p>
                 <?php endif; ?>
             <?php endif; ?>
        </div>
         </div>
</section>

    <section id="cta" class="py-5 bg-primary text-white">
         <div class="container text-center">
            <h2 class="display-6 mb-3">Butuh Furniture Custom?</h2>
            <p class="lead mb-4">Wujudkan desain furniture impian Anda bersama kami. Diskusikan kebutuhan Anda sekarang!</p>
            <a href="<?php echo htmlspecialchars($pages_url_base . 'kontak.php'); ?>" class="btn btn-light btn-lg me-md-2"><i class="bi bi-envelope-fill me-2"></i>Hubungi Kami</a>
             <a href="https://wa.me/6281567958549?text=Halo%20Sedaya%20Furniture,%20saya%20tertarik%20dengan%20produk%20Anda." target="_blank" class="btn btn-success btn-lg mt-2 mt-md-0"><i class="bi bi-whatsapp me-2"></i> Chat WhatsApp</a>
        </div>
    </section>

    <?php
    // Include footer dari pages/templat
    // Variabel $footer_categories, $categories, $base_url, $project_folder, $pages_url_base sudah didefinisikan
    include __DIR__ . '/pages/templat/footer.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($assets_url_base . 'js/main.js'); ?>"></script>
</body>
</html>
<?php
// Tutup koneksi jika dibuka di awal
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>