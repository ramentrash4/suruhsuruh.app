<?php
session_start();
require '../../config/database.php';

// Mengambil ID dari URL (Composite Key)
$id_pekerja = isset($_GET['id_pekerja']) ? intval($_GET['id_pekerja']) : 0;
$id_perusahaan = isset($_GET['id_perusahaan']) ? intval($_GET['id_perusahaan']) : 0;

if ($id_pekerja <= 0 || $id_perusahaan <= 0) {
    $_SESSION['error_message'] = "ID Pekerja atau ID Perusahaan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Tidak ada tabel lain yang merujuk ke 'pekerja' berdasarkan skema, jadi bisa langsung hapus.
// Jika ada, tambahkan pemeriksaan FK di sini.

$query_delete = "DELETE FROM pekerja WHERE Id_Pekerja = $id_pekerja AND Id_Perusahaan = $id_perusahaan";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data pekerja berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pekerja tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data pekerja: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>