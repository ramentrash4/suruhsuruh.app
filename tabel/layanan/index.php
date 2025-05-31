<?php
session_start();
require '../../config/database.php';

// Query utama untuk mengambil data layanan utama
$data_layanan_query = mysqli_query($koneksi, "
  SELECT *
  FROM layanan
  ORDER BY Id_Layanan ASC
");

if (!$data_layanan_query) {
    die("Error fetching layanan data: " . mysqli_error($koneksi));
}

// Fungsi untuk mengambil mitra yang terikat dengan layanan
function getMitraTerkait($id_layanan, $koneksi) {
    $mitra_terkait = [];
    $query = "SELECT m.Id_Mitra, m.Nama_Mitra, m.Spesialis_Mitra 
              FROM mitra m
              JOIN terikat t ON m.Id_Mitra = t.Id_Mitra
              WHERE t.Id_Layanan = " . intval($id_layanan) . "
              ORDER BY m.Nama_Mitra ASC";
    $result = mysqli_query($koneksi, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $mitra_terkait[] = $row;
        }
    }
    return $mitra_terkait;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Layanan Utama</title>
    <link rel="stylesheet" href="../../assets/style.css"> 
    <style>
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
        .sub-menu-layanan { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .sub-menu-layanan h3 { margin-bottom: 15px; }
        .sub-menu-layanan ul { list-style-type: none; padding: 0; }
        .sub-menu-layanan li { margin-bottom: 8px; }
        .sub-menu-layanan a { display: inline-block; padding: 8px 12px; background-color: #e9ecef; border-radius: 4px; text-decoration: none; color: #212529; }
        .sub-menu-layanan a:hover { background-color: #dee2e6; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🛠️ Data Layanan Utama</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <a href="tambah.php" class="btn btn-primary">Tambah Layanan Utama Baru</a>
        <a href="../../dashboard.php" class="btn">Kembali ke Dashboard</a>
        <br><br>

        <table border="1">
            <thead>
                <tr>
                    <th>ID Layanan</th>
                    <th>Nama Layanan</th>
                    <th>Jenis Layanan</th>
                    <th>Deskripsi Umum</th>
                    <th>Status</th>
                    <th>Mitra Penyedia</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data_layanan_query) > 0): ?>
                    <?php while ($l = mysqli_fetch_assoc($data_layanan_query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['Id_Layanan']) ?></td>
                        <td><?= htmlspecialchars($l['Nama_Layanan']) ?></td>
                        <td><?= htmlspecialchars($l['Jenis_Layanan']) ?></td>
                        <td><?= nl2br(htmlspecialchars($l['Deskripsi_Umum'])) ?></td>
                        <td><?= $l['Status_Aktif'] ? 'Aktif' : 'Tidak Aktif' ?></td>
                        <td>
                            <?php
                            $mitra_penyedia = getMitraTerkait($l['Id_Layanan'], $koneksi);
                            if (!empty($mitra_penyedia)) {
                                echo count($mitra_penyedia) . " mitra ";
                                echo "<button onclick=\"toggleDetail('detail_mitra_layanan_" . $l['Id_Layanan'] . "')\" class=\"btn-lihat\">Lihat</button>";
                                echo "<div id=\"detail_mitra_layanan_" . $l['Id_Layanan'] . "\" class=\"detail-popup\">";
                                echo "<strong>Mitra Penyedia Layanan Ini:</strong><ul>";
                                foreach ($mitra_penyedia as $mitra) {
                                    echo "<li>" . htmlspecialchars($mitra['Nama_Mitra']) . " (ID: " . htmlspecialchars($mitra['Id_Mitra']) . ", Spesialis: " . htmlspecialchars($mitra['Spesialis_Mitra']) . ")</li>";
                                }
                                echo "</ul></div>";
                            } else {
                                echo "<span class='text-muted'>Belum ada</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $l['Id_Layanan'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $l['Id_Layanan'] ?>" class="btn btn-hapus" onclick="return confirm('PERHATIAN: Menghapus layanan utama ini juga akan menghapus detail spesifiknya (jika ON DELETE CASCADE aktif) dan semua keterkaitannya dengan mitra & pesanan. Yakin ingin menghapus?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Tidak ada data layanan utama.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="sub-menu-layanan">
            <h3>Manajemen Detail Layanan Spesifik</h3>
            <p><small>Catatan: Setelah membuat "Layanan Utama" dengan jenis tertentu, tambahkan detail spesifiknya melalui menu CRUD di bawah ini.</small></p>
            <ul>
                <li><a href="layanan_makanan_index.php">🍔 CRUD Detail Layanan Makanan</a></li>
                <li><a href="layanan_kesehatan_index.php">⚕️ CRUD Detail Layanan Kesehatan</a></li>
                <li><a href="layanan_pelayanan_rumah_index.php">🏠 CRUD Detail Layanan Pelayanan Rumah</a></li>
            </ul>
        </div>
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