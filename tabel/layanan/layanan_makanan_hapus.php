<?php
session_start();
require '../../config/database.php';

$id_layanan_hapus = isset($_GET['id_layanan']) ? intval($_GET['id_layanan']) : 0;

if ($id_layanan_hapus <= 0) {
    $_SESSION['error_message'] = "ID Layanan tidak valid untuk penghapusan detail makanan.";
    header("Location: layanan_makanan_index.php");
    exit;
}

// Menghapus hanya entri dari layanan_makanan. 
// Entri di tabel 'layanan' utama tidak dihapus dari sini.
// Jika ingin menghapus layanan utama juga, harus dilakukan dari CRUD layanan utama.
// Foreign Key dari layanan_makanan ke layanan memiliki ON DELETE CASCADE,
// jadi jika layanan utama dihapus, detail makanan ini akan ikut terhapus.
// Sebaliknya, menghapus detail makanan tidak menghapus layanan utama.

$query_delete = "DELETE FROM layanan_makanan WHERE Id_Layanan = $id_layanan_hapus";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Detail layanan makanan berhasil dihapus. Entri di Layanan Utama tidak berubah.";
    } else {
        $_SESSION['error_message'] = "Detail layanan makanan tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus detail layanan makanan: " . mysqli_error($koneksi);
}

header("Location: layanan_makanan_index.php");
exit;
?>