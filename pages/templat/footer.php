<?php
// File: pages/templat/footer.php

/**
 * ========================================================================
 * HARAP DIPERHATIKAN:
 * ========================================================================
 * File ini mengharapkan variabel berikut sudah didefinisikan
 * oleh file PHP yang meng-include file ini:
 *
 * 1. $footer_categories (array): Array MAKSIMAL 6 kategori.
 * 2. $categories (array): (Opsional) Array LENGKAP kategori.
 * 3. $base_url (string): URL dasar website.
 * 4. $project_folder (string): Nama folder proyek.
 * 5. $pages_url_base (string): URL dasar untuk halaman di folder 'pages'.
 * ========================================================================
 */

// Validasi variabel yang diharapkan
if (!isset($footer_categories) || !is_array($footer_categories)) {
    $footer_categories = [];
}
if (!isset($categories) || !is_array($categories)) {
    $categories = [];
}
$base_url_footer = $base_url ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']);
if (isset($project_folder)) {
    $project_folder_footer = rtrim($project_folder, '/');
    if (!empty($project_folder_footer) && $project_folder_footer[0] !== '/') {
        $project_folder_footer = '/' . $project_folder_footer;
    }
} else {
    $project_folder_footer = ''; // Fallback
}
if ($project_folder_footer === '/') {
    $project_folder_footer = '';
}

// $pages_url_base diharapkan sudah benar dari file pemanggil (index.php)
$pages_url_base = $pages_url_base ?? (rtrim($base_url_footer, '/') . $project_folder_footer . '/pages/');

// Definisikan URL halaman spesifik menggunakan $pages_url_base
$home_url_footer = rtrim($base_url_footer, '/') . $project_folder_footer . '/'; // Link ke root proyek
$produk_url_footer = $pages_url_base . 'produk.php';
$kontak_url_footer = $pages_url_base . 'kontak.php';

// *** PERBAIKAN: Path logo footer menggunakan $project_folder_footer ***
// Asumsi logo ada di /images di dalam folder proyek
$logo_footer_url = rtrim($base_url_footer, '/') . $project_folder_footer . '/images/logo-sedaya-footer.png';

// URL placeholder (tetap sama)
$url_facebook = '#';
$url_instagram = '#';
$url_whatsapp = 'https://wa.me/6281567958549?text=Halo%20Sedaya%20Furniture,%20saya%20tertarik%20dengan%20produk%20Anda.';
$url_pinterest = '#';

// --- URL HALAMAN INFORMASI (menggunakan $pages_url_base) ---
$url_faq = $pages_url_base . 'info.php?page=faq';
$url_kebijakan_privasi = $pages_url_base . 'info.php?page=kebijakan-privasi';
$url_syarat_ketentuan = $pages_url_base . 'info.php?page=syarat-ketentuan';
$url_info_pengiriman = $pages_url_base . 'info.php?page=info-pengiriman';
$url_tentang_kami = $pages_url_base . 'info.php?page=tentang-kami';
$url_cara_pesan = $pages_url_base . 'info.php?page=cara-pesan';
// ----------------------------------------------------------

?>
<style>
/* Style footer tetap sama */
.footer-link { color: rgba(255, 255, 255, 0.7); text-decoration: none; transition: color 0.2s ease, text-decoration 0.2s ease; }
.footer-link:hover, .footer-link:focus { color: #ffffff; text-decoration: underline; }
.social-icon-link { color: rgba(255, 255, 255, 0.7); text-decoration: none; transition: color 0.2s ease; }
.social-icon-link:hover, .social-icon-link:focus { color: #ffffff; }
.social-icon-link:hover i, .social-icon-link:focus i { text-decoration: none; }
.footer-links li { margin-bottom: 0.5rem; }
.footer-links .footer-link i { transition: transform 0.2s ease; display: inline-block; font-size: 0.8em; opacity: 0.7; }
.footer-links .footer-link:hover i, .footer-links .footer-link:focus i { transform: translateX(3px); opacity: 1; text-decoration: none; }
</style>
<footer class="bg-dark text-light pt-5 pb-4">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="text-uppercase fw-bold mb-4 d-flex align-items-center">
                    <span>Sedaya Furniture</span>
                </h5>
                <p class="small text-white-50 mb-2">
                    <i class="bi bi-geo-alt-fill me-2"></i> Batealit, Jepara<br><span class="ms-4">Jawa Tengah, Indonesia</span>
                </p>
                <p class="small mb-2">
                    <i class="bi bi-telephone-fill me-2 text-white-50"></i> <a href="tel:+6281567958549" class="footer-link">+62 81 567 958 549</a>
                </p>
                <p class="small mb-3">
                    <i class="bi bi-envelope-fill me-2 text-white-50"></i> <a href="mailto:hello@sedayafurniture.com" class="footer-link">hello@sedayafurniture.com</a>
                </p>
                <div class="mt-3">
                     <a href="<?php echo htmlspecialchars($url_facebook); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link me-3" title="Facebook Sedaya Furniture"><i class="bi bi-facebook fs-5"></i></a>
                     <a href="<?php echo htmlspecialchars($url_instagram); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link me-3" title="Instagram Sedaya Furniture"><i class="bi bi-instagram fs-5"></i></a>
                     <a href="<?php echo htmlspecialchars($url_whatsapp); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link me-3" title="WhatsApp Sedaya Furniture"><i class="bi bi-whatsapp fs-5"></i></a>
                     <a href="<?php echo htmlspecialchars($url_pinterest); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link" title="Pinterest Sedaya Furniture"><i class="bi bi-pinterest fs-5"></i></a>
                 </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="text-uppercase fw-bold mb-4">Kategori</h5>
                <ul class="list-unstyled footer-links">
                    <?php if (!empty($footer_categories)): ?>
                        <?php foreach ($footer_categories as $kat): ?>
                        <li><a href="<?php echo $produk_url_footer; ?>?kategori=<?php echo $kat['id']; ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> <?php echo htmlspecialchars($kat['nama']); ?></a></li>
                        <?php endforeach; ?>
                         <?php if (isset($categories) && count($categories) > count($footer_categories)): // Check if $categories exists ?>
                           <li><a href="<?php echo $produk_url_footer; ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Lainnya...</a></li>
                         <?php endif; ?>
                    <?php else: ?>
                        <li><span class="text-white-50">Kategori tidak dimuat.</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                 <h5 class="text-uppercase fw-bold mb-4">Tautan Cepat</h5>
                 <ul class="list-unstyled footer-links">
                    <li><a href="<?php echo $home_url_footer; ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Beranda</a></li>
                    <li><a href="<?php echo $produk_url_footer; ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Semua Produk</a></li>
                    <li><a href="<?php echo $kontak_url_footer; ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Hubungi Kami</a></li>
                    <li><a href="<?php echo htmlspecialchars($url_tentang_kami); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Tentang Kami</a></li>
                    <li><a href="<?php echo htmlspecialchars($url_cara_pesan); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Cara Pesan</a></li>
                 </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                 <h5 class="text-uppercase fw-bold mb-4">Informasi</h5>
                 <ul class="list-unstyled footer-links">
                    <li><a href="<?php echo htmlspecialchars($url_faq); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Pertanyaan Umum (FAQ)</a></li>
                    <li><a href="<?php echo htmlspecialchars($url_kebijakan_privasi); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Kebijakan Privasi</a></li>
                    <li><a href="<?php echo htmlspecialchars($url_syarat_ketentuan); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Syarat & Ketentuan</a></li>
                    <li><a href="<?php echo htmlspecialchars($url_info_pengiriman); ?>" class="footer-link"><i class="bi bi-chevron-right me-1"></i> Informasi Pengiriman</a></li>
                 </ul>
           </div>
        </div>

        <hr class="my-4">
        <div class="row">
            <div class="col text-center text-white-50 small">
                &copy; <?php echo date("Y"); ?> Sedaya Furniture. Hak Cipta Dilindungi.
                 </div>
        </div>
    </div>
</footer>