<?php
session_start();
require '../../config/database.php';

// Query mengambil data pekerja dan data perusahaan terkait
$data_pekerja_query = mysqli_query($koneksi, "
  SELECT pk.*, 
         p.Nama AS Perusahaan_Nama,
         p.CEO AS Perusahaan_CEO,
         p.Kota AS Perusahaan_Kota,
         p.Jalan AS Perusahaan_Jalan,
         p.Kode_Pos AS Perusahaan_Kode_Pos
  FROM pekerja pk
  JOIN perusahaan p ON pk.Id_Perusahaan = p.Id_Perusahaan
  ORDER BY pk.Id_Pekerja ASC
");

if (!$data_pekerja_query) {
    die("Error fetching pekerja data: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pekerja</title>
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
        <h2>👥 Data Pekerja</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Data Pekerja</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Pekerja</th>
                    <th>Nama Lengkap</th>
                    <th>Tanggal Lahir</th>
                    <th>No. Telepon</th>
                    <th>Perusahaan (ID)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_pekerja_query) > 0): ?>
                    <?php while ($pk = mysqli_fetch_assoc($data_pekerja_query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($pk['Id_Pekerja']) ?></td>
                        <td><?= htmlspecialchars(trim($pk['Nama_Depan'] . ' ' . $pk['Nama_Tengah'] . ' ' . $pk['Nama_Belakang'])) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($pk['Tanggal_lahir']))) ?></td>
                        <td><?= htmlspecialchars($pk['NO_Telp']) ?></td>
                        <td>
                            ID: <?= htmlspecialchars($pk['Id_Perusahaan']) ?> - <?= htmlspecialchars($pk['Perusahaan_Nama']) ?>
                            <button onclick="toggleDetail('detail_perusahaan_pekerja_<?= $pk['Id_Pekerja'] ?>_<?= $pk['Id_Perusahaan'] ?>')" class="btn-lihat">Lihat Detail Perusahaan</button>
                            <div id="detail_perusahaan_pekerja_<?= $pk['Id_Pekerja'] ?>_<?= $pk['Id_Perusahaan'] ?>" class="detail-popup">
                                <strong>ID Perusahaan:</strong> <?= htmlspecialchars($pk['Id_Perusahaan']) ?><br>
                                <strong>Nama Perusahaan:</strong> <?= htmlspecialchars($pk['Perusahaan_Nama']) ?><br>
                                <strong>CEO:</strong> <?= htmlspecialchars($pk['Perusahaan_CEO']) ?><br>
                                <strong>Kota:</strong> <?= htmlspecialchars($pk['Perusahaan_Kota']) ?><br>
                                <strong>Jalan:</strong> <?= htmlspecialchars($pk['Perusahaan_Jalan']) ?><br>
                                <strong>Kode Pos:</strong> <?= htmlspecialchars($pk['Perusahaan_Kode_Pos']) ?><br>
                            </div>
                        </td>
                        <td>
                            <a href="edit.php?id_pekerja=<?= $pk['Id_Pekerja'] ?>&id_perusahaan=<?= $pk['Id_Perusahaan'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id_pekerja=<?= $pk['Id_Pekerja'] ?>&id_perusahaan=<?= $pk['Id_Perusahaan'] ?>" class="btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data pekerja ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada data pekerja.</td>
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