<?php
session_start();
require '../../config/database.php';

// Karena hanya ada 1 perusahaan, ID biasanya = 1 atau ID yang ada.
// Untuk fleksibilitas, kita tetap ambil dari GET.
// Jika tidak ada ID di GET, coba ambil ID perusahaan pertama (asumsi perusahaan utama).
$id_perusahaan = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_perusahaan = intval($_GET['id']);
} else {
    $first_company_query = mysqli_query($koneksi, "SELECT Id_Perusahaan FROM perusahaan ORDER BY Id_Perusahaan ASC LIMIT 1");
    if ($first_company_query && mysqli_num_rows($first_company_query) > 0) {
        $id_perusahaan = mysqli_fetch_assoc($first_company_query)['Id_Perusahaan'];
    }
}

if (empty($id_perusahaan) && $id_perusahaan !== 0) { // Cek jika $id_perusahaan masih null atau tidak valid
    $_SESSION['error_message'] = "Tidak ada data perusahaan untuk diedit atau ID tidak valid.";
    header("Location: index.php");
    exit;
}

$error_message = '';

// Fetch current perusahaan data
$query_current = "SELECT * FROM perusahaan WHERE Id_Perusahaan = $id_perusahaan";
$result_current = mysqli_query($koneksi, $query_current);
$perusahaan = mysqli_fetch_assoc($result_current);

if (!$perusahaan) {
    $_SESSION['error_message'] = "Data perusahaan dengan ID $id_perusahaan tidak ditemukan.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $ceo = mysqli_real_escape_string($koneksi, $_POST['ceo']);
    $kota = mysqli_real_escape_string($koneksi, $_POST['kota']);
    $jalan = mysqli_real_escape_string($koneksi, $_POST['jalan']);
    $kode_pos = mysqli_real_escape_string($koneksi, $_POST['kode_pos']);

    if (empty($nama) || empty($ceo) || empty($kota) || empty($jalan) || empty($kode_pos)) {
        $error_message = "Semua field (Nama, CEO, Kota, Jalan, Kode Pos) wajib diisi.";
    } else {
        // Id_Profit sudah tidak ada, jadi query update disederhanakan
        $query_update = "UPDATE perusahaan SET 
                            Nama = '$nama', 
                            CEO = '$ceo', 
                            Kota = '$kota', 
                            Jalan = '$jalan', 
                            Kode_Pos = '$kode_pos' 
                         WHERE Id_Perusahaan = $id_perusahaan";
        
        if (mysqli_query($koneksi, $query_update)) {
            $_SESSION['success_message'] = "Data perusahaan berhasil diperbarui!";
            header("Location: index.php"); // Redirect kembali ke index yang akan menampilkan info perusahaan
            exit;
        } else {
            $error_message = "Gagal memperbarui data perusahaan: " . mysqli_error($koneksi);
        }
    }
    // Re-populate $perusahaan dengan submitted data jika ada error untuk menjaga isian form
    $perusahaan['Nama'] = $nama;
    $perusahaan['CEO'] = $ceo;
    $perusahaan['Kota'] = $kota;
    $perusahaan['Jalan'] = $jalan;
    $perusahaan['Kode_Pos'] = $kode_pos;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Perusahaan</title>
    <link rel="stylesheet" href="../../assets/style.css"> </head>
<body>
    <div class="container">
        <h2>✏️ Edit Data Perusahaan: <?= htmlspecialchars($perusahaan['Nama']) ?></h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Nama Perusahaan:</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($perusahaan['Nama']) ?>" required>
            </div>
            <div class="form-group">
                <label>CEO:</label>
                <input type="text" name="ceo" value="<?= htmlspecialchars($perusahaan['CEO']) ?>" required>
            </div>
            <div class="form-group">
                <label>Kota:</label>
                <input type="text" name="kota" value="<?= htmlspecialchars($perusahaan['Kota']) ?>" required>
            </div>
            <div class="form-group">
                <label>Jalan:</label>
                <input type="text" name="jalan" value="<?= htmlspecialchars($perusahaan['Jalan']) ?>" required>
            </div>
            <div class="form-group">
                <label>Kode Pos:</label>
                <input type="text" name="kode_pos" value="<?= htmlspecialchars($perusahaan['Kode_Pos']) ?>" required>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>
</body>
</html>