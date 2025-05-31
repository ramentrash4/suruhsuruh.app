<?php
session_start();
require '../../config/database.php';

// 1. Ambil data perusahaan (asumsi hanya ada satu perusahaan utama, misal dengan ID tetap atau ID terkecil)
// Jika Anda selalu ingin mengedit perusahaan dengan ID=1:
$id_perusahaan_utama = 1; // Tentukan ID perusahaan utama Anda
$data_perusahaan_query = mysqli_query($koneksi, "
  SELECT *
  FROM perusahaan
  WHERE Id_Perusahaan = $id_perusahaan_utama 
  LIMIT 1 
");

if (!$data_perusahaan_query) {
    die("Error fetching perusahaan data: " . mysqli_error($koneksi));
}
$perusahaan = mysqli_fetch_assoc($data_perusahaan_query);

// 2. Hitung total profit keseluruhan dari tabel profit
//    PENTING: Pastikan kolom total_Profit di tabel profit sudah numerik (DECIMAL atau INT)
//    atau sesuaikan parsing string di bawah ini jika masih VARCHAR 'RP. xxx.xxx'.
$total_profit_keseluruhan_query = mysqli_query($koneksi, "
    SELECT SUM(
        -- Mengganti 'RP. ' dan '.' lalu cast ke DECIMAL. Sesuaikan jika format berbeda.
        -- Jika kolom total_Profit sudah DECIMAL, cukup SUM(total_Profit)
        CAST(REPLACE(REPLACE(total_Profit, 'RP. ', ''), '.', '') AS DECIMAL(15,2))
    ) AS Grand_Total_Profit 
    FROM profit
");

$grand_total_profit = 0;
if ($total_profit_keseluruhan_query && mysqli_num_rows($total_profit_keseluruhan_query) > 0) {
    $profit_row = mysqli_fetch_assoc($total_profit_keseluruhan_query);
    $grand_total_profit = $profit_row['Grand_Total_Profit'] ? $profit_row['Grand_Total_Profit'] : 0;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Perusahaan</title>
    <link rel="stylesheet" href="../../assets/style.css"> <style>
        .info-box { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;}
        .info-box h3 { margin-top: 0; }
        .info-box p strong { min-width: 200px; display: inline-block;}
    </style>
</head>
<body>
    <div class="container">
        <h2>🏢 Data Perusahaan Utama</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <?php if ($perusahaan): ?>
            <div class="info-box">
                <h3>Informasi Perusahaan</h3>
                <p><strong>ID Perusahaan:</strong> <?= htmlspecialchars($perusahaan['Id_Perusahaan']) ?></p>
                <p><strong>Nama:</strong> <?= htmlspecialchars($perusahaan['Nama']) ?></p>
                <p><strong>CEO:</strong> <?= htmlspecialchars($perusahaan['CEO']) ?></p>
                <p><strong>Kota:</strong> <?= htmlspecialchars($perusahaan['Kota']) ?></p>
                <p><strong>Jalan:</strong> <?= htmlspecialchars($perusahaan['Jalan']) ?></p>
                <p><strong>Kode Pos:</strong> <?= htmlspecialchars($perusahaan['Kode_Pos']) ?></p>
                <hr>
                <p><strong>Total Akumulasi Profit Sistem:</strong> Rp <?= htmlspecialchars(number_format($grand_total_profit, 2, ',', '.')) ?></p>
                <br>
                <a href="edit.php?id=<?= $perusahaan['Id_Perusahaan'] ?>" class="btn btn-edit">Edit Data Perusahaan</a>
                </div>
        <?php else: ?>
            <p style="text-align:center;">Data perusahaan utama belum ada. 
                Silakan <a href="tambah.php">tambahkan data perusahaan utama</a> terlebih dahulu.
            </p>
        <?php endif; ?>
        
    </div>
</body>
</html>