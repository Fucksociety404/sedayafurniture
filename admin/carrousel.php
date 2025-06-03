<?php
// File: admin/carrousel.php (Refactored)

require_once "session.php"; // Memeriksa sesi admin & fungsi helper
require_login();
require_once __DIR__ . '/../config/koneksi.php'; // Koneksi ke database & Konstanta URL Gambar
require_once "page-template.php"; // <--- TAMBAHKAN INI

// --- Pengecekan Konstanta --- (Tetap sama)
if (!defined('MAIN_SITE_IMAGE_URL_BASE') || !defined('PLACEHOLDER_IMAGE_URL')) {
    define('MAIN_SITE_IMAGE_URL_BASE', '../images/'); // Fallback relatif dari admin
    define('PLACEHOLDER_IMAGE_URL', MAIN_SITE_IMAGE_URL_BASE . 'placeholder.png');
}
$fs_target_dir = realpath(__DIR__ . '/../images/carrousel/') . DIRECTORY_SEPARATOR;
//---------------------------

global $con; // Gunakan koneksi global dari koneksi.php

// Inisialisasi pesan (akan ditangani oleh flash message)
// $pesan_sukses = null; // Dihapus
// $pesan_error = null; // Dihapus

// Fungsi generateRandomString (Tetap Sama)
function generateRandomString($length = 10) { /* ... code ... */ }

// --- Proses Tambah Carrousel ---
if (isset($_POST['simpan_carrousel'])) {
    validate_csrf_token($_POST['csrf_token'] ?? ''); // Validasi CSRF

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $nama_file_baru = "";
    $uploadOk = 0;
    $upload_error_message = ''; // Pesan error spesifik upload

    if (isset($_FILES["gambar"]) && $_FILES["gambar"]["error"] === UPLOAD_ERR_OK) {
        $tmp_file = $_FILES["gambar"]["tmp_name"];
        $imageFileType = strtolower(pathinfo(basename($_FILES["gambar"]["name"]), PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $check = @getimagesize($tmp_file);
        if ($check !== false && in_array($imageFileType, $allowed_extensions)) {
            if ($_FILES["gambar"]["size"] <= 5000000) { // Maks 5MB
                if (!is_dir($fs_target_dir)) {
                    if (!mkdir($fs_target_dir, 0775, true)) {
                         $upload_error_message = "Gagal membuat direktori target.";
                         log_error("Carrousel Upload: Gagal mkdir " . $fs_target_dir);
                         $uploadOk = 0;
                    }
                }

                if (is_dir($fs_target_dir) && is_writable($fs_target_dir)) {
                    $nama_file_baru = generateRandomString(20) . '.' . $imageFileType;
                    $target_file_path_absolute = $fs_target_dir . $nama_file_baru;

                    if (move_uploaded_file($tmp_file, $target_file_path_absolute)) {
                        $uploadOk = 1; // Upload file berhasil
                    } else {
                        $upload_error_message = "Terjadi kesalahan saat memindahkan file.";
                        log_error("Carrousel Upload Error - Gagal move_uploaded_file ke: " . $target_file_path_absolute);
                        $uploadOk = 0;
                    }
                } elseif (empty($upload_error_message)) {
                     $upload_error_message = "Direktori target tidak dapat ditulis.";
                     $uploadOk = 0;
                }
            } else {
                $upload_error_message = "Ukuran file gambar terlalu besar (maks 5MB).";
                $uploadOk = 0;
            }
        } else {
            $upload_error_message = "File bukan gambar atau tipe file tidak valid (JPG, JPEG, PNG, GIF, WEBP).";
            $uploadOk = 0;
        }
    } else {
        $php_upload_errors = [ /* ... daftar error ... */ ]; // Anda bisa definisikan array ini
        $error_code = $_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE;
        $upload_error_message = $php_upload_errors[$error_code] ?? "Silakan pilih file gambar.";
        $uploadOk = 0;
    }

    // Lanjutkan ke DB hanya jika upload file berhasil
    if ($uploadOk == 1 && !empty($nama_file_baru)) {
        if (empty($judul)) {
            set_flash_message('error', "Judul carrousel tidak boleh kosong.");
            if (file_exists($target_file_path_absolute)) unlink($target_file_path_absolute); // Hapus file jika validasi gagal
            header("Location: carrousel.php"); exit;
        } else {
            $stmt = $con->prepare("INSERT INTO carrousel (judul, deskripsi, gambar) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $judul, $deskripsi, $nama_file_baru);
                if ($stmt->execute()) {
                    set_flash_message('success', "Item carrousel berhasil ditambahkan.");
                    header("Location: carrousel.php"); exit;
                } else {
                    set_flash_message('error', "Gagal menyimpan data ke database: " . $stmt->error);
                    log_error("Carrousel DB Insert Error: " . $stmt->error);
                    if (file_exists($target_file_path_absolute)) unlink($target_file_path_absolute); // Hapus file jika query gagal
                }
                $stmt->close();
            } else {
                 set_flash_message('error', "Gagal menyiapkan statement database: " . $con->error);
                 log_error("Carrousel Prepare Statement Error (Insert): " . $con->error);
                 if (file_exists($target_file_path_absolute)) unlink($target_file_path_absolute);
            }
        }
    } else {
        // Jika upload gagal, tampilkan pesan error upload
        set_flash_message('error', $upload_error_message ?: "Terjadi kesalahan saat upload gambar.");
    }
    // Redirect kembali ke halaman form jika ada error
    header("Location: carrousel.php");
    exit;
}

// --- Proses Hapus Carrousel --- (Logic tetap sama, menggunakan flash message)
if (isset($_GET['hapus'])) {
    $id_hapus = filter_input(INPUT_GET, 'hapus', FILTER_VALIDATE_INT);
    // Idealnya tambahkan validasi CSRF token di GET request juga jika memungkinkan
    // $csrf_token_get = $_GET['token'] ?? '';
    // validate_csrf_token($csrf_token_get);

    if ($id_hapus) {
        $stmtAmbil = $con->prepare("SELECT gambar FROM carrousel WHERE id=?");
        if ($stmtAmbil) {
            $stmtAmbil->bind_param("i", $id_hapus);
            $stmtAmbil->execute();
            $resultAmbilGambar = $stmtAmbil->get_result();
            $dataGambar = $resultAmbilGambar->fetch_assoc();
            $stmtAmbil->close();

            if ($dataGambar) {
                $nama_file_hapus = $dataGambar['gambar'];
                $path_file_hapus_absolute = $fs_target_dir . $nama_file_hapus;

                $stmtHapus = $con->prepare("DELETE FROM carrousel WHERE id=?");
                if ($stmtHapus) {
                    $stmtHapus->bind_param("i", $id_hapus);
                    if ($stmtHapus->execute() && $stmtHapus->affected_rows > 0) {
                        if (!empty($nama_file_hapus) && file_exists($path_file_hapus_absolute)) {
                            if (!unlink($path_file_hapus_absolute)) {
                                log_error("Gagal menghapus file carrousel: " . $path_file_hapus_absolute);
                                set_flash_message('warning', "Item berhasil dihapus dari DB, tapi gagal hapus file gambar.");
                            } else {
                                set_flash_message('success', "Item carrousel berhasil dihapus beserta gambarnya.");
                            }
                        } else {
                            set_flash_message('success', "Item carrousel berhasil dihapus.");
                        }
                    } else {
                        set_flash_message('error',"Gagal menghapus item carrousel dari database: " . ($stmtHapus->error ?: 'Item tidak ditemukan/sudah dihapus.'));
                         log_error("Gagal execute hapus carrousel ID $id_hapus: " . $stmtHapus->error);
                    }
                    $stmtHapus->close();
                } else {
                     set_flash_message('error',"Gagal menyiapkan statement hapus: " . $con->error);
                     log_error("Gagal prepare statement hapus carrousel: " . $con->error);
                }
            } else {
                set_flash_message('error',"Item carrousel tidak ditemukan.");
            }
        } else {
             set_flash_message('error',"Gagal menyiapkan statement ambil gambar: " . $con->error);
             log_error("Gagal prepare statement ambil gambar carrousel: " . $con->error);
        }
    } else {
        set_flash_message('error', "ID item carrousel tidak valid untuk dihapus.");
    }
    header("Location: carrousel.php"); // Redirect setelah proses hapus (sukses/gagal)
    exit;
}

// Ambil data carrousel dari database untuk ditampilkan
$queryCarrousel = mysqli_query($con, "SELECT id, judul, deskripsi, gambar FROM carrousel ORDER BY id DESC");
if (!$queryCarrousel) {
    log_error("Gagal fetch carrousel data: " . mysqli_error($con));
    $jumlahCarrousel = 0;
    if (!isset($_SESSION['flash_message'])) { // Hanya set jika belum ada flash error lain
        set_flash_message('error', "Gagal memuat data carrousel.");
    }
} else {
    $jumlahCarrousel = mysqli_num_rows($queryCarrousel);
}

// Generate CSRF token untuk form tambah
$csrf_token = generate_csrf_token();

mysqli_close($con); // Tutup koneksi

// --- Mulai Output HTML ---
admin_header('Manajemen Carrousel');
?>
<style>
    .img-thumbnail-small { max-height: 60px; max-width: 120px; height: auto; width: auto; object-fit: contain; }
    .card-header.bg-purple { background-color: #6f42c1 !important; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Carrousel</li>
    </ol>
</nav>

<div class="mb-3">
     <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTambahCarrousel" aria-expanded="false" aria-controls="collapseTambahCarrousel">
        <i class="bi bi-plus-circle-fill me-2"></i>Tambah Item Carrousel
    </button>
</div>

<div class="collapse" id="collapseTambahCarrousel">
    <div class="card shadow mb-4">
        <div class="card-header bg-purple text-white"> <h5 class="m-0"><i class="bi bi-plus-square-fill me-2"></i>Form Tambah Item Carrousel</h5>
        </div>
        <div class="card-body">
            <form action="carrousel.php" method="post" enctype="multipart/form-data">
                 <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label for="judul" class="form-label">Judul <span class="text-danger">*</span></label>
                    <input type="text" id="judul" name="judul" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="gambar" class="form-label">Gambar <span class="text-danger">*</span></label>
                    <input type="file" id="gambar" name="gambar" class="form-control" accept="image/jpeg, image/png, image/gif, image/webp" required>
                    <div class="form-text">Format: JPG, JPEG, PNG, GIF, WEBP. Maks: 5MB.</div>
                </div>
                <button type="submit" name="simpan_carrousel" class="btn btn-primary"><i class="bi bi-save me-2"></i>Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseTambahCarrousel">
                    Batal
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card shadow mt-4">
     <div class="card-header">
        <h5 class="m-0"><i class="bi bi-list-ul me-2"></i>Daftar Item Carrousel (<?php echo $jumlahCarrousel; ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;" class="text-center">No.</th>
                        <th scope="col">Judul</th>
                        <th scope="col">Deskripsi</th>
                        <th scope="col" class="text-center">Gambar</th>
                        <th scope="col" class="text-center" style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($queryCarrousel && $jumlahCarrousel == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Tidak ada data carrousel.</td>
                        </tr>
                    <?php elseif (!$queryCarrousel): ?>
                         <tr>
                            <td colspan="5" class="text-center text-danger">Gagal memuat data.</td>
                        </tr>
                    <?php else: ?>
                        <?php $nomor = 1; ?>
                        <?php while ($data = mysqli_fetch_assoc($queryCarrousel)): ?>
                            <tr>
                                <td class="text-center"><?php echo $nomor++; ?></td>
                                <td><?php echo htmlspecialchars($data['judul']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($data['deskripsi'])); ?></td>
                                <td class="text-center">
                                    <?php
                                    $displayImageUrl = PLACEHOLDER_IMAGE_URL;
                                    $imageName = $data['gambar'];
                                    $altText = "Placeholder Carrousel";
                                    if (!empty($imageName)) {
                                        $imageFileSystemPath = $fs_target_dir . $imageName;
                                        if (file_exists($imageFileSystemPath)) {
                                            // Buat URL relatif dari admin
                                            $displayImageUrl = MAIN_SITE_IMAGE_URL_BASE . 'carrousel/' . $imageName;
                                            $altText = htmlspecialchars($data['judul']);
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($displayImageUrl); ?>?t=<?php echo time(); // Cache busting ?>" alt="<?php echo $altText; ?>" class="img-thumbnail img-thumbnail-small">
                                </td>
                                <td class="text-center">
                                     <?php
                                        // Tambahkan CSRF token ke link hapus jika memungkinkan (atau gunakan form POST kecil)
                                        // $delete_url = "carrousel.php?hapus={$data['id']}&token=" . generate_csrf_token(); // Contoh dengan token GET
                                        $delete_url = "carrousel.php?hapus={$data['id']}"; // Versi sederhana tanpa token GET
                                     ?>
                                    <a href="<?php echo $delete_url; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini? Gambar terkait juga akan dihapus.');" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php mysqli_free_result($queryCarrousel); ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
admin_footer(); // Panggil template footer
?>