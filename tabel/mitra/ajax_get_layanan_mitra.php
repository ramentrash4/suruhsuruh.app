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
header('Content-Type: application/json');

$id_mitra = isset($_GET['id_mitra']) ? (int)$_GET['id_mitra'] : 0;
$response = ['success' => false, 'layanan' => [], 'message' => 'ID Mitra tidak valid.'];

if ($id_mitra > 0) {
    $sql = "SELECT l.Nama_Layanan, l.Jenis_Layanan 
            FROM layanan l
            JOIN terikat t ON l.Id_Layanan = t.Id_Layanan
            WHERE t.Id_Mitra = ?
            ORDER BY l.Nama_Layanan ASC";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id_mitra);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $layanan_arr = [];
            while ($row = $result->fetch_assoc()) {
                $layanan_arr[] = $row;
            }
            $response['success'] = true;
            $response['layanan'] = $layanan_arr;
            $response['message'] = count($layanan_arr) > 0 ? 'Data layanan ditemukan.' : 'Tidak ada layanan terkait.';
        } else {
            $response['message'] = "Gagal mengeksekusi query layanan: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Gagal mempersiapkan query layanan: " . $koneksi->error;
    }
}
echo json_encode($response);
?>