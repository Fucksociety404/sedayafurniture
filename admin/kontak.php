<?php
// File: pages/kontak.php (Diperbaiki - Menampilkan Flash Message)
session_start(); // <-- TAMBAHKAN INI DI BARIS PALING ATAS

// 1. Sertakan Koneksi & Utilitas dari root proyek
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/utils.php';

global $con;

// --- Variabel dan Konfigurasi Path/URL (Sama seperti sebelumnya) ---
$categories = []; $database_connection_error = false;
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$project_folder = "/newsedaya24apr2025";
$project_root_url = rtrim($base_url, '/') . rtrim($project_folder, '/');
$pages_url_base = $project_root_url . '/pages/';
$assets_url_base = rtrim($project_folder, '/') . '/assets/';
$images_url_base = rtrim($project_folder, '/') . '/images/';
// ... (Definisi variabel SEO, Peta, dll. lainnya tetap sama) ...
$nama_toko = "Sedaya Furniture";
$page_title = "Kontak Sedaya Furniture - Hubungi Kami untuk Mebel Jepara Berkualitas";
$deskripsi_seo = "Hubungi Sedaya Furniture untuk pertanyaan, pemesanan custom, atau informasi lebih lanjut mengenai furniture Jepara berkualitas ekspor. Kami siap melayani Anda.";
$keywords_seo = "kontak sedaya furniture, hubungi sedaya furniture, alamat sedaya furniture, telepon sedaya furniture, email sedaya furniture, furniture jepara, mebel jepara";
$canonical_url = $base_url . $_SERVER['REQUEST_URI'];
$og_image_url = $project_root_url . "/images/og_image_kontak.png";
$latitude = "-6.632490299661487";
$longitude = "110.75467850375612";
$zoom_level = 16;
$map_iframe_url = "https://maps.google.com/maps?q={$latitude},{$longitude}&hl=id&z={$zoom_level}&output=embed";
$map_view_url = "https://maps.google.com/maps?q={$latitude},{$longitude}";
// --- Akhir Konfigurasi ---


// Cek Koneksi & Ambil Kategori
if (!isset($con) || !$con instanceof mysqli) {
    $database_connection_error = true;
    log_error("Kontak - Koneksi database tidak valid (\$con dari config/koneksi.php).");
} else {
    $kategori_query = "SELECT id, nama FROM kategori ORDER BY nama ASC";
    $kategori_result = mysqli_query($con, $kategori_query);
    if ($kategori_result) {
        while ($kategori_data = mysqli_fetch_assoc($kategori_result)) { $categories[] = $kategori_data; }
        mysqli_free_result($kategori_result);
    } else { log_error("Kontak - Gagal fetch kategori all: " . mysqli_error($con)); }
}

// Ambil data form input sebelumnya jika ada (setelah redirect karena error di proses_kontak)
$form_input = $_SESSION['form_input_kontak'] ?? [];
unset($_SESSION['form_input_kontak']); // Hapus setelah diambil

// Siapkan variabel untuk footer
$footer_categories = array_slice($categories, 0, 6);

// --- >> FUNGSI UNTUK MENAMPILKAN FLASH MESSAGE << ---
// (Jika fungsi display_flash_message() belum ada di navbar.php atau file lain yang di-include)
// Jika sudah ada di navbar.php, Anda tidak perlu blok ini dan cukup panggil display_flash_message()
if (!function_exists('display_flash_message')) {
    function display_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']); // Hapus setelah diambil
            $alert_type = '';
            $icon_class = '';
            switch ($flash['type']) {
                case 'success': $alert_type = 'success'; $icon_class = 'bi-check-circle-fill'; break;
                case 'error': $alert_type = 'danger'; $icon_class = 'bi-exclamation-triangle-fill'; break;
                case 'warning': $alert_type = 'warning'; $icon_class = 'bi-exclamation-triangle-fill'; break;
                default: $alert_type = 'info'; $icon_class = 'bi-info-circle-fill'; break;
            }
            echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">';
            echo '<i class="bi ' . $icon_class . ' me-2"></i>';
            echo $flash['message']; // Pesan dari session (sudah di-escape jika perlu di proses_kontak)
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}
// ----------------------------------------------------

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LHH00CCQR9"></script>
    <script>/* ... gtag config ... */</script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords_seo); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($nama_toko); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>" />
    <meta property="og:site_name" content="<?php echo htmlspecialchars($nama_toko); ?>" />
    <meta property="og:locale" content="id_ID" />
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_url); ?>">

    <link rel="icon" href="<?php echo htmlspecialchars($images_url_base . 'favicon.png'); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($images_url_base . 'apple-touch-icon.png'); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assets_url_base . 'css/style.css'); ?>">

    <script type="application/ld+json">{ /* ... Schema JSON LD ... */ }</script>
    <style>/* ... CSS Inline ... */</style>
</head>

<body>
  <?php include 'templat/header.php'; ?>
  <br>

  <section id="kontak" class="py-5" aria-labelledby="kontak-heading">
    <div class="container">
      <h1 id="kontak-heading" class="section-title"><?php echo htmlspecialchars($nama_toko); ?> - Hubungi Kami</h1>
      <p class="text-center text-muted mb-5 col-lg-8 mx-auto">...</p>

      <div class="row justify-content-center">
          <div class="col-lg-10">
             <?php display_flash_message(); // Panggil fungsi untuk menampilkan pesan ?>
          </div>
      </div>
      <?php if ($database_connection_error): ?>
          <div class="alert alert-danger text-center">Gagal terhubung ke database.</div>
      <?php endif; ?>

      <div class="row g-4 g-lg-5">
        <div class="col-lg-5 mb-4 mb-lg-0 contact-info">
        </div>

        <div class="col-lg-7 contact-form">
          <div class="card h-100 shadow-sm">
              <div class="card-body">
                  <h2 class="h5 card-title mb-4"><i class="fas fa-paper-plane me-2 text-primary"></i>Kirim Pesan</h2>
                  <form id="contactForm" action="proses_kontak.php" method="POST">
                    <div class="mb-3">
                      <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="nama" name="nama" required value="<?php echo htmlspecialchars($form_input['nama'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($form_input['email'] ?? ''); ?>">
                    </div>
                     <div class="mb-3">
                      <label for="telepon" class="form-label">Nomor Telepon (Opsional)</label>
                      <input type="tel" class="form-control" id="telepon" name="telepon" value="<?php echo htmlspecialchars($form_input['telepon'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label for="subjek" class="form-label">Subjek <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="subjek" name="subjek" required value="<?php echo htmlspecialchars($form_input['subjek'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label for="pesan" class="form-label">Pesan <span class="text-danger">*</span></label>
                      <textarea class="form-control" id="pesan" name="pesan" rows="5" required><?php echo htmlspecialchars($form_input['pesan'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Kirim Pesan</button>
                  </form>
                <div id="form-message" class="mt-3"></div>
              </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'templat/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo htmlspecialchars($assets_url_base . 'js/main.js'); ?>"></script>
  <script>
      // Tambahkan script untuk menutup alert otomatis jika diinginkan
      window.setTimeout(function() {
          var alerts = document.querySelectorAll('.alert-dismissible');
          alerts.forEach(function(alert) {
              if (alert) {
                  var bsAlert = new bootstrap.Alert(alert);
                  bsAlert.close();
              }
          });
      }, 7000); // Tutup setelah 7 detik
  </script>

</body>
</html>
<?php
// Tutup koneksi database jika masih terbuka
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>