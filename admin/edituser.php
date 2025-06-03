<?php
// File: admin/edituser.php (Refactored)

// 1. Mulai Session & Wajibkan Login
require_once "session.php";
require_login();

// 2. Sertakan Koneksi Database & Template
require_once __DIR__ . '/../config/koneksi.php';
require_once "page-template.php"; // <--- TAMBAHKAN INI

global $con; // Gunakan koneksi global

// 3. Validasi Input ID dari GET Parameter
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id || $user_id <= 0) {
    set_flash_message('error', 'ID Pengguna tidak valid.');
    header('Location: user.php');
    exit;
}

// Inisialisasi variabel data user
$user_data = null;

// 4. Proses Update Data Jika Form Disubmit (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // Ambil dan validasi data dari POST
    $posted_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $username_baru = trim($_POST['username'] ?? '');
    $email_baru = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $nama_baru = trim($_POST['nama'] ?? ''); // Ambil nama baru

    if ($posted_id !== $user_id) {
        set_flash_message('error', 'Terjadi ketidakcocokan data pengguna.');
        header('Location: user.php');
        exit;
    }

    // Validasi Input Dasar
    if (empty($username_baru) || $email_baru === false || empty($nama_baru)) {
        set_flash_message('error', 'Nama, Username, dan Email valid tidak boleh kosong.');
        header('Location: edituser.php?id=' . $user_id); // Kembali ke edit form
        exit;
    }

    if ($con instanceof mysqli) {
        // Cek apakah username atau email baru sudah digunakan oleh user lain
        $stmt_check = $con->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        if ($stmt_check) {
            $stmt_check->bind_param("ssi", $username_baru, $email_baru, $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                set_flash_message('warning', 'Username atau Email sudah digunakan oleh pengguna lain.');
                $stmt_check->close();
                header('Location: edituser.php?id=' . $user_id); // Kembali ke form edit
                exit;
            }
            $stmt_check->close();
        } else {
             log_error("Edit User - Prepare statement check duplicate gagal: " . $con->error);
             set_flash_message('error', 'Gagal memeriksa data duplikat.');
             header('Location: edituser.php?id=' . $user_id);
             exit;
        }

        // Lakukan Update Menggunakan Prepared Statement
        $stmt_update = $con->prepare("UPDATE users SET nama = ?, username = ?, email = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("sssi", $nama_baru, $username_baru, $email_baru, $user_id);
            if ($stmt_update->execute()) {
                set_flash_message('success', 'Data pengguna berhasil diperbarui.');
                header('Location: user.php'); // Redirect ke daftar user
                exit;
            } else {
                log_error("Edit User - Gagal execute update (ID: $user_id): " . $stmt_update->error);
                set_flash_message('error', 'Gagal memperbarui data pengguna.');
            }
            $stmt_update->close();
        } else {
            log_error("Edit User - Gagal prepare statement update: " . $con->error);
            set_flash_message('error', 'Gagal menyiapkan pembaruan data pengguna.');
        }
    } else {
         set_flash_message('error', 'Koneksi database bermasalah saat mencoba update.');
    }
    // Redirect kembali ke halaman edit jika terjadi error saat update
    header('Location: edituser.php?id=' . $user_id);
    exit;
}

// 5. Ambil Data User untuk Ditampilkan di Form (Method GET atau setelah error POST)
if ($con instanceof mysqli) {
    $stmt_get = $con->prepare("SELECT id, nama, username, email FROM users WHERE id = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $user_data = $result_get->fetch_assoc();
        $stmt_get->close();

        if (!$user_data) {
            set_flash_message('error', 'Pengguna dengan ID tersebut tidak ditemukan.');
            header('Location: user.php');
            exit;
        }
    } else {
        log_error("Edit User - Gagal prepare statement get (ID: $user_id): " . $con->error);
        set_flash_message('error', 'Gagal memuat data pengguna.');
        header('Location: user.php');
        exit;
    }
} else {
    set_flash_message('error', 'Koneksi database bermasalah. Tidak dapat memuat data.');
    // Tidak bisa menampilkan form jika data tidak ada, $user_data akan null
}

// Hasilkan token CSRF untuk form
$csrf_token = generate_csrf_token();

// --- Mulai Output HTML ---
$page_title = $user_data ? 'Edit Pengguna: ' . htmlspecialchars($user_data['username']) : 'Edit Pengguna';
admin_header($page_title);
?>
<style>
    .card-header { background-color: #0d6efd; color: white; } /* Header Biru */
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="user.php">Pengguna</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Pengguna</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                 <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i> Edit Pengguna</h5>
            </div>
            <div class="card-body">
                <?php if ($user_data): // Hanya tampilkan form jika data user berhasil diambil ?>
                <form method="POST" action="edituser.php?id=<?php echo $user_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required autocomplete="email">
                    </div>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle-fill"></i> Untuk mengubah password, silakan gunakan fitur "Lupa Password" (jika tersedia) atau hubungi administrator sistem.
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" name="update" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Perbarui Data</button>
                        <a href="user.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning">Data pengguna tidak dapat dimuat untuk diedit.</div>
                    <a href="user.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar</a>
                <?php endif; ?>
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