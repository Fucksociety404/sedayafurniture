<?php
require "../config/koneksi.php";

$id = $_GET["p"];

// Ambil informasi produk sebelum dihapus
$query = mysqli_query($con, "SELECT foto, kode_produk FROM produk WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

// Hapus file foto jika ada
if ($data['foto'] && file_exists("../images/produk/" . $data['foto'])) {
    unlink("../images/produk/" . $data['foto']);
}

// Hapus folder kode produk
$folderPath = "../images/produk/" . $data['kode_produk'];
if (is_dir($folderPath)) {
    // Hapus semua file dalam folder
    $files = glob($folderPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    // Hapus folder
    rmdir($folderPath);
}

// Hapus data dari database
$hapus = mysqli_query($con, "DELETE FROM produk WHERE id='$id'");

if ($hapus) {
?>
    <script type="text/javascript">
        alert('Data berhasil dihapus!');
    </script>
    <meta http-equiv="refresh" content="1; url=produk.php">
<?php
}
?>