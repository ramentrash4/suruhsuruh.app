<?php
session_start();
require '../../config/database.php';

// Fetch profit list for dropdown, ordered by Id_Profit ASC
// Include related detail_pesanan data for detailed display
$profit_list_query = mysqli_query($koneksi, "
    SELECT 
        p.Id_Profit, p.Tanggal_Profit, p.total_Profit,
        p.Id_DetailPesanan AS Profit_Id_DetailPesanan,
        dp.Id_Pesanan AS DetailPesanan_Id_Pesanan,
        dp.Harga AS DetailPesanan_Harga,
        dp.Jumlah AS DetailPesanan_Jumlah
    FROM profit p
    LEFT JOIN detail_pesanan dp ON p.Id_DetailPesanan = dp.Id_DetailPesanan
    ORDER BY p.Id_Profit ASC
");

if (!$profit_list_query) {
    die("Error fetching profit list: " . mysqli_error($koneksi));
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_profit = isset($_POST['id_profit']) && !empty($_POST['id_profit']) ? intval($_POST['id_profit']) : null;
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $ceo = mysqli_real_escape_string($koneksi, $_POST['ceo']);
    $kota = mysqli_real_escape_string($koneksi, $_POST['kota']);
    $jalan = mysqli_real_escape_string($koneksi, $_POST['jalan']);
    $kode_pos = mysqli_real_escape_string($koneksi, $_POST['kode_pos']);

    if (empty($nama) || empty($ceo) || empty($kota) || empty($jalan) || empty($kode_pos)) {
        $error_message = "Semua field (Nama, CEO, Kota, Jalan, Kode Pos) wajib diisi.";
    } else {
        $id_profit_sql = $id_profit ? "'$id_profit'" : "NULL";

        $query = "INSERT INTO perusahaan (Id_Profit, Nama, CEO, Kota, Jalan, Kode_Pos) 
                  VALUES ($id_profit_sql, '$nama', '$ceo', '$kota', '$jalan', '$kode_pos')";
        
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success_message'] = "Data perusahaan berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal menambahkan data perusahaan: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data Perusahaan</title>
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
        .detail-display strong { color: #333; min-width: 160px; display: inline-block; }
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Tambah Data Perusahaan Baru</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Nama Perusahaan:</label>
                <input type="text" name="nama" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>CEO:</label>
                <input type="text" name="ceo" value="<?= isset($_POST['ceo']) ? htmlspecialchars($_POST['ceo']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Kota:</label>
                <input type="text" name="kota" value="<?= isset($_POST['kota']) ? htmlspecialchars($_POST['kota']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Jalan:</label>
                <input type="text" name="jalan" value="<?= isset($_POST['jalan']) ? htmlspecialchars($_POST['jalan']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Kode Pos:</label>
                <input type="text" name="kode_pos" value="<?= isset($_POST['kode_pos']) ? htmlspecialchars($_POST['kode_pos']) : '' ?>" required>
            </div>

            <div class="form-group">
                <label>Profit Terkait (Opsional):</label>
                <select name="id_profit" id="id_profit_select" onchange="tampilkanDetailProfit()">
                    <option value="">-- Pilih Profit (Jika Berkaitan) --</option>
                    <?php mysqli_data_seek($profit_list_query, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($profit_list_query)) : ?>
                    <option 
                        value="<?= $p['Id_Profit'] ?>" 
                        data-id_profit="<?= htmlspecialchars($p['Id_Profit']) ?>"
                        data-tanggal_profit="<?= !empty($p['Tanggal_Profit']) ? htmlspecialchars(date('d M Y', strtotime($p['Tanggal_Profit']))) : '' ?>"
                        data-total_profit="<?= htmlspecialchars($p['total_Profit']) ?>"
                        data-profit_id_detail_pesanan="<?= htmlspecialchars($p['Profit_Id_DetailPesanan']) ?>"
                        data-detailpesanan_id_pesanan="<?= htmlspecialchars($p['DetailPesanan_Id_Pesanan']) ?>"
                        data-detailpesanan_harga="<?= htmlspecialchars($p['DetailPesanan_Harga']) ?>"
                        data-detailpesanan_jumlah="<?= htmlspecialchars($p['DetailPesanan_Jumlah']) ?>"
                        <?= (isset($_POST['id_profit']) && $_POST['id_profit'] == $p['Id_Profit']) ? 'selected' : '' ?>>
                        ID Profit: <?= $p['Id_Profit'] ?> (Total: <?= htmlspecialchars($p['total_Profit']) ?> <?= !empty($p['Tanggal_Profit']) ? '- Tgl: '.date('d M Y', strtotime($p['Tanggal_Profit'])) : '' ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_profit_info" class="detail-display empty">
                </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Data Perusahaan</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>

<script>
function tampilkanDetailProfit() {
    const select = document.getElementById('id_profit_select');
    const detailDiv = document.getElementById('detail_profit_info');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value) {
        let html = '<h4>Detail Profit Terpilih:</h4>';
        html += `<strong>ID Profit:</strong> ${selectedOption.dataset.id_profit || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Tanggal Profit:</strong> ${selectedOption.dataset.tanggal_profit || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Total Profit:</strong> ${selectedOption.dataset.total_profit || "<span class='text-muted'>-</span>"}<br>`;
        
        if (selectedOption.dataset.profit_id_detail_pesanan) {
            html += '<hr>';
            html += '<strong>Terkait Detail Pesanan:</strong><br>';
            html += `&nbsp;&nbsp;&nbsp;<strong>ID Detail Pesanan:</strong> ${selectedOption.dataset.profit_id_detail_pesanan}<br>`;
            html += `&nbsp;&nbsp;&nbsp;<strong>ID Pesanan Induk:</strong> ${selectedOption.dataset.detailpesanan_id_pesanan || "<span class='text-muted'>-</span>"}<br>`;
            let harga = parseFloat(selectedOption.dataset.detailpesanan_harga || 0);
            let jumlah = parseInt(selectedOption.dataset.detailpesanan_jumlah || 0);
            html += `&nbsp;&nbsp;&nbsp;<strong>Harga Item:</strong> Rp ${harga.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<br>`;
            html += `&nbsp;&nbsp;&nbsp;<strong>Jumlah Item:</strong> ${jumlah}<br>`;
        } else {
            html += '<hr>';
            html += '<strong>Terkait Detail Pesanan:</strong> <span class="text-muted">Tidak ada</span><br>';
        }
        
        detailDiv.innerHTML = html;
        detailDiv.classList.remove('empty');
    } else {
        detailDiv.innerHTML = '';
        detailDiv.classList.add('empty');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_profit_select').value) {
        tampilkanDetailProfit();
    }
});
</script>
</body>
</html>