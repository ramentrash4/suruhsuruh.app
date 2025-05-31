<?php
session_start();
require '../../config/database.php';

$id_profit = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_profit <= 0) {
    $_SESSION['error_message'] = "ID Profit tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Karena profit.Id_DetailPesanan memiliki ON DELETE SET NULL,
// menghapus profit tidak akan mempengaruhi detail_pesanan.
// Namun, tabel 'perusahaan' memiliki FK ke 'profit' (perusahaan_ibfk_1).
// Periksa apakah profit ini direferensikan di tabel 'perusahaan'
$check_perusahaan_query = "SELECT COUNT(*) as count FROM perusahaan WHERE Id_Profit = $id_profit";
$perusahaan_result = mysqli_query($koneksi, $check_perusahaan_query);
$perusahaan_count = 0;
if ($perusahaan_result) {
    $perusahaan_count = mysqli_fetch_assoc($perusahaan_result)['count'];
}


if ($perusahaan_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus data profit ini karena masih direferensikan oleh $perusahaan_count data perusahaan. Atur ulang referensi di data perusahaan terlebih dahulu atau hapus data perusahaan terkait.";
    header("Location: index.php");
    exit;
}


$query_delete = "DELETE FROM profit WHERE Id_Profit = $id_profit";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data profit berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data profit tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data profit: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>