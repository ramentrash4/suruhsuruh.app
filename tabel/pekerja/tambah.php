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

$perusahaan_list_query = $koneksi->query("SELECT * FROM perusahaan ORDER BY Nama ASC");
if (!$perusahaan_list_query) { die("Error fetching perusahaan list: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_perusahaan = isset($_POST['id_perusahaan']) ? (int)$_POST['id_perusahaan'] : 0;
    $nama_depan = trim($_POST['nama_depan']);
    $nama_tengah = trim($_POST['nama_tengah']);
    $nama_belakang = trim($_POST['nama_belakang']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $no_telp = trim($_POST['no_telp']); // Perhatikan nama kolom NO_Telp di DB

    if ($id_perusahaan <= 0 || empty($nama_depan) || empty($nama_belakang) || empty($tanggal_lahir) || empty($no_telp)) {
        $error_message = "Semua field (kecuali Nama Tengah) dan Perusahaan wajib diisi.";
    } else {
        $sql = "INSERT INTO pekerja (Id_Perusahaan, Nama_Depan, Nama_Tengah, Nama_Belakang, Tanggal_lahir, NO_Telp) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
        else {
            $stmt->bind_param("isssss", $id_perusahaan, $nama_depan, $nama_tengah, $nama_belakang, $tanggal_lahir, $no_telp);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Data pekerja baru berhasil ditambahkan!";
                header("Location: index.php");
                exit;
            } else { $error_message = "Gagal menambahkan pekerja: " . $stmt->error; }
            $stmt->close();
        }
    }
}
$page_title = "Tambah Data Pekerja";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-person-plus-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahPekerja" novalidate>
            <div class="col-md-7">
                <label for="id_perusahaan_select" class="form-label fw-semibold"><i class="bi bi-building-check me-2"></i>Perusahaan (Wajib)</label>
                <select name="id_perusahaan" id="id_perusahaan_select" class="form-select select2-init" required data-preview-target="#detail_perusahaan_panel">
                    <option value="">-- Pilih Perusahaan --</option>
                    <?php if($perusahaan_list_query) while ($p = $perusahaan_list_query->fetch_assoc()) : ?><option value="<?= $p['Id_Perusahaan'] ?>" data-nama_perusahaan="<?= htmlspecialchars($p['Nama']) ?>" data-ceo_perusahaan="<?= htmlspecialchars($p['CEO']) ?>" data-kota_perusahaan="<?= htmlspecialchars($p['Kota']) ?>"><?= htmlspecialchars($p['Nama']) ?> (ID: <?= $p['Id_Perusahaan'] ?>)</option><?php endwhile; ?>
                </select><div class="invalid-feedback">Perusahaan wajib dipilih.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Detail Perusahaan Terpilih:</label>
                <div id="detail_perusahaan_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:70px;"><small class="text-muted">Detail perusahaan akan muncul.</small></div>
            </div>

            <div class="col-md-4"><label for="nama_depan" class="form-label fw-semibold"><i class="bi bi-person-fill me-2"></i>Nama Depan</label><input type="text" name="nama_depan" id="nama_depan" class="form-control" required value="<?= isset($_POST['nama_depan']) ? htmlspecialchars($_POST['nama_depan']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-4"><label for="nama_tengah" class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Nama Tengah <span class="text-muted fw-normal">(Ops.)</span></label><input type="text" name="nama_tengah" id="nama_tengah" class="form-control" value="<?= isset($_POST['nama_tengah']) ? htmlspecialchars($_POST['nama_tengah']) : '' ?>"></div>
            <div class="col-md-4"><label for="nama_belakang" class="form-label fw-semibold">Nama Belakang</label><input type="text" name="nama_belakang" id="nama_belakang" class="form-control" required value="<?= isset($_POST['nama_belakang']) ? htmlspecialchars($_POST['nama_belakang']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            
            <div class="col-md-6"><label for="tanggal_lahir" class="form-label fw-semibold"><i class="bi bi-calendar-event me-2"></i>Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control" required value="<?= isset($_POST['tanggal_lahir']) ? htmlspecialchars($_POST['tanggal_lahir']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="no_telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" name="no_telp" id="no_telp" class="form-control" required value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>

            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Pekerja</button>
            </div>
        </form>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#id_perusahaan_select').select2({ theme: 'bootstrap-5', placeholder: "-- Pilih Perusahaan --", allowClear: true })
    .on('select2:select select2:unselect', function (e) {
        var selectedOption = e.params.data ? e.params.data.element : null;
        var previewPanel = $($(this).data('preview-target'));
        if (selectedOption && $(selectedOption).val() !== "") {
            previewPanel.html(
                '<strong>Nama:</strong> ' + ($(selectedOption).data('nama_perusahaan')||'N/A') + '<br>' +
                '<strong>CEO:</strong> ' + ($(selectedOption).data('ceo_perusahaan')||'N/A') + '<br>' +
                '<strong>Kota:</strong> ' + ($(selectedOption).data('kota_perusahaan')||'N/A'));
        } else { previewPanel.html('<small class="text-muted">Detail perusahaan akan muncul.</small>'); }
    });
    $('#formTambahPekerja').on('submit', function() { $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...'); });
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>