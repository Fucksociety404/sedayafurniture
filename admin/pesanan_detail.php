<?php
// File: admin/pesanan_detail.php (Layout Navbar)
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php';

global $con;

// Validasi ID dari GET (Sama seperti sebelumnya)
$pesanan_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pesanan_id || $pesanan_id <= 0) { /* ... handle error ID invalid ... */ }

// Proses Update Status/Catatan (Logika PHP tetap sama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatePesanan'])) {
    // ... (Validasi CSRF, Ambil data POST, Validasi status, Cek Koneksi) ...
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $posted_id = filter_input(INPUT_POST, 'pesanan_id', FILTER_VALIDATE_INT);
    if ($posted_id !== $pesanan_id) { /* ... error ID tidak cocok ... */ }
    elseif (/* ... status tidak valid ... */) { /* ... error status ... */ }
    else {
        if ($con instanceof mysqli) {
            $stmt = $con->prepare("UPDATE pesanan SET status_pesanan = ?, catatan_admin = ?, total_harga = ? WHERE id = ?");
            if ($stmt) { /* ... bind, execute, set flash ... */ $stmt->close(); }
            else { /* ... handle prepare error ... */ }
        } else { /* ... handle connection error ... */ }
    }
    header('Location: pesanan_detail.php?id=' . $pesanan_id); exit; // Redirect kembali
}

// Ambil data pesanan untuk ditampilkan (Logika PHP tetap sama)
$pesanan_data = null;
if ($con instanceof mysqli) {
    $stmt_get = $con->prepare("SELECT * FROM pesanan WHERE id = ?");
    if ($stmt_get) { /* ... bind, execute, fetch ... */ $stmt_get->close(); }
    else { /* ... handle prepare error ... */ }
    if (!$pesanan_data) { /* ... handle not found error ... */ }
     mysqli_close($con); // Tutup koneksi
} else { /* ... handle connection error ... */ header('Location: pesanan.php'); exit;}

$page_title = "Detail Pesanan #" . ($pesanan_data['id'] ?? 'Error');
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
    <style>
         .detail-label { font-weight: 600; color: #555; } .detail-value { margin-bottom: 0.8rem; }
         /* ... (Style badge status sama seperti di pesanan.php) ... */
         .status-badge { /* ... */ } .status-baru { /* ... */ } .status-diproses { /* ... */ } .status-dikirim { /* ... */ } .status-selesai { /* ... */ } .status-batal { /* ... */ }
         pre { white-space: pre-wrap; word-wrap: break-word; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6; font-size: 0.9em;}
    </style>
</head>
<body>
    <?php require "navbar.php"; // Include Navbar Horizontal ?>

    <main class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="pesanan.php">Pesanan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail Pesanan #<?php echo htmlspecialchars($pesanan_data['id'] ?? ''); ?></li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                 <?php // Tampilkan flash message di sini jika ada ?>
                 <?php // display_flash_message(); // Sudah di navbar ?>

                <?php if ($pesanan_data): // Tampilkan detail jika data ada ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i> Detail Pesanan #<?php echo $pesanan_data['id']; ?></h5>
                        </div>
                        <div class="card-body">
                             <dl class="row">
                                <dt class="col-sm-4 detail-label">ID Pesanan:</dt><dd class="col-sm-8 detail-value"><?php echo $pesanan_data['id']; ?></dd>
                                <dt class="col-sm-4 detail-label">Tanggal Pesan:</dt><dd class="col-sm-8 detail-value"><?php echo date('d M Y, H:i', strtotime($pesanan_data['tanggal_pesanan'])); ?></dd>
                                <dt class="col-sm-4 detail-label">Nama Pelanggan:</dt><dd class="col-sm-8 detail-value"><?php echo htmlspecialchars($pesanan_data['nama_pelanggan']); ?></dd>
                                <dt class="col-sm-4 detail-label">Kontak:</dt><dd class="col-sm-8 detail-value"><?php echo htmlspecialchars($pesanan_data['kontak_pelanggan'] ?? '-'); ?></dd>
                                <dt class="col-sm-4 detail-label">Sumber:</dt><dd class="col-sm-8 detail-value"><?php echo htmlspecialchars($pesanan_data['sumber_pesanan'] ?? '-'); ?></dd>
                                <dt class="col-sm-4 detail-label">Total Harga (Rp):</dt><dd class="col-sm-8 detail-value"><?php echo ($pesanan_data['total_harga'] !== null) ? number_format($pesanan_data['total_harga'], 0, ',', '.') : '-'; ?></dd>
                                <dt class="col-sm-4 detail-label">Status Saat Ini:</dt>
                                <dd class="col-sm-8 detail-value">
                                     <?php $status_class = 'secondary'; $status_text = $pesanan_data['status_pesanan']; switch (strtolower($status_text)) { case 'baru': $status_class = 'baru'; break; case 'diproses': $status_class = 'diproses'; break; case 'dikirim': $status_class = 'dikirim'; break; case 'selesai': $status_class = 'selesai'; break; case 'batal': $status_class = 'batal'; break; } ?>
                                    <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                </dd>
                                <dt class="col-sm-4 detail-label">Terakhir Update:</dt><dd class="col-sm-8 detail-value"><?php echo date('d M Y, H:i', strtotime($pesanan_data['tanggal_update_status'])); ?></dd>
                                <dt class="col-sm-12 detail-label mt-3">Detail Pesanan:</dt><dd class="col-sm-12"><pre><?php echo htmlspecialchars($pesanan_data['detail_pesanan'] ?? '-'); ?></pre></dd>
                                <dt class="col-sm-12 detail-label mt-3">Catatan Admin:</dt><dd class="col-sm-12"><pre><?php echo htmlspecialchars($pesanan_data['catatan_admin'] ?? '-'); ?></pre></dd>
                             </dl>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                             <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Ubah Status / Catatan / Harga</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="pesanan_detail.php?id=<?php echo $pesanan_id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="pesanan_id" value="<?php echo $pesanan_id; ?>">

                                <div class="row">
                                     <div class="col-md-6 mb-3">
                                         <label for="status_pesanan" class="form-label">Ubah Status Menjadi <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status_pesanan" name="status_pesanan" required>
                                            <?php $current_status = $pesanan_data['status_pesanan']; $allowed_status = ['Baru', 'Diproses', 'Dikirim', 'Selesai', 'Batal']; ?>
                                            <?php foreach($allowed_status as $status_opt): ?>
                                                <option value="<?php echo $status_opt; ?>" <?php echo ($current_status == $status_opt) ? 'selected' : ''; ?>><?php echo $status_opt; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                     <div class="col-md-6 mb-3">
                                         <label for="total_harga" class="form-label">Ubah Total Harga (Rp)</label>
                                        <input type="number" step="0.01" class="form-control" id="total_harga" name="total_harga" placeholder="Kosongkan jika tidak berubah" value="<?php echo htmlspecialchars($pesanan_data['total_harga'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="catatan_admin" class="form-label">Tambah/Ubah Catatan Admin</label>
                                    <textarea class="form-control" id="catatan_admin" name="catatan_admin" rows="4" placeholder="Tambahkan catatan internal di sini..."><?php echo htmlspecialchars($pesanan_data['catatan_admin'] ?? ''); ?></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="updatePesanan" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                                    <a href="pesanan.php" class="btn btn-secondary"><i class="bi bi-list-ul me-1"></i> Kembali ke Daftar</a>
                                </div>
                            </form>
                         </div>
                    </div>
                 <?php else: ?>
                     // Pesan jika data pesanan tidak ditemukan (meskipun sudah ada redirect)
                     <div class="alert alert-warning">Detail pesanan tidak ditemukan.</div>
                     <a href="pesanan.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar</a>
                 <?php endif; ?>
                </div>
            </div>
        </main>

        <footer class="bg-light py-3 mt-5">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
            </div>
        </footer>

    </div> // Penutup main-content-wrapper tidak diperlukan jika tidak pakai sidebar
</div> // Penutup admin-wrapper tidak diperlukan

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
// <script src="assets/js/admin-scripts.js"></script> // JS ini mungkin tidak diperlukan jika tidak ada sidebar toggle
</body>
</html>
<?php if (isset($con) && $con instanceof mysqli) { mysqli_close($con); } ?>