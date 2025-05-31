<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}
require '../../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = "ID Pengguna tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Cek keterkaitan dengan tabel lain sebelum menghapus (opsional tapi sangat direkomendasikan)
$check_query = "SELECT 
                    (SELECT COUNT(*) FROM pesanan WHERE Id_Pengguna = $id) AS pesanan_count,
                    (SELECT COUNT(*) FROM bayaran WHERE Id_Pengguna = $id) AS bayaran_count";
$check_result = mysqli_query($koneksi, $check_query);
$counts = mysqli_fetch_assoc($check_result);

if ($counts['pesanan_count'] > 0 || $counts['bayaran_count'] > 0) {
    $_SESSION['error_message'] = "Gagal menghapus! Pengguna ini masih terkait dengan " . $counts['pesanan_count'] . " pesanan dan " . $counts['bayaran_count'] . " pembayaran.";
    header("Location: index.php");
    exit;
}

// Jika tidak ada keterkaitan, lanjutkan penghapusan
$query = "DELETE FROM pengguna WHERE Id_pengguna=$id";
if (mysqli_query($koneksi, $query)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data pengguna berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pengguna tidak ditemukan atau sudah dihapus sebelumnya.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus pengguna. Error: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>