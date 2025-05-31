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

$id_layanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_layanan <= 0) {
    $_SESSION['error_message'] = "ID Layanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

$koneksi->begin_transaction();
try {
    // 1. Periksa FK di tabel 'pesanan'
    $stmt_check_pesanan = $koneksi->prepare("SELECT COUNT(*) as count FROM pesanan WHERE Id_Layanan = ?");
    if(!$stmt_check_pesanan) throw new Exception("Prepare cek pesanan gagal: ".$koneksi->error);
    $stmt_check_pesanan->bind_param("i", $id_layanan);
    $stmt_check_pesanan->execute();
    $pesanan_count = $stmt_check_pesanan->get_result()->fetch_assoc()['count'];
    $stmt_check_pesanan->close();

    if ($pesanan_count > 0) {
        throw new Exception("Tidak dapat menghapus layanan ini karena masih direferensikan oleh ".$pesanan_count." data pesanan. Atur ulang referensi atau hapus data pesanan terkait terlebih dahulu.");
    }

    // 2. Hapus referensi dari tabel 'terikat'
    $stmt_delete_terikat = $koneksi->prepare("DELETE FROM terikat WHERE Id_Layanan = ?");
    if(!$stmt_delete_terikat) throw new Exception("Prepare delete terikat gagal: ".$koneksi->error);
    $stmt_delete_terikat->bind_param("i", $id_layanan);
    if(!$stmt_delete_terikat->execute()){ /* Tidak perlu error jika tidak ada, yang penting tidak gagal query */ }
    $stmt_delete_terikat->close();

    // 3. Hapus data dari tabel spesialisasi anak (SUDAH DIHILANGKAN SESUAI PERMINTAAN)
    // Karena tabel layanan_makanan, layanan_kesehatan, layanan_pelayanan_rumah sudah tidak ada/tidak digunakan,
    // kita tidak perlu menghapus dari sana. Constraint FK dari tabel anak ke layanan juga seharusnya sudah dihapus dari DB.

    // 4. Hapus data layanan utama
    $stmt_delete_layanan = $koneksi->prepare("DELETE FROM layanan WHERE Id_Layanan = ?");
    if(!$stmt_delete_layanan) throw new Exception("Prepare delete layanan gagal: ".$koneksi->error);
    $stmt_delete_layanan->bind_param("i", $id_layanan);
    
    if ($stmt_delete_layanan->execute()) {
        if ($stmt_delete_layanan->affected_rows > 0) {
            $_SESSION['success_message'] = "Data layanan dan relasi mitra terkait berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Data layanan tidak ditemukan atau sudah dihapus.";
        }
    } else {
        throw new Exception("Gagal menghapus data layanan utama: " . $stmt_delete_layanan->error);
    }
    $stmt_delete_layanan->close();
    $koneksi->commit();

} catch (Exception $e) {
    $koneksi->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: index.php");
exit;
?>