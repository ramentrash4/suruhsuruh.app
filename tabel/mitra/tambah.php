<?php
session_start();
require '../../config/database.php';

// Fetch perusahaan list for dropdown
$perusahaan_list_query = mysqli_query($koneksi, "SELECT * FROM perusahaan ORDER BY Id_Perusahaan ASC");
if (!$perusahaan_list_query) {
    die("Error fetching perusahaan list: " . mysqli_error($koneksi));
}

// Fetch layanan list for checkboxes (for table 'terikat')
$layanan_list_all_query = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY Nama_Layanan ASC");
if (!$layanan_list_all_query) {
    die("Error fetching layanan list: " . mysqli_error($koneksi));
}


$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mitra = mysqli_real_escape_string($koneksi, $_POST['nama_mitra']);
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $spesialis_mitra = mysqli_real_escape_string($koneksi, $_POST['spesialis_mitra']);
    $id_perusahaan = isset($_POST['id_perusahaan']) && !empty($_POST['id_perusahaan']) ? intval($_POST['id_perusahaan']) : null;
    $layanan_terpilih = isset($_POST['id_layanan']) ? $_POST['id_layanan'] : []; // Array of selected layanan IDs

    if (empty($nama_mitra) || empty($no_telp) || empty($spesialis_mitra)) {
        $error_message = "Nama Mitra, No. Telepon, dan Spesialis Mitra wajib diisi.";
    } else {
        $id_perusahaan_sql = $id_perusahaan ? "'$id_perusahaan'" : "NULL";

        // Start transaction
        mysqli_begin_transaction($koneksi);

        try {
            $query_mitra = "INSERT INTO mitra (Nama_Mitra, No_Telp, Spesialis_Mitra, Id_Perusahaan) 
                            VALUES ('$nama_mitra', '$no_telp', '$spesialis_mitra', $id_perusahaan_sql)";
            
            if (!mysqli_query($koneksi, $query_mitra)) {
                throw new Exception("Gagal menambahkan data mitra: " . mysqli_error($koneksi));
            }
            
            $new_mitra_id = mysqli_insert_id($koneksi);

            // Insert into 'terikat' table
            if (!empty($layanan_terpilih) && $new_mitra_id > 0) {
                foreach ($layanan_terpilih as $id_layanan_single) {
                    $id_layanan_single = intval($id_layanan_single);
                    $query_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES ('$new_mitra_id', '$id_layanan_single')";
                    if (!mysqli_query($koneksi, $query_terikat)) {
                        // Jika ada duplikasi Id_Mitra dan Id_Layanan, ini akan error jika PK sudah ada
                        // Anda mungkin ingin menggunakan INSERT IGNORE atau ON DUPLICATE KEY UPDATE jika perlu
                        // Untuk sekarang, kita asumsikan kombinasi baru
                        throw new Exception("Gagal menambahkan data ke tabel terikat: " . mysqli_error($koneksi));
                    }
                }
            }

            mysqli_commit($koneksi);
            $_SESSION['success_message'] = "Data mitra dan layanan terkait berhasil ditambahkan!";
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
    <title>Tambah Data Mitra</title>
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
        .checkbox-group label { display: block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Tambah Data Mitra Baru</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Nama Mitra:</label>
                <input type="text" name="nama_mitra" value="<?= isset($_POST['nama_mitra']) ? htmlspecialchars($_POST['nama_mitra']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>No. Telepon:</label>
                <input type="text" name="no_telp" value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Spesialis Mitra:</label>
                <input type="text" name="spesialis_mitra" value="<?= isset($_POST['spesialis_mitra']) ? htmlspecialchars($_POST['spesialis_mitra']) : '' ?>" required>
            </div>

            <div class="form-group">
                <label>Perusahaan Afiliasi (Opsional):</label>
                <select name="id_perusahaan" id="id_perusahaan_select" onchange="tampilkanDetailPerusahaan()">
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

            <div class="form-group">
                <label>Layanan yang Disediakan (Pilih satu atau lebih):</label>
                <div class="checkbox-group">
                    <?php mysqli_data_seek($layanan_list_all_query, 0); ?>
                    <?php while ($lyn = mysqli_fetch_assoc($layanan_list_all_query)) : ?>
                        <label>
                            <input type="checkbox" name="id_layanan[]" value="<?= $lyn['Id_Layanan'] ?>"
                                <?= (isset($_POST['id_layanan']) && is_array($_POST['id_layanan']) && in_array($lyn['Id_Layanan'], $_POST['id_layanan'])) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($lyn['Nama_Layanan']) ?> (<?= htmlspecialchars($lyn['Jenis_Layanan']) ?>)
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Data Mitra</button>
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