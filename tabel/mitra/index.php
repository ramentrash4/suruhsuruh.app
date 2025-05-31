<?php
session_start();
require '../../config/database.php';

// Query utama untuk mengambil data mitra dan perusahaan terkait
$data_mitra_query = mysqli_query($koneksi, "
  SELECT m.*, 
         p.Nama AS Perusahaan_Nama,
         p.CEO AS Perusahaan_CEO,
         p.Kota AS Perusahaan_Kota,
         p.Jalan AS Perusahaan_Jalan,
         p.Kode_Pos AS Perusahaan_Kode_Pos
  FROM mitra m
  LEFT JOIN perusahaan p ON m.Id_Perusahaan = p.Id_Perusahaan
  ORDER BY m.Id_Mitra ASC
");

if (!$data_mitra_query) {
    die("Error fetching mitra data: " . mysqli_error($koneksi));
}

// Fungsi untuk mengambil layanan yang terikat dengan mitra
function getLayananTerkait($id_mitra, $koneksi) {
    $layanan_terkait = [];
    $query = "SELECT l.Id_Layanan, l.Nama_Layanan, l.Jenis_Layanan 
              FROM layanan l
              JOIN terikat t ON l.Id_Layanan = t.Id_Layanan
              WHERE t.Id_Mitra = " . intval($id_mitra) . "
              ORDER BY l.Nama_Layanan ASC";
    $result = mysqli_query($koneksi, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $layanan_terkait[] = $row;
        }
    }
    return $layanan_terkait;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mitra</title>
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
        .detail-popup ul { padding-left: 20px; margin-top: 5px;}
        .btn-lihat { padding: 2px 6px; font-size: 0.8em; margin-left: 5px; cursor: pointer;}
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🤝 Data Mitra</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Data Mitra</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Mitra</th>
                    <th>Nama Mitra</th>
                    <th>No. Telepon</th>
                    <th>Spesialis Mitra</th>
                    <th>Perusahaan Afiliasi</th>
                    <th>Layanan Terkait</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_mitra_query) > 0): ?>
                    <?php while ($m = mysqli_fetch_assoc($data_mitra_query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['Id_Mitra']) ?></td>
                        <td><?= htmlspecialchars($m['Nama_Mitra']) ?></td>
                        <td><?= htmlspecialchars($m['No_Telp']) ?></td>
                        <td><?= htmlspecialchars($m['Spesialis_Mitra']) ?></td>
                        <td>
                            <?php if ($m['Id_Perusahaan']): ?>
                                ID: <?= htmlspecialchars($m['Id_Perusahaan']) ?> - <?= htmlspecialchars($m['Perusahaan_Nama']) ?>
                                <button onclick="toggleDetail('detail_perusahaan_mitra_<?= $m['Id_Mitra'] ?>')" class="btn-lihat">Lihat Detail Perusahaan</button>
                                <div id="detail_perusahaan_mitra_<?= $m['Id_Mitra'] ?>" class="detail-popup">
                                    <strong>ID Perusahaan:</strong> <?= htmlspecialchars($m['Id_Perusahaan']) ?><br>
                                    <strong>Nama Perusahaan:</strong> <?= htmlspecialchars($m['Perusahaan_Nama']) ?><br>
                                    <strong>CEO:</strong> <?= htmlspecialchars($m['Perusahaan_CEO']) ?><br>
                                    <strong>Kota:</strong> <?= htmlspecialchars($m['Perusahaan_Kota']) ?><br>
                                    <strong>Jalan:</strong> <?= htmlspecialchars($m['Perusahaan_Jalan']) ?><br>
                                    <strong>Kode Pos:</strong> <?= htmlspecialchars($m['Perusahaan_Kode_Pos']) ?><br>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $layanan_mitra = getLayananTerkait($m['Id_Mitra'], $koneksi);
                            if (!empty($layanan_mitra)) {
                                echo count($layanan_mitra) . " layanan ";
                                echo "<button onclick=\"toggleDetail('detail_layanan_mitra_" . $m['Id_Mitra'] . "')\" class=\"btn-lihat\">Lihat</button>";
                                echo "<div id=\"detail_layanan_mitra_" . $m['Id_Mitra'] . "\" class=\"detail-popup\">";
                                echo "<strong>Layanan yang Disediakan:</strong><ul>";
                                foreach ($layanan_mitra as $layanan) {
                                    echo "<li>" . htmlspecialchars($layanan['Nama_Layanan']) . " (ID: " . htmlspecialchars($layanan['Id_Layanan']) . ", Jenis: " . htmlspecialchars($layanan['Jenis_Layanan']) . ")</li>";
                                }
                                echo "</ul></div>";
                            } else {
                                echo "<span class='text-muted'>Tidak ada</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $m['Id_Mitra'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $m['Id_Mitra'] ?>" class="btn btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data mitra ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Tidak ada data mitra.</td>
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