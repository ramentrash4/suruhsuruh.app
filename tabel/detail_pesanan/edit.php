<?php
session_start();
require '../../config/database.php';

$id_detail_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';

if ($id_detail_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Detail Pesanan tidak valid.";
    header("Location: index.php");
    exit;
}

// Fetch current detail_pesanan data
$query_current = "SELECT * FROM detail_pesanan WHERE Id_DetailPesanan = $id_detail_pesanan";
$result_current = mysqli_query($koneksi, $query_current);
$detail_pesanan = mysqli_fetch_assoc($result_current);

if (!$detail_pesanan) {
    $_SESSION['error_message'] = "Data detail pesanan tidak ditemukan.";
    header("Location: index.php");
    exit;
}

// Query mengambil semua kolom dari pesanan dan data pengguna terkait untuk dropdown
$pesanan_list_query = mysqli_query($koneksi, "
    SELECT 
        p.Id_Pesanan, p.Tanggal AS Pesanan_Tanggal,
        p.Id_Pengguna AS Pesanan_Id_Pengguna,
        u.Nama_Depan AS Pengguna_Nama_Depan, u.Nama_Tengah AS Pengguna_Nama_Tengah, u.Nama_Belakang AS Pengguna_Nama_Belakang, u.Email AS Pengguna_Email, u.Alamat AS Pengguna_Alamat,
        p.Id_Pembayaran AS Pesanan_Id_Pembayaran,
        b.Jumlah AS Pembayaran_Jumlah, b.Tanggal AS Pembayaran_Tanggal_Bayar,
        p.Id_Layanan AS Pesanan_Id_Layanan,
        l.Nama_Layanan AS Layanan_Nama, l.Jenis_Layanan AS Layanan_Jenis
    FROM pesanan p 
    LEFT JOIN pengguna u ON p.Id_Pengguna = u.Id_pengguna 
    LEFT JOIN bayaran b ON p.Id_Pembayaran = b.Id_Pembayaran
    LEFT JOIN layanan l ON p.Id_Layanan = l.Id_Layanan
    ORDER BY p.Id_Pesanan ASC
");

if (!$pesanan_list_query) {
    die("Error fetching pesanan list: " . mysqli_error($koneksi));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = intval($_POST['id_pesanan']);
    $harga = filter_var($_POST['harga'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $jumlah = intval($_POST['jumlah']);

    if (empty($id_pesanan) || !is_numeric($harga) || $harga < 0 || empty($jumlah) || $jumlah <= 0) {
        $error_message = "Semua field wajib diisi dengan benar. Harga dan Jumlah harus positif.";
    } else {
        $query_update = "UPDATE detail_pesanan SET 
                            Id_Pesanan = '$id_pesanan', 
                            Harga = '$harga', 
                            Jumlah = '$jumlah' 
                         WHERE Id_DetailPesanan = $id_detail_pesanan";
        
        if (mysqli_query($koneksi, $query_update)) {
            $_SESSION['success_message'] = "Data detail pesanan berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui detail pesanan: " . mysqli_error($koneksi);
        }
    }
    // Re-populate $detail_pesanan with submitted data if there was an error
    $detail_pesanan['Id_Pesanan'] = $id_pesanan;
    $detail_pesanan['Harga'] = $_POST['harga']; 
    $detail_pesanan['Jumlah'] = $jumlah;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Detail Pesanan</title>
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
        <h2>✏️ Edit Detail Pesanan ID: <?= htmlspecialchars($detail_pesanan['Id_DetailPesanan']) ?></h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Pesanan Induk (ID):</label>
                <select name="id_pesanan" id="id_pesanan_select" onchange="tampilkanDetailPesanan()" required>
                    <option value="">-- Pilih Pesanan --</option>
                    <?php mysqli_data_seek($pesanan_list_query, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($pesanan_list_query)) : ?>
                    <option 
                        value="<?= $p['Id_Pesanan'] ?>" 
                        data-pesanan_id_pesanan="<?= htmlspecialchars($p['Id_Pesanan']) ?>"
                        data-pesanan_tanggal="<?= htmlspecialchars(date('d M Y', strtotime($p['Pesanan_Tanggal']))) ?>"
                        data-pesanan_id_pengguna="<?= htmlspecialchars($p['Pesanan_Id_Pengguna']) ?>"
                        data-pengguna_nama_lengkap="<?= htmlspecialchars(trim($p['Pengguna_Nama_Depan'] . ' ' . $p['Pengguna_Nama_Tengah'] . ' ' . $p['Pengguna_Nama_Belakang'])) ?>"
                        data-pengguna_email="<?= htmlspecialchars($p['Pengguna_Email']) ?>"
                        data-pengguna_alamat="<?= htmlspecialchars($p['Pengguna_Alamat']) ?>"
                        data-pesanan_id_pembayaran="<?= htmlspecialchars($p['Pesanan_Id_Pembayaran']) ?>"
                        data-pembayaran_jumlah="<?= htmlspecialchars($p['Pembayaran_Jumlah']) ?>"
                        data-pembayaran_tanggal_bayar="<?= !empty($p['Pembayaran_Tanggal_Bayar']) ? htmlspecialchars(date('d M Y', strtotime($p['Pembayaran_Tanggal_Bayar']))) : '' ?>"
                        data-pesanan_id_layanan="<?= htmlspecialchars($p['Pesanan_Id_Layanan']) ?>"
                        data-layanan_nama="<?= htmlspecialchars($p['Layanan_Nama']) ?>"
                        data-layanan_jenis="<?= htmlspecialchars($p['Layanan_Jenis']) ?>"
                        <?= ($p['Id_Pesanan'] == $detail_pesanan['Id_Pesanan']) ? 'selected' : '' ?>>
                        ID: <?= $p['Id_Pesanan'] ?> (Tgl: <?= htmlspecialchars(date('d M Y', strtotime($p['Pesanan_Tanggal']))) ?> - Pemesan: <?= htmlspecialchars(trim($p['Pengguna_Nama_Depan'].' '.$p['Pengguna_Nama_Belakang'])) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_pesanan_info" class="detail-display empty">
                 </div>

            <div class="form-group">
                <label>Harga Satuan:</label>
                <input type="number" name="harga" step="0.01" min="0" value="<?= htmlspecialchars($detail_pesanan['Harga']) ?>" required>
            </div>

            <div class="form-group">
                <label>Jumlah Item:</label>
                <input type="number" name="jumlah" min="1" value="<?= htmlspecialchars($detail_pesanan['Jumlah']) ?>" required>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>
<script>
function tampilkanDetailPesanan() {
    const select = document.getElementById('id_pesanan_select');
    const detailDiv = document.getElementById('detail_pesanan_info');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value) {
        let html = '<h4>Detail Pesanan Induk Terpilih:</h4>';
        html += `<strong>ID Pesanan:</strong> ${selectedOption.dataset.pesanan_id_pesanan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Tanggal Pesanan:</strong> ${selectedOption.dataset.pesanan_tanggal || "<span class='text-muted'>-</span>"}<br>`;
        html += '<hr>';
        html += '<strong>Pengguna:</strong><br>';
        html += `&nbsp;&nbsp;&nbsp;<strong>ID Pengguna:</strong> ${selectedOption.dataset.pesanan_id_pengguna || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Nama:</strong> ${selectedOption.dataset.pengguna_nama_lengkap || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Email:</strong> ${selectedOption.dataset.pengguna_email || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Alamat:</strong> ${selectedOption.dataset.pengguna_alamat || "<span class='text-muted'>-</span>"}<br>`;
        html += '<hr>';
        html += '<strong>Pembayaran:</strong><br>';
        html += `&nbsp;&nbsp;&nbsp;<strong>ID Pembayaran:</strong> ${selectedOption.dataset.pesanan_id_pembayaran || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Jumlah Bayar:</strong> ${selectedOption.dataset.pembayaran_jumlah || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Tgl Bayar:</strong> ${selectedOption.dataset.pembayaran_tanggal_bayar || "<span class='text-muted'>-</span>"}<br>`;
        html += '<hr>';
        html += '<strong>Layanan:</strong><br>';
        html += `&nbsp;&nbsp;&nbsp;<strong>ID Layanan:</strong> ${selectedOption.dataset.pesanan_id_layanan || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Nama Layanan:</strong> ${selectedOption.dataset.layanan_nama || "<span class='text-muted'>-</span>"}<br>`;
        html += `&nbsp;&nbsp;&nbsp;<strong>Jenis Layanan:</strong> ${selectedOption.dataset.layanan_jenis || "<span class='text-muted'>-</span>"}<br>`;
        
        detailDiv.innerHTML = html;
        detailDiv.classList.remove('empty');
    } else {
        detailDiv.innerHTML = '';
        detailDiv.classList.add('empty');
    }
}
// Initialize on page load to show details for the initially selected pesanan
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_pesanan_select').value) {
        tampilkanDetailPesanan();
    }
});
</script>
</body>
</html>