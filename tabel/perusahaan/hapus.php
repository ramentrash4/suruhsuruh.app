<?php
session_start();
require '../../config/database.php';

$id_perusahaan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_perusahaan <= 0) {
    $_SESSION['error_message'] = "ID Perusahaan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Periksa apakah perusahaan ini direferensikan di tabel 'pekerja' (pekerja_ibfk_1)
// atau 'mitra' (fk_Id_Perusahaan)
$check_pekerja_query = "SELECT COUNT(*) as count FROM pekerja WHERE Id_Perusahaan = $id_perusahaan";
$pekerja_result = mysqli_query($koneksi, $check_pekerja_query);
$pekerja_count = 0;
if ($pekerja_result) {
    $pekerja_count = mysqli_fetch_assoc($pekerja_result)['count'];
}

if ($pekerja_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus data perusahaan ini karena masih memiliki $pekerja_count data pekerja terkait. Atur ulang referensi atau hapus data pekerja terkait terlebih dahulu.";
    header("Location: index.php");
    exit;
}

$check_mitra_query = "SELECT COUNT(*) as count FROM mitra WHERE Id_Perusahaan = $id_perusahaan";
$mitra_result = mysqli_query($koneksi, $check_mitra_query);
$mitra_count = 0;
if ($mitra_result) {
    $mitra_count = mysqli_fetch_assoc($mitra_result)['count'];
}

if ($mitra_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus data perusahaan ini karena masih memiliki $mitra_count data mitra terkait. Atur ulang referensi atau hapus data mitra terkait terlebih dahulu.";
    header("Location: index.php");
    exit;
}


$query_delete = "DELETE FROM perusahaan WHERE Id_Perusahaan = $id_perusahaan";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data perusahaan berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data perusahaan tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data perusahaan: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>