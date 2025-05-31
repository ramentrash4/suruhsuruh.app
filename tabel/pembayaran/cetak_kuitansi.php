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

$id_pembayaran = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data_bayaran = null;
$nama_pengguna = "N/A";

if ($id_pembayaran <= 0) {
    die("ID Pembayaran tidak valid.");
}

// Ambil data bayaran dan join dengan pengguna
$sql = "SELECT b.*, p.Nama_Depan, p.Nama_Tengah, p.Nama_Belakang 
        FROM bayaran b 
        LEFT JOIN pengguna p ON b.Id_Pengguna = p.Id_pengguna 
        WHERE b.Id_Pembayaran = ?";
$stmt = $koneksi->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $koneksi->error);
}
$stmt->bind_param("i", $id_pembayaran);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $data_bayaran = $result->fetch_assoc();
    $nama_pengguna = trim($data_bayaran['Nama_Depan'] . ' ' . $data_bayaran['Nama_Tengah'] . ' ' . $data_bayaran['Nama_Belakang']);
} else {
    die("Data pembayaran tidak ditemukan.");
}
$stmt->close();

// Fungsi sederhana untuk terbilang (opsional, bisa dikembangkan)
function terbilangSederhana($angka) {
    $angka = abs(intval($angka));
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $temp = "";
    if ($angka < 12) {
        $temp = " " . $huruf[$angka];
    } else if ($angka < 20) {
        $temp = terbilangSederhana($angka - 10) . " Belas";
    } else if ($angka < 100) {
        $temp = terbilangSederhana($angka / 10) . " Puluh" . terbilangSederhana($angka % 10);
    } else if ($angka < 200) {
        $temp = " Seratus" . terbilangSederhana($angka - 100);
    } else if ($angka < 1000) {
        $temp = terbilangSederhana($angka / 100) . " Ratus" . terbilangSederhana($angka % 100);
    } else if ($angka < 2000) {
        $temp = " Seribu" . terbilangSederhana($angka - 1000);
    } else if ($angka < 1000000) {
        $temp = terbilangSederhana($angka / 1000) . " Ribu" . terbilangSederhana($angka % 1000);
    }     
    return $temp;
}
$jumlah_angka = preg_replace("/[^0-9]/", "", $data_bayaran['Jumlah']);
$terbilang = trim(terbilangSederhana($jumlah_angka)) . " Rupiah";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Pembayaran #<?= htmlspecialchars($data_bayaran['Id_Pembayaran']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; background-color: #fff; }
        .receipt-container { max-width: 800px; margin: 30px auto; padding: 30px; border: 2px solid #000; background-color: #fff; }
        .receipt-header { text-align: center; margin-bottom: 20px; }
        .receipt-header h3 { margin-bottom: 5px; font-weight: bold; }
        .receipt-header p { margin-bottom: 0; font-size: 0.9rem; }
        .receipt-details table { width: 100%; margin-bottom: 20px; }
        .receipt-details th, .receipt-details td { padding: 8px 0; vertical-align: top; }
        .receipt-details th { width: 30%; text-align: left; }
        .receipt-details td { width: 70%; }
        .amount-section { margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ccc; }
        .amount-section .total { font-size: 1.2rem; font-weight: bold; }
        .terbilang-section { font-style: italic; margin-top:5px; }
        .signature-section { margin-top: 50px; }
        .signature-section .signature-box { width: 200px; margin: 0 auto; text-align: center; }
        .signature-section .signature-line { border-bottom: 1px solid #000; height: 60px; margin-bottom: 5px; }
        .print-button-container { text-align: center; margin-top: 20px; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt-container { margin: 0; border: none; box-shadow: none; }
            .print-button-container { display: none; }
            .btn-back-container { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="btn-back-container mt-3 d-flex justify-content-between">
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Bayaran</a>
            <button onclick="window.print()" class="btn btn-primary print-button"><i class="bi bi-printer-fill"></i> Cetak Kuitansi</button>
        </div>

        <div class="receipt-container">
            <div class="receipt-header">
                <h3>KUITANSI PEMBAYARAN</h3>
                <p>SuruhSuruh.com - Semua Jadi Mudah</p>
            </div>

            <div class="receipt-info mb-4">
                <div class="row">
                    <div class="col-6">
                        <strong>No. Kuitansi:</strong> SS/INV/<?= date('Ymd') ?>/<?= htmlspecialchars($data_bayaran['Id_Pembayaran']) ?>
                    </div>
                    <div class="col-6 text-end">
                        <strong>Tanggal:</strong> <?= htmlspecialchars(date('d F Y', strtotime($data_bayaran['Tanggal']))) ?>
                    </div>
                </div>
            </div>

            <hr>

            <div class="receipt-details">
                <table>
                    <tr>
                        <th>Telah Diterima Dari</th>
                        <td>: <?= htmlspecialchars($nama_pengguna) ?></td>
                    </tr>
                    <tr>
                        <th>Untuk Pembayaran</th>
                        <td>: Pembayaran Layanan Aplikasi SuruhSuruh.com</td>
                    </tr>
                    <tr>
                        <th>Jumlah</th>
                        <td class="total">: Rp <?= number_format(preg_replace("/[^0-9]/", "", $data_bayaran['Jumlah']), 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th>Terbilang</th>
                        <td class="terbilang-section">: <?= htmlspecialchars($terbilang) ?></td>
                    </tr>
                </table>
            </div>

            <div class="signature-section">
                <div class="row">
                    <div class="col-6">
                        </div>
                    <div class="col-6 text-center">
                        <p>Bandung, <?= htmlspecialchars(date('d F Y')) ?></p>
                        <p>Penerima,</p>
                        <div class="signature-line"></div>
                        <p>( SuruhSuruh.com )</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></script> </body>
</html>