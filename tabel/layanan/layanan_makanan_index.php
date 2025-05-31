<?php
session_start();
require '../../config/database.php';

$data_layanan_makanan = mysqli_query($koneksi, "
    SELECT l.Id_Layanan, l.Nama_Layanan AS Nama_Layanan_Umum, 
           lm.Restoran, lm.Nama_Kurir_Default, lm.Kendaraan_Kurir_Default, lm.Tarif_Pengiriman_Makanan 
    FROM layanan l
    JOIN layanan_makanan lm ON l.Id_Layanan = lm.Id_Layanan
    WHERE l.Jenis_Layanan = 'Makanan'
    ORDER BY l.Nama_Layanan ASC
");
if (!$data_layanan_makanan) {
    die("Error fetching layanan_makanan data: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Layanan Makanan</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <h2>🍔 Detail Layanan Makanan</h2>
         <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="layanan_makanan_tambah.php" class="btn btn-primary">Tambah Detail Layanan Makanan Baru</a>
        <a href="index.php" class="btn">Kembali ke Manajemen Layanan Utama</a>
        <a href="../../dashboard.php" class="btn">Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Layanan</th>
                    <th>Nama Layanan (Umum)</th>
                    <th>Restoran/Brand</th>
                    <th>Kurir Default</th>
                    <th>Kendaraan Default</th>
                    <th>Tarif Pengiriman</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_layanan_makanan) > 0): ?>
                    <?php while ($lm = mysqli_fetch_assoc($data_layanan_makanan)): ?>
                    <tr>
                        <td><?= htmlspecialchars($lm['Id_Layanan']) ?></td>
                        <td><?= htmlspecialchars($lm['Nama_Layanan_Umum']) ?></td>
                        <td><?= htmlspecialchars($lm['Restoran']) ?></td>
                        <td><?= htmlspecialchars($lm['Nama_Kurir_Default']) ?></td>
                        <td><?= htmlspecialchars($lm['Kendaraan_Kurir_Default']) ?></td>
                        <td><?= htmlspecialchars($lm['Tarif_Pengiriman_Makanan']) ?></td>
                        <td>
                            <a href="layanan_makanan_edit.php?id_layanan=<?= $lm['Id_Layanan'] ?>" class="btn btn-edit">Edit Detail</a>
                            <a href="layanan_makanan_hapus.php?id_layanan=<?= $lm['Id_Layanan'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin ingin menghapus detail layanan makanan ini? Ini hanya menghapus detail spesifik, bukan layanan utamanya.')">Hapus Detail</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Tidak ada data detail layanan makanan. Tambahkan layanan utama berjenis 'Makanan' terlebih dahulu, lalu tambahkan detailnya di sini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>