<?php
session_start();
require '../../config/database.php';
$error_message = '';

$id_layanan_edit = isset($_GET['id_layanan']) ? intval($_GET['id_layanan']) : 0;

if ($id_layanan_edit <= 0) {
    $_SESSION['error_message'] = "ID Layanan tidak valid untuk edit detail makanan.";
    header("Location: layanan_makanan_index.php");
    exit;
}

// Fetch current data including Nama_Layanan from parent table
$query_data = mysqli_query($koneksi, "
    SELECT l.Nama_Layanan AS Nama_Layanan_Umum, lm.* FROM layanan_makanan lm
    JOIN layanan l ON lm.Id_Layanan = l.Id_Layanan
    WHERE lm.Id_Layanan = $id_layanan_edit AND l.Jenis_Layanan = 'Makanan'
");
$data_spec = mysqli_fetch_assoc($query_data);

if (!$data_spec) {
    $_SESSION['error_message'] = "Detail layanan makanan tidak ditemukan atau ID layanan bukan jenis makanan.";
    header("Location: layanan_makanan_index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Id_Layanan tidak diubah karena itu adalah PK dan FK penghubung
    $restoran = mysqli_real_escape_string($koneksi, $_POST['restoran']);
    $kurir_default = mysqli_real_escape_string($koneksi, $_POST['nama_kurir_default']);
    $kendaraan_default = mysqli_real_escape_string($koneksi, $_POST['kendaraan_kurir_default']);
    $tarif_kirim = mysqli_real_escape_string($koneksi, $_POST['tarif_pengiriman_makanan']);

    if (empty($restoran)) {
        $error_message = "Nama Restoran wajib diisi.";
    } else {
        $query_update_spec = "UPDATE layanan_makanan SET 
                                Restoran = '$restoran', 
                                Nama_Kurir_Default = '$kurir_default', 
                                Kendaraan_Kurir_Default = '$kendaraan_default', 
                                Tarif_Pengiriman_Makanan = '$tarif_kirim'
                              WHERE Id_Layanan = $id_layanan_edit";
            
        if (mysqli_query($koneksi, $query_update_spec)) {
            $_SESSION['success_message'] = "Detail layanan makanan berhasil diperbarui!";
            header("Location: layanan_makanan_index.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui detail layanan makanan: " . mysqli_error($koneksi);
        }
    }
    // Re-populate on error
    $data_spec['Restoran'] = $restoran;
    $data_spec['Nama_Kurir_Default'] = $kurir_default;
    $data_spec['Kendaraan_Kurir_Default'] = $kendaraan_default;
    $data_spec['Tarif_Pengiriman_Makanan'] = $tarif_kirim;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Detail Layanan Makanan</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<div class="container">
    <h2>✏️ Edit Detail Layanan Makanan untuk: <?= htmlspecialchars($data_spec['Nama_Layanan_Umum']) ?> (ID Layanan: <?= $id_layanan_edit ?>)</h2>
    <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Layanan Induk (ID: <?= $id_layanan_edit ?>):</label>
            <input type="text" value="<?= htmlspecialchars($data_spec['Nama_Layanan_Umum']) ?>" disabled readonly style="background-color: #e9ecef;">
        </div>

        <div class="form-group">
            <label for="restoran">Nama Restoran/Brand:</label>
            <input type="text" name="restoran" id="restoran" value="<?= htmlspecialchars($data_spec['Restoran']) ?>" required>
        </div>
        <div class="form-group">
            <label for="nama_kurir_default">Nama Kurir Default (Opsional):</label>
            <input type="text" name="nama_kurir_default" id="nama_kurir_default" value="<?= htmlspecialchars($data_spec['Nama_Kurir_Default']) ?>">
        </div>
        <div class="form-group">
            <label for="kendaraan_kurir_default">Kendaraan Kurir Default (Opsional):</label>
            <input type="text" name="kendaraan_kurir_default" id="kendaraan_kurir_default" value="<?= htmlspecialchars($data_spec['Kendaraan_Kurir_Default']) ?>">
        </div>
        <div class="form-group">
            <label for="tarif_pengiriman_makanan">Tarif Pengiriman (Opsional):</label>
            <input type="text" name="tarif_pengiriman_makanan" id="tarif_pengiriman_makanan" value="<?= htmlspecialchars($data_spec['Tarif_Pengiriman_Makanan']) ?>">
        </div>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="layanan_makanan_index.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>