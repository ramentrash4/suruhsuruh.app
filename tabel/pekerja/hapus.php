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

$id_pekerja = isset($_GET['id_pekerja']) ? (int)$_GET['id_pekerja'] : 0;
$id_perusahaan = isset($_GET['id_perusahaan']) ? (int)$_GET['id_perusahaan'] : 0;

if ($id_pekerja <= 0 || $id_perusahaan <= 0) {
    $_SESSION['error_message'] = "ID Pekerja atau ID Perusahaan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Tabel 'pekerja' tidak menjadi FK di tabel lain dalam skema Anda, jadi bisa langsung hapus
// Namun, pastikan tidak ada logika bisnis lain yang bergantung padanya.

$sql = "DELETE FROM pekerja WHERE Id_Pekerja = ? AND Id_Perusahaan = ?";
$stmt = $koneksi->prepare($sql);
if($stmt === false) { 
    $_SESSION['error_message'] = "Gagal mempersiapkan query hapus: " . $koneksi->error;
    header("Location: index.php");
    exit;
}
$stmt->bind_param("ii", $id_pekerja, $id_perusahaan);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Data pekerja (ID: ".$id_pekerja.", Prsh ID: ".$id_perusahaan.") berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pekerja tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data pekerja: " . $stmt->error;
}
$stmt->close();

header("Location: index.php");
exit;
?>