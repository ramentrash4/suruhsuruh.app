<?php
session_start();
require '../../config/database.php';

$id_detail_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_detail_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Detail Pesanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Periksa apakah detail pesanan ini direferensikan di tabel 'profit'
// Karena ON DELETE SET NULL, kita bisa langsung hapus, atau beri peringatan jika mau.
// Untuk contoh ini, kita akan langsung hapus. Jika ON DELETE RESTRICT, perlu pemeriksaan.

$query_delete = "DELETE FROM detail_pesanan WHERE Id_DetailPesanan = $id_detail_pesanan";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data detail pesanan berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data detail pesanan tidak ditemukan atau sudah dihapus.";
    }
} else {
    // Ini mungkin menangkap error lain, misalnya jika ada constraint lain yang tidak terduga
    $_SESSION['error_message'] = "Gagal menghapus data detail pesanan: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>