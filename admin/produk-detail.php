<?php
// File: admin/produk-detail.php (Refactored)
require_once "session.php";
require_login();
require_once "../config/koneksi.php";
require_once "page-template.php"; // <--- TAMBAHKAN INI

// --- Path & Config ---
// Gunakan konstanta dari koneksi.php jika ada, atau definisikan fallback
if (!defined('MAIN_SITE_IMAGE_URL_BASE')) { define('MAIN_SITE_IMAGE_URL_BASE', '../images/'); } // Base URL relatif ke admin
if (!defined('PLACEHOLDER_IMAGE_URL')) { define('PLACEHOLDER_IMAGE_URL', MAIN_SITE_IMAGE_URL_BASE . 'placeholder.png'); }

define('IMAGE_FS_BASE_PATH', realpath(__DIR__ . '/../images/produk/') . DIRECTORY_SEPARATOR);
define('IMAGE_WEB_PATH_BASE', '../images/produk/'); // Relatif path for web access from admin folder
//----------------------

$produk_id = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
$dataProduk = null; // Inisialisasi data produk
$daftarKategori = [];
$error_message = ''; // Untuk error saat fetch data awal

if (!$produk_id) {
    set_flash_message('error', 'ID Produk tidak valid.');
    header('Location: produk.php');
    exit;
}

// --- Helper Functions --- (Tetap sama)
function generateRandomString($length = 20) { /* ... code ... */ }
function deleteDirectory($dir) { /* ... code ... */ }

// --- Proses Form (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $posted_produk_id = filter_input(INPUT_POST, 'produk_id', FILTER_VALIDATE_INT);

    if (!$posted_produk_id || $posted_produk_id !== $produk_id) {
        set_flash_message('error', 'ID Produk tidak cocok.');
        header('Location: produk.php');
        exit;
    }

    // --- Proses Hapus Produk ---
    if (isset($_POST['hapus'])) {
        // 1. Ambil detail produk (kode, nama file gambar) sebelum hapus DB
        $stmtInfo = $con->prepare("SELECT kode_produk, foto, foto_thumbnail1, foto_thumbnail2, foto_thumbnail3 FROM produk WHERE id = ?");
        // ... (bind, execute, fetch) ...
        $stmtInfo->bind_param("i", $produk_id);
        $stmtInfo->execute();
        $resultInfo = $stmtInfo->get_result();
        $produkInfo = $resultInfo->fetch_assoc();
        $stmtInfo->close();


        if ($produkInfo) {
            // 2. Hapus data dari database
            $stmtDelete = $con->prepare("DELETE FROM produk WHERE id = ?");
             $stmtDelete->bind_param("i", $produk_id);

            if ($stmtDelete->execute() && $stmtDelete->affected_rows > 0) {
                // 3. Hapus folder gambar jika DB delete berhasil
                $folderPath = IMAGE_FS_BASE_PATH . $produkInfo['kode_produk'];
                if (is_dir($folderPath)) {
                    if (!deleteDirectory($folderPath)) {
                         log_error("Gagal menghapus folder produk (ID: $produk_id): " . $folderPath);
                         set_flash_message('warning', 'Produk berhasil dihapus dari database, tetapi gagal menghapus folder gambar terkait.');
                    } else {
                         set_flash_message('success', 'Produk berhasil dihapus beserta gambar terkait.');
                    }
                } else {
                     set_flash_message('success', 'Produk berhasil dihapus (tidak ada folder gambar ditemukan).');
                }
                header('Location: produk.php');
                exit;
            } else {
                log_error("Gagal hapus produk dari DB (ID: $produk_id): " . ($stmtDelete ? $stmtDelete->error : $con->error));
                set_flash_message('error', 'Gagal menghapus produk dari database.');
            }
             if ($stmtDelete) $stmtDelete->close();
        } else {
             set_flash_message('error', 'Produk yang akan dihapus tidak ditemukan.');
        }
         header('Location: produk.php'); // Redirect jika info tidak ditemukan atau gagal delete DB
         exit;
    }
    // --- Proses Update Produk ---
    elseif (isset($_POST['update'])) {
        // Ambil dan sanitasi data dari form
        $nama = trim($_POST['nama'] ?? '');
        $kategori_id = filter_input(INPUT_POST, 'kategori', FILTER_VALIDATE_INT);
        $kodeProduk = trim($_POST['kodeProduk'] ?? ''); // Kode produk sebaiknya tidak bisa diedit
        $harga = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_FLOAT);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $detail = trim($_POST['detail'] ?? '');
        $stok = trim($_POST['stok'] ?? '');
        $status = trim($_POST['status'] ?? 'nonaktif');
        $bahan = trim($_POST['bahan'] ?? '');
        $ukuran = trim($_POST['ukuran'] ?? '');

        // Validasi dasar
        if (empty($nama) || !$kategori_id || $harga === false || $harga < 0 || empty($stok) || !in_array($status, ['aktif', 'nonaktif'])) {
             set_flash_message('error', 'Data tidak lengkap atau tidak valid. Mohon periksa kembali.');
             header('Location: produk-detail.php?p=' . $produk_id);
             exit;
        }

        // Ambil data produk saat ini (terutama nama file foto lama dan kode)
        $stmtCurrent = $con->prepare("SELECT foto, kode_produk FROM produk WHERE id = ?");
        // ... (bind, execute, fetch) ...
        $stmtCurrent->bind_param("i", $produk_id);
        $stmtCurrent->execute();
        $resultCurrent = $stmtCurrent->get_result();
        $currentProduct = $resultCurrent->fetch_assoc();
        $stmtCurrent->close();


        if (!$currentProduct) {
            set_flash_message('error', 'Produk yang akan diupdate tidak ditemukan.');
            header('Location: produk.php');
            exit;
        }

        $foto_lama = $currentProduct['foto'];
        $kode_produk_lama = $currentProduct['kode_produk'];
        if($kodeProduk !== $kode_produk_lama) {
             set_flash_message('error', 'Kode produk tidak boleh diubah.');
             header('Location: produk-detail.php?p=' . $produk_id);
             exit;
        }

        $nama_file_baru = $foto_lama; // Default pakai foto lama
        $uploadError = false;
        $uploadWarning = '';
        $target_dir_produk_fs = IMAGE_FS_BASE_PATH . $kodeProduk . DIRECTORY_SEPARATOR;

        // Proses upload foto baru jika ada file yang dipilih
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Buat direktori jika belum ada
             if (!is_dir($target_dir_produk_fs)) {
                 if (!mkdir($target_dir_produk_fs, 0775, true)) {
                     log_error("Gagal membuat direktori: " . $target_dir_produk_fs);
                     set_flash_message('error', 'Gagal membuat direktori gambar produk.');
                     $uploadError = true;
                 }
             }

            if (!$uploadError) {
                $file = $_FILES['foto'];
                $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
                $max_size = 2 * 1024 * 1024; // 2 MB

                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $new_filename_base = "utama_" . generateRandomString(16);
                $new_filename = $new_filename_base . "." . $file_ext;
                $target_file_path_fs = $target_dir_produk_fs . $new_filename;

                // Validasi
                if ($file['size'] > $max_size) {
                    $uploadWarning = 'Ukuran file foto melebihi batas (Maks 2MB). Foto tidak diubah.';
                    $uploadError = true; // Anggap error jika ukuran > batas
                } elseif (!in_array($file_ext, $allowed_types)) {
                    $uploadWarning = 'Tipe file foto tidak valid (hanya JPG, JPEG, PNG, WEBP). Foto tidak diubah.';
                    $uploadError = true; // Anggap error jika tipe salah
                } else {
                    // Pindahkan file upload
                    if (move_uploaded_file($file['tmp_name'], $target_file_path_fs)) {
                        $nama_file_baru = $new_filename; // Gunakan nama file baru
                        // Hapus foto lama jika upload berhasil dan nama file berbeda
                        if (!empty($foto_lama) && $foto_lama !== $nama_file_baru && file_exists($target_dir_produk_fs . $foto_lama)) {
                            if (!unlink($target_dir_produk_fs . $foto_lama)) {
                                log_error("Gagal hapus foto lama (produk ID: $produk_id): " . $target_dir_produk_fs . $foto_lama);
                                // Opsional: beri tahu user foto lama gagal dihapus
                            }
                        }
                    } else {
                        log_error("Gagal memindahkan file upload (produk ID: $produk_id): " . $target_file_path_fs);
                        set_flash_message('error', 'Gagal mengupload foto baru.');
                        $uploadError = true;
                    }
                }
            }
             // Jika terjadi error saat upload, JANGAN update nama file di DB
             if ($uploadError) {
                 $nama_file_baru = $foto_lama; // Kembalikan ke nama file lama
                 if (!empty($uploadWarning)) {
                      set_flash_message('warning', $uploadWarning); // Tampilkan warning ukuran/tipe
                 }
             }
        } // End if upload file

        // Update data produk di database
        // (Perhatikan urutan field dan tipe data di bind_param)
        $stmtUpdate = $con->prepare("UPDATE produk SET
            kategori_id = ?, nama = ?, harga = ?, foto = ?,
            detail = ?, deskripsi = ?, bahan = ?, ukuran = ?,
            stok = ?, status = ?
            WHERE id = ?");

        if ($stmtUpdate) {
            // Tipe data: i s d s s s s s s s i
            $stmtUpdate->bind_param("isdsssssssi",
                $kategori_id, $nama, $harga, $nama_file_baru, $detail, $deskripsi,
                $bahan, $ukuran, $stok, $status, $produk_id);

            if ($stmtUpdate->execute()) {
                 // Kombinasikan pesan sukses dengan warning upload jika ada
                 $final_message = 'Produk berhasil diperbarui.';
                 $final_type = 'success';
                 if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] == 'warning') {
                    // Jika ada warning upload sebelumnya
                     $final_message .= ' Namun, ' . $_SESSION['flash_message']['message'];
                     $final_type = 'warning'; // Ubah tipe pesan jadi warning
                 }
                 set_flash_message($final_type, $final_message);

                header('Location: produk.php'); // Redirect ke daftar produk
                exit;
            } else {
                log_error("Gagal update produk (ID: $produk_id): " . $stmtUpdate->error);
                set_flash_message('error', 'Gagal memperbarui data produk di database.');
                 // Hapus file baru jika upload tadi berhasil tapi update DB gagal
                 if (!$uploadError && $nama_file_baru !== $foto_lama && file_exists($target_dir_produk_fs . $nama_file_baru)) {
                     unlink($target_dir_produk_fs . $nama_file_baru);
                 }
                 header('Location: produk-detail.php?p=' . $produk_id); // Kembali ke form edit
                 exit;
            }
            $stmtUpdate->close();
        } else {
             log_error("Gagal prepare statement update produk (ID: $produk_id): " . $con->error);
             set_flash_message('error', 'Gagal menyiapkan pembaruan produk.');
              header('Location: produk-detail.php?p=' . $produk_id); // Kembali ke form edit
             exit;
        }

    } // End if update

} // End if POST

// --- Ambil Data Produk untuk Tampilan Form (GET Request) ---
$stmt = $con->prepare("SELECT p.*, k.nama AS nama_kategori
                       FROM produk p
                       LEFT JOIN kategori k ON p.kategori_id = k.id
                       WHERE p.id = ?");
if ($stmt) {
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataProduk = $result->fetch_assoc();
    $stmt->close();

    if (!$dataProduk) {
        set_flash_message('error', 'Produk tidak ditemukan.');
        header('Location: produk.php');
        exit;
    }

    // Ambil daftar kategori lain untuk dropdown
    $current_kategori_id = $dataProduk['kategori_id'];
    $queryKategoriLain = mysqli_query($con, "SELECT id, nama FROM kategori WHERE id != '$current_kategori_id' ORDER BY nama ASC");
    if ($queryKategoriLain) {
        while($kat = mysqli_fetch_assoc($queryKategoriLain)) {
            $daftarKategori[] = $kat;
        }
        mysqli_free_result($queryKategoriLain);
    } else {
        log_error("Gagal ambil daftar kategori lain: " . mysqli_error($con));
    }

} else {
    log_error("Prepare Statement Error (Detail Produk GET): " . $con->error);
    set_flash_message('error', 'Gagal menyiapkan data produk.');
    header('Location: produk.php');
    exit;
}


// --- Tentukan Path Gambar ---
$kodeProduk = $dataProduk['kode_produk'];
$namaFoto = $dataProduk['foto'];
$currentFotoPathWeb = PLACEHOLDER_IMAGE_URL; // Default web path
if (!empty($namaFoto) && !empty($kodeProduk)) {
    $fsPath = IMAGE_FS_BASE_PATH . $kodeProduk . DIRECTORY_SEPARATOR . $namaFoto;
    if (file_exists($fsPath)) {
        // Gunakan IMAGE_WEB_PATH_BASE yang relatif dari admin
        $currentFotoPathWeb = IMAGE_WEB_PATH_BASE . $kodeProduk . '/' . $namaFoto;
    }
}
// Ambil juga gambar thumbnail jika ada
$thumbnailsWeb = [];
$thumbnail_fields = ['foto_thumbnail1', 'foto_thumbnail2', 'foto_thumbnail3'];
foreach ($thumbnail_fields as $field) {
    if (!empty($dataProduk[$field]) && !empty($kodeProduk)) {
         $thumbFileSystemPath = IMAGE_FS_BASE_PATH . $kodeProduk . DIRECTORY_SEPARATOR . $dataProduk[$field];
         if (file_exists($thumbFileSystemPath)) {
             $thumbnailsWeb[] = IMAGE_WEB_PATH_BASE . $kodeProduk . '/' . $dataProduk[$field];
         }
    }
}


$csrf_token = generate_csrf_token();
mysqli_close($con);

// --- Mulai Output HTML ---
admin_header('Edit Produk: ' . htmlspecialchars($dataProduk['nama']));
?>

<style>
    .current-images img { max-width: 120px; /* Sedikit lebih besar */ height: auto; margin-right: 10px; margin-bottom: 10px; border: 1px solid #ddd; padding: 3px; border-radius: 4px; }
    .image-preview { max-width: 180px; max-height: 180px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; display: none; /* Initially hidden */ border-radius: 4px;}
    label { font-weight: 500; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Produk</li>
    </ol>
</nav>

<div class="card shadow-sm">
     <div class="card-header bg-primary text-white">
         <h3 class="h5 mb-0"><i class="bi bi-pencil-fill me-2"></i>Edit Produk: <?php echo htmlspecialchars($dataProduk['nama']); ?></h3>
     </div>
     <div class="card-body">
         <form action="produk-detail.php?p=<?php echo $produk_id; ?>" method="post" enctype="multipart/form-data">
             <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
             <input type="hidden" name="produk_id" value="<?php echo $produk_id; ?>">

             <div class="row">
                 <div class="col-md-8">
                     <div class="mb-3">
                         <label for="nama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                         <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($dataProduk['nama']); ?>" class="form-control" required>
                     </div>

                     <div class="row">
                         <div class="col-md-6 mb-3">
                             <label for="kodeProduk" class="form-label">Kode Produk <span class="text-secondary">(Tidak bisa diubah)</span></label>
                             <input type="text" class="form-control" name="kodeProduk" id="kodeProduk" value="<?php echo htmlspecialchars($dataProduk['kode_produk']); ?>" readonly>
                         </div>
                         <div class="col-md-6 mb-3">
                             <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                             <select name="kategori" id="kategori" class="form-select" required>
                                 <option value="<?php echo $dataProduk['kategori_id']; ?>" selected><?php echo htmlspecialchars($dataProduk['nama_kategori'] ?? 'Pilih...'); ?></option>
                                 <?php foreach ($daftarKategori as $kat): ?>
                                     <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['nama']); ?></option>
                                 <?php endforeach; ?>
                             </select>
                         </div>
                     </div>

                      <div class="row">
                         <div class="col-md-6 mb-3">
                              <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                              <input type="number" class="form-control" name="harga" id="harga" value="<?php echo htmlspecialchars($dataProduk['harga']); ?>" required min="0" step="any">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label for="stok" class="form-label">Status Stok <span class="text-danger">*</span></label>
                               <select name="stok" id="stok" class="form-select" required>
                                   <option value="PreOrder" <?php echo ($dataProduk['stok'] == 'PreOrder') ? 'selected' : ''; ?>>Pre Order</option>
                                   <option value="ReadyStock" <?php echo ($dataProduk['stok'] == 'ReadyStock') ? 'selected' : ''; ?>>Ready Stock</option>
                                   <?php if (!in_array($dataProduk['stok'], ['PreOrder', 'ReadyStock'])): ?>
                                      <option value="<?php echo htmlspecialchars($dataProduk['stok']); ?>" selected><?php echo htmlspecialchars($dataProduk['stok']); ?> (Lainnya)</option>
                                   <?php endif; ?>
                               </select>
                           </div>
                      </div>

                     <div class="mb-3">
                         <label for="deskripsi" class="form-label">Deskripsi Produk</label>
                         <textarea name="deskripsi" id="deskripsi" rows="4" class="form-control"><?php echo htmlspecialchars($dataProduk['deskripsi']); ?></textarea>
                     </div>
                     <div class="mb-3">
                         <label for="detail" class="form-label">Detail Tambahan</label>
                         <textarea name="detail" id="detail" rows="6" class="form-control"><?php echo htmlspecialchars($dataProduk['detail']); ?></textarea>
                     </div>
                     <div class="row">
                          <div class="col-md-6 mb-3">
                              <label for="bahan" class="form-label">Bahan</label>
                              <input type="text" class="form-control" name="bahan" id="bahan" value="<?php echo htmlspecialchars($dataProduk['bahan']); ?>">
                          </div>
                          <div class="col-md-6 mb-3">
                              <label for="ukuran" class="form-label">Ukuran</label>
                              <input type="text" class="form-control" name="ukuran" id="ukuran" value="<?php echo htmlspecialchars($dataProduk['ukuran']); ?>">
                          </div>
                     </div>
                     <div class="mb-3">
                         <label for="status" class="form-label">Status Publikasi <span class="text-danger">*</span></label>
                         <select name="status" id="status" class="form-select" required>
                              <option value="aktif" <?php echo ($dataProduk['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif (Tampil)</option>
                              <option value="nonaktif" <?php echo ($dataProduk['status'] == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif (Tersembunyi)</option>
                         </select>
                     </div>
                 </div>

                 <div class="col-md-4">
                     <div class="mb-3">
                         <label class="form-label">Gambar Produk Saat Ini</label>
                         <div class="current-images border p-2 rounded bg-light text-center">
                              <p class="small text-muted mb-1">Utama:</p>
                             <img src="<?php echo htmlspecialchars($currentFotoPathWeb); ?>?t=<?php echo time(); ?>" alt="Foto Utama Saat Ini" title="Foto Utama Saat Ini" class="mb-2">
                              <?php if (!empty($thumbnailsWeb)): ?>
                                 <p class="small text-muted mb-1 mt-2">Thumbnails:</p>
                                 <?php foreach ($thumbnailsWeb as $thumb): ?>
                                     <img src="<?php echo htmlspecialchars($thumb); ?>?t=<?php echo time(); ?>" alt="Thumbnail Saat Ini" title="Thumbnail Saat Ini">
                                 <?php endforeach; ?>
                              <?php endif; ?>
                              <?php if (empty($namaFoto) && empty($thumbnailsWeb)): ?>
                                   <small class="text-muted d-block">Tidak ada gambar.</small>
                              <?php endif; ?>
                         </div>
                     </div>
                     <div class="mb-3">
                         <label for="foto" class="form-label">Ganti Foto Utama <span class="text-secondary">(Opsional)</span></label>
                         <input type="file" class="form-control" name="foto" id="foto" accept="image/jpeg, image/png, image/webp">
                         <small class="form-text text-muted">Maks 2MB. Kosongkan jika tidak ingin mengganti.</small>
                          <img id="fotoPreview" src="#" alt="Preview Foto Baru" class="image-preview img-fluid rounded mt-2"/>
                     </div>
                 </div>
             </div>

             <hr>
             <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                 <button type="submit" class="btn btn-primary" name="update"><i class="bi bi-save-fill me-1"></i> Simpan Perubahan</button>
                  <a href="produk.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
                 <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                     <i class="bi bi-trash-fill me-1"></i> Hapus Produk Ini
                 </button>
             </div>
         </form>
     </div> </div> <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Konfirmasi Hapus Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus produk "<strong><?php echo htmlspecialchars($dataProduk['nama']); ?></strong>"? <br>
                <strong class="text-danger">Semua data dan gambar terkait produk ini akan dihapus permanen!</strong> Tindakan ini tidak dapat diurungkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="produk-detail.php?p=<?php echo $produk_id; ?>" method="post" style="display: inline;">
                     <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; // Gunakan token yang sama ?>">
                     <input type="hidden" name="produk_id" value="<?php echo $produk_id; ?>">
                    <button type="submit" class="btn btn-danger" name="hapus">Ya, Hapus Produk</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const fotoInput = document.getElementById('foto');
    const fotoPreview = document.getElementById('fotoPreview');

    if (fotoInput && fotoPreview) {
        fotoInput.onchange = evt => {
            const [file] = fotoInput.files;
            if (file) {
                 // Client-side validation (optional, already handled server-side)
                 if (file.size > 2 * 1024 * 1024) { // 2MB
                     alert("Ukuran file melebihi 2MB.");
                     fotoInput.value = ""; // Clear input
                     fotoPreview.src = "#";
                     fotoPreview.style.display = 'none';
                     return;
                 }
                 const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                 if (!allowedTypes.includes(file.type)) {
                     alert("Tipe file tidak valid.");
                      fotoInput.value = "";
                      fotoPreview.src = "#";
                      fotoPreview.style.display = 'none';
                     return;
                 }

                fotoPreview.src = URL.createObjectURL(file);
                fotoPreview.style.display = 'block'; // Tampilkan preview
            } else {
                 fotoPreview.src = '#';
                 fotoPreview.style.display = 'none'; // Sembunyikan jika tidak ada file
            }
        }
    }
</script>

<?php
admin_footer(); // Panggil template footer
?>