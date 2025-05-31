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

$id_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Pesanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Check for related records in `detail_pesanan`
$stmt_check = $koneksi->prepare("SELECT COUNT(*) as count FROM detail_pesanan WHERE Id_Pesanan = ?");
if($stmt_check === false) { $_SESSION['error_message'] = "Gagal mempersiapkan query cek detail pesanan."; header("Location: index.php"); exit;}
$stmt_check->bind_param("i", $id_pesanan);
$stmt_check->execute();
$detail_result = $stmt_check->get_result();
$detail_count = $detail_result->fetch_assoc()['count'];
$stmt_check->close();

if ($detail_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus pesanan #".$id_pesanan." karena masih memiliki ".$detail_count." item detail pesanan terkait. Hapus detail pesanan terlebih dahulu atau implementasikan penghapusan berantai (cascade delete).";
    header("Location: index.php");
    exit;
}

// Lanjutkan penghapusan jika tidak ada detail terkait
$stmt_delete = $koneksi->prepare("DELETE FROM pesanan WHERE Id_Pesanan = ?");
if($stmt_delete === false) { $_SESSION['error_message'] = "Gagal mempersiapkan query hapus pesanan."; header("Location: index.php"); exit;}
$stmt_delete->bind_param("i", $id_pesanan);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['success_message'] = "Data pesanan #".$id_pesanan." berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pesanan #".$id_pesanan." tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data pesanan: " . $stmt_delete->error;
}
$stmt_delete->close();

header("Location: index.php");
exit;
?>