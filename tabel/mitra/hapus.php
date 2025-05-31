<?php
session_start();
require '../../config/database.php';

$id_mitra = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_mitra <= 0) {
    $_SESSION['error_message'] = "ID Mitra tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // 1. Hapus referensi dari tabel 'terikat' terlebih dahulu
    $query_delete_terikat = "DELETE FROM terikat WHERE Id_Mitra = $id_mitra";
    if (!mysqli_query($koneksi, $query_delete_terikat)) {
        throw new Exception("Gagal menghapus relasi layanan terkait: " . mysqli_error($koneksi));
    }

    // 2. Hapus data mitra
    $query_delete_mitra = "DELETE FROM mitra WHERE Id_Mitra = $id_mitra";
    if (mysqli_query($koneksi, $query_delete_mitra)) {
        if (mysqli_affected_rows($koneksi) > 0) {
            mysqli_commit($koneksi);
            $_SESSION['success_message'] = "Data mitra dan relasi layanan terkait berhasil dihapus.";
        } else {
            // Mungkin sudah dihapus atau ID tidak ada, tapi relasi terikat mungkin sudah terhapus.
            // Anggap berhasil jika tidak ada error. Jika ingin lebih ketat, bisa throw exception.
            mysqli_commit($koneksi);
            $_SESSION['error_message'] = "Data mitra tidak ditemukan (mungkin sudah dihapus), relasi layanan terkait telah diperiksa/dihapus.";
        }
    } else {
        throw new Exception("Gagal menghapus data mitra: " . mysqli_error($koneksi));
    }

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: index.php");
exit;
?>