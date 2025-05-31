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

$id_mitra = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_mitra <= 0) {
    $_SESSION['error_message'] = "ID Mitra tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

$koneksi->begin_transaction();
try {
    // Hapus dari tabel 'terikat' terlebih dahulu
    $sql_delete_terikat = "DELETE FROM terikat WHERE Id_Mitra = ?";
    $stmt_terikat = $koneksi->prepare($sql_delete_terikat);
    if($stmt_terikat === false) throw new Exception("Prepare delete terikat gagal: ".$koneksi->error);
    $stmt_terikat->bind_param("i", $id_mitra);
    if(!$stmt_terikat->execute()) throw new Exception("Eksekusi delete terikat gagal: ".$stmt_terikat->error);
    $stmt_terikat->close();

    // Hapus dari tabel 'mitra'
    $sql_delete_mitra = "DELETE FROM mitra WHERE Id_Mitra = ?";
    $stmt_mitra = $koneksi->prepare($sql_delete_mitra);
    if($stmt_mitra === false) throw new Exception("Prepare delete mitra gagal: ".$koneksi->error);
    $stmt_mitra->bind_param("i", $id_mitra);
    
    if ($stmt_mitra->execute()) {
        if ($stmt_mitra->affected_rows > 0) {
            $_SESSION['success_message'] = "Data mitra dan relasi layanan terkait berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Data mitra tidak ditemukan atau sudah dihapus (relasi layanan terkait telah diperiksa/dihapus).";
        }
    } else {
        throw new Exception("Gagal menghapus data mitra: " . $stmt_mitra->error);
    }
    $stmt_mitra->close();
    $koneksi->commit();

} catch (Exception $e) {
    $koneksi->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: index.php");
exit;
?>