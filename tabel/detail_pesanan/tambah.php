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

$id_detail_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_detail_pesanan <= 0) { $_SESSION['error_message'] = "ID Detail Pesanan tidak valid."; header("Location: index.php"); exit; }

$stmt_get = $koneksi->prepare("SELECT * FROM detail_pesanan WHERE Id_DetailPesanan = ?");
if($stmt_get === false) { die("Prepare failed: (" . $koneksi->errno . ") " . $koneksi->error); }
$stmt_get->bind_param("i", $id_detail_pesanan);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
if ($result_get->num_rows === 0) { $_SESSION['error_message'] = "Data detail pesanan tidak ditemukan."; header("Location: index.php"); exit; }
$detail_pesanan = $result_get->fetch_assoc();
$stmt_get->close();

$pesanan_list_query = $koneksi->query("
    SELECT ps.Id_Pesanan, ps.Tanggal, ps.Status_Pesanan, 
           pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Belakang AS Pengguna_Nama_Belakang,
           l.Nama_Layanan AS Layanan_Nama
    FROM pesanan ps
    LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
    LEFT JOIN layanan l ON ps.Id_Layanan = l.Id_Layanan
    ORDER BY ps.Tanggal DESC, ps.Id_Pesanan DESC
");
if (!$pesanan_list_query) { die("Error fetching pesanan list: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan_form = isset($_POST['id_pesanan']) ? (int)$_POST['id_pesanan'] : 0;
    $harga = filter_input(INPUT_POST, 'harga', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;

    if ($id_pesanan_form <= 0 || !is_numeric($harga) || $harga < 0 || $jumlah <= 0) {
        $error_message = "ID Pesanan, Harga, dan Jumlah wajib diisi dengan benar.";
        $detail_pesanan['Id_Pesanan'] = $id_pesanan_form; // Keep user's selection
        $detail_pesanan['Harga'] = $_POST['harga'];
        $detail_pesanan['Jumlah'] = $_POST['jumlah'];
    } else {
        $sql = "UPDATE detail_pesanan SET Id_Pesanan = ?, Harga = ?, Jumlah = ? WHERE Id_DetailPesanan = ?";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
        else {
            $stmt->bind_param("idii", $id_pesanan_form, $harga, $jumlah, $id_detail_pesanan);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Detail pesanan #".$id_detail_pesanan." berhasil diperbarui!";
                header("Location: index.php");
                exit;
            } else { $error_message = "Gagal memperbarui detail pesanan: " . $stmt->error; }
            $stmt->close();
        }
    }
}
$page_title = "Edit Detail Pesanan #" . $detail_pesanan['Id_DetailPesanan'];
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formEditDetail" novalidate>
            <div class="col-md-12">
                <label for="id_pesanan_select" class="form-label fw-semibold"><i class="bi bi-receipt me-2"></i>Pesanan Induk (Wajib)</label>
                <select name="id_pesanan" id="id_pesanan_select" class="form-select select2-init" required data-preview-target="#detail_pesanan_panel">
                    <option value="">-- Pilih Pesanan Induk --</option>
                    <?php if($pesanan_list_query) mysqli_data_seek($pesanan_list_query, 0); while ($p = $pesanan_list_query->fetch_assoc()) : ?>
                    <option value="<?= $p['Id_Pesanan'] ?>" 
                            <?= ($p['Id_Pesanan'] == $detail_pesanan['Id_Pesanan']) ? 'selected' : '' ?>
                            data-tanggal_pesanan="<?= htmlspecialchars(date('d M Y', strtotime($p['Tanggal']))) ?>"
                            data-status_pesanan="<?= htmlspecialchars($p['Status_Pesanan']) ?>"
                            data-nama_pengguna="<?= htmlspecialchars(trim($p['Pengguna_Nama_Depan'].' '.$p['Pengguna_Nama_Belakang'])) ?>"
                            data-nama_layanan="<?= htmlspecialchars($p['Layanan_Nama']) ?>">
                        ID: <?= $p['Id_Pesanan'] ?> (<?= htmlspecialchars(date('d M Y', strtotime($p['Tanggal']))) ?> - Oleh: <?= htmlspecialchars(trim($p['Pengguna_Nama_Depan'].' '.$p['Pengguna_Nama_Belakang'])) ?> - Layanan: <?= htmlspecialchars($p['Layanan_Nama']) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
                <div class="invalid-feedback">Pesanan Induk wajib dipilih.</div>
                <div id="detail_pesanan_panel" class="detail-preview-panel mt-2 p-3 border rounded bg-light" style="font-size:0.9rem; min-height:100px;"><small class="text-muted">Detail pesanan induk akan muncul di sini.</small></div>
            </div>
            <div class="col-md-6">
                <label for="harga" class="form-label fw-semibold"><i class="bi bi-tag-fill me-2"></i>Harga Satuan (Rp)</label>
                <input type="number" name="harga" id="harga" class="form-control" step="0.01" min="0" required value="<?= htmlspecialchars($detail_pesanan['Harga']) ?>">
                <div class="invalid-feedback">Harga wajib diisi dan harus angka positif.</div>
            </div>
            <div class="col-md-6">
                <label for="jumlah" class="form-label fw-semibold"><i class="bi bi-bar-chart-line-fill me-2"></i>Jumlah Item</label>
                <input type="number" name="jumlah" id="jumlah" class="form-control" min="1" required value="<?= htmlspecialchars($detail_pesanan['Jumlah']) ?>">
                <div class="invalid-feedback">Jumlah item wajib diisi dan minimal 1.</div>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var selectPesanan = $('#id_pesanan_select');
    selectPesanan.select2({ theme: 'bootstrap-5', placeholder: "-- Pilih Pesanan Induk --", allowClear: true })
    .on('select2:select select2:unselect', function (e) {
        var selectedOption = e.params.data ? e.params.data.element : null;
        var previewPanel = $($(this).data('preview-target'));
        if (selectedOption && $(selectedOption).val() !== "") {
            previewPanel.html(
                '<strong>Tgl Pesan:</strong> ' + ($(selectedOption).data('tanggal_pesanan')||'N/A') + '<br>' +
                '<strong>Status:</strong> ' + ($(selectedOption).data('status_pesanan')||'N/A') + '<br>' +
                '<strong>Pengguna:</strong> ' + ($(selectedOption).data('nama_pengguna')||'N/A') + '<br>' +
                '<strong>Layanan:</strong> ' + ($(selectedOption).data('nama_layanan')||'N/A'));
        } else { previewPanel.html('<small class="text-muted">Detail pesanan induk akan muncul di sini.</small>'); }
    });
    if (selectPesanan.val() && selectPesanan.val() !== "") { // Trigger for initial load
        selectPesanan.trigger({ type: 'select2:select', params: { data: { element: selectPesanan.find('option:selected')[0] } } });
    }
    $('#formEditDetail').on('submit', function() {
        $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
    });
});
</script>
<?php require_once '../../templates/footer.php'; ?>