<?php
// File: pages/proses_kontak.php (Dengan Email Tembusan/BCC)
session_start(); // Mulai session untuk flash messages

// --- Pengaturan Dasar ---
$admin_email_penerima = "hello@sedayafurniture.com"; // <-- EMAIL UTAMA PENERIMA
// --->> TAMBAHKAN DAFTAR EMAIL TEMBUSAN (BCC) DI SINI <<---
// Pisahkan beberapa email dengan koma
$email_tembusan_bcc = "sedayafurniture@gmail.com, wisnucaraka06@gmail.com";
// ----------------------------------------------------------->>
$subjek_email_prefix = "[Pesan Kontak Sedaya Furniture]";
$halaman_kontak = "kontak.php"; // Halaman redirect

// --- Include File yang Dibutuhkan ---
// Sesuaikan path jika struktur Anda berbeda
require_once __DIR__ . '/../config/koneksi.php'; // Untuk koneksi DB (jika menyimpan pesan) & log
require_once __DIR__ . '/../includes/utils.php'; // Untuk fungsi log_error

global $con;

// --- Fungsi Redirect dengan Flash Message ---
if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }
}
// ------------------------------------------


// --- Proses Hanya Jika Method POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Ambil dan Sanitasi Input ---
    $nama = trim(filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $telepon = trim(filter_input(INPUT_POST, 'telepon', FILTER_SANITIZE_SPECIAL_CHARS));
    $subjek = trim(filter_input(INPUT_POST, 'subjek', FILTER_SANITIZE_SPECIAL_CHARS));
    $pesan = trim(filter_input(INPUT_POST, 'pesan', FILTER_SANITIZE_SPECIAL_CHARS));

    // --- Validasi Input ---
    $errors = [];
    if (empty($nama)) { $errors[] = "Nama wajib diisi."; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Alamat email tidak valid."; }
    if (empty($subjek)) { $errors[] = "Subjek wajib diisi."; }
    if (empty($pesan)) { $errors[] = "Pesan wajib diisi."; }
    // TODO: Tambahkan validasi anti-spam jika perlu

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        $_SESSION['form_input_kontak'] = $_POST;
        header("Location: " . $halaman_kontak);
        exit;
    }

    // --- Siapkan Email untuk Admin ---
    $email_subject_final = $subjek_email_prefix . " " . $subjek;
    $email_body = "Pesan baru dari formulir kontak Sedaya Furniture:\n\n";
    $email_body .= "Nama     : " . $nama . "\n";
    $email_body .= "Email    : " . $email . "\n";
    if (!empty($telepon)) {
        $email_body .= "Telepon  : " . $telepon . "\n";
    }
    $email_body .= "Subjek   : " . $subjek . "\n";
    $email_body .= "Pesan    :\n------------------------------------\n" . $pesan . "\n------------------------------------\n";
    $email_body .= "\nDikirim pada: " . date("d-m-Y H:i:s");

    // Header email
    $headers = "From: \"" . addslashes($nama) . "\" <" . $email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";

    // --- >> TAMBAHKAN HEADER BCC DI SINI << ---
    if (!empty($email_tembusan_bcc)) {
        $headers .= "Bcc: " . $email_tembusan_bcc . "\r\n";
    }
    // ----------------------------------------->>

    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // --- Kirim Email ---
    if (mail($admin_email_penerima, $email_subject_final, $email_body, $headers)) {
        // Sukses Kirim Email
        set_flash_message('success', 'Terima kasih! Pesan Anda telah berhasil terkirim.');
        unset($_SESSION['form_input_kontak']);
        header("Location: " . $halaman_kontak);
        exit;
    } else {
        // Gagal Kirim Email
        log_error("Gagal mengirim email kontak dari: $email ke $admin_email_penerima (BCC: $email_tembusan_bcc)");
        set_flash_message('error', 'Maaf, terjadi kesalahan teknis saat mengirim pesan Anda. Silakan coba lagi nanti atau hubungi kami langsung.');
        $_SESSION['form_input_kontak'] = $_POST;
        header("Location: " . $halaman_kontak);
        exit;
    }

    // --- Opsional: Simpan Pesan ke Database ---
    /*
    if ($con instanceof mysqli) {
        $stmt_simpan = $con->prepare("INSERT INTO pesan_kontak (nama, email, telepon, subjek, pesan, tanggal_kirim) VALUES (?, ?, ?, ?, ?, NOW())");
        // ... (bind_param, execute, close) ...
        mysqli_close($con);
    }
    */

} else {
    // Jika script diakses langsung tanpa POST
    set_flash_message('error', 'Akses tidak diizinkan.');
    header("Location: " . $halaman_kontak);
    exit;
}

?>