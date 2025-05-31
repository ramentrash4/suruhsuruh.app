<?php
session_start();
require '../../config/database.php';
$error_message = '';

$id_layanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_layanan <= 0) {
    $_SESSION['error_message'] = "ID Layanan tidak valid.";
    header("Location: index.php");
    exit;
}

// Fetch data layanan utama
$query_layanan = "SELECT * FROM layanan WHERE Id_Layanan = $id_layanan";
$result_layanan = mysqli_query($koneksi, $query_layanan);
$layanan_data = mysqli_fetch_assoc($result_layanan);

if (!$layanan_data) {
    $_SESSION['error_message'] = "Data layanan tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Fetch mitra list for checkboxes
$mitra_list_all_query = mysqli_query($koneksi, "SELECT * FROM mitra ORDER BY Nama_Mitra ASC");
if (!$mitra_list_all_query) {
    die("Error fetching mitra list: " . mysqli_error($koneksi));
}

// Fetch mitra yang terikat saat ini
$linked_mitra_ids = [];
$query_linked_mitra = "SELECT Id_Mitra FROM terikat WHERE Id_Layanan = $id_layanan";
$result_linked_mitra = mysqli_query($koneksi, $query_linked_mitra);
if ($result_linked_mitra) {
    while($row = mysqli_fetch_assoc($result_linked_mitra)) {
        $linked_mitra_ids[] = $row['Id_Mitra'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan = mysqli_real_escape_string($koneksi, $_POST['nama_layanan']);
    // Jenis_Layanan tidak diubah dari form ini untuk menjaga integritas dengan tabel anak.
    // Jika perlu diubah, itu adalah operasi yang lebih kompleks (hapus dari tabel anak lama, insert ke tabel anak baru).
    $deskripsi_umum = mysqli_real_escape_string($koneksi, $_POST['deskripsi_umum']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $mitra_terpilih_baru = isset($_POST['id_mitra']) ? $_POST['id_mitra'] : [];


    if (empty($nama_layanan)) {
        $error_message = "Nama Layanan wajib diisi.";
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            $query_update_layanan_induk = "UPDATE layanan SET 
                                            Nama_Layanan = '$nama_layanan', 
                                            Deskripsi_Umum = '$deskripsi_umum', 
                                            Status_Aktif = '$status_aktif'
                                          WHERE Id_Layanan = $id_layanan";
            if (!mysqli_query($koneksi, $query_update_layanan_induk)) {
                throw new Exception("Gagal memperbarui data layanan utama: " . mysqli_error($koneksi));
            }
            
            // Update tabel 'terikat'
            $query_delete_terikat = "DELETE FROM terikat WHERE Id_Layanan = $id_layanan";
            if(!mysqli_query($koneksi, $query_delete_terikat)) {
                throw new Exception("Gagal menghapus relasi mitra lama: " . mysqli_error($koneksi));
            }
            if (!empty($mitra_terpilih_baru)) {
                foreach ($mitra_terpilih_baru as $id_mitra_single) {
                    $id_mitra_single = intval($id_mitra_single);
                    $query_insert_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES ('$id_mitra_single', '$id_layanan')";
                    if (!mysqli_query($koneksi, $query_insert_terikat)) {
                        throw new Exception("Gagal mengaitkan layanan dengan mitra baru: " . mysqli_error($koneksi));
                    }
                }
            }

            mysqli_commit($koneksi);
            $_SESSION['success_message'] = "Layanan utama berhasil diperbarui!";
            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $error_message = $e->getMessage();
        }
    }
    // Re-populate form on error
    $layanan_data['Nama_Layanan'] = $nama_layanan;
    $layanan_data['Deskripsi_Umum'] = $deskripsi_umum;
    $layanan_data['Status_Aktif'] = $status_aktif;
    $linked_mitra_ids = $mitra_terpilih_baru; // Show newly selected if form submit fails
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Layanan Utama</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .checkbox-group label { display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h2>✏️ Edit Layanan Utama: <?= htmlspecialchars($layanan_data['Nama_Layanan']) ?></h2>
    <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form method="POST">
        <fieldset>
            <legend>Informasi Umum Layanan</legend>
            <div class="form-group">
                <label for="nama_layanan">Nama Layanan:</label>
                <input type="text" name="nama_layanan" id="nama_layanan" value="<?= htmlspecialchars($layanan_data['Nama_Layanan']) ?>" required>
            </div>
            <div class="form-group">
                <label for="jenis_layanan_display">Jenis Layanan:</label>
                <input type="text" id="jenis_layanan_display" value="<?= htmlspecialchars($layanan_data['Jenis_Layanan']) ?>" disabled readonly>
                <small>Jenis layanan tidak dapat diubah setelah dibuat untuk menjaga integritas data spesifik. Buat layanan baru jika ingin jenis berbeda.</small>
            </div>
            <div class="form-group">
                <label for="deskripsi_umum">Deskripsi Umum (Opsional):</label>
                <textarea name="deskripsi_umum" id="deskripsi_umum"><?= htmlspecialchars($layanan_data['Deskripsi_Umum']) ?></textarea>
            </div>
             <div class="form-group">
                <label for="status_aktif">
                    <input type="checkbox" name="status_aktif" id="status_aktif" value="1" <?= ($layanan_data['Status_Aktif'] == 1) ? 'checked' : '' ?>>
                    Aktifkan Layanan
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
                                <?= (in_array($m['Id_Mitra'], $linked_mitra_ids)) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($m['Nama_Mitra']) ?> (<?= htmlspecialchars($m['Spesialis_Mitra']) ?>)
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
        </fieldset>
        <br>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="index.php" class="btn">Batal</a>
    </form>
</div>
</body>
</html>