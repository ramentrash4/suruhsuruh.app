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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { $_SESSION['error_message'] = "ID Bayaran tidak valid."; header("Location: index.php"); exit; }

$stmt_get = $koneksi->prepare("SELECT * FROM bayaran WHERE Id_Pembayaran = ?");
if ($stmt_get === false) { die("Error preparing select query: " . $koneksi->error); }
$stmt_get->bind_param("i", $id);
if (!$stmt_get->execute()) { die("Error executing select query: " . $stmt_get->error); }
$result_get = $stmt_get->get_result();
if ($result_get->num_rows === 0) { $_SESSION['error_message'] = "Data bayaran tidak ditemukan."; header("Location: index.php"); exit; }
$data = $result_get->fetch_assoc();
$stmt_get->close();

// Ambil daftar pengguna untuk dropdown, sertakan semua data untuk preview
$pengguna_result = $koneksi->query("SELECT Id_pengguna, Nama_Depan, Nama_Belakang, Email, No_Telp, Alamat FROM pengguna WHERE status = 'aktif' ORDER BY Nama_Depan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = (int)$_POST['id_pengguna'];
    $tanggal = $_POST['tanggal'];
    $jumlah = preg_replace("/[^0-9]/", "", $_POST['jumlah']);

    if ($id_pengguna > 0 && !empty($tanggal) && !empty($jumlah)) {
        $sql = "UPDATE bayaran SET Id_Pengguna = ?, Tanggal = ?, Jumlah = ? WHERE Id_Pembayaran = ?";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing update statement: " . $koneksi->error; }
        else {
            $stmt->bind_param("isdi", $id_pengguna, $tanggal, $jumlah, $id);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Data bayaran berhasil diperbarui!";
                header("Location: index.php");
                exit;
            } else { $error_message = "Gagal memperbarui data: " . $stmt->error; }
            $stmt->close();
        }
    } else { $error_message = "Semua field wajib diisi dengan benar."; }
}

$page_title = "Edit Bayaran";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3">
            <div class="col-md-7">
                <label for="id_pengguna" class="form-label fw-semibold"><i class="bi bi-person-check-fill me-2"></i>Pilih Pengguna</label>
                <select name="id_pengguna" id="id_pengguna" class="form-select" required>
                    <option value="">-- Cari dan Pilih Pengguna --</option>
                    <?php if ($pengguna_result && $pengguna_result->num_rows > 0): ?>
                        <?php mysqli_data_seek($pengguna_result, 0); // Reset pointer jika perlu ?>
                        <?php while($p = $pengguna_result->fetch_assoc()): ?>
                        <option value="<?= $p['Id_pengguna'] ?>"
                                data-nama_lengkap="<?= htmlspecialchars(trim($p['Nama_Depan'] . ' ' . $p['Nama_Belakang'])) ?>"
                                data-email="<?= htmlspecialchars($p['Email']) ?>"
                                data-telp="<?= htmlspecialchars($p['No_Telp']) ?>"
                                data-alamat="<?= htmlspecialchars($p['Alamat']) ?>"
                                <?= ($p['Id_pengguna'] == $data['Id_Pengguna']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim($p['Nama_Depan'] . ' ' . $p['Nama_Belakang'])) ?> (ID: <?= $p['Id_pengguna'] ?>)
                        </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
             <div class="col-md-5">
                <label class="form-label fw-semibold">Detail Pengguna Terpilih:</label>
                <div id="penggunaPreviewPanel" class="p-3 border rounded bg-light" style="min-height: 120px; font-size: 0.9rem;">
                    <small class="text-muted">Pilih pengguna untuk melihat detail.</small>
                </div>
            </div>
            <div class="col-md-6 mt-3">
                <label for="tanggal" class="form-label fw-semibold"><i class="bi bi-calendar-week me-2"></i>Tanggal Bayaran</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required value="<?= htmlspecialchars($data['Tanggal']) ?>">
            </div>
            <div class="col-md-6 mt-3">
                <label for="jumlah" class="form-label fw-semibold"><i class="bi bi-cash-coin me-2"></i>Jumlah Bayaran (Rp)</label>
                <input type="text" name="jumlah" id="jumlah" class="form-control" required placeholder="Contoh: 50000" value="<?= number_format(preg_replace("/[^0-9]/", "", $data['Jumlah']), 0, ',', '.') ?>">
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" /><script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var selectPengguna = $('#id_pengguna');
    selectPengguna.select2({ theme: 'bootstrap-5', placeholder: "-- Cari dan Pilih Pengguna --", allowClear: true })
    .on('select2:select', function (e) {
        var selectedOption = e.params.data.element;
        var previewPanel = $('#penggunaPreviewPanel');
        if (selectedOption && $(selectedOption).val() !== "") {
            previewPanel.html(
                '<strong>Nama:</strong> ' + ($(selectedOption).data('nama_lengkap') || 'N/A') + '<br>' +
                '<strong>Email:</strong> ' + ($(selectedOption).data('email') || 'N/A') + '<br>' +
                '<strong>Telp:</strong> ' + ($(selectedOption).data('telp') || 'N/A') + '<br>' +
                '<strong>Alamat:</strong> <div style="white-space: pre-wrap; max-height: 60px; overflow-y:auto;">' + ($(selectedOption).data('alamat') || 'N/A') + '</div>');
        } else { previewPanel.html('<small class="text-muted">Pilih pengguna untuk melihat detail.</small>'); }
    }).on('select2:unselect', function () {
         $('#penggunaPreviewPanel').html('<small class="text-muted">Pilih pengguna untuk melihat detail.</small>');
    });
    // Trigger select event if a user is already selected (for edit page)
    if (selectPengguna.val() && selectPengguna.val() !== "") {
        selectPengguna.trigger({ type: 'select2:select', params: { data: { element: selectPengguna.find('option:selected')[0] } } });
    }
});
</script>
<?php require_once '../../templates/footer.php'; ?>