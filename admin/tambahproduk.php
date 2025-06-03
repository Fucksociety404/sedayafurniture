<?php
// File: admin/tambahproduk.php (Refactored)
require_once "session.php";
require_login();
require_once "../config/koneksi.php";
require_once "page-template.php"; // <--- Tambahkan ini

global $con;

$queryKategori = mysqli_query($con, "SELECT * FROM kategori");

// --- Fungsi Helper --- (Tetap sama)
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        try {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        } catch (Exception $e) {
             // Fallback for environments where random_int is not available
             $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
    }
    return $randomString;
}

function generateRandomSedayaFurnitureCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
         try {
             $randomString .= $characters[random_int(0, $charactersLength - 1)];
         } catch (Exception $e) {
              $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
         }
    }
    return 'SF-' . strtoupper($randomString);
}

$kodeProdukOtomatis = generateRandomSedayaFurnitureCode();

// --- Path & Directory Handling ---
// Gunakan __DIR__ untuk path absolut
$main_product_dir_fs = realpath(__DIR__ . '/../images/produk/');
if (!$main_product_dir_fs) {
    log_error("Direktori produk utama tidak valid: " . __DIR__ . '/../images/produk/');
    // Handle error, mungkin dengan pesan flash atau die()
    set_flash_message('error', 'Konfigurasi direktori gambar produk bermasalah.');
    // Mungkin redirect atau hentikan eksekusi jika fatal
    // header('Location: produk.php'); exit;
} else {
    $main_product_dir_fs .= DIRECTORY_SEPARATOR;
    // Buat direktori utama jika belum ada
    if (!is_dir($main_product_dir_fs)) {
        if (!mkdir($main_product_dir_fs, 0775, true)) {
            log_error("Gagal membuat direktori produk utama: " . $main_product_dir_fs);
            set_flash_message('error', 'Gagal menyiapkan direktori gambar produk.');
            // header('Location: produk.php'); exit;
        }
    }
}

// --- Fungsi Upload File --- (Tetap sama, tapi gunakan $main_product_dir_fs)
function uploadFile($file, $target_subdir, $max_size = 1000000, $file_type = 'utama') {
    global $main_product_dir_fs; // Gunakan path FS absolut

    if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) {
        return ''; // No file uploaded
    }
    if ($file['error'] != UPLOAD_ERR_OK) {
        log_error("File upload error: Code " . $file['error'] . " for file " . $file['name']);
        // Tampilkan pesan yang lebih user-friendly
        $upload_errors = [ /* ... daftar error PHP ... */ ];
        set_flash_message('error', 'Upload Error (' . $file_type . '): ' . ($upload_errors[$file['error']] ?? 'Unknown error'));
        return 'error_upload';
    }
    if ($file['size'] > $max_size) {
        return 'error_size';
    }

    $allowed_types = ['jpg', 'jpeg', 'png', 'webp']; // Tambahkan webp jika didukung
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return 'error_type';
    }

    // Buat subdirektori produk jika belum ada
    $product_specific_dir = $main_product_dir_fs . $target_subdir . DIRECTORY_SEPARATOR;
    if (!is_dir($product_specific_dir)) {
        if (!mkdir($product_specific_dir, 0775, true)) {
            log_error("Gagal membuat direktori spesifik produk: " . $product_specific_dir);
            return 'error_mkdir'; // Error baru untuk gagal buat sub-direktori
        }
    }

    $random_name = generateRandomString(20);
    $new_name = $file_type . "_" . $random_name . "." . $file_ext; // Prefix nama file
    $target_file_absolute = $product_specific_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_file_absolute)) {
        return $new_name; // Kembalikan nama file saja
    } else {
        log_error("Failed to move uploaded file: " . $file['tmp_name'] . " to " . $target_file_absolute);
        return 'error_move';
    }
}


// --- Proses Form Submit ---
$alertMessage = ''; // Tidak digunakan lagi, diganti flash message
$alertType = '';   // Tidak digunakan lagi

if (isset($_POST['simpan'])) {
    validate_csrf_token($_POST['csrf_token'] ?? ''); // Validasi CSRF

    $nama = trim(htmlspecialchars($_POST['nama'] ?? ''));
    $kategori = filter_input(INPUT_POST, 'kategori', FILTER_VALIDATE_INT);
    $detail = trim(htmlspecialchars($_POST['detail'] ?? ''));
    $deskripsi = trim(htmlspecialchars($_POST['deskripsi'] ?? ''));
    $kodeProduk = trim(htmlspecialchars($_POST['kodeProduk'] ?? '')); // Sebaiknya readonly, tapi ambil jika terkirim
    $harga = filter_input(INPUT_POST, 'harga', FILTER_VALIDATE_FLOAT);
    $stok = trim(htmlspecialchars($_POST['stok'] ?? ''));
    $status = trim(htmlspecialchars($_POST['status'] ?? ''));
    $bahan = trim(htmlspecialchars($_POST['bahan'] ?? ''));
    $ukuran = trim(htmlspecialchars($_POST['ukuran'] ?? ''));

    // Ambil kode produk otomatis jika POST kosong (seharusnya tidak terjadi jika readonly)
    if (empty($kodeProduk)) {
        $kodeProduk = $kodeProdukOtomatis;
    }

    // Validasi Input Lebih Ketat
    $errors = [];
    if (empty($nama)) { $errors[] = "Nama produk wajib diisi."; }
    if (empty($kategori)) { $errors[] = "Kategori wajib dipilih."; }
    if ($harga === false || $harga < 0) { $errors[] = "Harga tidak valid."; }
    if (empty($deskripsi)) { $errors[] = "Deskripsi produk wajib diisi."; }
    if (empty($stok)) { $errors[] = "Status stok wajib dipilih."; }
    if (empty($status) || !in_array($status, ['aktif', 'nonaktif'])) { $errors[] = "Status produk tidak valid."; }
    if (empty($_FILES['fotoUtama']['name']) || $_FILES['fotoUtama']['error'] == UPLOAD_ERR_NO_FILE) {
         $errors[] = "Foto Utama Produk wajib diunggah.";
    }

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
        // Simpan input ke session untuk repopulate (kecuali file)
        $_SESSION['form_input_produk'] = $_POST;
        header('Location: tambahproduk.php');
        exit;
    }

    // --- Proses Upload Files ---
    $upload_results = [];
    $upload_has_critical_error = false;

    // Upload Foto Utama (Required)
    $foto_utama_result = uploadFile($_FILES['fotoUtama'], $kodeProduk, 1000000, 'utama');
    if (strpos($foto_utama_result, 'error_') === 0) {
        // Handle specific upload errors for foto utama
        $error_map = [
            'error_upload' => 'Terjadi kesalahan teknis saat mengunggah Foto Utama.',
            'error_size' => 'Ukuran Foto Utama melebihi batas 1MB.',
            'error_type' => 'Tipe file Foto Utama tidak valid (hanya .jpg, .jpeg, .png, .webp).',
            'error_mkdir' => 'Gagal membuat direktori untuk Foto Utama.',
            'error_move' => 'Gagal memindahkan file Foto Utama ke direktori tujuan.'
        ];
        set_flash_message('error', $error_map[$foto_utama_result] ?? 'Kesalahan upload Foto Utama tidak diketahui.');
        $upload_has_critical_error = true;
    } else {
        $upload_results['foto'] = $foto_utama_result; // Nama file utama
    }

    // Upload Thumbnails (Optional) - Hanya jika foto utama berhasil
    if (!$upload_has_critical_error) {
        $thumb_fields = ['fotoThumbnail1', 'fotoThumbnail2', 'fotoThumbnail3'];
        $thumb_db_fields = ['foto_thumbnail1', 'foto_thumbnail2', 'foto_thumbnail3'];
        $thumbnail_warning = '';

        for ($i = 0; $i < count($thumb_fields); $i++) {
             $field_name = $thumb_fields[$i];
             $db_field = $thumb_db_fields[$i];
             if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                 $thumb_result = uploadFile($_FILES[$field_name], $kodeProduk, 1000000, 'thumb' . ($i + 1));
                 if (strpos($thumb_result, 'error_') === 0 && $thumb_result !== 'error_upload') { // Abaikan UPLOAD_ERR_NO_FILE
                     // Catat warning tapi jangan hentikan proses
                      $thumbnail_warning .= "Gagal upload Thumbnail " . ($i + 1) . " (" . $thumb_result . "). ";
                      $upload_results[$db_field] = ''; // Kosongkan jika gagal
                 } elseif ($thumb_result !== 'error_upload') { // Hanya simpan jika berhasil atau tidak diupload
                      $upload_results[$db_field] = $thumb_result;
                 } else {
                     $upload_results[$db_field] = ''; // Kosongkan jika error upload umum
                 }
             } else {
                  $upload_results[$db_field] = ''; // Tidak ada file diupload
             }
        }
        if (!empty($thumbnail_warning)) {
            set_flash_message('warning', trim($thumbnail_warning)); // Tampilkan warning thumbnail jika ada
        }
    }


    // --- Insert ke Database ---
    if (!$upload_has_critical_error) {
        // Query insert to product table
        $sql = "INSERT INTO produk (kategori_id, kode_produk, nama, harga, foto, detail, deskripsi, bahan, ukuran, stok, status, foto_thumbnail1, foto_thumbnail2, foto_thumbnail3, tanggal_ditambah)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $con->prepare($sql);
        if ($stmt) {
            // Sesuaikan tipe data: i s s d s s s s s s s s s
            $stmt->bind_param("issdsssssssss",
                $kategori,
                $kodeProduk,
                $nama,
                $harga,
                $upload_results['foto'], // Nama file foto utama
                $detail,
                $deskripsi,
                $bahan,
                $ukuran,
                $stok,
                $status,
                $upload_results['foto_thumbnail1'], // Nama file thumb 1
                $upload_results['foto_thumbnail2'], // Nama file thumb 2
                $upload_results['foto_thumbnail3']  // Nama file thumb 3
            );

            if ($stmt->execute()) {
                $success_msg = "Produk berhasil ditambahkan.";
                if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'warning') {
                    // Jika ada warning thumbnail sebelumnya, tambahkan pesan sukses
                    $success_msg .= " " . $_SESSION['flash_message']['message'];
                     set_flash_message('warning', $success_msg); // Tampilkan sebagai warning gabungan
                } else {
                    set_flash_message('success', $success_msg);
                }
                unset($_SESSION['form_input_produk']); // Hapus input tersimpan
                header('Location: produk.php'); // Redirect ke daftar produk
                exit;
            } else {
                log_error("Gagal insert produk: " . $stmt->error . " | Kode: " . $kodeProduk);
                set_flash_message('error', "Terjadi kesalahan saat menyimpan data produk ke database.");
                // Hapus file yang sudah terlanjur diupload jika DB insert gagal
                $product_specific_dir = $main_product_dir_fs . $kodeProduk . DIRECTORY_SEPARATOR;
                if(isset($upload_results['foto']) && !empty($upload_results['foto']) && file_exists($product_specific_dir . $upload_results['foto'])) unlink($product_specific_dir . $upload_results['foto']);
                if(isset($upload_results['foto_thumbnail1']) && !empty($upload_results['foto_thumbnail1']) && file_exists($product_specific_dir . $upload_results['foto_thumbnail1'])) unlink($product_specific_dir . $upload_results['foto_thumbnail1']);
                if(isset($upload_results['foto_thumbnail2']) && !empty($upload_results['foto_thumbnail2']) && file_exists($product_specific_dir . $upload_results['foto_thumbnail2'])) unlink($product_specific_dir . $upload_results['foto_thumbnail2']);
                if(isset($upload_results['foto_thumbnail3']) && !empty($upload_results['foto_thumbnail3']) && file_exists($product_specific_dir . $upload_results['foto_thumbnail3'])) unlink($product_specific_dir . $upload_results['foto_thumbnail3']);

                $_SESSION['form_input_produk'] = $_POST; // Simpan lagi input form
                 header('Location: tambahproduk.php');
                 exit;
            }
            $stmt->close();
        } else {
            log_error("Gagal prepare statement insert produk: " . $con->error);
            set_flash_message('error', "Gagal menyiapkan penyimpanan data produk.");
             $_SESSION['form_input_produk'] = $_POST;
            header('Location: tambahproduk.php');
            exit;
        }
    } else {
         // Jika ada error kritis saat upload, redirect kembali
         $_SESSION['form_input_produk'] = $_POST;
         header('Location: tambahproduk.php');
         exit;
    }
}

// Ambil input form sebelumnya jika ada (setelah redirect karena error)
$form_input = $_SESSION['form_input_produk'] ?? [];
unset($_SESSION['form_input_produk']); // Hapus setelah diambil

$csrf_token = generate_csrf_token(); // Generate CSRF token untuk form

mysqli_close($con);

// --- Mulai Output HTML ---
admin_header('Tambah Produk Baru');
?>

<style>
    .thumbnail-container { display: flex; gap: 20px; flex-wrap: wrap; }
    .thumbnail-item { flex: 1; min-width: 200px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; }
    .preview-container { margin-top: 10px; text-align: center; }
    .preview-container img { max-width: 150px; /* Perkecil preview */ height: auto; border-radius: 4px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); display: none; /* Default hidden */ margin: auto; }
    .form-label { font-weight: 500; color: #495057; }
    .required-field::after { content: " *"; color: red; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tambah Produk</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8"> <div class="card shadow-sm">
            <div class="card-header bg-success text-white"> <h3 class="mb-0 h5"><i class="bi bi-plus-square me-2"></i> Tambah Produk Baru</h3>
            </div>
            <div class="card-body">
                <form id="formTambahProduk" action="tambahproduk.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="nama" class="form-label required-field">Nama Produk</label>
                        <input type="text" name="nama" id="nama" class="form-control" required value="<?php echo htmlspecialchars($form_input['nama'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kodeProduk" class="form-label required-field">Kode Produk</label>
                             <input type="text" name="kodeProduk" id="kodeProduk" class="form-control" required value="<?php echo $kodeProdukOtomatis ?>" readonly>
                             <small class="text-muted">Kode produk dibuat otomatis.</small>
                         </div>
                        <div class="col-md-6 mb-3">
                            <label for="kategori" class="form-label required-field">Kategori</label>
                            <select name="kategori" id="kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php
                                $selectedKategori = $form_input['kategori'] ?? '';
                                mysqli_data_seek($queryKategori, 0); // Reset pointer
                                while ($data = mysqli_fetch_array($queryKategori)) {
                                    $selected = ($data['id'] == $selectedKategori) ? 'selected' : '';
                                    echo "<option value='{$data['id']}' {$selected}>" . htmlspecialchars($data['nama']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="harga" class="form-label required-field">Harga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="harga" id="harga" required value="<?php echo htmlspecialchars($form_input['harga'] ?? ''); ?>" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fotoUtama" class="form-label required-field">Foto Utama Produk (Maks. 1MB, JPG/JPEG/PNG/WEBP)</label>
                        <div class="thumbnail-item"> <input type="file" class="form-control" name="fotoUtama" id="fotoUtama" accept="image/jpeg, image/png, image/jpg, image/webp" required onchange="previewImage(this, 'previewUtama')">
                            <div class="preview-container">
                                <img id="previewUtama" src="#" alt="Preview Foto Utama">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Thumbnail Produk (Opsional, Maks. 1MB per foto)</label>
                        <div class="thumbnail-container">
                            <div class="thumbnail-item">
                                <label for="fotoThumbnail1" class="form-label small">Thumbnail 1</label>
                                <input type="file" class="form-control form-control-sm" name="fotoThumbnail1" id="fotoThumbnail1" accept="image/jpeg, image/png, image/jpg, image/webp" onchange="previewImage(this, 'previewThumbnail1')">
                                <div class="preview-container">
                                    <img id="previewThumbnail1" src="#" alt="Preview Thumbnail 1">
                                </div>
                            </div>
                            <div class="thumbnail-item">
                                <label for="fotoThumbnail2" class="form-label small">Thumbnail 2</label>
                                <input type="file" class="form-control form-control-sm" name="fotoThumbnail2" id="fotoThumbnail2" accept="image/jpeg, image/png, image/jpg, image/webp" onchange="previewImage(this, 'previewThumbnail2')">
                                <div class="preview-container">
                                    <img id="previewThumbnail2" src="#" alt="Preview Thumbnail 2">
                                </div>
                            </div>
                            <div class="thumbnail-item">
                                <label for="fotoThumbnail3" class="form-label small">Thumbnail 3</label>
                                <input type="file" class="form-control form-control-sm" name="fotoThumbnail3" id="fotoThumbnail3" accept="image/jpeg, image/png, image/jpg, image/webp" onchange="previewImage(this, 'previewThumbnail3')">
                                <div class="preview-container">
                                    <img id="previewThumbnail3" src="#" alt="Preview Thumbnail 3">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label required-field">Deskripsi Produk</label>
                        <textarea name="deskripsi" id="deskripsi" rows="4" class="form-control" required><?php echo htmlspecialchars($form_input['deskripsi'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="detail" class="form-label">Detail Tambahan (Opsional)</label>
                        <textarea name="detail" id="detail" rows="5" class="form-control"><?php echo htmlspecialchars($form_input['detail'] ?? ''); ?></textarea>
                        <small class="text-muted">Gunakan tanda * untuk membuat list item (Contoh: * Item 1)</small>
                    </div>

                     <div class="row">
                         <div class="col-md-6 mb-3">
                             <label for="bahan" class="form-label">Bahan (Opsional)</label>
                             <input type="text" class="form-control" name="bahan" id="bahan" value="<?php echo htmlspecialchars($form_input['bahan'] ?? ''); ?>">
                         </div>
                         <div class="col-md-6 mb-3">
                             <label for="ukuran" class="form-label">Ukuran (Opsional)</label>
                             <input type="text" class="form-control" name="ukuran" id="ukuran" value="<?php echo htmlspecialchars($form_input['ukuran'] ?? ''); ?>">
                         </div>
                    </div>


                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stok" class="form-label required-field">Status Stok</label>
                            <select name="stok" id="stok" class="form-select" required>
                                <option value="">-- Pilih Status Stok --</option>
                                <option value="PreOrder" <?php echo (($form_input['stok'] ?? '') == 'PreOrder') ? 'selected' : ''; ?>>Pre Order</option>
                                <option value="ReadyStock" <?php echo (($form_input['stok'] ?? '') == 'ReadyStock') ? 'selected' : ''; ?>>Ready Stock</option>
                            </select>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="status" class="form-label required-field">Status Produk</label>
                            <select name="status" id="status" class="form-select" required>
                                 <option value="">-- Pilih Status Produk --</option>
                                <option value="aktif" <?php echo (($form_input['status'] ?? '') == 'aktif') ? 'selected' : ''; ?>>Aktif (Tampil)</option>
                                <option value="nonaktif" <?php echo (($form_input['status'] ?? '') == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif (Tersembunyi)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" class="btn btn-primary" name="simpan"><i class="bi bi-save me-2"></i> Simpan Produk</button>
                         <a href="produk.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) return; // Handle jika elemen preview tidak ditemukan

        if (input.files && input.files[0]) {
            // Validasi Ukuran (Client-side)
            if (input.files[0].size > 1000000) { // 1MB limit
                alert("Ukuran file melebihi 1MB.");
                input.value = ''; // Hapus file dari input
                preview.src = '#';
                preview.style.display = 'none';
                return;
            }
            // Validasi Tipe (Client-side)
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(input.files[0].type)) {
                alert("Tipe file tidak valid. Hanya JPG, JPEG, PNG, WEBP yang diizinkan.");
                input.value = '';
                preview.src = '#';
                preview.style.display = 'none';
                return;
            }

            // Tampilkan preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block'; // Tampilkan gambar
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            // Jika tidak ada file dipilih (misal dibatalkan)
            preview.src = '#';
            preview.style.display = 'none';
        }
    }
</script>

<?php
admin_footer(); // Panggil template footer
?>