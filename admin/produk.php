<?php
// File: admin/produk.php (Refactored)
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php'; // Koneksi & Konstanta URL Gambar

// --- Pengecekan Konstanta URL (Opsional fallback) ---
if (!defined('MAIN_SITE_IMAGE_URL_BASE')) { define('MAIN_SITE_IMAGE_URL_BASE', 'https://sedayafurniture.com/images/'); } // Use actual base URL
if (!defined('PLACEHOLDER_IMAGE_URL')) { define('PLACEHOLDER_IMAGE_URL', MAIN_SITE_IMAGE_URL_BASE . 'placeholder.png'); } // Placeholder relative to base

// --- Path Filesystem (FS) ---
// Pastikan path ini benar relatif terhadap file produk.php
$image_fs_base_produk = realpath(__DIR__ . '/../images/produk/');
if (!$image_fs_base_produk) {
    // Fallback atau log error jika path tidak valid
    log_error("Direktori base gambar produk tidak ditemukan atau tidak valid: " . __DIR__ . '/../images/produk/');
    // Definisikan fallback agar tidak error di bawah, meskipun gambar mungkin tidak tampil
    define('IMAGE_FS_BASE_PATH', __DIR__ . '/../images/produk/');
} else {
     define('IMAGE_FS_BASE_PATH', $image_fs_base_produk . DIRECTORY_SEPARATOR);
}


// --- Include utils jika perlu ---
// session.php biasanya sudah include utils jika diperlukan untuk log_error, set_flash_message
// require_once __DIR__ . '/../includes/utils.php';

global $con;

// --- Ambil Parameter Filter, Pencarian, Limit, dan Halaman ---
$kategori_id_filter = filter_input(INPUT_GET, 'kategori', FILTER_VALIDATE_INT);
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS);
$keyword = $keyword ? trim($keyword) : '';

// Validasi status filter
$valid_status = ['aktif', 'nonaktif'];
if (!empty($status_filter) && !in_array(strtolower($status_filter), $valid_status)) {
    $status_filter = null; // Reset jika tidak valid
} elseif(!empty($status_filter)) {
    $status_filter = strtolower($status_filter);
}

// Pengaturan Pagination & Limit
$allowed_limits = [10, 20, 30, 40, 50];
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
if (!$limit || !in_array($limit, $allowed_limits)) {
    $limit = 10; // Default limit
}

$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$currentPage || $currentPage < 1) {
    $currentPage = 1; // Default halaman 1
}
// ---------------------------------------------------------

// --- Handle Ubah Status Produk ---
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['p'])) {
    $idProduk = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
    validate_csrf_token($_GET['csrf_token'] ?? '', true); // Validasi CSRF dari URL (GET request)

    // Buat URL redirect dengan mempertahankan SEMUA filter dan pagination saat ini
    $redirectUrl = "produk.php?";
    $queryParams = [];
    if (!empty($kategori_id_filter)) $queryParams['kategori'] = $kategori_id_filter;
    if (!empty($status_filter)) $queryParams['status'] = $status_filter;
    if (!empty($keyword)) $queryParams['keyword'] = $keyword;
    if ($limit != 10) $queryParams['limit'] = $limit;
    if ($currentPage > 1) $queryParams['page'] = $currentPage;
    $redirectUrl .= http_build_query($queryParams);

    if (!$idProduk || $idProduk <= 0) {
        set_flash_message('error', 'ID Produk tidak valid.');
        header("Location: " . $redirectUrl); exit;
    }
    if (!$con instanceof mysqli) {
        set_flash_message('error', 'Koneksi database bermasalah.');
        header("Location: " . $redirectUrl); exit;
    }

    // Ambil status saat ini
    $stmt_status = $con->prepare("SELECT status FROM produk WHERE id = ?");
    $statusBaru = null;
    if ($stmt_status) {
        $stmt_status->bind_param("i", $idProduk);
        $stmt_status->execute();
        $resultStatus = $stmt_status->get_result();
        $produkData = $resultStatus->fetch_assoc();
        if ($produkData) {
            $statusBaru = (strtolower($produkData['status']) === 'aktif') ? 'nonaktif' : 'aktif';
        } else {
             set_flash_message('error', 'Produk tidak ditemukan.');
             header("Location: " . $redirectUrl); exit;
        }
        $stmt_status->close();
    } else {
        log_error("Gagal prepare statement get status produk (ID: $idProduk): " . $con->error);
        set_flash_message('error', 'Gagal memeriksa status produk.');
        header("Location: " . $redirectUrl); exit;
    }

    // Update status
    $stmt_update = $con->prepare("UPDATE produk SET status = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $statusBaru, $idProduk);
        if ($stmt_update->execute()) {
            set_flash_message('success', 'Status produk berhasil diubah menjadi ' . ucfirst($statusBaru) . '.');
        } else {
            log_error("Gagal update status produk (ID: $idProduk): " . $stmt_update->error);
            set_flash_message('error', 'Gagal mengubah status produk.');
        }
        $stmt_update->close();
    } else {
        log_error("Gagal prepare statement update status produk (ID: $idProduk): " . $con->error);
        set_flash_message('error', 'Gagal menyiapkan perubahan status produk.');
    }

    header("Location: " . $redirectUrl); // Redirect setelah proses
    exit;
}

// --- Ambil Daftar Kategori ---
$daftarKategori = [];
if ($con instanceof mysqli) {
     $queryKategoriList = mysqli_query($con, "SELECT id, nama FROM kategori ORDER BY nama");
     if ($queryKategoriList) {
         while ($kategori = mysqli_fetch_assoc($queryKategoriList)) { $daftarKategori[] = $kategori; }
         mysqli_free_result($queryKategoriList);
     } else {
         log_error("Admin Produk - Gagal fetch kategori: " . mysqli_error($con));
         // Jangan set flash message error di sini karena mungkin sudah ada dari toggle status
     }
}

// --- Query untuk Menghitung TOTAL Produk (dengan filter) ---
$totalProduk = 0;
$totalPages = 0;
$offset = 0; // Inisialisasi offset
$error_fetch = null; // Error spesifik untuk fetch

if ($con instanceof mysqli) {
    $sql_count_base = "FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id"; // Bagian FROM dan JOIN
    $sql_count = "SELECT COUNT(p.id) as total " . $sql_count_base;
    $conditions = []; $params_count = []; $types_count = "";

    // Tambahkan filter ke WHERE clause
    if (!empty($status_filter)) { $conditions[] = "LOWER(p.status) = LOWER(?)"; $params_count[] = $status_filter; $types_count .= "s"; }
    if (!empty($kategori_id_filter)) {
        $is_kategori_valid = false; // Validasi ID kategori
        foreach ($daftarKategori as $kat) { if ($kat['id'] == $kategori_id_filter) { $is_kategori_valid = true; break; } }
        if ($is_kategori_valid) {
            $conditions[] = "p.kategori_id = ?"; $params_count[] = $kategori_id_filter; $types_count .= "i";
        } else {
             $kategori_id_filter = null; // Reset jika tidak valid
             set_flash_message('warning','Filter kategori tidak valid atau tidak ditemukan.');
        }
    }
    if (!empty($keyword)) {
        $conditions[] = "(p.nama LIKE ? OR p.kode_produk LIKE ? OR k.nama LIKE ?)";
        $keyword_param = "%" . $keyword . "%";
        $params_count[] = $keyword_param; $params_count[] = $keyword_param; $params_count[] = $keyword_param; $types_count .= "sss";
    }
    if (!empty($conditions)) { $sql_count .= " WHERE " . implode(" AND ", $conditions); }

    // Eksekusi query count
    $stmt_count = $con->prepare($sql_count);
    if (!$stmt_count) {
         $error_fetch = "Gagal menyiapkan penghitungan produk: " . $con->error;
         log_error("Prepare Statement Error (Count Produk): " . $con->error);
         set_flash_message('error', $error_fetch);
    } else {
        if (!empty($params_count)) { $stmt_count->bind_param($types_count, ...$params_count); }
        if (!$stmt_count->execute()) {
            $error_fetch = "Gagal mengeksekusi penghitungan produk: " . $stmt_count->error;
            log_error("Execute Error (Count Produk): " . $stmt_count->error);
            set_flash_message('error', $error_fetch);
        } else {
            $result_count = $stmt_count->get_result();
            $totalProduk = $result_count->fetch_assoc()['total'] ?? 0;
            $totalPages = $limit > 0 ? ceil($totalProduk / $limit) : 0; // Hitung total halaman
        }
        $stmt_count->close();

        // Validasi ulang currentPage jika melebihi total halaman (setelah total dihitung)
        if ($currentPage > $totalPages && $totalPages > 0) { $currentPage = $totalPages; }
        elseif ($currentPage < 1) { $currentPage = 1; }
        $offset = ($currentPage - 1) * $limit; // Hitung offset berdasarkan currentPage yg valid
    }
} else {
    $error_fetch = 'Koneksi database bermasalah.';
    // Flash message mungkin sudah di set oleh toggle status atau session.php
    if (!isset($_SESSION['flash_message'])) { set_flash_message('error', $error_fetch); }
}


// --- Query Utama Mengambil Produk Halaman Ini (Dengan Filter, Limit, Offset) ---
$daftarProduk = [];
if ($con instanceof mysqli && $totalProduk > 0 && !$error_fetch) { // Hanya query jika ada produk & koneksi OK & tidak ada error sebelumnya
    $sql = "SELECT p.id, p.nama, p.kode_produk, p.harga, p.stok, p.status, p.foto, k.nama AS nama_kategori " . $sql_count_base; // Gunakan FROM JOIN yang sama
    // Kondisi WHERE sama dengan query count
    if (!empty($conditions)) { $sql .= " WHERE " . implode(" AND ", $conditions); }
    // Tambahkan ORDER BY, LIMIT, OFFSET
    $sql .= " ORDER BY p.nama ASC LIMIT ? OFFSET ?";

    // Siapkan parameter: gabungkan parameter filter dengan limit dan offset
    $params_data = $params_count; // Ambil parameter dari filter count
    $types_data = $types_count;   // Ambil tipe dari filter count
    $params_data[] = $limit;      // Tambah limit
    $params_data[] = $offset;     // Tambah offset
    $types_data .= "ii";          // Tambah tipe integer untuk limit & offset

    $stmt_produk = $con->prepare($sql);
    if (!$stmt_produk) {
         $error_fetch = "Gagal menyiapkan query data produk: " . $con->error;
         log_error("Prepare Statement Error (List Produk Data): " . $con->error);
         set_flash_message('error', $error_fetch);
    } else {
        if (!empty($params_data)) { $stmt_produk->bind_param($types_data, ...$params_data); }
        if (!$stmt_produk->execute()) {
            $error_fetch = "Gagal mengeksekusi query data produk: " . $stmt_produk->error;
            log_error("Execute Error (List Produk Data): " . $stmt_produk->error);
            set_flash_message('error', $error_fetch);
        } else {
            $resultProduk = $stmt_produk->get_result();
            while ($data = $resultProduk->fetch_assoc()) { $daftarProduk[] = $data; }
        }
        $stmt_produk->close();
    }
} elseif ($totalProduk == 0 && !$error_fetch) {
    // Tidak ada produk ditemukan yang cocok filter, bukan error
    $daftarProduk = [];
}

// Generate CSRF token untuk link toggle status
$csrf_token_toggle = generate_csrf_token();

// --- Mulai Output HTML ---
require_once 'page-template.php';
admin_header('Manajemen Produk');
?>

<style> /* Style spesifik untuk halaman ini */
    .table img.product-thumb { max-width: 80px; max-height: 80px; width: auto; height: auto; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; background-color: #f8f9fa; }
    .table td, .table th { vertical-align: middle; text-align: center; }
    .table th.col-nama, .table td.col-nama { text-align: left; }
    .col-nomor { width: 5%; } .col-foto { width: 10%; } .col-harga, .col-stok, .col-status { width: 10%; } .col-aksi { width: 20%; min-width: 180px; }
    .status-badge { display: inline-block; padding: 0.35em 0.65em; font-size: .75em; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.375rem; }
    .status-aktif { background-color: #198754; } .status-nonaktif { background-color: #dc3545; }
    .action-buttons .btn, .action-buttons form { display: inline-block; margin: 0 2px; margin-bottom: 5px; } .action-buttons form { margin-bottom: 0; }
    .filter-form .form-select, .filter-form .btn, .filter-form .form-control { height: calc(1.5em + .75rem + 2px); } /* Smaller form controls */
    @media (min-width: 992px) { .filter-form .col-keyword { flex-grow: 1; } }
    .pagination { margin-bottom: 0; } /* Hilangkan margin bawah default pagination */
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item active" aria-current="page">Produk</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="h4 mb-0"><i class="bi bi-box-seam-fill me-2"></i>Manajemen Produk (Total: <?php echo $totalProduk; ?>)</h2>
    <div><a href="tambahproduk.php" class="btn btn-success"><i class="bi bi-plus-square-fill me-1"></i> Tambah Produk</a></div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light">
       <form method="GET" action="produk.php" class="row g-3 align-items-end filter-form">
            <div class="col-md-6 col-lg-3">
                <label for="kategori" class="form-label small mb-1">Kategori:</label>
                <select name="kategori" id="kategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($daftarKategori as $kategori): ?>
                        <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_id_filter == $kategori['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kategori['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                 <label for="status" class="form-label small mb-1">Status:</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?php echo ($status_filter == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="nonaktif" <?php echo ($status_filter == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="limit" class="form-label small mb-1">Tampil:</label>
                <select name="limit" id="limit" class="form-select form-select-sm">
                    <?php foreach ($allowed_limits as $lim_opt): ?>
                        <option value="<?php echo $lim_opt; ?>" <?php echo ($limit == $lim_opt) ? 'selected' : ''; ?>>
                            <?php echo $lim_opt; ?> / hal
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-3 col-keyword">
                <label for="keyword" class="form-label small mb-1">Pencarian:</label>
                <input type="text" name="keyword" id="keyword" class="form-control form-control-sm" placeholder="Nama / Kode / Kategori..." value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
            </div>
            <div class="col-auto">
                 <input type="hidden" name="page" value="1"> <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Cari</button>
            </div>
             <div class="col-auto">
                 <a href="produk.php" class="btn btn-secondary btn-sm w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
             </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if ($error_fetch && !isset($_SESSION['flash_message'])): // Tampilkan error fetch jika belum ada flash message ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_fetch); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="col-nomor">#</th>
                        <th class="col-nama">Nama Produk</th>
                        <th class="col-foto">Foto</th>
                        <th>Kategori</th>
                        <th>Kode</th>
                        <th class="col-harga">Harga</th>
                        <th class="col-stok">Stok</th>
                        <th class="col-status">Status</th>
                        <th class="col-aksi text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($daftarProduk)) {
                        $startNo = $offset + 1;
                        foreach ($daftarProduk as $data) {
                            $productId = (int) $data['id'];
                            $namaProduk = htmlspecialchars($data['nama']);
                            $kodeProduk = htmlspecialchars($data['kode_produk']);
                            $namaFoto = $data['foto'];
                            $status = strtolower(htmlspecialchars($data['status'] ?? 'nonaktif'));

                            // Penentuan URL Gambar
                            $displayFotoUrl = PLACEHOLDER_IMAGE_URL;
                            $altText = "Placeholder";
                            if (!empty($namaFoto) && !empty($kodeProduk)) {
                                // Gunakan path FS yang sudah didefine di atas
                                $fotoFileSystemPath = rtrim(IMAGE_FS_BASE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $kodeProduk . DIRECTORY_SEPARATOR . $namaFoto;
                                if (file_exists($fotoFileSystemPath)) {
                                    // Gunakan base URL dari konstanta
                                    $displayFotoUrl = rtrim(MAIN_SITE_IMAGE_URL_BASE, '/') . '/produk/' . $kodeProduk . '/' . $namaFoto;
                                    $altText = $namaProduk;
                                }
                            }

                            // Query String untuk Link Aksi (pertahankan filter saat ini)
                            $queryParamsAction = [];
                            if (!empty($kategori_id_filter)) $queryParamsAction['kategori'] = $kategori_id_filter;
                            if (!empty($status_filter)) $queryParamsAction['status'] = $status_filter;
                            if (!empty($keyword)) $queryParamsAction['keyword'] = $keyword;
                            if ($limit != 10) $queryParamsAction['limit'] = $limit;
                            if ($currentPage > 1) $queryParamsAction['page'] = $currentPage; // Pertahankan halaman untuk lihat/edit

                            // --- Khusus untuk Toggle Status ---
                            $queryParamsToggle = $queryParamsAction; // Salin parameter
                            // Tambahkan action, id produk (p), dan CSRF token
                            $queryParamsToggle['action'] = 'toggle_status';
                            $queryParamsToggle['p'] = $productId;
                            $queryParamsToggle['csrf_token'] = $csrf_token_toggle;
                            $toggleUrl = 'produk.php?' . http_build_query($queryParamsToggle);

                            // Query string untuk lihat dan edit (tanpa action, id, csrf)
                            $queryStringAction = !empty($queryParamsAction) ? '?' . http_build_query($queryParamsAction) : '';
                            $lihatUrl = 'lihatproduk.php?p=' . $productId . (!empty($queryStringAction) ? '&'.ltrim($queryStringAction,'?') : '');
                            $editUrl = 'produk-detail.php?p=' . $productId . (!empty($queryStringAction) ? '&'.ltrim($queryStringAction,'?') : '');
                    ?>
                            <tr>
                                <td class="col-nomor"><?php echo $startNo++; ?></td>
                                <td class="col-nama"><?php echo $namaProduk; ?></td>
                                <td class="col-foto">
                                    <img src="<?php echo htmlspecialchars($displayFotoUrl); ?>?t=<?php echo time(); // Cache busting ?>" alt="<?php echo htmlspecialchars($altText); ?>" class="product-thumb">
                                </td>
                                <td><?php echo htmlspecialchars($data['nama_kategori'] ?? '-'); ?></td>
                                <td><?php echo $kodeProduk; ?></td>
                                <td class="col-harga">Rp <?php echo number_format((float)($data['harga'] ?? 0), 0, ',', '.'); ?></td>
                                <td class="col-stok"><?php echo htmlspecialchars($data['stok'] ?? '-'); ?></td>
                                <td class="col-status">
                                    <span class="status-badge <?php echo $status == 'aktif' ? 'status-aktif' : 'status-nonaktif'; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="col-aksi text-center action-buttons">
                                    <a href="<?php echo $lihatUrl; ?>" class="btn btn-sm btn-outline-info" title="Lihat Detail"><i class="bi bi-eye"></i></a>
                                    <a href="<?php echo $editUrl; ?>" class="btn btn-sm btn-outline-primary" title="Edit Produk"><i class="bi bi-pencil-square"></i></a>
                                    <a href="<?php echo $toggleUrl; // Gunakan URL yang sudah dibuild ?>"
                                       class="btn btn-sm <?php echo $status == 'aktif' ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                       title="<?php echo $status == 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>"
                                       onclick="return confirm('Anda yakin ingin mengubah status produk \'<?php echo addslashes($namaProduk); ?>\' menjadi <?php echo $status == 'aktif' ? 'Nonaktif' : 'Aktif'; ?>?');">
                                        <?php if ($status == 'aktif'): ?><i class="bi bi-toggle-off"></i><?php else: ?><i class="bi bi-toggle-on"></i><?php endif; ?>
                                    </a>
                                </td>
                            </tr>
                    <?php
                        } // End foreach
                    } else {
                        $colspan = 9; // Sesuaikan colspan
                        echo '<tr><td colspan="' . $colspan . '" class="text-center py-4">Tidak ada data produk ditemukan' . (!empty($kategori_id_filter) || !empty($status_filter) || !empty($keyword) ? ' dengan filter/pencarian yang dipilih' : '') . '.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div> <?php if ($totalPages > 1): ?>
            <nav aria-label="Navigasi Halaman Produk Admin" class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                 <span class="text-muted small">
                     Halaman <?php echo $currentPage; ?> dari <?php echo $totalPages; ?> (Total <?php echo $totalProduk; ?> produk)
                 </span>
                 <ul class="pagination pagination-sm mb-0">
                    <?php
                    $base_params = []; // Parameter dasar untuk link pagination
                    if (!empty($kategori_id_filter)) $base_params['kategori'] = $kategori_id_filter;
                    if (!empty($status_filter)) $base_params['status'] = $status_filter;
                    if (!empty($keyword)) $base_params['keyword'] = $keyword;
                    if ($limit != 10) $base_params['limit'] = $limit;

                    // Tombol Sebelumnya
                    if ($currentPage > 1) {
                        $prev_page = $currentPage - 1;
                        $prev_params = $base_params + ['page' => $prev_page];
                        $prev_link = 'produk.php?' . http_build_query($prev_params);
                        echo '<li class="page-item"><a class="page-link" href="'.$prev_link.'" aria-label="Sebelumnya"><span aria-hidden="true">&laquo;</span></a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link" aria-hidden="true">&laquo;</span></li>';
                    }

                    // Nomor Halaman (Logic Sederhana untuk Ellipsis)
                     $max_nav_pages = 5; // Jumlah tombol nomor maks
                     $start_page = max(1, $currentPage - floor($max_nav_pages / 2));
                     $end_page = min($totalPages, $start_page + $max_nav_pages - 1);
                     // Adjust if near the end
                     if ($end_page - $start_page + 1 < $max_nav_pages) {
                         $start_page = max(1, $end_page - $max_nav_pages + 1);
                     }

                     // Ellipsis Awal
                     if ($start_page > 1) {
                         $first_params = $base_params + ['page' => 1];
                         echo '<li class="page-item"><a class="page-link" href="produk.php?'.http_build_query($first_params).'">1</a></li>';
                         if ($start_page > 2) {
                             echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                         }
                     }

                     // Nomor Halaman Tengah
                     for ($i = $start_page; $i <= $end_page; $i++) {
                         $page_params = $base_params + ['page' => $i];
                         $active_class = ($i == $currentPage) ? 'active' : '';
                         echo '<li class="page-item '.$active_class.'"><a class="page-link" href="produk.php?'.http_build_query($page_params).'">'.$i.'</a></li>';
                     }

                     // Ellipsis Akhir
                     if ($end_page < $totalPages) {
                         if ($end_page < $totalPages - 1) {
                             echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                         }
                         $last_params = $base_params + ['page' => $totalPages];
                         echo '<li class="page-item"><a class="page-link" href="produk.php?'.http_build_query($last_params).'">'.$totalPages.'</a></li>';
                     }


                    // Tombol Selanjutnya
                    if ($currentPage < $totalPages) {
                        $next_page = $currentPage + 1;
                        $next_params = $base_params + ['page' => $next_page];
                        $next_link = 'produk.php?' . http_build_query($next_params);
                         echo '<li class="page-item"><a class="page-link" href="'.$next_link.'" aria-label="Selanjutnya"><span aria-hidden="true">&raquo;</span></a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link" aria-hidden="true">&raquo;</span></li>';
                    }
                    ?>
                </ul>
            </nav>
         <?php endif; ?>
         </div> </div> <?php
// Tutup koneksi database jika masih terbuka dan valid
if (isset($con) && $con instanceof mysqli) {
    mysqli_close($con);
}

admin_footer(); // Panggil template footer
?>