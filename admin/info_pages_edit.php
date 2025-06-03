<?php
// File: admin/info_pages_edit.php (Refactored)
require_once "session.php";
require_login();
require_once "../config/koneksi.php";
require_once "page-template.php"; // <--- TAMBAHKAN INI

global $con;
$page_data = null;
$page_slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);
$error_message = ''; // Untuk error fetch
$success_message = ''; // Tidak terpakai, diganti flash

// Cek koneksi & slug
if (!$con instanceof mysqli) {
     $error_message = "Koneksi database gagal.";
     if (function_exists('log_error')) { log_error("Admin Info Pages Edit - Koneksi DB Gagal"); }
     if (function_exists('set_flash_message')) { set_flash_message('error', $error_message); }
     // Jika koneksi gagal, mungkin tidak bisa render halaman sama sekali
     // header('Location: info_pages.php'); exit;
} elseif (empty($page_slug)) {
    set_flash_message('error', 'Slug halaman tidak valid.');
    header('Location: info_pages.php');
    exit;
} else {
    // Proses Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateBtn'])) {
        validate_csrf_token($_POST['csrf_token'] ?? '');

        $posted_slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);
        $judul_baru = trim($_POST['judul'] ?? '');
        $konten_baru = $_POST['konten'] ?? ''; // Ambil HTML dari TinyMCE

        if ($posted_slug !== $page_slug) {
            set_flash_message('error', 'Slug tidak cocok.');
            header('Location: info_pages.php');
            exit;
        }
        if (empty($judul_baru)) {
            set_flash_message('error', 'Judul halaman tidak boleh kosong.');
            header('Location: info_pages_edit.php?slug=' . urlencode($page_slug));
            exit;
        }

        $stmt = $con->prepare("UPDATE halaman_info SET judul = ?, konten = ?, tanggal_update = NOW() WHERE slug = ?"); // Tambah update tanggal
        if ($stmt) {
            $stmt->bind_param("sss", $judul_baru, $konten_baru, $page_slug);
            if ($stmt->execute()) {
                set_flash_message('success', 'Halaman "' . htmlspecialchars($judul_baru) . '" berhasil diperbarui.');
                header('Location: info_pages.php'); // Redirect ke daftar
                exit;
            } else {
                log_error("Gagal update halaman_info (slug: $page_slug): " . $stmt->error);
                set_flash_message('error', 'Gagal memperbarui halaman.');
            }
            $stmt->close();
        } else {
            log_error("Gagal prepare statement update halaman_info (slug: $page_slug): " . $con->error);
            set_flash_message('error', 'Gagal menyiapkan pembaruan halaman.');
        }
         header('Location: info_pages_edit.php?slug=' . urlencode($page_slug)); // Kembali ke edit jika gagal
         exit;
    }

    // Ambil data halaman saat ini (GET Request atau setelah gagal POST)
    $stmt_get = $con->prepare("SELECT id, slug, judul, konten FROM halaman_info WHERE slug = ?");
    if ($stmt_get) {
        $stmt_get->bind_param("s", $page_slug);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $page_data = $result_get->fetch_assoc();
        $stmt_get->close();

        if (!$page_data) {
            set_flash_message('error', 'Halaman informasi dengan slug "' . htmlspecialchars($page_slug) . '" tidak ditemukan.');
            header('Location: info_pages.php');
            exit;
        }
    } else {
        log_error("Gagal prepare statement get halaman_info (slug: $page_slug): " . $con->error);
        $error_message = 'Gagal memuat data halaman.';
        if (function_exists('set_flash_message')) { set_flash_message('error', $error_message); }
        // $page_data tetap null jika gagal fetch
    }
    // Jangan tutup koneksi jika footer butuh
    // mysqli_close($con);
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// --- Mulai Output HTML ---
$page_title = ($page_data && isset($page_data['judul'])) ? 'Edit Halaman: ' . htmlspecialchars($page_data['judul']) : 'Edit Halaman';
admin_header($page_title); // Panggil template header
?>
<style>
    .card-header { background-color: #fd7e14; color: white;} /* Orange Header */
    /* Pastikan editor TinyMCE tampil dengan benar */
    .tox-tinymce { border-radius: 0.375rem; border: 1px solid #dee2e6 !important; }
</style>

 <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="info_pages.php">Halaman Informasi</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Halaman</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Halaman: <?php echo htmlspecialchars($page_data['judul'] ?? $page_slug); ?></h3>
    </div>
    <div class="card-body">
        <?php if ($error_message && !$page_data): // Tampilkan error fetch jika data gagal load ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <a href="info_pages.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar</a>
        <?php elseif ($page_data): ?>
            <form action="info_pages_edit.php?slug=<?php echo urlencode($page_slug); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($page_data['slug']); ?>">

                <div class="mb-3">
                    <label for="slug_display" class="form-label">Slug (URL)</label>
                    <input type="text" id="slug_display" class="form-control" value="<?php echo htmlspecialchars($page_data['slug']); ?>" readonly disabled>
                    <small class="text-muted">Slug tidak dapat diubah setelah dibuat.</small>
                </div>

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Halaman <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($page_data['judul']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="konten" class="form-label">Konten Halaman</label>
                    <textarea class="form-control" id="konten" name="konten" rows="20"><?php echo htmlspecialchars($page_data['konten'] ?? ''); // Isi textarea dengan data ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary" name="updateBtn"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                    <a href="info_pages.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
                </div>
            </form>
        <?php elseif(!$error_message): // Jika $page_data null tapi tidak ada error message (kasus aneh) ?>
             <div class="alert alert-warning">Data halaman tidak dapat dimuat.</div>
             <a href="info_pages.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  // Inisialisasi TinyMCE pada textarea dengan id="konten"
  tinymce.init({
    selector: '#konten',
    plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
    menubar: 'file edit view insert format tools table help',
    toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media link anchor codesample | ltr rtl',
    height: 500, // Atur tinggi editor
    // Opsi lain (sesuaikan dengan kebutuhan Anda)
     image_title: true,
     automatic_uploads: true, // Jika ingin handle upload otomatis (perlu konfigurasi backend)
     file_picker_types: 'image',
     /* file_picker_callback: function (cb, value, meta) { ... } */ // Untuk custom file picker
     content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }' // Sesuaikan style konten editor jika perlu
  });
</script>

<?php
// Tutup koneksi di footer jika diperlukan
admin_footer();
if (isset($con) && $con instanceof mysqli && mysqli_ping($con)) {
    mysqli_close($con);
}
?>