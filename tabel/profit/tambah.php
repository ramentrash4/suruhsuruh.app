<?php
session_start();
require '../../config/database.php';

// Fetch detail_pesanan list for dropdown, ordered by Id_DetailPesanan ASC
$detail_pesanan_list_query = mysqli_query($koneksi, "
    SELECT 
        dp.Id_DetailPesanan, dp.Id_Pesanan, dp.Harga, dp.Jumlah,
        p.Tanggal AS Pesanan_Tanggal,
        u.Nama_Depan AS Pengguna_Nama_Depan, u.Nama_Belakang AS Pengguna_Nama_Belakang
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.Id_Pesanan = p.Id_Pesanan
    LEFT JOIN pengguna u ON p.Id_Pengguna = u.Id_pengguna
    ORDER BY dp.Id_DetailPesanan ASC
");

if (!$detail_pesanan_list_query) {
    die("Error fetching detail_pesanan list: " . mysqli_error($koneksi));
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_detail_pesanan = isset($_POST['id_detail_pesanan']) && !empty($_POST['id_detail_pesanan']) ? intval($_POST['id_detail_pesanan']) : null;
    $tanggal_profit = mysqli_real_escape_string($koneksi, $_POST['tanggal_profit']); // Sekarang jadi Tanggal_Profit
    $total_profit = mysqli_real_escape_string($koneksi, $_POST['total_profit']);

    // Validasi: Tanggal Profit (jika diisi) harus valid, Total Profit wajib
    if (empty($total_profit)) {
        $error_message = "Total Profit wajib diisi.";
    } elseif (!empty($tanggal_profit) && !strtotime($tanggal_profit)) {
         $error_message = "Format Tanggal Profit tidak valid.";
    } else {
        $id_detail_pesanan_sql = $id_detail_pesanan ? "'$id_detail_pesanan'" : "NULL";
        // Tanggal_Profit bisa NULL jika tidak diisi
        $tanggal_profit_sql = !empty($tanggal_profit) ? "'$tanggal_profit'" : "NULL";

        $query = "INSERT INTO profit (Id_DetailPesanan, Tanggal_Profit, total_Profit) 
                  VALUES ($id_detail_pesanan_sql, $tanggal_profit_sql, '$total_profit')";
        
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success_message'] = "Data profit berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal menambahkan data profit: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data Profit</title>
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
        .detail-display strong { color: #333; min-width: 150px; display: inline-block; }
        .text-muted { color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Tambah Data Profit Baru</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Detail Pesanan (Opsional):</label>
                <select name="id_detail_pesanan" id="id_detail_pesanan_select" onchange="tampilkanDetailItemPesanan()">
                    <option value="">-- Pilih Detail Pesanan (Jika Berkaitan) --</option>
                    <?php mysqli_data_seek($detail_pesanan_list_query, 0); ?>
                    <?php while ($dp = mysqli_fetch_assoc($detail_pesanan_list_query)) : ?>
                    <option 
                        value="<?= $dp['Id_DetailPesanan'] ?>" 
                        data-id_detail_pesanan="<?= htmlspecialchars($dp['Id_DetailPesanan']) ?>"
                        data-id_pesanan="<?= htmlspecialchars($dp['Id_Pesanan']) ?>"
                        data-harga="<?= htmlspecialchars($dp['Harga']) ?>"
                        data-jumlah="<?= htmlspecialchars($dp['Jumlah']) ?>"
                        data-pesanan_tanggal="<?= htmlspecialchars(date('d M Y', strtotime($dp['Pesanan_Tanggal']))) ?>"
                        data-pengguna_nama="<?= htmlspecialchars(trim($dp['Pengguna_Nama_Depan'] . ' ' . $dp['Pengguna_Nama_Belakang'])) ?>"
                        <?= (isset($_POST['id_detail_pesanan']) && $_POST['id_detail_pesanan'] == $dp['Id_DetailPesanan']) ? 'selected' : '' ?>>
                        ID Detail: <?= $dp['Id_DetailPesanan'] ?> (Pesanan ID: <?= $dp['Id_Pesanan'] ?>, Item Subtotal: Rp <?= number_format($dp['Harga']*$dp['Jumlah'],0,',','.') ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div id="detail_item_pesanan_info" class="detail-display empty">
                </div>

            <div class="form-group">
                <label>Tanggal Profit (Opsional):</label>
                <input type="date" name="tanggal_profit" value="<?= isset($_POST['tanggal_profit']) ? htmlspecialchars($_POST['tanggal_profit']) : '' ?>">
            </div>
            <div class="form-group">
                <label>Total Profit:</label>
                <input type="text" name="total_profit" placeholder="cth: RP. 18.520.000" value="<?= isset($_POST['total_profit']) ? htmlspecialchars($_POST['total_profit']) : '' ?>" required>
            </div>
            <br>
            <button type="submit" class="btn btn-primary">Simpan Data Profit</button>
            <a href="index.php" class="btn">Batal</a>
        </form>
    </div>

<script>
function tampilkanDetailItemPesanan() {
    const select = document.getElementById('id_detail_pesanan_select');
    const detailDiv = document.getElementById('detail_item_pesanan_info');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.value) {
        let html = '<h4>Detail Item Pesanan Terpilih:</h4>';
        html += `<strong>ID Detail Pesanan:</strong> ${selectedOption.dataset.id_detail_pesanan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>ID Pesanan Induk:</strong> ${selectedOption.dataset.id_pesanan || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Harga Satuan:</strong> Rp ${parseFloat(selectedOption.dataset.harga || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<br>`;
        html += `<strong>Jumlah Item:</strong> ${selectedOption.dataset.jumlah || "<span class='text-muted'>-</span>"}<br>`;
        let subtotal = (parseFloat(selectedOption.dataset.harga || 0) * parseInt(selectedOption.dataset.jumlah || 0));
        html += `<strong>Subtotal Item:</strong> Rp ${subtotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<br>`;
        html += '<hr>';
        html += `<strong>Tanggal Pesanan Induk:</strong> ${selectedOption.dataset.pesanan_tanggal || "<span class='text-muted'>-</span>"}<br>`;
        html += `<strong>Pemesan:</strong> ${selectedOption.dataset.pengguna_nama || "<span class='text-muted'>-</span>"}<br>`;
        
        detailDiv.innerHTML = html;
        detailDiv.classList.remove('empty');
    } else {
        detailDiv.innerHTML = '';
        detailDiv.classList.add('empty');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('id_detail_pesanan_select').value) {
        tampilkanDetailItemPesanan();
    }
});
</script>
</body>
</html>