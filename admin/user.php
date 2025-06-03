<?php
// File: admin/user.php (Refactored)

// 1. Mulai Session & Wajibkan Login
require_once "session.php";
require_login();

// 2. Sertakan Koneksi & Template
require_once __DIR__ . '/../config/koneksi.php';
require_once "page-template.php"; // <--- TAMBAHKAN INI
// Utils biasanya sudah di-include oleh session.php jika diperlukan

global $con; // Gunakan koneksi global

// 3. Proses Hapus Pengguna (Jika ada request POST untuk hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUser'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $user_id_to_delete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!$user_id_to_delete || $user_id_to_delete <= 0) {
        set_flash_message('error', 'ID Pengguna tidak valid untuk dihapus.');
    } else {
        // Tidak boleh menghapus diri sendiri
        if (isset($_SESSION['user_id']) && $user_id_to_delete == $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        } else {
            if ($con instanceof mysqli) {
                $stmt_delete = $con->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $user_id_to_delete);
                    if ($stmt_delete->execute()) {
                        if ($stmt_delete->affected_rows > 0) {
                            set_flash_message('success', 'Pengguna berhasil dihapus.');
                        } else {
                            set_flash_message('warning', 'Pengguna tidak ditemukan atau sudah dihapus sebelumnya.');
                        }
                    } else {
                        log_error("Hapus User - Gagal execute delete (ID: $user_id_to_delete): " . $stmt_delete->error);
                        set_flash_message('error', 'Gagal menghapus pengguna.');
                    }
                    $stmt_delete->close();
                } else {
                    log_error("Hapus User - Gagal prepare statement delete: " . $con->error);
                    set_flash_message('error', 'Gagal menyiapkan proses hapus pengguna.');
                }
            } else {
                set_flash_message('error', 'Koneksi database bermasalah.');
            }
        }
    }
    // Redirect kembali ke halaman user list setelah proses hapus
    header("Location: user.php");
    exit;
}

// 4. Ambil Daftar Pengguna untuk Ditampilkan
$daftarUser = [];
$fetch_error = null;
if ($con instanceof mysqli) {
    $queryUser = mysqli_query($con, "SELECT id, nama, username, email FROM users ORDER BY nama ASC");
    if ($queryUser) {
        while ($user = mysqli_fetch_assoc($queryUser)) {
            $daftarUser[] = $user;
        }
        mysqli_free_result($queryUser);
    } else {
        $fetch_error = "Gagal mengambil data pengguna: " . mysqli_error($con);
        log_error("Fetch User List Error: " . mysqli_error($con));
        // Set flash message jika belum ada error lain
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('error', 'Gagal memuat daftar pengguna.');
        }
    }
    // Jangan tutup koneksi di sini jika template footer memerlukannya
    // mysqli_close($con);
} else {
    $fetch_error = "Koneksi database tidak tersedia.";
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('error', $fetch_error);
    }
}

// Hasilkan token CSRF untuk form hapus
$csrf_token = generate_csrf_token();

// --- Mulai Output HTML ---
admin_header('Manajemen Pengguna');
?>
<style>
    .table td, .table th { vertical-align: middle; }
    .table th { text-align: center; }
    .table td:first-child, .table td:last-child { text-align: center; } /* Nomor dan Aksi tengah */
    .action-buttons .btn,
    .action-buttons form {
         display: inline-block;
         margin: 0 3px; /* Sedikit jarak */
         margin-bottom: 3px;
    }
     .action-buttons form { margin-bottom: 0; } /* Reset margin form */
     .action-buttons .btn-sm { padding: 0.25rem 0.5rem; font-size: .875rem; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-people-fill me-2"></i>Manajemen Pengguna (<?php echo count($daftarUser); ?>)</h2>
    <a href="tambahanggota.php" class="btn btn-success"><i class="bi bi-person-plus-fill me-1"></i> Tambah User</a>
</div>

<?php // Tampilkan error fetch jika ada dan belum ada flash message error
if ($fetch_error && !(isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
     <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Pengguna</h5>
     </div>
     <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">#</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Username</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarUser) && !$fetch_error): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada data pengguna.</td>
                        </tr>
                    <?php elseif (!empty($daftarUser)): ?>
                        <?php $nomorUrut = 1; foreach ($daftarUser as $user) : ?>
                            <tr>
                                <td><?php echo $nomorUrut++; ?></td>
                                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="action-buttons">
                                    <a href="edituser.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <?php // Tombol Hapus menggunakan Form POST ?>
                                    <form action="user.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna \'<?php echo addslashes(htmlspecialchars($user['username'])); ?>\'? Tindakan ini tidak dapat diurungkan.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="deleteUser" class="btn btn-danger btn-sm" title="Hapus" <?php echo (isset($_SESSION['user_id']) && $user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; // Disable hapus diri sendiri ?>>
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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