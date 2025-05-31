<?php
session_start();
require '../../config/database.php';

// Query mengambil semua kolom dari profit dan detail_pesanan yang relevan, serta data pesanan terkait
$data_profit_query = mysqli_query($koneksi, "
  SELECT pr.*, 
         dp.Id_DetailPesanan AS Detail_Id_DetailPesanan, 
         dp.Id_Pesanan AS Detail_Id_Pesanan, 
         dp.Harga AS Detail_Harga, 
         dp.Jumlah AS Detail_Jumlah,
         p.Tanggal AS Pesanan_Tanggal,
         pg.Nama_Depan AS Pengguna_Nama_Depan, 
         pg.Nama_Belakang AS Pengguna_Nama_Belakang
  FROM profit pr
  LEFT JOIN detail_pesanan dp ON pr.Id_DetailPesanan = dp.Id_DetailPesanan
  LEFT JOIN pesanan p ON dp.Id_Pesanan = p.Id_Pesanan
  LEFT JOIN pengguna pg ON p.Id_Pengguna = pg.Id_pengguna
  ORDER BY pr.Id_Profit ASC
");

if (!$data_profit_query) {
    die("Error fetching profit data: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Profit</title>
    <link rel="stylesheet" href="../../assets/style.css"> <style>
        .detail-popup {
            display: none; 
            font-size: 90%;
            margin-top: 5px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
            line-height: 1.6;
        }
        .detail-popup.show {
            display: block;
        }
        .detail-popup strong { color: #333; min-width: 140px; display: inline-block;}
        .btn-lihat { padding: 2px 6px; font-size: 0.8em; margin-left: 5px; cursor: pointer;}
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 Data Profit</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Data Profit</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Profit</th>
                    <th>Detail Pesanan (ID)</th>
                    <th>Tanggal Profit</th>
                    <th>Total Profit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_profit_query) > 0): ?>
                    <?php while ($pr = mysqli_fetch_assoc($data_profit_query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($pr['Id_Profit']) ?></td>
                        <td>
                            <?php if ($pr['Id_DetailPesanan']): ?>
                                ID Detail: <?= htmlspecialchars($pr['Detail_Id_DetailPesanan']) ?>
                                <button onclick="toggleDetail('detail_pesanan_profit_<?= $pr['Id_Profit'] ?>')" class="btn-lihat">Lihat Detail Pesanan</button>
                                <div id="detail_pesanan_profit_<?= $pr['Id_Profit'] ?>" class="detail-popup">
                                    <strong>ID Detail Pesanan:</strong> <?= htmlspecialchars($pr['Detail_Id_DetailPesanan']) ?><br>
                                    <strong>ID Pesanan Induk:</strong> <?= htmlspecialchars($pr['Detail_Id_Pesanan']) ?><br>
                                    <strong>Harga Satuan:</strong> Rp <?= htmlspecialchars(number_format($pr['Detail_Harga'], 2, ',', '.')) ?><br>
                                    <strong>Jumlah Item:</strong> <?= htmlspecialchars($pr['Detail_Jumlah']) ?><br>
                                    <strong>Subtotal Item:</strong> Rp <?= htmlspecialchars(number_format($pr['Detail_Harga'] * $pr['Detail_Jumlah'], 2, ',', '.')) ?><br>
                                    <hr>
                                    <strong>Tanggal Pesanan Induk:</strong> <?= !empty($pr['Pesanan_Tanggal']) ? htmlspecialchars(date('d M Y', strtotime($pr['Pesanan_Tanggal']))) : "<span class='text-muted'>-</span>" ?><br>
                                    <strong>Pemesan:</strong> <?= !empty($pr['Pengguna_Nama_Depan']) ? htmlspecialchars($pr['Pengguna_Nama_Depan'] . ' ' . $pr['Pengguna_Nama_Belakang']) : "<span class='text-muted'>-</span>" ?><br>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= !empty($pr['Tanggal_Profit']) ? htmlspecialchars(date('d M Y', strtotime($pr['Tanggal_Profit']))) : "<span class='text-muted'>Belum Diatur</span>" ?>
                        </td>
                        <td><?= htmlspecialchars($pr['total_Profit']) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $pr['Id_Profit'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $pr['Id_Profit'] ?>" class="btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data profit ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Tidak ada data profit.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function toggleDetail(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.classList.toggle('show');
        }
    }
    </script>
</body>
</html>