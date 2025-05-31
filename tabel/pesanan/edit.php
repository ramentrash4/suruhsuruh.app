<?php
require_once __DIR__ . '/../../config.php';

$id_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';

if ($id_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Pesanan tidak valid.";
    header("Location: index.php");
    exit;
}

// Fetch current pesanan data
$query_current = "SELECT * FROM pesanan WHERE Id_Pesanan = $id_pesanan";
$result_current = mysqli_query($koneksi, $query_current);
$pesanan = mysqli_fetch_assoc($result_current);

if (!$pesanan) {
    $_SESSION['error_message'] = "Data pesanan tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Fetch data for FK dropdowns - KOREKSI ORDER BY
$pengguna_list = mysqli_query($koneksi, "SELECT Id_pengguna, Nama_Depan, Nama_Tengah, Nama_Belakang, Email, Alamat FROM pengguna ORDER BY Id_pengguna ASC");
$bayaran_list = mysqli_query($koneksi, "SELECT Id_Pembayaran, Jumlah, Tanggal FROM bayaran ORDER BY Id_Pembayaran ASC"); // Diubah dari Tanggal DESC
$layanan_list = mysqli_query($koneksi, "SELECT Id_Layanan, Nama_Layanan, Jenis_Layanan FROM layanan ORDER BY Id_Layanan ASC");


if (!$pengguna_list || !$bayaran_list || !$layanan_list) {
    die("Error fetching lists for dropdowns: " . mysqli_error($koneksi));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = isset($_POST['id_pengguna']) && !empty($_POST['id_pengguna']) ? intval($_POST['id_pengguna']) : null;
    $id_pembayaran = isset($_POST['id_pembayaran']) && !empty($_POST['id_pembayaran']) ? intval($_POST['id_pembayaran']) : null;
    $id_layanan = isset($_POST['id_layanan']) && !empty($_POST['id_layanan']) ? intval($_POST['id_layanan']) : null;
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);

    if (empty($tanggal)) {
        $error_message = "Tanggal pesanan wajib diisi.";
    } else {
        $id_pengguna_sql = $id_pengguna ? "'$id_pengguna'" : "NULL";
        $id_pembayaran_sql = $id_pembayaran ? "'$id_pembayaran'" : "NULL";
        $id_layanan_sql = $id_layanan ? "'$id_layanan'" : "NULL";
        
        $query_update = "UPDATE pesanan SET 
                            Id_Pengguna = $id_pengguna_sql, 
                            Id_Pembayaran = $id_pembayaran_sql, 
                            Id_Layanan = $id_layanan_sql, 
                            Tanggal = '$tanggal' 
                         WHERE Id_Pesanan = $id_pesanan";
        
        if (mysqli_query($koneksi, $query_update)) {
            $_SESSION['success_message'] = "Data pesanan berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui pesanan: " . mysqli_error($koneksi);
        }
    }
    // Re-populate $pesanan with submitted data if there was an error
    $pesanan_post_data = $_POST; // Keep submitted data for form re-population
    $pesanan['Id_Pengguna'] = isset($pesanan_post_data['id_pengguna']) && !empty($pesanan_post_data['id_pengguna']) ? intval($pesanan_post_data['id_pengguna']) : $pesanan['Id_Pengguna'];
    $pesanan['Id_Pembayaran'] = isset($pesanan_post_data['id_pembayaran']) && !empty($pesanan_post_data['id_pembayaran']) ? intval($pesanan_post_data['id_pembayaran']) : $pesanan['Id_Pembayaran'];
    $pesanan['Id_Layanan'] = isset($pesanan_post_data['id_layanan']) && !empty($pesanan_post_data['id_layanan']) ? intval($pesanan_post_data['id_layanan']) : $pesanan['Id_Layanan'];
    $pesanan['Tanggal'] = isset($pesanan_post_data['tanggal']) ? $pesanan_post_data['tanggal'] : $pesanan['Tanggal'];

}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pesanan</title>
    <link rel="stylesheet" href="../../assets/style.css"> <style>
        .detail-display { margin-top:10px; border:1px solid #ccc; padding:10px; min-height: 50px; background-color: #f9f9f9; border-radius: 4px;}
        .detail-display.empty { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>✏️ Edit Pesanan ID: <?= htmlspecialchars($pesanan['Id_Pesanan']) ?></h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Pengguna:</label>
                <select name="id_pengguna" id="id_pengguna_select" onchange="tampilkanDetail('pengguna', this)">
                    <option value="">-- Pilih Pengguna (Opsional) --</option>
                    <?php mysqli_data_seek($pengguna_list, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($pengguna_list)) : ?>
                    <option 
                        value="<?= $p['Id_pengguna'] ?>" 
                        data-nama="<?= htmlspecialchars($p['Nama_Depan'] . ' ' . $p['Nama_Tengah'] . ' ' . $p['Nama_Belakang']) ?>"
                        data-email="<?= htmlspecialchars($p['Email']) ?>"
                        data-alamat="<?= htmlspecialchars($p['Alamat']) ?>"
                        <?= ($p['Id_pengguna'] == $pesanan['Id_Pengguna']) ? 'selected' : '' ?>>
                        ID: <?= $p['Id_pengguna'] ?> - <?= htmlspecialchars($p['Nama_Depan'] . ' ' . $p['Nama_Belakang']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_pengguna" class="detail-display empty"></div>

            <div class="form-group">
                <label>Pembayaran:</label>
                <select name="id_pembayaran" id="id_pembayaran_select" onchange="tampilkanDetail('pembayaran', this)">
                    <option value="">-- Pilih Pembayaran (Opsional) --</option>
                     <?php mysqli_data_seek($bayaran_list, 0); ?>
                    <?php while ($b = mysqli_fetch_assoc($bayaran_list)) : ?>
                    <option 
                        value="<?= $b['Id_Pembayaran'] ?>"
                        data-jumlah="<?= htmlspecialchars($b['Jumlah']) ?>"
                        data-tanggal_bayar="<?= htmlspecialchars(date('d M Y', strtotime($b['Tanggal']))) ?>"
                        <?= ($b['Id_Pembayaran'] == $pesanan['Id_Pembayaran']) ? 'selected' : '' ?>>
                        ID: <?= $b['Id_Pembayaran'] ?> - <?= htmlspecialchars($b['Jumlah']) ?> (Tgl: <?= htmlspecialchars(date('d M Y', strtotime($b['Tanggal']))) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_pembayaran" class="detail-display empty"></div>

            <div class="form-group">
                <label>Layanan:</label>
                <select name="id_layanan" id="id_layanan_select" onchange="tampilkanDetail('layanan', this)">
                    <option value="">-- Pilih Layanan (Opsional) --</option>
                    <?php mysqli_data_seek($layanan_list, 0); ?>
                    <?php while ($l = mysqli_fetch_assoc($layanan_list)) : ?>
                    <option 
                        value="<?= $l['Id_Layanan'] ?>"
                        data-nama_layanan="<?= htmlspecialchars($l['Nama_Layanan']) ?>"
                        data-jenis_layanan="<?= htmlspecialchars($l['Jenis_Layanan']) ?>"
                        <?= ($l['Id_Layanan'] == $pesanan['Id_Layanan']) ? 'selected' : '' ?>>
                        ID: <?= $l['Id_Layanan'] ?> - <?= htmlspecialchars($l['Nama_Layanan']) ?> (<?= htmlspecialchars($l['Jenis_Layanan'])?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_layanan" class="detail-display empty"></div>

            <div class="form-group">
                <label>Tanggal Pesan:</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($pesanan['Tanggal']) ?>" required>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>
<script>
function tampilkanDetail(prefix, selectElement) {
    const detailDiv = document.getElementById('detail_' + prefix);
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        let detailsHtml = '';
        if (prefix === 'pengguna') {
            detailsHtml = `<strong>Nama:</strong> ${selectedOption.getAttribute('data-nama')}<br>
                           <strong>Email:</strong> ${selectedOption.getAttribute('data-email')}<br>
                           <strong>Alamat:</strong> ${selectedOption.getAttribute('data-alamat')}`;
        } else if (prefix === 'pembayaran') {
            detailsHtml = `<strong>Jumlah Bayar:</strong> ${selectedOption.getAttribute('data-jumlah')}<br>
                           <strong>Tanggal Bayar:</strong> ${selectedOption.getAttribute('data-tanggal_bayar')}`;
        } else if (prefix === 'layanan') {
            detailsHtml = `<strong>Nama Layanan:</strong> ${selectedOption.getAttribute('data-nama_layanan')}<br>
                           <strong>Jenis Layanan:</strong> ${selectedOption.getAttribute('data-jenis_layanan')}`;
        }
        detailDiv.innerHTML = detailsHtml;
        detailDiv.classList.remove('empty');
    } else {
        detailDiv.innerHTML = '';
        detailDiv.classList.add('empty');
    }
}
// Initialize on page load to show details for the initially selected options
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_pengguna_select').value) tampilkanDetail('pengguna', document.getElementById('id_pengguna_select'));
    if (document.getElementById('id_pembayaran_select').value) tampilkanDetail('pembayaran', document.getElementById('id_pembayaran_select'));
    if (document.getElementById('id_layanan_select').value) tampilkanDetail('layanan', document.getElementById('id_layanan_select'));
});
</script>
</body>
</html>