<?php
// File: admin/tambahanggota.php (Refactored)

// 1. Mulai Session & Wajibkan Login
require_once "session.php";
require_login();

// 2. Sertakan Koneksi Database & Template
require_once __DIR__ . '/../config/koneksi.php';
require_once "page-template.php"; // <--- TAMBAHKAN INI

global $con; // Gunakan koneksi global

// 3. Proses Penambahan Pengguna (Jika Form Disubmit - Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // Ambil dan validasi data dari POST
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validasi Input Dasar Server-Side
    $errors = [];
    if (empty($nama)) { $errors[] = "Nama tidak boleh kosong."; }
    if (empty($username)) { $errors[] = "Username tidak boleh kosong."; }
    if ($email === false) { $errors[] = "Format email tidak valid."; }
    if (empty($password)) { $errors[] = "Password tidak boleh kosong."; }
    // Validasi tambahan (contoh: panjang password)
    // if (strlen($password) < 6) { $errors[] = "Password minimal 6 karakter."; }

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        $_SESSION['form_input_tambah_anggota'] = $_POST; // Simpan input
    } else {
        if ($con instanceof mysqli) {
            // Cek apakah username atau email sudah ada
            $stmt_check = $con->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            if ($stmt_check) {
                $stmt_check->bind_param("ss", $username, $email);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    set_flash_message('warning', 'Username atau Email sudah terdaftar. Gunakan yang lain.');
                    $stmt_check->close();
                    $_SESSION['form_input_tambah_anggota'] = $_POST; // Simpan input
                } else {
                    $stmt_check->close();
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Lakukan INSERT Menggunakan Prepared Statement
                    $stmt_insert = $con->prepare("INSERT INTO users (nama, username, email, password) VALUES (?, ?, ?, ?)");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("ssss", $nama, $username, $email, $hashed_password);
                        if ($stmt_insert->execute()) {
                            set_flash_message('success', 'Pengguna baru berhasil ditambahkan.');
                            unset($_SESSION['form_input_tambah_anggota']); // Hapus input tersimpan
                            header('Location: user.php'); // Redirect ke daftar user
                            exit;
                        } else {
                            log_error("Tambah Anggota - Gagal execute insert: " . $stmt_insert->error);
                            set_flash_message('error', 'Gagal menambahkan pengguna ke database.');
                        }
                        $stmt_insert->close();
                    } else {
                        log_error("Tambah Anggota - Gagal prepare statement insert: " . $con->error);
                        set_flash_message('error', 'Gagal menyiapkan proses tambah pengguna.');
                    }
                }
            } else {
                 log_error("Tambah Anggota - Prepare statement check duplicate gagal: " . $con->error);
                 set_flash_message('error', 'Gagal memeriksa data duplikat.');
            }
        } else {
             set_flash_message('error', 'Koneksi database bermasalah.');
        }
    }
    // Redirect kembali ke halaman tambah jika terjadi error atau warning
    header('Location: tambahanggota.php');
    exit;
}

// Ambil data form input sebelumnya jika ada (setelah redirect karena error)
$form_input = $_SESSION['form_input_tambah_anggota'] ?? [];
unset($_SESSION['form_input_tambah_anggota']); // Hapus setelah diambil

// Hasilkan token CSRF untuk form
$csrf_token = generate_csrf_token();

// --- Mulai Output HTML ---
admin_header('Tambah Pengguna Baru');
?>
<style>
    .card-header { background-color: #198754; color: white; } /* Warna header hijau */
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="user.php">Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tambah Pengguna</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Tambah Pengguna Baru</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="tambahanggota.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" autocomplete="name" required value="<?php echo htmlspecialchars($form_input['nama'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" autocomplete="username" required value="<?php echo htmlspecialchars($form_input['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" autocomplete="email" required value="<?php echo htmlspecialchars($form_input['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                        <div id="passwordHelpBlock" class="form-text">
                          Minimal 6 karakter (disarankan kombinasi huruf dan angka).
                        </div>
                    </div>
                     <div class="d-flex justify-content-between">
                        <button type="submit" name="tambah" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah</button>
                        <a href="user.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Tutup koneksi di footer jika diperlukan
admin_footer();
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}
?>