<?php
// File: admin/pesanan.php (Layout Navbar Horizontal)
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php'; // Path ke koneksi utama

global $con;

// Pengaturan Pagination (Bisa diaktifkan nanti)
// $limit = 20;
// $currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
// $offset = ($currentPage - 1) * $limit;

$daftarPesanan = [];
$totalPesanan = 0; // Untuk info jumlah
$fetch_error = null;

if ($con instanceof mysqli) {
    // Query untuk mengambil data
    // TODO: Tambahkan LIMIT ?, OFFSET ? jika pakai pagination
    $sql = "SELECT id, nama_pelanggan, kontak_pelanggan, tanggal_pesanan, sumber_pesanan, status_pesanan
            FROM pesanan
            ORDER BY tanggal_pesanan DESC";
    $stmt = $con->prepare($sql);

    if ($stmt) {
        // TODO: Bind param jika pakai LIMIT OFFSET
        $stmt->execute();
        $result = $stmt->get_result();
        $totalPesanan = $result->num_rows; // Hitung jumlah hasil
        while ($row = $result->fetch_assoc()) {
            $daftarPesanan[] = $row;
        }
        $stmt->close();
    } else {
        $fetch_error = "Gagal menyiapkan query data pesanan: " . $con->error;
        if(function_exists('log_error')) log_error("Admin Pesanan List - Prepare Error: " . $con->error);
        // Jangan set flash message di sini, biarkan navbar yang handle jika koneksi awal gagal
    }
     // Tutup koneksi setelah selesai
     mysqli_close($con);
} else {
    $fetch_error = "Koneksi database tidak valid.";
    // Flash message kemungkinan sudah di-set oleh session.php atau navbar.php
}

$page_title = "Manajemen Pesanan";
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
    <?php // Link ke CSS Admin utama jika ada ?>
    <?php // <link rel="stylesheet" href="assets/css/admin-styles.css"> ?>
     <style>
        body { background-color: #f8f9fa; }

        .status-badge { display: inline-block; padding: 0.35em 0.65em; font-size: .75em; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.375rem; }
        .status-baru { background-color: #0d6efd; } .status-diproses { background-color: #ffc107; color: #000 !important; } .status-dikirim { background-color: #0dcaf0; } .status-selesai { background-color: #198754; } .status-batal { background-color: #dc3545; }
        .table th, .table td { vertical-align: middle; }
        .card-header { background-color: #6c757d; color: white; } 
    </style>
</head>
<body>
    <?php require "navbar.php"; // Include Navbar Horizontal (Navbar akan menampilkan flash message) ?>

    <main class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pesanan</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-receipt me-2"></i>Manajemen Pesanan (<?php echo $totalPesanan; ?>)</h1>
            <a href="pesanan_tambah.php" class="btn btn-success">
                <i class="bi bi-plus-square-fill me-1"></i> Tambah Pesanan Baru
            </a>
        </div>

        <?php // Tampilkan error fetch spesifik jika ada dan belum ada flash message ?>
        <?php if ($fetch_error && !isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($fetch_error); ?></div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-light">Daftar Pesanan Masuk</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Nama Pelanggan</th>
                                <th>Kontak</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarPesanan) && !$fetch_error): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan yang tercatat.</td></tr>
                            <?php else: ?>
                                <?php foreach ($daftarPesanan as $pesanan): ?>
                                <tr>
                                    <td><?php echo $pesanan['id']; ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($pesanan['tanggal_pesanan'])); ?></td>
                                    <td><?php echo htmlspecialchars($pesanan['nama_pelanggan']); ?></td>
                                    <td><?php echo htmlspecialchars($pesanan['kontak_pelanggan'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($pesanan['sumber_pesanan'] ?? '-'); ?></td>
                                    <td>
                                        <?php // Logika badge status (sama seperti sebelumnya)
                                            $status_class = 'secondary'; $status_text = $pesanan['status_pesanan'];
                                            switch (strtolower($status_text)) { case 'baru': $status_class = 'baru'; break; case 'diproses': $status_class = 'diproses'; break; case 'dikirim': $status_class = 'dikirim'; break; case 'selesai': $status_class = 'selesai'; break; case 'batal': $status_class = 'batal'; break; }
                                        ?>
                                        <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="pesanan_detail.php?id=<?php echo $pesanan['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail/Ubah Status">
                                            <i class="bi bi-pencil-square"></i> Detail
                                        </a>
                                        <?php // TODO: Tambahkan tombol hapus jika perlu (pakai form POST & CSRF) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php // Tempat untuk Navigasi Pagination jika diimplementasikan ?>
            </div>
        </div>
    </main>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> Sedaya Furniture</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php // Jika ada JS admin terpusat: <script src="assets/js/admin-scripts.js"></script> ?>
</body>
</html>
<?php
// Tutup koneksi jika masih terbuka
if (isset($con) && $con instanceof mysqli && mysqli_ping($con)) {
    mysqli_close($con);
}
?>