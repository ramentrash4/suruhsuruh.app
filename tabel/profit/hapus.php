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
$id_profit = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_profit <= 0) {
    $_SESSION['error_message'] = "ID Profit tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Cek keterkaitan dengan tabel 'perusahaan'
$stmt_check = $koneksi->prepare("SELECT COUNT(*) as count FROM perusahaan WHERE Id_Profit = ?");
if($stmt_check === false) { 
    $_SESSION['error_message'] = "Gagal mempersiapkan query cek perusahaan: " . $koneksi->error; 
    header("Location: index.php"); 
    exit;
}
$stmt_check->bind_param("i", $id_profit);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$count_perusahaan = $result_check->fetch_assoc()['count'];
$stmt_check->close();

if ($count_perusahaan > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus data profit #".$id_profit." karena masih direferensikan oleh ".$count_perusahaan." data perusahaan. Atur ulang referensi di data perusahaan atau hapus data perusahaan terkait terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// Lanjutkan penghapusan jika tidak ada keterkaitan
$sql = "DELETE FROM profit WHERE Id_Profit = ?";
$stmt = $koneksi->prepare($sql);
if($stmt === false) { 
    $_SESSION['error_message'] = "Gagal mempersiapkan query hapus: " . $koneksi->error;
    header("Location: index.php");
    exit;
}
$stmt->bind_param("i", $id_profit);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Data profit #".$id_profit." berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data profit #".$id_profit." tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data profit: " . $stmt->error;
}
$stmt->close();

header("Location: index.php");
exit;
?>