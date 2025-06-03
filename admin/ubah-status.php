<?php
require "session.php";
require "../config/koneksi.php";

if (isset($_GET['p']) && isset($_GET['status'])) {
    $idProduk = intval($_GET['p']);
    $statusBaru = $_GET['status'] === 'aktif' ? 'nonaktif' : 'aktif';

    // Update status produk di database
    $sqlUpdate = "UPDATE produk SET status = ? WHERE id = ?";
    $stmtUpdate = $con->prepare($sqlUpdate);
    $stmtUpdate->bind_param("si", $statusBaru, $idProduk);
    
    if ($stmtUpdate->execute()) {
        // Redirect kembali ke halaman produk setelah berhasil
        header("Location: produk.php?status=success");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: produk.php?status=error");
    }
    $stmtUpdate->close();
    exit;
}
?>
