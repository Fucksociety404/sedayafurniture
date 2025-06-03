<?php
// File: admin/lihatproduk.php (Refactored & Corrected)
require_once "session.php";
require_login();
require_once __DIR__ . '/../config/koneksi.php'; // Koneksi & Konstanta URL
require_once "page-template.php"; // <--- TAMBAHKAN INI

// --- Pengecekan Konstanta (Opsional) ---
if (!defined('MAIN_SITE_IMAGE_URL_BASE') || !defined('PLACEHOLDER_IMAGE_URL')) {
    define('MAIN_SITE_IMAGE_URL_BASE', '../images/'); // Fallback relatif dari admin
    define('PLACEHOLDER_IMAGE_URL', MAIN_SITE_IMAGE_URL_BASE . 'placeholder.png');
}
define('IMAGE_FS_BASE_PATH', realpath(__DIR__ . '/../images/produk/') . DIRECTORY_SEPARATOR); // Path File System Absolut
//---------------------------------------

$produk_id = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
$dataProduk = null;

if (!$produk_id) {
    set_flash_message('error', 'ID Produk tidak valid.');
    header('Location: produk.php'); exit;
}

// Ambil data produk
$stmt = $con->prepare("SELECT p.*, k.nama AS nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.id = ?");
if ($stmt) {
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataProduk = $result->fetch_assoc();
    $stmt->close();
} else {
    log_error("Gagal prepare statement ambil detail produk (ID: $produk_id): " . $con->error);
    set_flash_message('error', 'Gagal memuat data produk.');
    header('Location: produk.php'); exit;
}
if (!$dataProduk) {
    set_flash_message('error', 'Produk tidak ditemukan.');
    header('Location: produk.php'); exit;
}

// --- URL GAMBAR UTAMA ---
$kodeProduk = $dataProduk['kode_produk'];
$namaFotoUtama = $dataProduk['foto'];
$displayFotoUrl = PLACEHOLDER_IMAGE_URL; // Default
$altTextUtama = "Placeholder Produk";
$imageFound = false;
if (!empty($namaFotoUtama) && !empty($kodeProduk)) {
    $fotoFileSystemPath = IMAGE_FS_BASE_PATH . $kodeProduk . DIRECTORY_SEPARATOR . $namaFotoUtama;
    if (file_exists($fotoFileSystemPath)) {
        // Gunakan base URL dari konstanta (relatif dari admin)
        $displayFotoUrl = MAIN_SITE_IMAGE_URL_BASE . 'produk/' . $kodeProduk . '/' . $namaFotoUtama;
        $altTextUtama = htmlspecialchars($dataProduk['nama']);
        $imageFound = true;
    } else {
         log_error("Lihat Produk - File gambar utama tidak ditemukan: " . $fotoFileSystemPath);
    }
}

// --- URL GAMBAR THUMBNAIL ---
$thumbnails = [];
$thumbnail_fields = ['foto_thumbnail1', 'foto_thumbnail2', 'foto_thumbnail3'];
foreach ($thumbnail_fields as $field) {
    $thumbName = $dataProduk[$field] ?? null;
    if (!empty($thumbName) && !empty($kodeProduk)) {
         $thumbFileSystemPath = IMAGE_FS_BASE_PATH . $kodeProduk . DIRECTORY_SEPARATOR . $thumbName;
         if (file_exists($thumbFileSystemPath)) {
             // Buat URL relatif untuk thumbnail
             $thumbnails[] = MAIN_SITE_IMAGE_URL_BASE . 'produk/' . $kodeProduk . '/' . $thumbName;
         } else {
              log_error("Lihat Produk - File thumbnail tidak ditemukan: " . $thumbFileSystemPath);
         }
    }
}

mysqli_close($con);

// --- Mulai Output HTML ---
admin_header('Detail Produk: ' . htmlspecialchars($dataProduk['nama']));
?>
<style>
    .product-image-main {
        max-width: 100%;
        height: auto;
        max-height: 450px;
        object-fit: contain;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        margin-bottom: 1rem;
        background-color: #f8f9fa; /* Latar belakang jika gambar transparan */
    }
    .product-thumbnails img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 2px solid transparent; /* Border awal transparan */
        border-radius: .25rem;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s ease-in-out, border-color 0.2s ease-in-out;
        padding: 2px; /* Padding kecil */
    }
    .product-thumbnails img:hover {
        opacity: 1;
    }
    .product-thumbnails img.active-thumb {
        opacity: 1;
        border-color: #0d6efd; /* Warna border saat aktif */
    }
    .product-details dt {
        font-weight: 600; /* Bold */
        color: #495057;
    }
    .product-details dd {
        color: #212529;
    }
     .status-badge-detail {
         display: inline-block;
         padding: 0.35em 0.65em;
         font-size: .9em; /* Sedikit lebih besar */
         font-weight: 700;
         line-height: 1;
         color: #fff;
         text-align: center;
         white-space: nowrap;
         vertical-align: baseline;
         border-radius: 0.375rem;
     }
     .status-aktif { background-color: #198754; }
     .status-nonaktif { background-color: #dc3545; }
</style>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
        <li class="breadcrumb-item"><a href="produk.php">Produk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detail Produk</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
         <h2 class="h5 mb-0"><i class="bi bi-eye-fill me-2"></i>Detail Produk: <?php echo htmlspecialchars($dataProduk['nama']); ?></h2>
    </div>
    <div class="card-body">
        <div class="row g-4"> {/* Tambah gap antar kolom */}
            <div class="col-md-5 text-center">
                <img src="<?php echo htmlspecialchars($displayFotoUrl); ?>?t=<?php echo time(); // Cache busting ?>" alt="<?php echo $altTextUtama; ?>" class="product-image-main img-fluid" id="mainProductImage">

                <?php
                // Tampilkan thumbnail jika gambar utama ADA ATAU jika ada thumbnail lain
                $has_other_thumbnails = count($thumbnails) > 0;
                if ($imageFound || $has_other_thumbnails):
                ?>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mt-3 product-thumbnails">
                        <?php if ($imageFound) : // Tampilkan gambar utama sebagai thumbnail pertama jika ada ?>
                        <img src="<?php echo htmlspecialchars($displayFotoUrl); ?>?t=<?php echo time();?>"
                             alt="<?php echo $altTextUtama; ?> - Thumbnail 1"
                             class="active-thumb"
                             onclick="changeMainImage('<?php echo htmlspecialchars($displayFotoUrl); ?>', this)">
                        <?php endif; ?>
                        <?php // Tampilkan thumbnail lain jika ada
                        foreach ($thumbnails as $index => $thumbUrl): ?>
                            <img src="<?php echo htmlspecialchars($thumbUrl); ?>?t=<?php echo time();?>"
                                 alt="<?php echo htmlspecialchars($dataProduk['nama']); ?> - Thumbnail <?php echo $index + ($imageFound ? 2 : 1); ?>"
                                 onclick="changeMainImage('<?php echo htmlspecialchars($thumbUrl); ?>', this)">
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!$imageFound && !$has_other_thumbnails): ?>
                    <p class="text-muted mt-3"><small>Tidak ada gambar untuk produk ini.</small></p>
                <?php endif; ?>
            </div>

            <div class="col-md-7">
                <h3 class="h4 mb-3"><?php echo htmlspecialchars($dataProduk['nama']); ?></h3>
                <dl class="row product-details">
                    <dt class="col-sm-4">Kode Produk</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($dataProduk['kode_produk']); ?></dd>

                    <dt class="col-sm-4">Kategori</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($dataProduk['nama_kategori'] ?? '-'); ?></dd>

                    <dt class="col-sm-4">Harga</dt>
                    <dd class="col-sm-8">Rp <?php echo number_format((float)($dataProduk['harga'] ?? 0), 0, ',', '.'); ?></dd>

                    <dt class="col-sm-4">Status Stok</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($dataProduk['stok'] ?? '-'); ?></dd>

                     <dt class="col-sm-4">Bahan</dt>
                    <dd class="col-sm-8"><?php echo !empty($dataProduk['bahan']) ? htmlspecialchars($dataProduk['bahan']) : '-'; ?></dd>

                    <dt class="col-sm-4">Ukuran</dt>
                    <dd class="col-sm-8"><?php echo !empty($dataProduk['ukuran']) ? htmlspecialchars($dataProduk['ukuran']) : '-'; ?></dd>

                    <dt class="col-sm-4">Status Publikasi</dt>
                    <dd class="col-sm-8">
                        <?php
                            $statusClass = (strtolower($dataProduk['status']) === 'aktif') ? 'status-aktif' : 'status-nonaktif';
                            $statusText = ucfirst(htmlspecialchars($dataProduk['status']));
                        ?>
                        <span class="status-badge-detail <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </dd>

                     <dt class="col-sm-4">Tanggal Ditambah</dt>
                    <dd class="col-sm-8"><?php echo $dataProduk['tanggal_ditambah'] ? date('d M Y H:i', strtotime($dataProduk['tanggal_ditambah'])) : '-'; ?></dd>

                    <dt class="col-sm-4">Terakhir Update</dt>
                    <dd class="col-sm-8"><?php echo $dataProduk['tanggal_update'] ? date('d M Y H:i', strtotime($dataProduk['tanggal_update'])) : '-'; ?></dd>
                </dl>
                <hr>
                <h4 class="h6 mt-4">Deskripsi:</h4>
                <p class="text-muted"><?php echo !empty($dataProduk['deskripsi']) ? nl2br(htmlspecialchars($dataProduk['deskripsi'])) : '<em>Tidak ada deskripsi.</em>'; ?></p>

                <?php if (!empty($dataProduk['detail'])): ?>
                <h4 class="h6 mt-4">Detail Tambahan:</h4>
                 <div class="text-muted">
                    <?php
                        // Format detail dengan list jika menggunakan '*'
                        $detailText = htmlspecialchars($dataProduk['detail']);
                        if (strpos($detailText, '*') !== false) {
                            echo '<ul class="list-unstyled">';
                            $items = explode('*', $detailText);
                            foreach ($items as $item) {
                                $trimmedItem = trim($item);
                                if (!empty($trimmedItem)) {
                                    echo '<li><i class="bi bi-dot"></i> ' . nl2br($trimmedItem) . '</li>';
                                }
                            }
                            echo '</ul>';
                        } else {
                            echo nl2br($detailText);
                        }
                    ?>
                 </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light text-end">
         <a href="produk.php<?php echo isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'produk.php') !== false ? '?' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) : ''; // Kembali ke halaman produk dengan filter sebelumnya jika memungkinkan ?>" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-1"></i> Kembali</a>
         <a href="produk-detail.php?p=<?php echo $produk_id; // Link ke halaman edit ?>" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i> Edit</a>
    </div>
</div>

<script>
    function changeMainImage(newSrc, clickedThumb) {
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            mainImage.src = newSrc; // Ganti source gambar utama
        }

        // Hapus kelas 'active-thumb' dari semua thumbnail
        const thumbnails = document.querySelectorAll('.product-thumbnails img');
        thumbnails.forEach(thumb => thumb.classList.remove('active-thumb'));

        // Tambahkan kelas 'active-thumb' ke thumbnail yang diklik
        if (clickedThumb) {
            clickedThumb.classList.add('active-thumb');
        }
    }
</script>

<?php
admin_footer(); // Panggil template footer
?>