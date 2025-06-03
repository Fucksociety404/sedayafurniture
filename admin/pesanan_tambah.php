<?php
// File: admin/pesanan_tambah.php (Layout Navbar)
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php';

global $con;
$page_title = "Tambah Pesanan Baru";

// Proses Simpan Pesanan Baru (Logika PHP tetap sama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpanPesanan'])) {
    // ... (Validasi CSRF, Ambil data POST, Validasi input, Cek Koneksi) ...
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $nama_pelanggan = trim(filter_input(INPUT_POST, 'nama_pelanggan', FILTER_SANITIZE_SPECIAL_CHARS));
    // ... (ambil input lainnya) ...
    $errors = []; // Cek error validasi
    if (empty($nama_pelanggan)) { $errors[] = "Nama pelanggan wajib diisi."; }
    // ... (cek error lain) ...
    if (!empty($errors)) { /* ... set flash message, simpan input, redirect ... */ }
    else {
        if ($con instanceof mysqli) {
             $stmt = $con->prepare("INSERT INTO pesanan (...) VALUES (?, ?, ... NOW())");
             if ($stmt) { /* ... bind, execute, set flash, redirect ... */ $stmt->close(); }
             else { /* ... handle prepare error ... */ }
        } else { /* ... handle connection error ... */ }
    }
     // Redirect kembali ke form jika gagal
     $_SESSION['form_input_pesanan'] = $_POST; header("Location: pesanan_tambah.php"); exit;
}

// Ambil data form input sebelumnya
$form_input = $_SESSION['form_input_pesanan'] ?? []; unset($_SESSION['form_input_pesanan']);
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Admin Sedaya</title>
    <link rel="icon" href="../images/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    // <link rel="stylesheet" href="assets/css/admin-styles.css">
</head>
<body>
    <?php require "navbar.php"; // Include Navbar Horizontal ?>

    <main class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="pesanan.php">Pesanan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Pesanan</li>
            </ol>
        </nav>

         <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle-fill me-2"></i> Tambah Catatan Pesanan Baru</h5>
                    </div>
                    <div class="card-body">
                        <?php // Flash message sudah di navbar ?>
                        <form method="POST" action="pesanan_tambah.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            {/* ... Form input (nama, kontak, sumber, detail, harga, status, catatan - sama seperti sebelumnya) ... */}
                            <div class="mb-3"> <label for="nama_pelanggan">Nama*</label><input type="text" name="nama_pelanggan" required value="<?php echo htmlspecialchars($form_input['nama_pelanggan'] ?? ''); ?>"></div>
                            <div class="mb-3"> <label for="kontak_pelanggan">Kontak</label><input type="text" name="kontak_pelanggan" value="<?php echo htmlspecialchars($form_input['kontak_pelanggan'] ?? ''); ?>"></div>
                            <div class="mb-3"> <label for="sumber_pesanan">Sumber</label><select name="sumber_pesanan">...</select></div>
                            <div class="mb-3"> <label for="detail_pesanan">Detail*</label><textarea name="detail_pesanan" required><?php echo htmlspecialchars($form_input['detail_pesanan'] ?? ''); ?></textarea></div>
                            <div class="row"><div class="col-md-6 mb-3"><label>Total Harga</label><input type="number" name="total_harga" value="..."></div><div class="col-md-6 mb-3"><label>Status*</label><select name="status_pesanan" required>...</select></div></div>
                            <div class="mb-3"> <label for="catatan_admin">Catatan</label><textarea name="catatan_admin"><?php echo htmlspecialchars($form_input['catatan_admin'] ?? ''); ?></textarea></div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" name="simpanPesanan" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Simpan</button>
                                <a href="pesanan.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    // <script src="assets/js/admin-scripts.js"></script>
</body>
</html>
<?php if (isset($con) && $con instanceof mysqli) { mysqli_close($con); } ?>