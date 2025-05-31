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

$id_layanan = isset($_GET['id_layanan']) ? (int)$_GET['id_layanan'] : 0;
$response = ['success' => false, 'mitra' => [], 'message' => 'ID Layanan tidak valid.'];

if ($id_layanan > 0) {
    $sql = "SELECT m.Id_Mitra, m.Nama_Mitra, m.Spesialis_Mitra 
            FROM mitra m
            JOIN terikat t ON m.Id_Mitra = t.Id_Mitra
            WHERE t.Id_Layanan = ?
            ORDER BY m.Nama_Mitra ASC";
    
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id_layanan);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $mitra_arr = [];
            while ($row = $result->fetch_assoc()) {
                $mitra_arr[] = $row;
            }
            $response['success'] = true;
            $response['mitra'] = $mitra_arr;
            $response['message'] = count($mitra_arr) > 0 ? 'Data mitra ditemukan.' : 'Tidak ada mitra terkait.';
        } else {
            $response['message'] = "Gagal mengeksekusi query mitra: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Gagal mempersiapkan query mitra: " . $koneksi->error;
    }
}
echo json_encode($response);
?>