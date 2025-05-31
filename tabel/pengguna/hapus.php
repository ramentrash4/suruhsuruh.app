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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = "ID Pengguna tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Cek keterkaitan data (praktik yang baik)
$stmt_check = $koneksi->prepare("SELECT (SELECT COUNT(*) FROM pesanan WHERE Id_Pengguna = ?) AS pesanan_count, (SELECT COUNT(*) FROM bayaran WHERE Id_Pengguna = ?) AS bayaran_count");
$stmt_check->bind_param("ii", $id, $id);
$stmt_check->execute();
$counts = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if ($counts['pesanan_count'] > 0 || $counts['bayaran_count'] > 0) {
    $_SESSION['error_message'] = "Gagal menghapus! Pengguna ini masih terkait dengan data lain (pesanan/pembayaran).";
    header("Location: index.php");
    exit;
}

// Hapus dengan prepared statement
$stmt_delete = $koneksi->prepare("DELETE FROM pengguna WHERE Id_pengguna = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['success_message'] = "Data pengguna berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pengguna tidak ditemukan atau sudah dihapus sebelumnya.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus pengguna. Error: " . $stmt_delete->error;
}
$stmt_delete->close();

header("Location: index.php");
exit;
?>