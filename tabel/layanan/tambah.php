<?php
session_start();
require '../../config/database.php';
$error_message = '';

// Untuk dropdown mitra (jika ingin mengaitkan mitra saat buat layanan baru)
$mitra_list_all_query = mysqli_query($koneksi, "SELECT * FROM mitra ORDER BY Nama_Mitra ASC");
if (!$mitra_list_all_query) {
    die("Error fetching mitra list: " . mysqli_error($koneksi));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan = mysqli_real_escape_string($koneksi, $_POST['nama_layanan']);
    $jenis_layanan = mysqli_real_escape_string($koneksi, $_POST['jenis_layanan']);
    $deskripsi_umum = mysqli_real_escape_string($koneksi, $_POST['deskripsi_umum']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $mitra_terpilih = isset($_POST['id_mitra']) ? $_POST['id_mitra'] : [];

    if (empty($nama_layanan) || empty($jenis_layanan)) {
        $error_message = "Nama Layanan dan Jenis Layanan wajib diisi.";
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            // 1. Insert ke tabel layanan (induk)
            $query_layanan_induk = "INSERT INTO layanan (Nama_Layanan, Jenis_Layanan, Deskripsi_Umum, Status_Aktif) 
                                    VALUES ('$nama_layanan', '$jenis_layanan', '$deskripsi_umum', '$status_aktif')";
            if (!mysqli_query($koneksi, $query_layanan_induk)) {
                throw new Exception("Gagal menyimpan data layanan utama: " . mysqli_error($koneksi));
            }
            $id_layanan_baru = mysqli_insert_id($koneksi);

            // 2. Insert ke tabel 'terikat' untuk relasi dengan mitra
            if (!empty($mitra_terpilih) && $id_layanan_baru > 0) {
                foreach ($mitra_terpilih as $id_mitra_single) {
                    $id_mitra_single = intval($id_mitra_single);
                    $query_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES ('$id_mitra_single', '$id_layanan_baru')";
                    if (!mysqli_query($koneksi, $query_terikat)) {
                        throw new Exception("Gagal mengaitkan layanan dengan mitra: " . mysqli_error($koneksi));
                    }
                }
            }

            mysqli_commit($koneksi);
            $_SESSION['success_message'] = "Layanan utama berhasil ditambahkan! Selanjutnya, tambahkan detail spesifiknya jika jenisnya bukan 'Lainnya' melalui menu CRUD layanan spesifik.";
            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error_message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Layanan Utama Baru</title>
    <link rel="stylesheet" href="../../assets/style.css">
     <style>
        .checkbox-group label { display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h2>➕ Tambah Layanan Utama Baru</h2>
    <p><small>Setelah membuat layanan utama, jika jenisnya Makanan, Kesehatan, atau Layanan Rumah, Anda perlu menambahkan detail spesifiknya melalui menu CRUD masing-masing jenis layanan.</small></p>

    <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form method="POST">
        <fieldset>
            <legend>Informasi Umum Layanan</legend>
            <div class="form-group">
                <label for="nama_layanan">Nama Layanan:</label>
                <input type="text" name="nama_layanan" id="nama_layanan" value="<?= isset($_POST['nama_layanan']) ? htmlspecialchars($_POST['nama_layanan']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="jenis_layanan">Jenis Layanan:</label>
                <select name="jenis_layanan" id="jenis_layanan" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Makanan" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                    <option value="Kesehatan" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Kesehatan') ? 'selected' : '' ?>>Kesehatan</option>
                    <option value="Layanan Rumah" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Layanan Rumah') ? 'selected' : '' ?>>Layanan Rumah</option>
                    <option value="Lainnya" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Lainnya') ? 'selected' : '' ?>>Lainnya (Umum)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="deskripsi_umum">Deskripsi Umum (Opsional):</label>
                <textarea name="deskripsi_umum" id="deskripsi_umum"><?= isset($_POST['deskripsi_umum']) ? htmlspecialchars($_POST['deskripsi_umum']) : '' ?></textarea>
            </div>
             <div class="form-group">
                <label for="status_aktif">
                    <input type="checkbox" name="status_aktif" id="status_aktif" value="1" checked> Aktifkan Layanan
                </label>
            </div>
        </fieldset>
        
        <fieldset style="margin-top: 15px;">
            <legend>Kaitkan dengan Mitra (Opsional)</legend>
            <div class="form-group">
                <label>Disediakan oleh Mitra (Pilih satu atau lebih):</label>
                <div class="checkbox-group">
                    <?php mysqli_data_seek($mitra_list_all_query, 0); ?>
                    <?php while ($m = mysqli_fetch_assoc($mitra_list_all_query)) : ?>
                        <label>
                            <input type="checkbox" name="id_mitra[]" value="<?= $m['Id_Mitra'] ?>"
                                <?= (isset($_POST['id_mitra']) && is_array($_POST['id_mitra']) && in_array($m['Id_Mitra'], $_POST['id_mitra'])) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($m['Nama_Mitra']) ?> (<?= htmlspecialchars($m['Spesialis_Mitra']) ?>)
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
        </fieldset>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Layanan Utama</button>
        <a href="index.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>