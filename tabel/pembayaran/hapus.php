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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { $_SESSION['error_message'] = "ID Bayaran tidak valid."; header("Location: index.php"); exit; }

// Cek keterkaitan data (misal dengan tabel pesanan)
$stmt_check = $koneksi->prepare("SELECT COUNT(*) as total FROM pesanan WHERE Id_Pembayaran = ?");
if ($stmt_check === false) { die("Error preparing check query: " . $koneksi->error); }
$stmt_check->bind_param("i", $id);
if (!$stmt_check->execute()) { die("Error executing check query: " . $stmt_check->error); }
$count_result = $stmt_check->get_result();
if ($count_result === false) { die("Error getting result for check query: " . $stmt_check->error); }
$count = $count_result->fetch_assoc()['total'];
$stmt_check->close();

if ($count > 0) {
    $_SESSION['error_message'] = "Gagal menghapus! Data bayaran ini masih terkait dengan " . $count . " pesanan.";
    header("Location: index.php");
    exit;
}

$stmt_delete = $koneksi->prepare("DELETE FROM bayaran WHERE Id_Pembayaran = ?");
if ($stmt_delete === false) { die("Error preparing delete statement: " . $koneksi->error); }
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    if ($stmt_delete->affected_rows > 0) {
        $_SESSION['success_message'] = "Data bayaran berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data bayaran tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data. Error: " . $stmt_delete->error;
}
$stmt_delete->close();

header("Location: index.php");
exit;
?>