<?php
// File: admin/kategori.php (Refactored)
require_once "session.php";
require_login();
require_once "../config/koneksi.php";
require_once "page-template.php"; // <--- Tambahkan ini

// Proses Simpan Kategori Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpanKategori'])) {
    // Validasi CSRF Token
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $kategoriBaru = trim($_POST['kategori'] ?? '');

    if (empty($kategoriBaru)) {
        set_flash_message('error', 'Nama kategori tidak boleh kosong.');
    } else {
        // Cek apakah kategori sudah ada (case-insensitive check)
        $stmtCheck = $con->prepare("SELECT id FROM kategori WHERE LOWER(nama) = LOWER(?)");
        $stmtCheck->bind_param("s", $kategoriBaru);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            set_flash_message('warning', 'Kategori "' . htmlspecialchars($kategoriBaru) . '" sudah tersedia.');
        } else {
            // Simpan kategori baru
            $stmtSimpan = $con->prepare("INSERT INTO kategori (nama) VALUES (?)");
            $stmtSimpan->bind_param("s", $kategoriBaru);
            if ($stmtSimpan->execute()) {
                set_flash_message('success', 'Kategori "' . htmlspecialchars($kategoriBaru) . '" berhasil ditambahkan.');
            } else {
                log_error("Gagal simpan kategori: " . $stmtSimpan->error);
                set_flash_message('error', 'Gagal menambahkan kategori. Silakan coba lagi.');
            }
            $stmtSimpan->close();
        }
        $stmtCheck->close();
    }
    // Redirect untuk mencegah resubmit dan menampilkan flash message
    header('Location: kategori.php');
    exit;
}

// Ambil daftar kategori
$queryKategori = mysqli_query($con, "SELECT id, nama FROM kategori ORDER BY nama ASC");
$daftarKategori = [];
if ($queryKategori) {
    while ($data = mysqli_fetch_assoc($queryKategori)) {
        $daftarKategori[] = $data;
    }
    mysqli_free_result($queryKategori);
} else {
    log_error("Gagal ambil daftar kategori: " . mysqli_error($con));
    set_flash_message('error', 'Gagal memuat daftar kategori.');
}

// Hasilkan token CSRF untuk form tambah
$csrf_token = generate_csrf_token();

mysqli_close($con);

// --- Mulai Output HTML dengan Template ---
admin_header('Manajemen Kategori'); // <--- Panggil Template Header
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Kategori</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                 <h5 class="mb-0"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Kategori Baru</h5>
            </div>
            <div class="card-body">
                <form action="kategori.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="kategori" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" id="kategori" name="kategori" placeholder="Masukkan nama kategori" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="simpanKategori"><i class="bi bi-save-fill me-1"></i> Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
             <div class="card-header">
                 <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Kategori (<?php echo count($daftarKategori); ?>)</h5>
             </div>
             <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" style="width: 5%;">#</th>
                                <th scope="col">Nama Kategori</th>
                                <th scope="col" style="width: 25%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarKategori)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada kategori.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($daftarKategori as $kategori): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($kategori['nama']); ?></td>
                                        <td class="text-center action-buttons">
                                            <a href="kategori-detail.php?p=<?php echo $kategori['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="produk.php?kategori=<?php echo $kategori['id']; ?>" class="btn btn-sm btn-warning" title="Lihat Produk">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                             </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// --- Akhiri Output HTML dengan Template ---
admin_footer(); // <--- Panggil Template Footer
?>