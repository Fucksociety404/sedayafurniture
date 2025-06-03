<?php
// File: admin/info_pages.php (Refactored)

// 1. Mulai Session & Wajibkan Login
require_once "session.php";
require_login();

// 2. Sertakan Koneksi Database & Konstanta & Template
require_once __DIR__ . '/../config/koneksi.php'; // Koneksi & Konstanta URL/Domain
require_once "page-template.php"; // <--- TAMBAHKAN INI

// --- Pengecekan Konstanta Domain Utama (Opsional, fallback) ---
if (!defined('MAIN_DOMAIN_BASE_URL')) {
    define('MAIN_DOMAIN_BASE_URL', 'https://sedayafurniture.com'); // Fallback jika belum ada
}
// -------------------------------------------------------------

global $con; // Gunakan koneksi global

// 3. Proses Hapus Halaman (Logic tetap sama, gunakan flash message)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deletePage'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $page_id_to_delete = filter_input(INPUT_POST, 'page_id', FILTER_VALIDATE_INT);
    if (!$page_id_to_delete || $page_id_to_delete <= 0) {
        set_flash_message('error', 'ID Halaman tidak valid untuk dihapus.');
    } else {
        if ($con instanceof mysqli) {
            $stmt_delete = $con->prepare("DELETE FROM halaman_info WHERE id = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $page_id_to_delete);
                if ($stmt_delete->execute()) {
                    if ($stmt_delete->affected_rows > 0) { set_flash_message('success', 'Halaman informasi berhasil dihapus.'); }
                    else { set_flash_message('warning', 'Halaman informasi tidak ditemukan atau sudah dihapus.'); }
                } else {
                    log_error("Hapus Halaman Info - Gagal execute delete (ID: $page_id_to_delete): " . $stmt_delete->error);
                    set_flash_message('error', 'Gagal menghapus halaman informasi.');
                }
                $stmt_delete->close();
            } else {
                 log_error("Hapus Halaman Info - Gagal prepare statement: " . $con->error);
                 set_flash_message('error', 'Gagal menyiapkan proses hapus halaman.');
             }
        } else { set_flash_message('error', 'Koneksi database bermasalah.'); }
    }
    header("Location: info_pages.php"); exit; // Redirect setelah proses
}

// 4. Ambil Daftar Halaman untuk Ditampilkan
$daftar_halaman = [];
$fetch_error = null;
if (!$con instanceof mysqli) {
    $fetch_error = "Koneksi database gagal.";
    if (!isset($_SESSION['flash_message'])) { set_flash_message('error', $fetch_error); }
    log_error("Admin Info Pages - Koneksi DB Gagal.");
} else {
    $sql = "SELECT id, slug, judul, tanggal_update FROM halaman_info ORDER BY judul ASC";
    $stmt = $con->prepare($sql);
    if ($stmt) {
        if ($stmt->execute()) {
             $result = $stmt->get_result();
             while ($row = $result->fetch_assoc()) { $daftar_halaman[] = $row; }
        } else {
             $fetch_error = "Gagal mengeksekusi query data halaman: " . $stmt->error;
             log_error("Admin Info Pages Execute Error: " . $stmt->error);
             if (!isset($_SESSION['flash_message'])) { set_flash_message('error', 'Gagal memuat data halaman.'); }
        }
        $stmt->close();
    } else {
        $fetch_error = "Gagal menyiapkan query data halaman: " . $con->error;
        log_error("Admin Info Pages Prepare Error: " . $con->error);
        if (!isset($_SESSION['flash_message'])) { set_flash_message('error', 'Gagal memuat data halaman.'); }
    }
    // Jangan tutup koneksi di sini jika footer membutuhkannya
    // mysqli_close($con);
}

// Hasilkan token CSRF untuk form hapus
$csrf_token = generate_csrf_token();

// --- Mulai Output HTML ---
admin_header('Manajemen Halaman Informasi');
?>
<style>
    .table code { font-size: 0.9em; padding: 0.2em 0.4em; background-color: #e9ecef; border-radius: 3px; color: #c7254e; }
    .action-buttons form, .action-buttons .btn { display: inline-block; margin: 0 3px; margin-bottom: 3px; }
    .action-buttons form { margin-bottom: 0; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Halaman Informasi</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="h4 mb-0"><i class="bi bi-file-text-fill me-2"></i>Manajemen Halaman Informasi (<?php echo count($daftar_halaman); ?>)</h2>
     <div><a href="info_pages_tambah.php" class="btn btn-success"><i class="bi bi-plus-square-fill me-1"></i> Tambah Halaman Baru</a></div>
</div>

<?php // Tampilkan error fetch jika ada dan belum ada flash message error
if ($fetch_error && !(isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error); ?></div>
<?php endif; ?>


<div class="card shadow-sm">
     <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Halaman</h5>
     </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Judul Halaman</th>
                        <th scope="col">Slug (URL)</th>
                        <th scope="col">Terakhir Update</th>
                        <th scope="col" class="text-center" style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_halaman) && !$fetch_error): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada halaman informasi. Silakan tambah halaman baru.</td></tr>
                    <?php elseif (!empty($daftar_halaman)): ?>
                        <?php foreach ($daftar_halaman as $halaman): ?>
                            <?php
                                // Buat URL absolut untuk tombol Lihat
                                $view_url = rtrim(MAIN_DOMAIN_BASE_URL, '/') . '/pages/info.php?page=' . urlencode($halaman['slug']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($halaman['judul']); ?></td>
                                <td><code><?php echo htmlspecialchars($halaman['slug']); ?></code></td>
                                <td><?php echo $halaman['tanggal_update'] ? date('d M Y H:i', strtotime($halaman['tanggal_update'])) : '-'; ?></td>
                                <td class="text-center action-buttons">
                                    <a href="info_pages_edit.php?slug=<?php echo urlencode($halaman['slug']); ?>" class="btn btn-sm btn-primary" title="Edit Halaman">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="<?php echo htmlspecialchars($view_url); ?>" class="btn btn-sm btn-outline-info" title="Lihat Halaman (Frontend)" target="_blank">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                     <form action="info_pages.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus halaman \'<?php echo addslashes(htmlspecialchars($halaman['judul'])); ?>\'?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="page_id" value="<?php echo $halaman['id']; ?>">
                                        <button type="submit" name="deletePage" class="btn btn-sm btn-danger" title="Hapus Halaman">
                                            <i class="bi bi-trash-fill"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mt-2">Slug digunakan sebagai bagian dari URL halaman informasi di website utama (contoh: .../pages/info.php?page=<b>slug-halaman-anda</b>).</p>
    </div>
</div>

<?php
// Tutup koneksi di footer jika diperlukan
admin_footer();
if (isset($con) && $con instanceof mysqli && mysqli_ping($con)) {
    mysqli_close($con);
}
?>