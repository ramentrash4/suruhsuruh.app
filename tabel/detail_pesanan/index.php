<?php
session_start();
require '../../config/database.php';

// Query mengambil semua kolom dari pesanan dan pengguna yang relevan
$data_detail_pesanan = mysqli_query($koneksi, "
  SELECT dp.*, 
         p.Id_Pesanan AS Pesanan_Id_Pesanan, 
         p.Id_Pengguna AS Pesanan_Id_Pengguna, 
         p.Id_Pembayaran AS Pesanan_Id_Pembayaran, 
         p.Id_Layanan AS Pesanan_Id_Layanan, 
         p.Tanggal AS Pesanan_Tanggal,
         pg.Nama_Depan AS Pengguna_Nama_Depan, 
         pg.Nama_Tengah AS Pengguna_Nama_Tengah, 
         pg.Nama_Belakang AS Pengguna_Nama_Belakang, 
         pg.Email AS Pengguna_Email,
         pg.Alamat AS Pengguna_Alamat,
         lyn.Nama_Layanan AS Layanan_Nama,
         lyn.Jenis_Layanan AS Layanan_Jenis,
         byr.Jumlah AS Pembayaran_Jumlah,
         byr.Tanggal AS Pembayaran_Tanggal_Bayar
  FROM detail_pesanan dp
  JOIN pesanan p ON dp.Id_Pesanan = p.Id_Pesanan
  LEFT JOIN pengguna pg ON p.Id_Pengguna = pg.Id_pengguna
  LEFT JOIN layanan lyn ON p.Id_Layanan = lyn.Id_Layanan
  LEFT JOIN bayaran byr ON p.Id_Pembayaran = byr.Id_Pembayaran
  ORDER BY dp.Id_DetailPesanan ASC
");

if (!$data_detail_pesanan) {
    die("Error fetching detail_pesanan data: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Detail Pesanan</title>
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
        .detail-popup strong { color: #333; min-width: 120px; display: inline-block;}
        .btn-lihat { padding: 2px 6px; font-size: 0.8em; margin-left: 5px; cursor: pointer;}
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🧾 Data Detail Pesanan</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Detail Pesanan</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Detail</th>
                    <th>Info Pesanan Induk (ID)</th>
                    <th>Harga Satuan</th>
                    <th>Jumlah Item</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_detail_pesanan) > 0): ?>
                    <?php while ($dp = mysqli_fetch_assoc($data_detail_pesanan)): ?>
                    <tr>
                        <td><?= htmlspecialchars($dp['Id_DetailPesanan']) ?></td>
                        <td>
                            ID Pesanan: <?= htmlspecialchars($dp['Pesanan_Id_Pesanan']) ?>
                            <button onclick="toggleDetail('detail_pesanan_info_<?= $dp['Id_DetailPesanan'] ?>')" class="btn-lihat">Lihat Detail Pesanan</button>
                            <div id="detail_pesanan_info_<?= $dp['Id_DetailPesanan'] ?>" class="detail-popup">
                                <strong>ID Pesanan:</strong> <?= htmlspecialchars($dp['Pesanan_Id_Pesanan']) ?><br>
                                <strong>Tanggal Pesanan:</strong> <?= htmlspecialchars(date('d M Y', strtotime($dp['Pesanan_Tanggal']))) ?><br>
                                <hr>
                                <strong>Pengguna:</strong><br>
                                &nbsp;&nbsp;&nbsp;<strong>ID Pengguna:</strong> <?= $dp['Pesanan_Id_Pengguna'] ? htmlspecialchars($dp['Pesanan_Id_Pengguna']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Nama:</strong> <?= !empty($dp['Pengguna_Nama_Depan']) ? htmlspecialchars($dp['Pengguna_Nama_Depan'] . ' ' . $dp['Pengguna_Nama_Tengah'] . ' ' . $dp['Pengguna_Nama_Belakang']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Email:</strong> <?= !empty($dp['Pengguna_Email']) ? htmlspecialchars($dp['Pengguna_Email']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Alamat:</strong> <?= !empty($dp['Pengguna_Alamat']) ? htmlspecialchars($dp['Pengguna_Alamat']) : "<span class='text-muted'>-</span>" ?><br>
                                <hr>
                                <strong>Pembayaran:</strong><br>
                                &nbsp;&nbsp;&nbsp;<strong>ID Pembayaran:</strong> <?= $dp['Pesanan_Id_Pembayaran'] ? htmlspecialchars($dp['Pesanan_Id_Pembayaran']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Jumlah Bayar:</strong> <?= !empty($dp['Pembayaran_Jumlah']) ? htmlspecialchars($dp['Pembayaran_Jumlah']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Tgl Bayar:</strong> <?= !empty($dp['Pembayaran_Tanggal_Bayar']) ? htmlspecialchars(date('d M Y', strtotime($dp['Pembayaran_Tanggal_Bayar']))) : "<span class='text-muted'>-</span>" ?><br>
                                <hr>
                                <strong>Layanan:</strong><br>
                                &nbsp;&nbsp;&nbsp;<strong>ID Layanan:</strong> <?= $dp['Pesanan_Id_Layanan'] ? htmlspecialchars($dp['Pesanan_Id_Layanan']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Nama Layanan:</strong> <?= !empty($dp['Layanan_Nama']) ? htmlspecialchars($dp['Layanan_Nama']) : "<span class='text-muted'>-</span>" ?><br>
                                &nbsp;&nbsp;&nbsp;<strong>Jenis Layanan:</strong> <?= !empty($dp['Layanan_Jenis']) ? htmlspecialchars($dp['Layanan_Jenis']) : "<span class='text-muted'>-</span>" ?><br>
                            </div>
                        </td>
                        <td>Rp <?= htmlspecialchars(number_format($dp['Harga'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars($dp['Jumlah']) ?></td>
                        <td>Rp <?= htmlspecialchars(number_format($dp['Harga'] * $dp['Jumlah'], 2, ',', '.')) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $dp['Id_DetailPesanan'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $dp['Id_DetailPesanan'] ?>" class="btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus detail pesanan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada data detail pesanan.</td>
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