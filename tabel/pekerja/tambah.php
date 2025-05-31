<?php
session_start();
require '../../config/database.php';

// Fetch perusahaan list for dropdown, ordered by Id_Perusahaan ASC
$perusahaan_list_query = mysqli_query($koneksi, "
    SELECT *
    FROM perusahaan
    ORDER BY Id_Perusahaan ASC
");

if (!$perusahaan_list_query) {
    die("Error fetching perusahaan list: " . mysqli_error($koneksi));
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Id_Pekerja adalah AUTO_INCREMENT, jadi tidak diinput dari form
    $id_perusahaan = intval($_POST['id_perusahaan']);
    $nama_depan = mysqli_real_escape_string($koneksi, $_POST['nama_depan']);
    $nama_tengah = mysqli_real_escape_string($koneksi, $_POST['nama_tengah']); // Bisa kosong
    $nama_belakang = mysqli_real_escape_string($koneksi, $_POST['nama_belakang']);
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);

    if (empty($id_perusahaan) || empty($nama_depan) || empty($nama_belakang) || empty($tanggal_lahir) || empty($no_telp)) {
        $error_message = "Semua field (kecuali Nama Tengah) wajib diisi.";
    } else {
        $query = "INSERT INTO pekerja (Id_Perusahaan, Nama_Depan, Nama_Tengah, Nama_Belakang, Tanggal_lahir, NO_Telp) 
                  VALUES ('$id_perusahaan', '$nama_depan', '$nama_tengah', '$nama_belakang', '$tanggal_lahir', '$no_telp')";
        
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success_message'] = "Data pekerja berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal menambahkan data pekerja: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data Pekerja</title>
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
        <h2>➕ Tambah Data Pekerja Baru</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Nama Depan:</label>
                <input type="text" name="nama_depan" value="<?= isset($_POST['nama_depan']) ? htmlspecialchars($_POST['nama_depan']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Tengah (Opsional):</label>
                <input type="text" name="nama_tengah" value="<?= isset($_POST['nama_tengah']) ? htmlspecialchars($_POST['nama_tengah']) : '' ?>">
            </div>
            <div class="form-group">
                <label>Nama Belakang:</label>
                <input type="text" name="nama_belakang" value="<?= isset($_POST['nama_belakang']) ? htmlspecialchars($_POST['nama_belakang']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Tanggal Lahir:</label>
                <input type="date" name="tanggal_lahir" value="<?= isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon:</label>
                <input type="text" name="no_telp" value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>" required>
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
                        <?= (isset($_POST['id_perusahaan']) && $_POST['id_perusahaan'] == $prs['Id_Perusahaan']) ? 'selected' : '' ?>>
                        ID: <?= $prs['Id_Perusahaan'] ?> - <?= htmlspecialchars($prs['Nama']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_perusahaan_info" class="detail-display empty">
                </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Data Pekerja</button>
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
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_perusahaan_select').value) {
        tampilkanDetailPerusahaan();
    }
});
</script>
</body>
</html>