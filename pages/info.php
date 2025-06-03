<?php
// File: pages/info.php (Diperbaiki)
// error_reporting(E_ALL); ini_set('display_errors', 1);

// 1. Sertakan Koneksi & Utilitas dari root proyek
require_once __DIR__ . '/../config/koneksi.php'; // <-- PERBAIKI PATH KE KONFIGURASI UTAMA
require_once __DIR__ . '/../includes/utils.php';

global $con;

// Inisialisasi variabel
$page_content = null;
$categories = []; // Untuk header/footer
$page_title = 'Informasi'; // Default title
$meta_description = 'Informasi dari Sedaya Furniture.';
$meta_keywords = 'informasi, sedaya furniture';
$canonical_url = '';
$slug = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS); // Ambil slug dari URL
$error_message = null;
$database_connection_error = false;

// --- KONFIGURASI PATH & URL (Konsisten dengan file lain) ---
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$project_folder = ""; // <<< Nama folder proyek Anda
$project_root_url = rtrim($base_url, '/') . rtrim($project_folder, '/');
$pages_url_base = $project_root_url . '/pages/';
$assets_url_base = rtrim($project_folder, '/') . '/assets/';
$images_url_base = rtrim($project_folder, '/') . '/images/';
// -------------------------------------------------------------

// Validasi Slug di awal
if (empty($slug)) {
    http_response_code(404);
    $error_message = "Halaman informasi tidak ditemukan. Parameter diperlukan dalam URL.";
    $page_title = 'Halaman Tidak Ditemukan | Sedaya Furniture';
    if (!headers_sent()) { header("X-Robots-Tag: noindex, follow", true); }
}

// Cek Koneksi Database
if (!isset($con) || !$con instanceof mysqli) {
    $database_connection_error = true;
    log_error("Info Page ({$slug}) - Koneksi database tidak valid (\$con dari config/koneksi.php).");
    if (!$error_message) { // Hanya set jika belum ada error dari slug
        $error_message = "Koneksi database gagal.";
        http_response_code(503);
        if (!headers_sent()) { header("X-Robots-Tag: noindex, follow", true); }
    }
} else { // <-- Mulai Blok jika Koneksi OK

    // --- Ambil Kategori untuk Header/Footer ---
    $kategori_query = "SELECT id, nama FROM kategori ORDER BY nama ASC";
    $kategori_result = mysqli_query($con, $kategori_query);
    if ($kategori_result) {
        while ($kategori_data = mysqli_fetch_assoc($kategori_result)) { $categories[] = $kategori_data; }
        mysqli_free_result($kategori_result);
    } else { log_error("Info Page ({$slug}) - Gagal fetch kategori all: " . mysqli_error($con)); }

    // --- Ambil Konten Halaman (Hanya jika slug valid dan belum ada error) ---
    if (!empty($slug) && !$error_message) {
        $stmt = $con->prepare("SELECT judul, konten FROM halaman_info WHERE slug = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $page_content = $result->fetch_assoc();
            $stmt->close();

            if ($page_content) {
                // Set SEO Vars dari DB
                $page_title = htmlspecialchars($page_content['judul']) . ' | Sedaya Furniture';
                if (function_exists('generate_meta_description')) {
                    $meta_description = generate_meta_description($page_content['konten'] ?: $page_content['judul']);
                } else {
                    $meta_description = htmlspecialchars(substr(strip_tags($page_content['konten'] ?: $page_content['judul']), 0, 160));
                }
                $meta_keywords = htmlspecialchars($page_content['judul']) . ', ' . htmlspecialchars($slug) . ', sedaya furniture, informasi';
                $canonical_url = $base_url . $_SERVER['REQUEST_URI']; // URL lengkap saat ini
            } else {
                 // Slug valid tapi tidak ditemukan di DB
                 if (!$error_message) {
                     http_response_code(404);
                     $error_message = "Halaman Informasi dengan penanda '" . htmlspecialchars($slug) . "' tidak ditemukan.";
                     $page_title = 'Halaman Tidak Ditemukan | Sedaya Furniture';
                     if (!headers_sent()) { header("X-Robots-Tag: noindex, follow", true); }
                 }
            }
        } else {
            log_error("Info Page ({$slug}) - Gagal prepare statement get halaman: " . $con->error);
             if (!$error_message) {
                 $error_message = "Terjadi kesalahan saat memuat halaman.";
                 http_response_code(500);
                 if (!headers_sent()) { header("X-Robots-Tag: noindex, follow", true); }
             }
        }
    } // <-- Penutup if (!empty($slug) && !$error_message)

    // Tutup koneksi setelah semua query selesai
     // mysqli_close($con); // Pindahkan ke akhir script

} // <-- Kurung kurawal penutup untuk blok else dari if (!$con instanceof mysqli || ...)

// Siapkan variabel untuk footer (tetap lakukan meskipun ada error)
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
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $meta_description; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords; ?>">
    <meta name="author" content="Sedaya Furniture">
    <?php if ($canonical_url): ?><link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" /><?php endif; ?>
    <?php // Meta robots sudah di set di logic PHP di atas ?>

    <link rel="icon" href="<?php echo htmlspecialchars($images_url_base . 'favicon.png'); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($images_url_base . 'apple-touch-icon.png'); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assets_url_base . 'css/style.css'); ?>">
    <style>
        /* Style CSS inline tetap sama */
        body { padding-top: 70px; }
        .info-page-content { line-height: 1.8; }
        /* ... sisa style ... */
    </style>

</head>
<body>
    <?php
        // Include header template (file ini ada di pages/templat/)
        // Variabel $categories, $base_url, $project_folder sudah didefinisikan
        include 'templat/header.php';
    ?>

    <main class="container py-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center my-5">
                <h1 class="h4"><?php echo ($page_title === 'Halaman Tidak Ditemukan | Sedaya Furniture') ? 'Halaman Tidak Ditemukan' : 'Error'; ?></h1>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <a href="<?php echo $project_root_url . '/'; ?>" class="btn btn-primary mt-3">Kembali ke Beranda</a>
            </div>
        <?php elseif ($page_content): ?>
             <nav aria-label="breadcrumb" style="--bs-breadcrumb-divider: '>';">
                <ol class="breadcrumb bg-light p-2 rounded-pill px-3 mb-4">
                    <li class="breadcrumb-item"><a href="<?php echo $project_root_url . '/'; ?>">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_content['judul']); ?></li>
                </ol>
            </nav>
            <article class="bg-white p-4 p-md-5 rounded shadow-sm">
                 <div class="info-page-content">
                     <h1 class="mb-4"><?php echo htmlspecialchars($page_content['judul']); ?></h1>
                     <?php echo $page_content['konten']; // Konten dari DB diasumsikan aman atau sudah disanitasi saat input ?>
                 </div>
             </article>
        <?php else: // Kasus jika slug ada tapi konten null/kosong (seharusnya tidak terjadi jika DB konsisten) ?>
             <div class="alert alert-warning text-center my-5">
                 <h1 class="h4">Konten Belum Tersedia</h1>
                 <p>Mohon maaf, konten untuk halaman '<?php echo htmlspecialchars($slug); ?>' belum tersedia.</p>
                 <a href="<?php echo $project_root_url . '/'; ?>" class="btn btn-primary mt-3">Kembali ke Beranda</a>
           </div>
        <?php endif; ?>
    </main>

    <?php
        // Include footer template (file ini ada di pages/templat/)
        include 'templat/footer.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($assets_url_base . 'js/main.js'); ?>"></script>
</body>
</html>
<?php
// Tutup koneksi database jika masih terbuka
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>