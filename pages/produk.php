<?php
// File: pages/produk.php (Lengkap - Dengan Pencarian, Limit, Pagination, dan Tampilan Card)
// error_reporting(E_ALL); ini_set('display_errors', 1); // Uncomment untuk debug

// 1. Sertakan Koneksi & Utilitas dari root proyek
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/utils.php'; // Untuk log_error jika diperlukan

global $con;

// Inisialisasi variabel
$produk_list = [];
$kategori_list = [];
$categories = []; // Untuk header/footer
$nama_kategori_seo = '';
$kategori_id = null;
$kategori_valid = false;
$error_message = null;
$database_connection_error = false;
$nama_toko = "Sedaya Furniture"; // Definisikan nama toko

// --- KONFIGURASI PATH & URL ---
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$project_folder = ""; // Kosongkan ("") jika di root domain
$project_root_url = rtrim($base_url, '/') . rtrim($project_folder, '/');
$pages_url_base = $project_root_url . '/pages/';
$assets_url_base = rtrim($project_folder, '/') . '/assets/';
$images_url_base = rtrim($project_folder, '/') . '/images/';
$url_dir_produk_base = $images_url_base . "produk/";
$fs_dir_images_root = realpath(__DIR__ . '/..') . '/images';
$fs_dir_produk_base = $fs_dir_images_root . '/produk/';
$default_placeholder_url = $project_root_url . "/images/placeholder.png";
$og_image_default = $project_root_url . "/images/og_image_produk.png";
// -------------------------------------------------

// --- PENGATURAN PAGINATION & LIMIT ---
$allowed_limits = [8, 12, 16, 24, 32]; // Sesuaikan opsi limit
$default_limit = 8; // Default tampilan per halaman
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
if (!$limit || !in_array($limit, $allowed_limits)) { $limit = $default_limit; }
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$currentPage || $currentPage < 1) { $currentPage = 1; }
// --------------------------------------

// --- === BARU: Ambil Keyword Pencarian === ---
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS);
$keyword = $keyword ? trim($keyword) : ''; // Trim keyword atau set jadi string kosong
// -----------------------------------------

// Cek Koneksi Database
if (!isset($con) || !$con instanceof mysqli) {
    $database_connection_error = true;
    log_error("Produk - Koneksi database tidak valid (\$con dari config/koneksi.php).");
    $error_message = "Tidak dapat terhubung ke database.";
}

// --- Ambil Semua Kategori ---
if (!$database_connection_error) {
    $kategori_query = "SELECT id, nama FROM kategori ORDER BY nama ASC";
    $kategori_result = mysqli_query($con, $kategori_query);
    if ($kategori_result) {
        while ($row = mysqli_fetch_assoc($kategori_result)) { $kategori_list[] = $row; }
        $categories = $kategori_list; // Untuk header/footer
        mysqli_free_result($kategori_result);
    } else { log_error("Produk - Error fetching kategori: " . mysqli_error($con)); $error_message = "Gagal memuat data kategori."; }
} else { $categories = []; }

// --- Proses Filter Kategori ---
$filter_kategori_id = filter_input(INPUT_GET, 'kategori', FILTER_VALIDATE_INT);
if ($filter_kategori_id && !empty($kategori_list)) {
    foreach ($kategori_list as $kat) { if ($kat['id'] == $filter_kategori_id) { $nama_kategori_seo = htmlspecialchars($kat['nama']); $kategori_id = $filter_kategori_id; $kategori_valid = true; break; } }
}

// --- Variabel untuk Pagination ---
$totalProduk = 0; $totalPages = 0; $offset = ($currentPage - 1) * $limit;

// --- Query untuk Menghitung TOTAL Produk (dengan filter & keyword) ---
if (!$database_connection_error && !$error_message) {
    // ===>> DIMODIFIKASI: Tambah LEFT JOIN dan kondisi keyword <<===
    $sql_count = "SELECT COUNT(DISTINCT p.id) as total
                  FROM produk p
                  LEFT JOIN kategori k ON p.kategori_id = k.id
                  WHERE p.status = 'aktif'";
    $params_count = [];
    $types_count = "";
    $conditions = []; // Array untuk menampung kondisi WHERE tambahan

    if ($kategori_valid && $kategori_id !== null) {
        $conditions[] = "p.kategori_id = ?";
        $params_count[] = $kategori_id;
        $types_count .= "i";
    }

    // ===>> BARU: Tambah kondisi keyword <<===
    if (!empty($keyword)) {
        $keyword_param = "%" . $keyword . "%";
        // Cari di nama produk ATAU kode produk ATAU nama kategori
        $conditions[] = "(p.nama LIKE ? OR p.kode_produk LIKE ? OR k.nama LIKE ?)";
        $params_count[] = $keyword_param;
        $params_count[] = $keyword_param;
        $params_count[] = $keyword_param;
        $types_count .= "sss";
    }
    // =====================================

    // Gabungkan kondisi jika ada
    if (!empty($conditions)) {
        $sql_count .= " AND " . implode(" AND ", $conditions);
    }
    // ===========================================================

    $stmt_count = $con->prepare($sql_count);
    if ($stmt_count) {
        if (!empty($params_count)) {
            $stmt_count->bind_param($types_count, ...$params_count);
        }
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $totalProduk = $result_count->fetch_assoc()['total'] ?? 0;
        $totalPages = ($limit > 0) ? ceil($totalProduk / $limit) : 0; // Perbaiki pembagian dengan 0
        $stmt_count->close();
        // Validasi ulang currentPage
        if ($currentPage > $totalPages && $totalPages > 0) { $currentPage = $totalPages; $offset = ($currentPage - 1) * $limit; }
        elseif ($currentPage < 1) { $currentPage = 1; $offset = 0; }
    } else { log_error("Produk - Gagal prepare statement count produk: " . $con->error); $error_message = "Gagal menghitung total produk."; }
}

// --- Query Utama untuk Mengambil Data Produk Halaman Ini ---
if (!$database_connection_error && !$error_message && $totalProduk > 0) {
    // ===>> DIMODIFIKASI: Tambah LEFT JOIN, kondisi, dan parameter <<===
    $sql_produk = "SELECT p.id, p.nama, p.kode_produk, p.foto
                   FROM produk p
                   LEFT JOIN kategori k ON p.kategori_id = k.id
                   WHERE p.status = 'aktif'";
    // Gunakan parameter dan tipe dari query count karena kondisinya sama
    $params_produk = $params_count;
    $types_produk = $types_count;

    // Gabungkan kondisi jika ada (sama seperti query count)
    if (!empty($conditions)) {
        $sql_produk .= " AND " . implode(" AND ", $conditions);
    }

    // Tambahkan ORDER BY, LIMIT, OFFSET
    $sql_produk .= " ORDER BY p.nama ASC LIMIT ? OFFSET ?";
    $params_produk[] = $limit;  // Tambahkan parameter limit
    $params_produk[] = $offset; // Tambahkan parameter offset
    $types_produk .= "ii";      // Tambahkan tipe 'i' dua kali
    // ==================================================================

    $stmt_produk = $con->prepare($sql_produk);
    if ($stmt_produk) {
        if (!empty($params_produk)) {
            // Bind semua parameter (filter, keyword, limit, offset)
            $stmt_produk->bind_param($types_produk, ...$params_produk);
        }
        $stmt_produk->execute();
        $result_produk = $stmt_produk->get_result();
        while ($dataproduk = $result_produk->fetch_assoc()) {
            // Logika pemrosesan gambar produk (tetap sama)
            $kode_produk = $dataproduk['kode_produk']; $gambar_filename = $dataproduk['foto'];
            $gambar_url_display = $default_placeholder_url; $gambar_url_absolute = $default_placeholder_url;
            if (!empty($gambar_filename) && !empty($kode_produk)) {
                $fs_path_produk = rtrim($fs_dir_produk_base, '/') . '/' . $kode_produk . '/' . $gambar_filename;
                if (file_exists($fs_path_produk)) {
                    $gambar_url_display = $project_root_url . '/images/produk/' . $kode_produk . '/' . $gambar_filename;
                    $gambar_url_absolute = $gambar_url_display;
                } else { log_error("Produk - File gambar tidak ditemukan: " . $fs_path_produk); }
            }
            $produk_list[] = [ 'id' => $dataproduk['id'], 'nama' => htmlspecialchars($dataproduk['nama']), 'url' => $pages_url_base . "detail-produk.php?id=" . $dataproduk['id'], 'image_display' => $gambar_url_display, 'image_absolute' => $gambar_url_absolute, 'kode_produk' => $kode_produk, 'foto' => $gambar_filename ];
        }
        $stmt_produk->close();
    } else { log_error("Produk - Gagal prepare statement produk: " . $con->error); $error_message = "Gagal memuat data produk."; $produk_list = []; }
} elseif($totalProduk == 0 && !$error_message) { $produk_list = []; }

// --- Siapkan Data SEO Dinamis ---
// ===>> DIMODIFIKASI: Update judul, deskripsi, canonical, schema jika ada keyword <<===
$canonical_url_produk = $pages_url_base . 'produk.php';
$seo_query_params = [];
if($kategori_id) $seo_query_params['kategori'] = $kategori_id;
if($limit != $default_limit) $seo_query_params['limit'] = $limit;
if($currentPage > 1) $seo_query_params['page'] = $currentPage;
if (!empty($keyword)) $seo_query_params['keyword'] = $keyword; // Tambah keyword ke params

if(!empty($seo_query_params)) $canonical_url_produk .= '?' . http_build_query($seo_query_params);

if (!empty($keyword)) {
    $keyword_safe = htmlspecialchars($keyword);
    if ($kategori_valid && !empty($nama_kategori_seo)) {
        $page_title = "Hasil Cari '$keyword_safe' Kategori $nama_kategori_seo | $nama_toko";
        $page_description = "Hasil pencarian '$keyword_safe' untuk furniture $nama_kategori_seo dari $nama_toko.";
        $main_heading = "Hasil Cari \"$keyword_safe\" <small class='text-muted fs-6'>(Kategori: $nama_kategori_seo)</small>";
    } else {
        $page_title = "Hasil Pencarian '$keyword_safe' | $nama_toko";
        $page_description = "Hasil pencarian produk furniture '$keyword_safe' dari $nama_toko.";
        $main_heading = "Hasil Pencarian: \"$keyword_safe\"";
    }
     $page_keywords = "hasil cari $keyword_safe, $nama_toko, furniture $keyword_safe";

} elseif ($kategori_valid && !empty($nama_kategori_seo)) {
    $page_title = "Jual Furniture Kategori $nama_kategori_seo Online | $nama_toko";
    $page_description = "Koleksi furniture $nama_kategori_seo berkualitas dari $nama_toko.";
    $page_keywords = "furniture $nama_kategori_seo, $nama_kategori_seo online, $nama_toko";
    $main_heading = "Produk Kategori: " . $nama_kategori_seo;
} else {
    $page_title = "Katalog Produk Furniture Online Terlengkap | $nama_toko";
    $page_description = "Lihat katalog produk furniture online terlengkap dari $nama_toko.";
    $page_keywords = "katalog furniture, produk furniture, mebel online, $nama_toko";
    $main_heading = "Semua Produk Furniture";
}
// =============================================================================

$og_image_url_produk = !empty($produk_list) ? $produk_list[0]['image_absolute'] : $og_image_default;

// --- Siapkan Schema JSON-LD ---
$schema_items = []; $position = $offset + 1;
foreach ($produk_list as $item) { $schema_items[] = [ "@type" => "ListItem", "position" => $position++, "item" => [ "@type" => "Product", "url" => $item['url'], "name" => $item['nama'], "image" => $item['image_absolute'], "sku" => $item['kode_produk'], "brand" => [ "@type" => "Brand", "name" => $nama_toko ] ] ]; }
$schema_json = [ "@context" => "https://schema.org", "@type" => "CollectionPage", "name" => $page_title, "description" => $page_description, "url" => $canonical_url_produk, // <-- URL sudah termasuk keyword jika ada
 "mainEntity" => [ "@type" => "ItemList", "numberOfItems" => $totalProduk, "itemListElement" => $schema_items ], "breadcrumb" => [ "@type" => "BreadcrumbList", "itemListElement" => [] ] ];
$home_url = $project_root_url . '/';
$produk_page_url = $pages_url_base . "produk.php";
$schema_json["breadcrumb"]["itemListElement"][] = ["@type" => "ListItem", "position" => 1, "name" => "Home", "item" => $home_url];
if ($kategori_valid && !empty($nama_kategori_seo)) { $schema_json["breadcrumb"]["itemListElement"][] = ["@type" => "ListItem", "position" => 2, "name" => "Produk", "item" => $produk_page_url]; $schema_json["breadcrumb"]["itemListElement"][] = ["@type" => "ListItem", "position" => 3, "name" => $nama_kategori_seo, "item" => $canonical_url_produk]; }
else { $schema_json["breadcrumb"]["itemListElement"][] = ["@type" => "ListItem", "position" => 2, "name" => "Produk", "item" => $canonical_url_produk]; }

// Siapkan variabel untuk footer
$footer_categories = array_slice($categories, 0, 6);

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
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-LHH00CCQR9'); </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    <meta name="keywords" content="<?php echo $page_keywords; ?>">
    <meta name="author" content="<?php echo htmlspecialchars($nama_toko); ?>">
    <link rel="canonical" href="<?php echo $canonical_url_produk; ?>" />
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="icon" href="<?php echo htmlspecialchars($images_url_base . 'favicon.png'); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($images_url_base . 'apple-touch-icon.png'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assets_url_base . 'css/style.css'); ?>">
    <script type="application/ld+json"><?php echo json_encode($schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <style>
        /* Sisipkan style tambahan atau biarkan kosong jika sudah ada di style.css */
    </style>
</head>
<body>
    <?php include 'templat/header.php'; ?>

    <main class="py-5">
        <div class="container">

            <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '>';" class="mb-4 small">
                <ol class="breadcrumb bg-light p-2 rounded-pill px-3">
                    <li class="breadcrumb-item"><a href="<?php echo $project_root_url . '/'; ?>">Beranda</a></li>
                    <?php if ($kategori_valid && !empty($keyword)): // Jika ada keyword DAN kategori ?>
                         <li class="breadcrumb-item"><a href="<?php echo $pages_url_base . 'produk.php'; ?>">Produk</a></li>
                         <li class="breadcrumb-item"><a href="<?php echo $pages_url_base . 'produk.php?kategori=' . $kategori_id; ?>"><?php echo $nama_kategori_seo; ?></a></li>
                         <li class="breadcrumb-item active" aria-current="page">Cari: "<?php echo htmlspecialchars($keyword); ?>"</li>
                    <?php elseif ($kategori_valid): // Hanya kategori ?>
                         <li class="breadcrumb-item"><a href="<?php echo $pages_url_base . 'produk.php'; ?>">Produk</a></li>
                         <li class="breadcrumb-item active" aria-current="page"><?php echo $nama_kategori_seo; ?></li>
                     <?php elseif (!empty($keyword)): // Hanya keyword ?>
                          <li class="breadcrumb-item"><a href="<?php echo $pages_url_base . 'produk.php'; ?>">Produk</a></li>
                          <li class="breadcrumb-item active" aria-current="page">Hasil Pencarian</li>
                    <?php else: // Halaman produk utama ?>
                         <li class="breadcrumb-item active" aria-current="page">Produk</li>
                    <?php endif; ?>
                </ol>
             </nav>

            <h1 class="text-center section-title mb-4"><?php echo $main_heading; // Main heading sudah termasuk info keyword/kategori ?></h1>

            <div class="filter-container mb-4 card shadow-sm">
                <div class="card-body bg-light py-2">
                    <form method="GET" action="produk.php" class="row g-2 align-items-center justify-content-start">

                        <?php // Ambil keyword saat ini untuk ditampilkan kembali di input
                            $current_keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS);
                        ?>

                        <div class="col-md-6 col-lg-3">
                            <label for="kategori-select" class="visually-hidden">Filter Kategori</label>
                            <select name="kategori" id="kategori-select" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($kategori_list as $kategori) { ?>
                                    <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_id == $kategori['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kategori['nama']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="limit-select" class="visually-hidden">Tampilkan per Halaman</label>
                             <div class="input-group input-group-sm">
                                <span class="input-group-text">Tampil:</span>
                                <select name="limit" id="limit-select" class="form-select">
                                    <?php foreach ($allowed_limits as $lim) { ?>
                                        <option value="<?php echo $lim; ?>" <?php echo ($limit == $lim) ? 'selected' : ''; ?>>
                                            <?php echo $lim; ?> / hal
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-9 col-lg-4">
                            <label for="keyword-input" class="visually-hidden">Cari Produk</label>
                            <input type="text" name="keyword" id="keyword-input" class="form-control form-control-sm" placeholder="Cari nama atau kode produk..." value="<?php echo htmlspecialchars($current_keyword ?? ''); ?>">
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary search-button"><i class="fas fa-search"></i> Cari</button>
                        </div>
                         <div class="col-auto">
                             <a href="produk.php" class="btn btn-sm btn-secondary reset-button"><i class="fas fa-sync-alt"></i> Reset</a>
                         </div>

                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (empty($produk_list) && !$error_message) { ?>
                <div class="alert alert-warning text-center" role="alert">
                    Mohon maaf, produk tidak ditemukan
                    <?php
                        if (!empty($keyword)) echo " untuk pencarian \"".htmlspecialchars($keyword)."\"";
                        if ($kategori_valid && !empty($nama_kategori_seo)) echo (!empty($keyword) ? ' dalam' : ' untuk')." kategori \"".$nama_kategori_seo."\"";
                    ?>.
                     <a href="<?php echo $pages_url_base . 'produk.php'; ?>" class="ms-2">Lihat semua produk</a>
                </div>
            <?php } elseif (!empty($produk_list)) { ?>
                <p class="text-center text-muted mb-4 small">
                    <?php
                        $startItem = min(($currentPage - 1) * $limit + 1, $totalProduk);
                        $endItem = min($startItem + $limit - 1, $totalProduk);
                        if ($totalProduk > 0) {
                             echo "Menampilkan $startItem - $endItem dari $totalProduk produk";
                             if (!empty($keyword)) echo " (Hasil cari: \"".htmlspecialchars($keyword)."\")";
                             if ($kategori_valid) echo " (Kategori: $nama_kategori_seo)";
                             echo ".";
                        }
                    ?>
                </p>

                <div class="products"> 
                    <?php foreach ($produk_list as $dataproduk) { ?>
                        <div class="product">                             <div class="product-img">
                                <a href="<?php echo $dataproduk['url']; ?>">
                                     <img src="<?php echo htmlspecialchars($dataproduk['image_display']); ?>?t=<?php echo time(); ?>" alt="<?php echo $dataproduk['nama']; ?>" loading="lazy">
                                </a>
                                </div>
                            <div class="product-info">
                                <?php
                                // Cari nama kategori produk ini (jika perlu ditampilkan)
                                $prod_cat_name = '';
                                foreach ($kategori_list as $k) {
                                    if (isset($dataproduk['kategori_id']) && $k['id'] == $dataproduk['kategori_id']) { // Cek $dataproduk['kategori_id'] dulu
                                        $prod_cat_name = htmlspecialchars($k['nama']);
                                        break;
                                    }
                                }
                                // Tampilkan kategori jika ada (sesuaikan dengan style .product-category)
                                // if($prod_cat_name) { echo '<div class="product-category">'.$prod_cat_name.'</div>'; }
                                ?>
                                <h3 class="product-title">
                                    <a href="<?php echo $dataproduk['url']; ?>">
                                        <?php echo $dataproduk['nama']; ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <div>
                                         </div>
                                    <a href="<?php echo $dataproduk['url']; ?>" class="btn btn-sm btn-outline-primary-theme details-button">Lihat Detail</a>
                                    <?php /* Atau gunakan cart button tema:
                                    <a href="<?php echo $dataproduk['url']; ?>" class="cart-btn"><i class="fas fa-eye"></i></a>
                                    */ ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigasi Halaman Produk" class="mt-5 d-flex justify-content-center">
                     <ul class="pagination"> 
                        <?php // --- AWAL LOGIKA PAGINATION --- ?>
                        <?php
                        $base_params = [];
                        if (!empty($kategori_id)) $base_params['kategori'] = $kategori_id;
                        if ($limit != $default_limit) $base_params['limit'] = $limit;
                        // ===>> DIMODIFIKASI: Tambahkan keyword ke base_params <<===
                        if (!empty($keyword)) $base_params['keyword'] = $keyword;
                        // ==========================================================

                        $prev_page = $currentPage - 1;
                        $prev_params = $base_params + ['page' => $prev_page];
                        $prev_link = 'produk.php?' . http_build_query($prev_params);
                        ?>
                        <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($currentPage > 1) ? $prev_link : '#'; ?>" aria-label="Sebelumnya"><span aria-hidden="true">&laquo;</span></a>
                        </li>

                        <?php
                        // Logika tampilan nomor halaman (tetap sama)
                        $max_pages_to_show = 5; $start_page = max(1, $currentPage - floor($max_pages_to_show / 2)); $end_page = min($totalPages, $start_page + $max_pages_to_show - 1);
                        if ($end_page - $start_page + 1 < $max_pages_to_show) { $start_page = max(1, $end_page - $max_pages_to_show + 1); }

                        if ($start_page > 1) { $first_params = $base_params + ['page' => 1]; echo '<li class="page-item"><a class="page-link" href="produk.php?' . http_build_query($first_params) . '">1</a></li>'; if ($start_page > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
                        for ($i = $start_page; $i <= $end_page; $i++) { $page_params = $base_params + ['page' => $i]; $page_link = 'produk.php?' . http_build_query($page_params); echo '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '"><a class="page-link" href="' . $page_link . '">' . $i . '</a></li>'; }
                        if ($end_page < $totalPages) { if ($end_page < $totalPages - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } $last_params = $base_params + ['page' => $totalPages]; echo '<li class="page-item"><a class="page-link" href="produk.php?' . http_build_query($last_params) . '">' . $totalPages . '</a></li>'; }

                        $next_page = $currentPage + 1;
                        $next_params = $base_params + ['page' => $next_page];
                        $next_link = 'produk.php?' . http_build_query($next_params);
                        ?>
                        <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo ($currentPage < $totalPages) ? $next_link : '#'; ?>" aria-label="Selanjutnya"><span aria-hidden="true">&raquo;</span></a>
                        </li>
                        <?php // --- AKHIR LOGIKA PAGINATION --- ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php } ?>
        </div>
    </main>

    <?php include 'templat/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($assets_url_base . 'js/main.js'); ?>"></script>
    </body>
</html>
<?php
// Tutup koneksi
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>