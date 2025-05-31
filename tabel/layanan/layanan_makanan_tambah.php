<?php
session_start();
require '../../config/database.php';
$error_message = '';

// Ambil layanan dari tabel 'layanan' yang berjenis 'Makanan' 
// dan BELUM memiliki entri di 'layanan_makanan' (tabel anak)
$layanan_induk_list = mysqli_query($koneksi, "
    SELECT l.Id_Layanan, l.Nama_Layanan 
    FROM layanan l
    LEFT JOIN layanan_makanan lm_spec ON l.Id_Layanan = lm_spec.Id_Layanan
    WHERE l.Jenis_Layanan = 'Makanan' AND lm_spec.Id_Layanan IS NULL
    ORDER BY l.Nama_Layanan ASC
");
if (!$layanan_induk_list) {
    die("Error fetching layanan induk (Makanan) list: " . mysqli_error($koneksi));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_layanan = intval($_POST['id_layanan']);
    $restoran = mysqli_real_escape_string($koneksi, $_POST['restoran']);
    $kurir_default = mysqli_real_escape_string($koneksi, $_POST['nama_kurir_default']);
    $kendaraan_default = mysqli_real_escape_string($koneksi, $_POST['kendaraan_kurir_default']);
    $tarif_kirim = mysqli_real_escape_string($koneksi, $_POST['tarif_pengiriman_makanan']);

    if (empty($id_layanan) || empty($restoran)) {
        $error_message = "Layanan Induk dan Nama Restoran wajib diisi.";
    } else {
        // Pastikan lagi bahwa Id_Layanan ini benar-benar belum ada di layanan_makanan (double check)
        $check_exist_query = "SELECT Id_Layanan FROM layanan_makanan WHERE Id_Layanan = $id_layanan";
        $check_exist_result = mysqli_query($koneksi, $check_exist_query);
        if (mysqli_num_rows($check_exist_result) > 0) {
            $error_message = "Detail untuk layanan ini sudah ada. Pilih layanan lain atau edit yang sudah ada.";
        } else {
            // Pastikan juga Id_Layanan yang dipilih adalah benar berjenis 'Makanan' di tabel induk
            $check_jenis_query = mysqli_query($koneksi, "SELECT Jenis_Layanan FROM layanan WHERE Id_Layanan = $id_layanan");
            $jenis_layanan_db = "";
            if ($check_jenis_query && mysqli_num_rows($check_jenis_query) > 0) {
                $jenis_layanan_db = mysqli_fetch_assoc($check_jenis_query)['Jenis_Layanan'];
            }

            if ($jenis_layanan_db !== 'Makanan') {
                $error_message = "Layanan Induk yang dipilih bukan berjenis 'Makanan'.";
            } else {
                $query_insert_spec = "INSERT INTO layanan_makanan 
                                        (Id_Layanan, Restoran, Nama_Kurir_Default, Kendaraan_Kurir_Default, Tarif_Pengiriman_Makanan)
                                      VALUES 
                                        ('$id_layanan', '$restoran', '$kurir_default', '$kendaraan_default', '$tarif_kirim')";
                
                if (mysqli_query($koneksi, $query_insert_spec)) {
                    $_SESSION['success_message'] = "Detail layanan makanan berhasil ditambahkan!";
                    header("Location: layanan_makanan_index.php");
                    exit;
                } else {
                    $error_message = "Gagal menyimpan detail layanan makanan: " . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Detail Layanan Makanan</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<div class="container">
    <h2>➕ Tambah Detail Layanan Makanan</h2>
    <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="id_layanan">Pilih Layanan Induk (Jenis: Makanan):</label>
            <select name="id_layanan" id="id_layanan" required>
                <option value="">-- Pilih Layanan Induk --</option>
                <?php while($l = mysqli_fetch_assoc($layanan_induk_list)): ?>
                    <option value="<?= $l['Id_Layanan'] ?>" <?= (isset($_POST['id_layanan']) && $_POST['id_layanan'] == $l['Id_Layanan']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['Nama_Layanan']) ?> (ID: <?= $l['Id_Layanan'] ?>)
                    </option>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($layanan_induk_list) == 0 && !(isset($_POST['id_layanan'])) ): ?>
                    <option value="" disabled>Tidak ada layanan (Jenis: Makanan) yang belum memiliki detail.</option>
                <?php endif; ?>
            </select>
            <small>Layanan yang muncul di sini adalah layanan dari "Manajemen Layanan Utama" yang berjenis "Makanan" dan belum memiliki detail spesifik.</small>
        </div>

        <div class="form-group">
            <label for="restoran">Nama Restoran/Brand:</label>
            <input type="text" name="restoran" id="restoran" value="<?= isset($_POST['restoran']) ? htmlspecialchars($_POST['restoran']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="nama_kurir_default">Nama Kurir Default (Opsional):</label>
            <input type="text" name="nama_kurir_default" id="nama_kurir_default" value="<?= isset($_POST['nama_kurir_default']) ? htmlspecialchars($_POST['nama_kurir_default']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="kendaraan_kurir_default">Kendaraan Kurir Default (Opsional):</label>
            <input type="text" name="kendaraan_kurir_default" id="kendaraan_kurir_default" value="<?= isset($_POST['kendaraan_kurir_default']) ? htmlspecialchars($_POST['kendaraan_kurir_default']) : '' ?>">
        </div>
        <div class="form-group">
            <label for="tarif_pengiriman_makanan">Tarif Pengiriman (Opsional):</label>
            <input type="text" name="tarif_pengiriman_makanan" id="tarif_pengiriman_makanan" placeholder="cth: RP. 5.000/KM" value="<?= isset($_POST['tarif_pengiriman_makanan']) ? htmlspecialchars($_POST['tarif_pengiriman_makanan']) : '' ?>">
        </div>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Detail Makanan</button>
        <a href="layanan_makanan_index.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>