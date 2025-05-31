<?php
// Pastikan error reporting aktif di paling atas
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin'] !== true) {
    if (!defined('BASE_URL')) define('BASE_URL', '/projekbasdat/');
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
require_once '../../config.php';

$id_perusahaan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_perusahaan <= 0) {
    $_SESSION['error_message'] = "ID Perusahaan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Cek keterkaitan dengan tabel 'pekerja'
$stmt_pekerja = $koneksi->prepare("SELECT COUNT(*) as count FROM pekerja WHERE Id_Perusahaan = ?");
if($stmt_pekerja === false) { $_SESSION['error_message'] = "Gagal cek pekerja: " . $koneksi->error; header("Location: index.php"); exit; }
$stmt_pekerja->bind_param("i", $id_perusahaan);
$stmt_pekerja->execute();
$pekerja_count = $stmt_pekerja->get_result()->fetch_assoc()['count'];
$stmt_pekerja->close();

if ($pekerja_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus perusahaan karena masih terkait dengan ".$pekerja_count." pekerja.";
    header("Location: index.php");
    exit;
}

// Cek keterkaitan dengan tabel 'mitra'
$stmt_mitra = $koneksi->prepare("SELECT COUNT(*) as count FROM mitra WHERE Id_Perusahaan = ?");
if($stmt_mitra === false) { $_SESSION['error_message'] = "Gagal cek mitra: " . $koneksi->error; header("Location: index.php"); exit; }
$stmt_mitra->bind_param("i", $id_perusahaan);
$stmt_mitra->execute();
$mitra_count = $stmt_mitra->get_result()->fetch_assoc()['count'];
$stmt_mitra->close();

if ($mitra_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus perusahaan karena masih terkait dengan ".$mitra_count." mitra.";
    header("Location: index.php");
    exit;
}

// Lanjutkan penghapusan
$stmt_delete = $koneksi->prepare("DELETE FROM perusahaan WHERE Id_Perusahaan = ?");
if($stmt_delete === false) { $_SESSION['error_message'] = "Gagal mempersiapkan query hapus: " . $koneksi->error; header("Location: index.php"); exit; }
$stmt_delete->bind_param("i", $id_perusahaan);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['success_message'] = "Data perusahaan berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data perusahaan tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data perusahaan: " . $stmt_delete->error;
}
$stmt_delete->close();

header("Location: index.php");
exit;
?>