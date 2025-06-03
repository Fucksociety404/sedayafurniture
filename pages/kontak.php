<?php
// File: pages/kontak.php (Lengkap & Diperbaiki UI + Kode)
session_start(); // <-- Mulai session DI AWAL untuk flash messages

// 1. Sertakan Koneksi & Utilitas dari root proyek
require_once __DIR__ . '/../config/koneksi.php'; // <-- Pastikan path ini benar
require_once __DIR__ . '/../includes/utils.php';

global $con;

// Inisialisasi variabel
$categories = []; $database_connection_error = false; $nama_toko = "Sedaya Furniture";

// --- KONFIGURASI PATH & URL (Konsisten) ---
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$project_folder = ""; // <<< Sesuaikan jika perlu
$project_root_url = rtrim($base_url, '/') . rtrim($project_folder, '/');
$pages_url_base = $project_root_url . '/pages/';
$assets_url_base = rtrim($project_folder, '/') . '/assets/';
$images_url_base = rtrim($project_folder, '/') . '/images/';
// -------------------------------------------------------------

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

// --- Konfigurasi SEO & Halaman Kontak ---
$page_title = "Kontak Sedaya Furniture - Hubungi Kami"; // Judul lebih singkat
$deskripsi_seo = "Hubungi Sedaya Furniture untuk pertanyaan, pemesanan custom furniture Jepara, atau informasi lainnya. Kunjungi kami di Batealit, Jepara atau hubungi via telepon/email.";
$keywords_seo = "kontak sedaya furniture, hubungi sedaya furniture, alamat sedaya furniture, telepon sedaya furniture, email sedaya furniture, furniture jepara, mebel jepara";
$canonical_url = $pages_url_base . 'kontak.php'; // URL halaman kontak
$og_image_url = $project_root_url . "/images/og_image_kontak.png";

// --- URL PETA ---
$latitude = "-6.632490299661487"; $longitude = "110.75467850375612"; $zoom_level = 16;
$map_iframe_url = "https://maps.google.com/maps?q={$latitude},{$longitude}&hl=id&z={$zoom_level}&output=embed"; // Format q=lat,lon
$map_view_url = "https://maps.google.com/?q={$latitude},{$longitude}";
// ---------------

// Ambil data form input sebelumnya jika ada (setelah redirect error)
$form_input = $_SESSION['form_input_kontak'] ?? [];
unset($_SESSION['form_input_kontak']); // Hapus setelah diambil

// Siapkan variabel untuk footer
$footer_categories = array_slice($categories, 0, 6);

// --- Fungsi Display Flash Message (jika belum ada di navbar/utils) ---
if (!function_exists('display_flash_message')) {
    function display_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message']; unset($_SESSION['flash_message']);
            $alert_type = ''; $icon_class = '';
            switch ($flash['type']) {
                case 'success': $alert_type = 'success'; $icon_class = 'bi-check-circle-fill'; break;
                case 'error': $alert_type = 'danger'; $icon_class = 'bi-exclamation-triangle-fill'; break;
                case 'warning': $alert_type = 'warning'; $icon_class = 'bi-exclamation-triangle-fill'; break;
                default: $alert_type = 'info'; $icon_class = 'bi-info-circle-fill'; break;
            }
            echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">';
            echo '<i class="bi ' . $icon_class . ' me-2"></i>';
            // Izinkan HTML dasar (seperti <br>) dalam pesan error
            echo $flash['message'];
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($deskripsi_seo); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords_seo); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($nama_toko); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />
    <meta name="robots" content="index, follow">


    <link rel="icon" href="<?php echo htmlspecialchars($images_url_base . 'favicon.png'); ?>" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($images_url_base . 'apple-touch-icon.png'); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assets_url_base . 'css/style.css'); ?>">

    <script type="application/ld+json">{ /* ... Schema JSON LD ... */ }</script>

    <style>
      body { padding-top: 70px; background-color: #f8f9fa; } /* Padding navbar & bg */
      .contact-info i.fa-fw { width: 1.5em; text-align: center; } /* Perbaiki selector ikon FA */
      .contact-info p { margin-bottom: 1rem; }
      .contact-info a { color: var(--bs-body-color); text-decoration: none; }
      .contact-info a:hover { color: var(--bs-primary); text-decoration: underline; }
      .map-container iframe { border-radius: 0.375rem; border: 1px solid #dee2e6; }
      .card { margin-bottom: 1.5rem; } /* Tambah margin bawah untuk card */
    </style>
</head>

<body>
  <?php include 'templat/header.php'; // Include header ?>
  <br>

  <section id="kontak" class="py-4 py-md-5" aria-labelledby="kontak-heading">
    <div class="container">
 
      <div class="text-center mb-5">
          <h1 id="kontak-heading" class="h2 fw-bold"><?php echo htmlspecialchars($nama_toko); ?> - Hubungi Kami</h1>
          <p class="lead text-muted">Kami siap membantu Anda. Hubungi kami melalui detail di bawah atau kirim pesan.</p>
      </div>

      
      <div class="row justify-content-center mb-4">
          <div class="col-lg-10 col-md-10">
             <?php display_flash_message(); // Panggil fungsi untuk menampilkan pesan ?>
          </div>
      </div>

      <?php if ($database_connection_error): ?>
          <div class="alert alert-danger text-center">Gagal terhubung ke database.</div>
      <?php endif; ?>

      
      <div class="row g-4 g-lg-5 justify-content-center">

      
        <div class="col-lg-5 col-md-6 order-md-1">
          <div class="card h-100 shadow-sm">
              <div class="card-body contact-info d-flex flex-column">
                  <h2 class="h5 card-title mb-4"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Kontak</h2>
                  <p><i class="fas fa-map-marker-alt fa-fw me-2 text-muted"></i><strong>Alamat:</strong><br><span class="ms-4">Batealit, Jepara,<br>Jawa Tengah, Indonesia</span></p>
                  <p><i class="fas fa-phone-alt fa-fw me-2 text-muted"></i><strong>Telepon:</strong><br><span class="ms-4"><a href="tel:+6281567958549" title="Hubungi Sedaya Furniture via Telepon">+62 81 567 958 549</a></span></p>
                  <p><i class="fas fa-envelope fa-fw me-2 text-muted"></i><strong>Email:</strong><br><span class="ms-4"><a href="mailto:hello@sedayafurniture.com" title="Kirim Email ke Sedaya Furniture">hello@sedayafurniture.com</a></span></p>

                  <div class="mt-auto"> 
                      <hr class="my-4">
                      <h3 class="h6 mb-3"><i class="fas fa-map-marked-alt me-2 text-primary"></i>Lokasi Kami:</h3>
                      <div class="map-container ratio ratio-4x3">
                           <iframe title="Peta Lokasi Sedaya Furniture" src="<?php echo htmlspecialchars($map_iframe_url); ?>" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                       </div>
                       <p class="mt-2 small text-center">
                           <a href="<?php echo htmlspecialchars($map_view_url); ?>" target="_blank" rel="noopener noreferrer" title="Buka Lokasi di Google Maps">Lihat di Google Maps <i class="fas fa-external-link-alt fa-xs"></i></a>
                       </p>
                  </div>
              </div>
          </div>
        </div>

       
        <div class="col-lg-7 col-md-6 order-md-2">
          <div class="card h-100 shadow-sm">
              <div class="card-body contact-form">
                  <h2 class="h5 card-title mb-4"><i class="fas fa-paper-plane me-2 text-primary"></i>Kirim Pesan Kepada Kami</h2>
                  <form id="contactForm" action="proses_kontak.php" method="POST">
                    <div class="mb-3">
                      <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="nama" name="nama" required aria-required="true" placeholder="Nama Anda" value="<?php echo htmlspecialchars($form_input['nama'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                           <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                           <input type="email" class="form-control" id="email" name="email" required aria-required="true" placeholder="email@anda.com" value="<?php echo htmlspecialchars($form_input['email'] ?? ''); ?>">
                        </div>
                         <div class="col-md-6 mb-3">
                          <label for="telepon" class="form-label">Nomor Telepon</label>
                          <input type="tel" class="form-control" id="telepon" name="telepon" placeholder="(Opsional)" value="<?php echo htmlspecialchars($form_input['telepon'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                      <label for="subjek" class="form-label">Subjek <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="subjek" name="subjek" required aria-required="true" placeholder="Subjek pesan" value="<?php echo htmlspecialchars($form_input['subjek'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label for="pesan" class="form-label">Pesan <span class="text-danger">*</span></label>
                      <textarea class="form-control" id="pesan" name="pesan" rows="5" required aria-required="true" placeholder="Tuliskan pesan Anda di sini..."><?php echo htmlspecialchars($form_input['pesan'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane me-2"></i> Kirim Pesan Sekarang</button>
                  </form>
              </div>
          </div>
        </div>

      </div> 
    </div>
  </section>

  <?php include 'templat/footer.php'; // Include footer ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo htmlspecialchars($assets_url_base . 'js/main.js'); ?>"></script>
  <script>
      // Script auto-close alert
      window.setTimeout(function() {
          var alerts = document.querySelectorAll('.alert-dismissible');
          alerts.forEach(function(alert) {
              if (alert) { new bootstrap.Alert(alert).close(); }
          });
      }, 7000); // Tutup setelah 7 detik
  </script>

</body>
</html>
<?php
// Tutup koneksi
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>