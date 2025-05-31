<?php
session_start();
require '../../config/database.php';

// Mengambil ID dari URL (Composite Key)
$id_pekerja = isset($_GET['id_pekerja']) ? intval($_GET['id_pekerja']) : 0;
$id_perusahaan_lama = isset($_GET['id_perusahaan']) ? intval($_GET['id_perusahaan']) : 0; // Id_Perusahaan saat ini

$error_message = '';

if ($id_pekerja <= 0 || $id_perusahaan_lama <= 0) {
    $_SESSION['error_message'] = "ID Pekerja atau ID Perusahaan tidak valid.";
    header("Location: index.php");
    exit;
}

// Fetch current pekerja data
$query_current = "SELECT * FROM pekerja WHERE Id_Pekerja = $id_pekerja AND Id_Perusahaan = $id_perusahaan_lama";
$result_current = mysqli_query($koneksi, $query_current);
$pekerja = mysqli_fetch_assoc($result_current);

if (!$pekerja) {
    $_SESSION['error_message'] = "Data pekerja tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Fetch perusahaan list for dropdown
$perusahaan_list_query = mysqli_query($koneksi, "
    SELECT *
    FROM perusahaan
    ORDER BY Id_Perusahaan ASC
");
if (!$perusahaan_list_query) {
    die("Error fetching perusahaan list: " . mysqli_error($koneksi));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_perusahaan_baru = intval($_POST['id_perusahaan']); // Id_Perusahaan yang baru dari form
    $nama_depan = mysqli_real_escape_string($koneksi, $_POST['nama_depan']);
    $nama_tengah = mysqli_real_escape_string($koneksi, $_POST['nama_tengah']);
    $nama_belakang = mysqli_real_escape_string($koneksi, $_POST['nama_belakang']);
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);

    if (empty($id_perusahaan_baru) || empty($nama_depan) || empty($nama_belakang) || empty($tanggal_lahir) || empty($no_telp)) {
        $error_message = "Semua field (kecuali Nama Tengah) wajib diisi.";
    } else {
        // Id_Pekerja (PK part 1) tidak diubah karena AUTO_INCREMENT dan identitas utama.
        // Kita mengupdate Id_Perusahaan (PK part 2) dan field lainnya.
        $query_update = "UPDATE pekerja SET 
                            Id_Perusahaan = '$id_perusahaan_baru', 
                            Nama_Depan = '$nama_depan', 
                            Nama_Tengah = '$nama_tengah', 
                            Nama_Belakang = '$nama_belakang', 
                            Tanggal_lahir = '$tanggal_lahir', 
                            NO_Telp = '$no_telp' 
                         WHERE Id_Pekerja = $id_pekerja AND Id_Perusahaan = $id_perusahaan_lama"; 
                         // Kondisi WHERE menggunakan Id_Perusahaan yang lama untuk menemukan record asli
        
        if (mysqli_query($koneksi, $query_update)) {
            $_SESSION['success_message'] = "Data pekerja berhasil diperbarui!";
            // Redirect ke index, atau ke halaman edit dengan ID baru jika Id_Perusahaan berubah
            header("Location: index.php"); 
            exit;
        } else {
            // Cek jika error karena duplikasi composite key baru (Id_Pekerja, Id_Perusahaan_Baru)
            if(mysqli_errno($koneksi) == 1062) { // Error code for duplicate entry
                 $error_message = "Gagal memperbarui: Kombinasi ID Pekerja ($id_pekerja) dan ID Perusahaan Baru ($id_perusahaan_baru) sudah ada. Harap pilih perusahaan lain atau periksa data.";
            } else {
                $error_message = "Gagal memperbarui data pekerja: " . mysqli_error($koneksi);
            }
        }
    }
    // Re-populate $pekerja dengan submitted data if there was an error
    $pekerja['Id_Perusahaan'] = $id_perusahaan_baru; // Gunakan Id_Perusahaan baru untuk form
    $pekerja['Nama_Depan'] = $nama_depan;
    $pekerja['Nama_Tengah'] = $nama_tengah;
    $pekerja['Nama_Belakang'] = $nama_belakang;
    $pekerja['Tanggal_lahir'] = $tanggal_lahir;
    $pekerja['NO_Telp'] = $no_telp;
    // $id_perusahaan_lama tetap digunakan untuk logika jika update gagal & perlu load ulang form dengan state awal
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Pekerja</title>
    <link rel="stylesheet" href="../../assets/style.css"> <style>
        .detail-display { 
            margin-top:10px; 
            border:1px solid #ccc; 
            padding:15px; 
            min-height: 50px; 
            background-color: #f9f9f9; 
            border-radius: 4px;
            line-height: 1.6;
        }
        .detail-display.empty { display: none; }
        .detail-display strong { color: #333; min-width: 120px; display: inline-block; }
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>✏️ Edit Data Pekerja: <?= htmlspecialchars(trim($pekerja['Nama_Depan'] . ' ' . $pekerja['Nama_Belakang'])) ?> (ID: <?= $id_pekerja ?>)</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id_pekerja" value="<?= $id_pekerja ?>">
            
            <div class="form-group">
                <label>Nama Depan:</label>
                <input type="text" name="nama_depan" value="<?= htmlspecialchars($pekerja['Nama_Depan']) ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Tengah (Opsional):</label>
                <input type="text" name="nama_tengah" value="<?= htmlspecialchars($pekerja['Nama_Tengah']) ?>">
            </div>
            <div class="form-group">
                <label>Nama Belakang:</label>
                <input type="text" name="nama_belakang" value="<?= htmlspecialchars($pekerja['Nama_Belakang']) ?>" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir:</label>
                <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($pekerja['Tanggal_lahir']) ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon:</label>
                <input type="text" name="no_telp" value="<?= htmlspecialchars($pekerja['NO_Telp']) ?>" required>
            </div>

            <div class="form-group">
                <label>Perusahaan:</label>
                <select name="id_perusahaan" id="id_perusahaan_select" onchange="tampilkanDetailPerusahaan()" required>
                    <option value="">-- Pilih Perusahaan --</option>
                    <?php mysqli_data_seek($perusahaan_list_query, 0); ?>
                    <?php while ($prs = mysqli_fetch_assoc($perusahaan_list_query)) : ?>
                    <option 
                        value="<?= $prs['Id_Perusahaan'] ?>" 
                        data-id_perusahaan="<?= htmlspecialchars($prs['Id_Perusahaan']) ?>"
                        data-nama_perusahaan="<?= htmlspecialchars($prs['Nama']) ?>"
                        data-ceo_perusahaan="<?= htmlspecialchars($prs['CEO']) ?>"
                        data-kota_perusahaan="<?= htmlspecialchars($prs['Kota']) ?>"
                        data-jalan_perusahaan="<?= htmlspecialchars($prs['Jalan']) ?>"
                        data-kodepos_perusahaan="<?= htmlspecialchars($prs['Kode_Pos']) ?>"
                        <?= ($prs['Id_Perusahaan'] == $pekerja['Id_Perusahaan']) ? 'selected' : '' ?>>
                        ID: <?= $prs['Id_Perusahaan'] ?> - <?= htmlspecialchars($prs['Nama']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
             <div id="detail_perusahaan_info" class="detail-display empty">
                </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>
<script>
function tampilkanDetailPerusahaan() {
    const select = document.getElementById('id_perusahaan_select');
    const detailDiv = document.getElementById('detail_perusahaan_info');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value) {
        let html = '<h4>Detail Perusahaan Terpilih:</h4>';
        html += `<strong>ID Perusahaan:</strong> ${selectedOption.dataset.id_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Nama Perusahaan:</strong> ${selectedOption.dataset.nama_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>CEO:</strong> ${selectedOption.dataset.ceo_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Kota:</strong> ${selectedOption.dataset.kota_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Jalan:</strong> ${selectedOption.dataset.jalan_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Kode Pos:</strong> ${selectedOption.dataset.kodepos_perusahaan || "<span class='text-muted'>-</span>"}<br>`;
        
        detailDiv.innerHTML = html;
        detailDiv.classList.remove('empty');
    } else {
        detailDiv.innerHTML = '';
        detailDiv.classList.add('empty');
    }
}
// Initialize on page load to show details for the initially selected perusahaan
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_perusahaan_select').value) {
        tampilkanDetailPerusahaan();
    }
});
</script>
</body>
</html>