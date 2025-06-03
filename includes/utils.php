<?php
// File: includes/utils.php

// Definisikan path log di sini atau ambil dari konfigurasi global jika ada
define('APP_LOG_FILE', __DIR__ . '/../logs/error.log'); // Path log relatif dari folder includes

/**
 * Fungsi untuk logging error ke file.
 * @param string $message Pesan error yang akan di-log.
 */
function log_error($message) {
    $timestamp = date("Y-m-d H:i:s");
    $log_dir = dirname(APP_LOG_FILE);
    // Pastikan direktori log ada
    if (!file_exists($log_dir)) {
        // Gunakan @ untuk menekan warning jika direktori sudah ada (race condition)
        @mkdir($log_dir, 0755, true);
    }
    // Tambahkan pesan ke file log
    // Gunakan @ untuk menekan warning jika file log tidak bisa ditulis
    @error_log("[$timestamp] " . $message . "\n", 3, APP_LOG_FILE);
}

// Tambahkan fungsi utilitas lain di sini jika perlu
?>