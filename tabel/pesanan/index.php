<?php
require_once __DIR__ . '/../../config.php';

// KOREKSI: Menggunakan ORDER BY ps.Id_Pesanan ASC
$data_pesanan = mysqli_query($koneksi, "
  SELECT ps.*, 
         pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Tengah AS Pengguna_Nama_Tengah, pg.Nama_Belakang AS Pengguna_Nama_Belakang, pg.Email AS Pengguna_Email, pg.Alamat AS Pengguna_Alamat,
         byr.Jumlah AS Pembayaran_Jumlah, byr.Tanggal AS Pembayaran_Tanggal_Bayar,
         lyn.Nama_Layanan AS Layanan_Nama, lyn.Jenis_Layanan AS Layanan_Jenis
  FROM pesanan ps
  LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
  LEFT JOIN bayaran byr ON ps.Id_Pembayaran = byr.Id_Pembayaran
  LEFT JOIN layanan lyn ON ps.Id_Layanan = lyn.Id_Layanan
  ORDER BY ps.Id_Pesanan ASC
");

if (!$data_pesanan) {
    die("Error fetching pesanan data: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pesanan</title>
    <link rel="stylesheet" href="../../assets/style.css"> <style>
        /* KOREKSI: CSS untuk toggleDetail menggunakan class 'show' */
        .detail-popup {
            display: none; /* Default disembunyikan */
            font-size: 90%;
            margin-top: 5px;
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .detail-popup.show {
            display: block; /* Tampilkan jika class 'show' ada */
        }
        .detail-popup strong { color: #333; }
        .btn-lihat { padding: 2px 6px; font-size: 0.8em; margin-left: 5px; cursor: pointer;}
    </style>
</head>
<body>
    <div class="container">
        <h2>📦 Data Pesanan</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Pesanan</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Pengguna</th>
                    <th>Pembayaran</th>
                    <th>Layanan</th>
                    <th>Tanggal Pesan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_pesanan) > 0): ?>
                    <?php while ($ps = mysqli_fetch_assoc($data_pesanan)): ?>
                    <tr>
                        <td><?= htmlspecialchars($ps['Id_Pesanan']) ?></td>
                        <td>
                            <?php if ($ps['Id_Pengguna']): ?>
                                ID: <?= htmlspecialchars($ps['Id_Pengguna']) ?> - <?= htmlspecialchars($ps['Pengguna_Nama_Depan'] . ' ' . $ps['Pengguna_Nama_Belakang']) ?>
                                <button onclick="toggleDetail('detail_pengguna_<?= $ps['Id_Pesanan'] ?>')" class="btn-lihat">Lihat</button>
                                <div id="detail_pengguna_<?= $ps['Id_Pesanan'] ?>" class="detail-popup">
                                    <strong>Nama:</strong> <?= htmlspecialchars($ps['Pengguna_Nama_Depan'] . ' ' . $ps['Pengguna_Nama_Tengah'] . ' ' . $ps['Pengguna_Nama_Belakang']) ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($ps['Pengguna_Email']) ?><br>
                                    <strong>Alamat:</strong> <?= htmlspecialchars($ps['Pengguna_Alamat']) ?>
                                </div>
                            <?php else: echo "<span class='text-muted'>N/A</span>"; endif; ?>
                        </td>
                        <td>
                            <?php if ($ps['Id_Pembayaran']): ?>
                                ID: <?= htmlspecialchars($ps['Id_Pembayaran']) ?>
                                <button onclick="toggleDetail('detail_pembayaran_<?= $ps['Id_Pesanan'] ?>')" class="btn-lihat">Lihat</button>
                                <div id="detail_pembayaran_<?= $ps['Id_Pesanan'] ?>" class="detail-popup">
                                    <strong>Jumlah:</strong> <?= htmlspecialchars($ps['Pembayaran_Jumlah']) ?><br>
                                    <strong>Tgl Bayar:</strong> <?= htmlspecialchars(date('d M Y', strtotime($ps['Pembayaran_Tanggal_Bayar']))) ?>
                                </div>
                            <?php else: echo "<span class='text-muted'>N/A</span>"; endif; ?>
                        </td>
                        <td>
                            <?php if ($ps['Id_Layanan']): ?>
                                ID: <?= htmlspecialchars($ps['Id_Layanan']) ?>
                                <button onclick="toggleDetail('detail_layanan_<?= $ps['Id_Pesanan'] ?>')" class="btn-lihat">Lihat</button>
                                <div id="detail_layanan_<?= $ps['Id_Pesanan'] ?>" class="detail-popup">
                                    <strong>Nama Layanan:</strong> <?= htmlspecialchars($ps['Layanan_Nama']) ?><br>
                                    <strong>Jenis Layanan:</strong> <?= htmlspecialchars($ps['Layanan_Jenis']) ?>
                                </div>
                            <?php else: echo "<span class='text-muted'>N/A</span>"; endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($ps['Tanggal']))) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $ps['Id_Pesanan'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $ps['Id_Pesanan'] ?>" class="btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data pesanan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada data pesanan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    // KOREKSI: JavaScript menggunakan classList.toggle
    function toggleDetail(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.classList.toggle('show');
        }
    }
    </script>
</body>
</html>