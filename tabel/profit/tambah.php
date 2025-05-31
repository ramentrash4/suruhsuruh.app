<?php
// Pastikan error reporting aktif di paling atas
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin'] !== true) {
    if (!defined('BASE_URL')) define('BASE_URL', '/projekbasdat/');
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
require_once '../../config.php';
$error_message = '';

$detail_pesanan_list_query = $koneksi->query("
    SELECT dp.Id_DetailPesanan, dp.Id_Pesanan, dp.Harga, dp.Jumlah,
           ps.Tanggal AS Pesanan_Tanggal,
           pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Belakang AS Pengguna_Nama_Belakang
    FROM detail_pesanan dp
    JOIN pesanan ps ON dp.Id_Pesanan = ps.Id_Pesanan
    LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
    ORDER BY dp.Id_DetailPesanan DESC
");
if (!$detail_pesanan_list_query) { die("Error fetching detail_pesanan list: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_detail_pesanan = isset($_POST['id_detail_pesanan']) && !empty($_POST['id_detail_pesanan']) ? (int)$_POST['id_detail_pesanan'] : null;
    $tanggal_profit = !empty($_POST['tanggal_profit']) ? $_POST['tanggal_profit'] : null;
    $total_profit = preg_replace("/[^0-9.]/", "", $_POST['total_profit']); // Hanya angka dan titik desimal

    if (empty($total_profit) || !is_numeric($total_profit) || $total_profit < 0) {
        $error_message = "Total Profit wajib diisi dengan angka positif.";
    } elseif ($tanggal_profit !== null && !strtotime($tanggal_profit)) {
         $error_message = "Format Tanggal Profit tidak valid.";
    } else {
        $sql = "INSERT INTO profit (Id_DetailPesanan, Tanggal_Profit, total_Profit) VALUES (?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
        else {
            // Tipe untuk Id_DetailPesanan adalah 'i' jika tidak null, atau null jika null
            // Tipe untuk Tanggal_Profit adalah 's' jika tidak null, atau null jika null
            // Tipe untuk total_Profit adalah 'd' (double/decimal)
            // Karena bind_param tidak bisa langsung handle NULL untuk tipe, kita akan set manual jika NULL
            
            $bp_id_detail = $id_detail_pesanan;
            $bp_tgl_profit = $tanggal_profit;
            $bp_total_profit = (float)$total_profit;

            // Karena Id_DetailPesanan dan Tanggal_Profit bisa NULL di DB,
            // kita perlu cara bind yang lebih fleksibel atau pastikan nilainya.
            // Untuk kesederhanaan, jika user tidak pilih, kita kirim NULL ke DB (sudah dihandle dengan ? di query).
            // mysqli bind_param tidak secara native mendukung pengiriman NULL untuk tipe tertentu tanpa trik.
            // Solusi paling mudah adalah membuat query dinamis atau memastikan NULL di sisi SQL jika field kosong.
            // Namun, untuk Prepared Statement, lebih baik bind dengan tipe dan kirim NULL PHP.
            // Mysqli akan handle konversi NULL PHP ke SQL NULL dengan tipe yang sesuai jika field di DB nullable.

            // Tipe: i untuk integer, d untuk double, s untuk string
            // Jika Id_DetailPesanan null, kirim null. Jika Tanggal_Profit null, kirim null.
            $stmt->bind_param("isd", $bp_id_detail, $bp_tgl_profit, $bp_total_profit);
            // Catatan: Jika field di DB adalah NOT NULL dan Anda kirim NULL, akan error.
            // Id_DetailPesanan dan Tanggal_Profit di tabel profit Anda bisa NULL.
            
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Data profit baru berhasil ditambahkan!";
                header("Location: index.php");
                exit;
            } else { $error_message = "Gagal menambahkan data profit: " . $stmt->error; }
            $stmt->close();
        }
    }
}
$page_title = "Tambah Data Profit";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-cash-stack me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahProfit" novalidate>
            <div class="col-md-12">
                <label for="id_detail_pesanan_select" class="form-label fw-semibold"><i class="bi bi-list-check me-2"></i>Detail Pesanan Terkait (Opsional)</label>
                <select name="id_detail_pesanan" id="id_detail_pesanan_select" class="form-select select2-init" data-preview-target="#detail_pesanan_info_panel">
                    <option value="">-- Tidak Terkait Langsung dengan Detail Pesanan --</option>
                    <?php if($detail_pesanan_list_query) mysqli_data_seek($detail_pesanan_list_query, 0); while ($dp = $detail_pesanan_list_query->fetch_assoc()) : ?>
                    <option value="<?= $dp['Id_DetailPesanan'] ?>" 
                            data-id_pesanan="<?= htmlspecialchars($dp['Id_Pesanan']) ?>"
                            data-harga_item="Rp <?= number_format($dp['Harga'], 0, ',', '.') ?>"
                            data-jumlah_item="<?= htmlspecialchars($dp['Jumlah']) ?>"
                            data-pesanan_tanggal="<?= htmlspecialchars(date('d M Y', strtotime($dp['Pesanan_Tanggal']))) ?>"
                            data-pengguna_nama="<?= htmlspecialchars(trim($dp['Pengguna_Nama_Depan'].' '.$dp['Pengguna_Nama_Belakang'])) ?>"
                            <?= (isset($_POST['id_detail_pesanan']) && $_POST['id_detail_pesanan'] == $dp['Id_DetailPesanan']) ? 'selected' : '' ?>>
                        ID Detail: <?= $dp['Id_DetailPesanan'] ?> (Pesanan #<?= $dp['Id_Pesanan'] ?> - Subtotal: Rp <?= number_format($dp['Harga'] * $dp['Jumlah'],0,',','.') ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
                <div id="detail_pesanan_info_panel" class="detail-preview-panel mt-2 p-3 border rounded bg-light" style="font-size:0.9rem; min-height:80px;"><small class="text-muted">Detail item pesanan akan muncul di sini jika dipilih.</small></div>
            </div>
            <div class="col-md-6">
                <label for="tanggal_profit" class="form-label fw-semibold"><i class="bi bi-calendar-check me-2"></i>Tanggal Profit (Opsional)</label>
                <input type="date" name="tanggal_profit" id="tanggal_profit" class="form-control" value="<?= isset($_POST['tanggal_profit']) ? htmlspecialchars($_POST['tanggal_profit']) : date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label for="total_profit" class="form-label fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Total Profit (Rp)</label>
                <input type="text" name="total_profit" id="total_profit" class="form-control" placeholder="Contoh: 150000.75" required pattern="[0-9]+([.][0-9]{1,2})?" value="<?= isset($_POST['total_profit']) ? htmlspecialchars($_POST['total_profit']) : '' ?>">
                <div class="invalid-feedback">Total profit wajib diisi dengan format angka yang benar (misal: 150000 atau 150000.75).</div>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Profit</button>
            </div>
        </form>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#id_detail_pesanan_select').select2({ theme: 'bootstrap-5', placeholder: "-- Pilih Detail Pesanan --", allowClear: true })
    .on('select2:select select2:unselect', function (e) {
        var selectedOption = e.params.data ? e.params.data.element : null;
        var previewPanel = $($(this).data('preview-target'));
        if (selectedOption && $(selectedOption).val() !== "") {
            previewPanel.html(
                '<strong>Pesanan Induk ID:</strong> #' + ($(selectedOption).data('id_pesanan')||'N/A') + '<br>' +
                '<strong>Item @:</strong> ' + ($(selectedOption).data('harga_item')||'N/A') + ' x ' + ($(selectedOption).data('jumlah_item')||'N/A') + '<br>' +
                '<strong>Tgl Pesan Induk:</strong> ' + ($(selectedOption).data('pesanan_tanggal')||'N/A') + '<br>' +
                '<strong>Oleh Pengguna:</strong> ' + ($(selectedOption).data('pengguna_nama')||'N/A') );
        } else { previewPanel.html('<small class="text-muted">Detail item pesanan akan muncul di sini jika dipilih.</small>'); }
    });
    // Pencegahan double submit
    $('#formTambahProfit').on('submit', function() {
        $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
    });
});
</script>
<?php require_once '../../templates/footer.php'; ?>