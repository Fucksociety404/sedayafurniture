<?php
// File: admin/session.php

// Memulai sesi hanya jika belum ada sesi yang aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// --- Fungsi Keamanan Dasar ---

/**
 * Memeriksa apakah pengguna sudah login. Jika tidak, redirect ke halaman login.
 */
function require_login() {
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        // Simpan URL yang diminta agar bisa redirect kembali setelah login (opsional)
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('location: login.php');
        exit;
    }
    // Regenerasi session ID secara berkala untuk mencegah session fixation (opsional, bisa ditambahkan timer)
    // if (!isset($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 1800) { // Contoh: tiap 30 menit
    //     session_regenerate_id(true);
    //     $_SESSION['last_regen'] = time();
    // }
}

/**
 * Menghasilkan token CSRF dan menyimpannya di session.
 * @return string Token CSRF yang dihasilkan.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi token CSRF yang dikirimkan melalui POST.
 * Redirect ke halaman error atau logout jika token tidak valid.
 * @param string $token Token yang diterima dari form.
 */
function validate_csrf_token($token) {
    // Pastikan session token ada sebelum validasi
    if (!isset($_SESSION['csrf_token'])) {
        // Mungkin sesi habis atau belum di-generate
         set_flash_message('error', 'Sesi tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
         header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
         exit;
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        // Token tidak valid
        unset($_SESSION['csrf_token']); // Hapus token lama
        set_flash_message('error', 'Aksi tidak valid (invalid token). Silakan coba lagi.');
        // Redirect ke halaman sebelumnya atau halaman utama admin
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }
    // Token valid, hapus agar tidak bisa digunakan lagi untuk request yang sama
     unset($_SESSION['csrf_token']);
     // Generate token baru untuk request berikutnya
     // generate_csrf_token(); // Atau generate saat form berikutnya ditampilkan
}

// --- Fungsi Flash Message ---

/**
 * Mengatur pesan flash (pesan sementara) dalam session.
 * @param string $type Tipe pesan ('success', 'error', 'warning', 'info').
 * @param string $message Isi pesan.
 */
function set_flash_message($type, $message) {
    // Pastikan sesi sudah aktif sebelum menggunakan $_SESSION
    if (session_status() === PHP_SESSION_ACTIVE) {
         $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
         ];
    } else {
        // Handle kasus sesi tidak aktif (seharusnya tidak terjadi jika session_start dipanggil benar)
        log_error("Attempted to set flash message without active session.");
    }
}


/**
 * Mengambil dan menghapus pesan flash dari session.
 * @return array|null Pesan flash atau null jika tidak ada.
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); // Hapus setelah diambil
        return $message;
    }
    return null;
}

/**
 * Menampilkan pesan flash jika ada.
 */
function display_flash_message() {
    $flash = get_flash_message();
    if ($flash) {
        $alert_class = '';
        $icon_class = '';
        switch ($flash['type']) {
            case 'success':
                $alert_class = 'alert-success';
                $icon_class = 'bi-check-circle-fill';
                break;
            case 'error':
                $alert_class = 'alert-danger';
                $icon_class = 'bi-exclamation-triangle-fill';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                $icon_class = 'bi-exclamation-triangle-fill';
                break;
            case 'info':
                $alert_class = 'alert-info';
                $icon_class = 'bi-info-circle-fill';
                break;
            default:
                $alert_class = 'alert-secondary';
                $icon_class = 'bi-info-circle-fill';
        }
        // Pastikan elemen alert ada di dalam container yang sesuai di layout Anda
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show mt-3" role="alert">';
        echo '<i class="bi ' . $icon_class . ' me-2"></i>';
        echo htmlspecialchars($flash['message']); // Pastikan pesan di-escape
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// Panggil require_login() di awal setiap halaman admin yang memerlukan autentikasi
// Contoh: require_once "session.php"; require_login();

?>