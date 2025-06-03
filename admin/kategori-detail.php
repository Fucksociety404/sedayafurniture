<?php
// File: admin/kategori-detail.php
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php';

$kategori_id = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
$data = null;
$error_message = '';

if (!$kategori_id) {
    set_flash_message('error', 'ID Kategori tidak valid.');
    header('Location: kategori.php');
    exit;
}

// Proses Update Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editBTN'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $nama_kategori_baru = trim($_POST['kategori'] ?? '');
    $current_id = filter_input(INPUT_POST, 'kategori_id', FILTER_VALIDATE_INT); // Ambil ID dari hidden input

    if (!$current_id || $current_id !== $kategori_id) {
        set_flash_message('error', 'ID Kategori tidak cocok.');
        header('Location: kategori.php');
        exit;
    }

    if (empty($nama_kategori_baru)) {
        set_flash_message('error', 'Nama kategori tidak boleh kosong.');
        header('Location: kategori-detail.php?p=' . $kategori_id);
        exit;
    }

    // Ambil nama kategori saat ini (untuk perbandingan)
    $stmtCurrent = $con->prepare("SELECT nama FROM kategori WHERE id = ?");
    $stmtCurrent->bind_param("i", $kategori_id);
    $stmtCurrent->execute();
    $resultCurrent = $stmtCurrent->get_result();
    $currentData = $resultCurrent->fetch_assoc();
    $stmtCurrent->close();

    if (!$currentData) {
         set_flash_message('error', 'Kategori tidak ditemukan.');
         header('Location: kategori.php');
         exit;
    }

    // Jika nama tidak berubah, tidak perlu update
    if (strtolower($currentData['nama']) === strtolower($nama_kategori_baru)) {
        set_flash_message('info', 'Tidak ada perubahan pada nama kategori.');
        header('Location: kategori.php');
        exit;
    }

    // Cek apakah nama baru sudah ada (kecuali untuk ID ini sendiri)
    $stmtCheck = $con->prepare("SELECT id FROM kategori WHERE LOWER(nama) = LOWER(?) AND id != ?");
    $stmtCheck->bind_param("si", $nama_kategori_baru, $kategori_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        set_flash_message('warning', 'Nama kategori "' . htmlspecialchars($nama_kategori_baru) . '" sudah digunakan.');
    } else {
        // Update kategori
        $stmtUpdate = $con->prepare("UPDATE kategori SET nama = ? WHERE id = ?");
        $stmtUpdate->bind_param("si", $nama_kategori_baru, $kategori_id);
        if ($stmtUpdate->execute()) {
            set_flash_message('success', 'Kategori berhasil diperbarui menjadi "' . htmlspecialchars($nama_kategori_baru) . '".');
            header('Location: kategori.php');
            exit;
        } else {
            log_error("Gagal update kategori (ID: $kategori_id): " . $stmtUpdate->error);
            set_flash_message('error', 'Gagal memperbarui kategori.');
        }
        $stmtUpdate->close();
    }
    $stmtCheck->close();
    // Redirect kembali ke halaman detail jika cek gagal atau ada warning
    header('Location: kategori-detail.php?p=' . $kategori_id);
    exit;
}

// Proses Hapus Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteBTN'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $current_id = filter_input(INPUT_POST, 'kategori_id', FILTER_VALIDATE_INT);

    if (!$current_id || $current_id !== $kategori_id) {
        set_flash_message('error', 'ID Kategori tidak cocok.');
        header('Location: kategori.php');
        exit;
    }

    // 1. Cek apakah kategori digunakan oleh produk
    $stmtCheckProduk = $con->prepare("SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?");
    $stmtCheckProduk->bind_param("i", $kategori_id);
    $stmtCheckProduk->execute();
    $resultProduk = $stmtCheckProduk->get_result()->fetch_assoc();
    $stmtCheckProduk->close();

    if ($resultProduk['total'] > 0) {
        set_flash_message('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh ' . $resultProduk['total'] . ' produk.');
        header('Location: kategori-detail.php?p=' . $kategori_id);
        exit;
    }

    // 2. Hapus kategori
    $stmtDelete = $con->prepare("DELETE FROM kategori WHERE id = ?");
    $stmtDelete->bind_param("i", $kategori_id);
    if ($stmtDelete->execute() && $stmtDelete->affected_rows > 0) {
        set_flash_message('success', 'Kategori berhasil dihapus.');
        header('Location: kategori.php');
        exit;
    } else {
        log_error("Gagal hapus kategori (ID: $kategori_id): " . $stmtDelete->error);
        set_flash_message('error', 'Gagal menghapus kategori.');
        header('Location: kategori-detail.php?p=' . $kategori_id);
        exit;
    }
    $stmtDelete->close();
}


// Ambil data kategori untuk ditampilkan di form
$stmt = $con->prepare("SELECT id, nama FROM kategori WHERE id = ?");
$stmt->bind_param("i", $kategori_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    set_flash_message('error', 'Kategori tidak ditemukan.');
    header('Location: kategori.php');
    exit;
}

$csrf_token = generate_csrf_token();
mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori - <?php echo htmlspecialchars($data['nama']); ?> | Admin Sedaya</title>
    <link rel="icon" href="../images/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php require "navbar.php"; ?>

    <main class="container mt-4">
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="kategori.php">Kategori</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Kategori</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Kategori</h5>
                    </div>
                    <div class="card-body">
                        <form action="kategori-detail.php?p=<?php echo $kategori_id; ?>" method="post" id="editForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="kategori_id" value="<?php echo $kategori_id; ?>"> <div class="mb-3">
                                <label for="kategori" class="form-label">Nama Kategori</label>
                                <input type="text" name="kategori" id="kategori" class="form-control" value="<?php echo htmlspecialchars($data['nama']); ?>" required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary" name="editBTN"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                    <i class="bi bi-trash-fill me-1"></i> Hapus Kategori
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                 <div class="mt-3">
                    <a href="kategori.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Daftar</a>
                 </div>
            </div>
        </div>

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus kategori "<strong><?php echo htmlspecialchars($data['nama']); ?></strong>"? <br>
                        <small class="text-danger">Tindakan ini tidak dapat diurungkan.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <form action="kategori-detail.php?p=<?php echo $kategori_id; ?>" method="post" style="display: inline;">
                             <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                             <input type="hidden" name="kategori_id" value="<?php echo $kategori_id; ?>">
                            <button type="submit" class="btn btn-danger" name="deleteBTN">Ya, Hapus</button>
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
</body>
</html>