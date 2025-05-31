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

$id_detail_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_detail_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Detail Pesanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Di SQL Anda, tabel 'profit' memiliki FOREIGN KEY ke 'detail_pesanan' dengan ON DELETE SET NULL.
// Jadi, kita bisa langsung menghapus 'detail_pesanan'.
// Jika constraint-nya ON DELETE RESTRICT, kita perlu cek dulu tabel 'profit'.

$sql = "DELETE FROM detail_pesanan WHERE Id_DetailPesanan = ?";
$stmt = $koneksi->prepare($sql);
if($stmt === false) { 
    $_SESSION['error_message'] = "Gagal mempersiapkan query hapus: " . $koneksi->error;
    header("Location: index.php");
    exit;
}
$stmt->bind_param("i", $id_detail_pesanan);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Data detail pesanan #".$id_detail_pesanan." berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data detail pesanan #".$id_detail_pesanan." tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data detail pesanan: " . $stmt->error;
}
$stmt->close();

header("Location: index.php");
exit;
?>