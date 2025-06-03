<?php
// File: admin/koneksi.php

// Konfigurasi Database (Pastikan ini BENAR!)
define('DB_HOST', 'localhost');     // Biasanya 'localhost'
define('DB_USER', 'sedt2135_sedayafurn');         // sedt2135_sedayafurn
define('DB_PASS', '@Wsncrk06!');         // @Wsncrk06!
define('DB_NAME', 'sedt2135_sedaya');       // sedt2135_sedaya
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');

// ===>>> TAMBAHKAN DEFINISI URL INI <<<===
// Pastikan URL ini benar menunjuk ke domain utama Anda, dengan http atau https
define('MAIN_DOMAIN_BASE_URL', 'https://sedayafurniture.com');
// Definisikan URL dasar untuk folder gambar utama
define('MAIN_SITE_IMAGE_URL_BASE', rtrim(MAIN_DOMAIN_BASE_URL, '/') . '/images/');
// Definisikan URL placeholder absolut
define('PLACEHOLDER_IMAGE_URL', rtrim(MAIN_DOMAIN_BASE_URL, '/') . '/images/placeholder.png');
// ==========================================

// Buat Koneksi
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek Koneksi
if (mysqli_connect_errno()) {
    log_error("Admin Koneksi Database Gagal: " . mysqli_connect_error());
    // Tidak perlu die() di sini agar index.php bisa menangani
} else {
    mysqli_set_charset($con, "utf8mb4");
}

// Fungsi log_error() sudah dipindah ke includes/utils.php
// Pastikan file yang meng-include koneksi.php ini juga sudah meng-include utils.php

?>