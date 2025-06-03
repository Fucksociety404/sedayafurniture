<?php
// File: admin/galery.php (Refactored)

require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php'; // Koneksi & Konstanta URL
require_once "page-template.php"; // <--- TAMBAHKAN INI

// --- Pengecekan Konstanta --- (Tetap sama)
if (!defined('MAIN_SITE_IMAGE_URL_BASE') || !defined('PLACEHOLDER_IMAGE_URL')) {
    define('MAIN_SITE_IMAGE_URL_BASE', '../images/'); // Fallback relatif dari admin
    define('PLACEHOLDER_IMAGE_URL', MAIN_SITE_IMAGE_URL_BASE . 'placeholder.png');
}
$fs_target_dir = realpath(__DIR__ . '/../images/galery/') . DIRECTORY_SEPARATOR;
//---------------------------

global $con;

// Inisialisasi pesan (ditangani flash message)
// $pesanSukses = null; // Hapus
// $pesanError = null; // Hapus

// Fungsi generateRandomString (Tetap Sama)
function generateRandomString($length = 10) { /* ... code ... */ }

// --- Proses Tambah Item Galeri ---
if (isset($_POST['simpan_galery'])) {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $upload_error_message = ''; // Pesan error spesifik upload

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nama_file = $_FILES['foto']['name'];
        $ukuran_file = $_FILES['foto']['size'];
        $tipe_file = $_FILES['foto']['type'];
        $tmp_file = $_FILES['foto']['tmp_name'];
        $imageFileType = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $image_size = @getimagesize($tmp_file);
        if ($image_size !== false && in_array($imageFileType, $allowed_extensions)) {
            if ($ukuran_file <= 5000000) { // Maks 5MB
                if (!is_dir($fs_target_dir)) {
                    if (!mkdir($fs_target_dir, 0775, true)) {
                         $upload_error_message = "Gagal membuat direktori target.";
                         log_error("Galery Upload: Gagal mkdir " . $fs_target_dir);
                    }
                }
                if (is_dir($fs_target_dir) && is_writable($fs_target_dir)) {
                    $nama_file_baru = generateRandomString(20) . '.' . $imageFileType;
                    $target_file_absolute = $fs_target_dir . $nama_file_baru;
                    if (move_uploaded_file($tmp_file, $target_file_absolute)) {
                        $stmt = mysqli_prepare($con, "INSERT INTO galery (gambar) VALUES (?)");
                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "s", $nama_file_baru);
                            if (mysqli_stmt_execute($stmt)) {
                                set_flash_message('success',"Item galeri berhasil ditambahkan.");
                                header("Location: galery.php"); exit;
                            } else {
                                $upload_error_message = "Gagal menyimpan data ke database: " . mysqli_error($con);
                                log_error("Galery Insert DB Error: " . mysqli_error($con));
                                if (file_exists($target_file_absolute)) unlink($target_file_absolute);
                            }
                            mysqli_stmt_close($stmt);
                        } else {
                             $upload_error_message = "Gagal menyiapkan statement database: " . mysqli_error($con);
                             log_error("Galery Prepare Insert Error: " . mysqli_error($con));
                             if (file_exists($target_file_absolute)) unlink($target_file_absolute);
                        }
                    } else {
                        $upload_error_message = "Gagal memindahkan file yang diupload.";
                        log_error("Galery Upload Error: Gagal move_uploaded_file ke " . $target_file_absolute);
                    }
                } elseif(empty($upload_error_message)) {
                    $upload_error_message = "Direktori target tidak dapat ditulis.";
                }
            } else {
                $upload_error_message = "Ukuran file gambar terlalu besar (maks 5MB).";
            }
        } else {
            $upload_error_message = "File bukan gambar atau tipe file tidak diizinkan (jpg, jpeg, png, gif, webp).";
        }
    } else {
        $php_upload_errors = [ /* ... daftar error PHP ... */ ];
        $error_code = $_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE;
        $upload_error_message = $php_upload_errors[$error_code] ?? "Silakan pilih file gambar.";
    }
     // Set flash message jika ada error upload/db
     if (!empty($upload_error_message)) {
        set_flash_message('error', $upload_error_message);
        header("Location: galery.php"); // Redirect kembali
        exit;
    }
}

// --- Proses Hapus Item Galeri --- (Logic tetap sama, gunakan flash message)
if (isset($_GET['hapus'])) {
    $id_hapus = filter_input(INPUT_GET, 'hapus', FILTER_VALIDATE_INT);
    // Idealnya tambahkan validasi CSRF token di GET request juga

    if ($id_hapus) {
        $stmtAmbil = mysqli_prepare($con, "SELECT gambar FROM galery WHERE id=?");
        if ($stmtAmbil) {
            mysqli_stmt_bind_param($stmtAmbil, "i", $id_hapus);
            mysqli_stmt_execute($stmtAmbil);
            $resultAmbilGambar = mysqli_stmt_get_result($stmtAmbil);
            $dataGambar = mysqli_fetch_assoc($resultAmbilGambar);
            mysqli_stmt_close($stmtAmbil);
            if ($dataGambar) {
                $nama_file_hapus = $dataGambar['gambar'];
                $path_file_hapus_absolute = $fs_target_dir . $nama_file_hapus;
                $stmtHapus = mysqli_prepare($con, "DELETE FROM galery WHERE id=?");
                if ($stmtHapus) {
                    mysqli_stmt_bind_param($stmtHapus, "i", $id_hapus);
                    if (mysqli_stmt_execute($stmtHapus) && mysqli_stmt_affected_rows($stmtHapus) > 0) {
                        if (!empty($nama_file_hapus) && file_exists($path_file_hapus_absolute)) {
                            if (!unlink($path_file_hapus_absolute)) {
                                log_error("Gagal menghapus file galeri: " . $path_file_hapus_absolute);
                                set_flash_message('warning',"Item berhasil dihapus dari DB, namun gagal hapus file gambar.");
                            } else {
                                set_flash_message('success',"Item galeri berhasil dihapus beserta gambarnya.");
                            }
                        } else {
                             set_flash_message('success',"Item galeri berhasil dihapus.");
                        }
                    } else {
                         set_flash_message('error',"Gagal menghapus item galeri dari database: " . ($stmtHapus->error ?: 'Item tidak ditemukan/sudah dihapus.'));
                         log_error("Gagal execute hapus galery ID $id_hapus: " . $stmtHapus->error);
                    }
                    mysqli_stmt_close($stmtHapus);
                } else {
                     set_flash_message('error',"Gagal menyiapkan statement hapus: " . mysqli_error($con));
                     log_error("Galery Prepare Delete Error: " . mysqli_error($con));
                }
            } else {
                set_flash_message('error',"Item galeri tidak ditemukan.");
            }
        } else {
             set_flash_message('error',"Gagal menyiapkan statement ambil gambar: " . mysqli_error($con));
             log_error("Galery Prepare Get Image Error: " . mysqli_error($con));
        }
    } else {
        set_flash_message('error', "ID item galeri tidak valid.");
    }
    header("Location: galery.php"); // Redirect setelah proses
    exit;
}

// Mengambil data galeri untuk ditampilkan
$queryGalery = mysqli_query($con, "SELECT * FROM galery ORDER BY id DESC");
if (!$queryGalery) {
    log_error("Gagal fetch galeri data: " . mysqli_error($con));
    $jumlahGalery = 0;
    if (!isset($_SESSION['flash_message'])) { // Hanya set jika belum ada flash error lain
        set_flash_message('error', "Gagal memuat data galeri.");
    }
} else {
    $jumlahGalery = mysqli_num_rows($queryGalery);
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
mysqli_close($con); // Tutup koneksi

// --- Mulai Output HTML ---
admin_header('Kelola Galeri');
?>

<style>
    /* Style spesifik halaman ini */
    .table img.gallery-thumb { max-width: 100px; max-height: 100px; height: auto; width: auto; object-fit: cover; border-radius: 5px; vertical-align: middle; border: 1px solid #ddd; }
    .card-header.bg-teal { background-color: #20c997 !important; color: white;}
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Galeri</li>
    </ol>
</nav>

<div class="mb-3">
     <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTambahGaleri" aria-expanded="false" aria-controls="collapseTambahGaleri">
        <i class="bi bi-plus-circle-fill me-2"></i>Tambah Item Galeri
    </button>
</div>

<div class="collapse" id="collapseTambahGaleri">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-teal"> <h3 class="mb-0 h5"><i class="bi bi-plus-square-fill me-2"></i> Tambah Item Galeri</h3>
        </div>
        <div class="card-body">
            <form action="galery.php" method="post" enctype="multipart/form-data">
                 <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label for="foto" class="form-label">Pilih Gambar <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg, image/png, image/gif, image/webp" required>
                    <div class="form-text">Tipe file: JPG, JPEG, PNG, GIF, WEBP. Ukuran maks: 5MB.</div>
                </div>
                <button type="submit" class="btn btn-primary" name="simpan_galery"><i class="bi bi-save me-2"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#collapseTambahGaleri">
                        Batal
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="mb-0 h5"><i class="bi bi-images me-2"></i> Daftar Item Galeri (<?php echo $jumlahGalery; ?> item)</h3>
    </div>
    <div class="card-body">
        <?php if ($queryGalery && $jumlahGalery == 0): ?>
            <div class="alert alert-warning" role="alert">Belum ada item galeri. Silakan tambahkan item baru.</div>
        <?php elseif (!$queryGalery): ?>
             <div class="alert alert-danger" role="alert">Gagal memuat daftar galeri.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="text-center" style="width: 5%;">No.</th>
                            <th scope="col" class="text-center">Gambar</th>
                            <th scope="col" class="text-center" style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($data = mysqli_fetch_assoc($queryGalery)):
                            $displayImageUrl = PLACEHOLDER_IMAGE_URL;
                            $imageName = $data['gambar'];
                            $altText = "Placeholder Galeri";
                            if (!empty($imageName)) {
                                $imageFileSystemPath = $fs_target_dir . $imageName;
                                if (file_exists($imageFileSystemPath)) {
                                    $displayImageUrl = MAIN_SITE_IMAGE_URL_BASE . 'galery/' . $imageName; // Path relatif dari admin
                                    $altText = "Galeri Item " . htmlspecialchars($data['id']);
                                }
                            }
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="text-center">
                                    <img src="<?php echo htmlspecialchars($displayImageUrl); ?>?t=<?php echo time(); // Cache busting ?>" alt="<?php echo $altText; ?>" class="gallery-thumb">
                                </td>
                                <td class="text-center">
                                     <?php
                                        // Idealnya gunakan form POST atau token GET untuk hapus
                                        $delete_url = "galery.php?hapus={$data['id']}";
                                     ?>
                                    <a href="<?php echo $delete_url; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini? Gambar yang terkait juga akan dihapus.');">
                                        <i class="bi bi-trash-fill"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                        mysqli_free_result($queryGalery); // Bebaskan memori
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
admin_footer(); // Panggil template footer
?>