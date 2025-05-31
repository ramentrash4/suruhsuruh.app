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

$perusahaan_list = $koneksi->query("SELECT Id_Perusahaan, Nama, CEO, Kota FROM perusahaan ORDER BY Nama ASC");
$layanan_list_all = $koneksi->query("SELECT Id_Layanan, Nama_Layanan, Jenis_Layanan FROM layanan WHERE Status_Aktif = 1 ORDER BY Nama_Layanan ASC");

if (!$perusahaan_list || !$layanan_list_all) { die("Error fetching lists: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mitra = trim($_POST['nama_mitra']);
    $no_telp = trim($_POST['no_telp']);
    $spesialis_mitra = trim($_POST['spesialis_mitra']);
    $id_perusahaan = isset($_POST['id_perusahaan']) && !empty($_POST['id_perusahaan']) ? (int)$_POST['id_perusahaan'] : null;
    $layanan_terpilih = isset($_POST['id_layanan']) && is_array($_POST['id_layanan']) ? $_POST['id_layanan'] : [];

    if (empty($nama_mitra) || empty($no_telp) || empty($spesialis_mitra)) {
        $error_message = "Nama Mitra, No. Telepon, dan Spesialis Mitra wajib diisi.";
    } else {
        $koneksi->begin_transaction();
        try {
            $sql_mitra = "INSERT INTO mitra (Nama_Mitra, No_Telp, Spesialis_Mitra, Id_Perusahaan) VALUES (?, ?, ?, ?)";
            $stmt_mitra = $koneksi->prepare($sql_mitra);
            if ($stmt_mitra === false) throw new Exception("Prepare mitra gagal: " . $koneksi->error);
            $stmt_mitra->bind_param("sssi", $nama_mitra, $no_telp, $spesialis_mitra, $id_perusahaan);
            if (!$stmt_mitra->execute()) throw new Exception("Eksekusi mitra gagal: " . $stmt_mitra->error);
            
            $new_mitra_id = $koneksi->insert_id;
            $stmt_mitra->close();

            if (!empty($layanan_terpilih) && $new_mitra_id > 0) {
                $sql_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES (?, ?)";
                $stmt_terikat = $koneksi->prepare($sql_terikat);
                if ($stmt_terikat === false) throw new Exception("Prepare terikat gagal: " . $koneksi->error);
                
                foreach ($layanan_terpilih as $id_layanan_single) {
                    $id_layanan_int = (int)$id_layanan_single;
                    $stmt_terikat->bind_param("ii", $new_mitra_id, $id_layanan_int);
                    if (!$stmt_terikat->execute()) throw new Exception("Eksekusi terikat gagal: " . $stmt_terikat->error);
                }
                $stmt_terikat->close();
            }
            $koneksi->commit();
            $_SESSION['success_message'] = "Mitra baru dan layanan terkait berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = $e->getMessage();
        }
    }
}
$page_title = "Tambah Data Mitra";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-person-plus-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahMitra" novalidate>
            <div class="col-md-6"><label for="nama_mitra" class="form-label fw-semibold"><i class="bi bi-person-badge me-2"></i>Nama Mitra</label><input type="text" name="nama_mitra" id="nama_mitra" class="form-control" required value="<?= isset($_POST['nama_mitra']) ? htmlspecialchars($_POST['nama_mitra']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="no_telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" name="no_telp" id="no_telp" class="form-control" required value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-12"><label for="spesialis_mitra" class="form-label fw-semibold"><i class="bi bi-star-fill me-2"></i>Spesialis Mitra</label><input type="text" name="spesialis_mitra" id="spesialis_mitra" class="form-control" required value="<?= isset($_POST['spesialis_mitra']) ? htmlspecialchars($_POST['spesialis_mitra']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            
            <div class="col-md-7">
                <label for="id_perusahaan_select" class="form-label fw-semibold"><i class="bi bi-building me-2"></i>Perusahaan Afiliasi (Opsional)</label>
                <select name="id_perusahaan" id="id_perusahaan_select" class="form-select select2-init" data-preview-target="#detail_perusahaan_panel">
                    <option value="">-- Pilih Perusahaan --</option>
                    <?php if($perusahaan_list) mysqli_data_seek($perusahaan_list,0); while ($p = $perusahaan_list->fetch_assoc()) : ?><option value="<?= $p['Id_Perusahaan'] ?>" data-nama_perusahaan="<?= htmlspecialchars($p['Nama']) ?>" data-ceo_perusahaan="<?= htmlspecialchars($p['CEO']) ?>" data-kota_perusahaan="<?= htmlspecialchars($p['Kota']) ?>" <?= (isset($_POST['id_perusahaan']) && $_POST['id_perusahaan'] == $p['Id_Perusahaan']) ? 'selected' : '' ?>><?= htmlspecialchars($p['Nama']) ?></option><?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Detail Perusahaan:</label>
                <div id="detail_perusahaan_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:70px;"><small class="text-muted">Pilih perusahaan untuk detail.</small></div>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label fw-semibold"><i class="bi bi-card-checklist me-2"></i>Layanan yang Disediakan (Pilih satu atau lebih):</label>
                <div class="row ps-2" style="max-height: 200px; overflow-y: auto;">
                    <?php if($layanan_list_all) mysqli_data_seek($layanan_list_all,0); while ($l = $layanan_list_all->fetch_assoc()) : ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="id_layanan[]" value="<?= $l['Id_Layanan'] ?>" id="layanan_<?= $l['Id_Layanan'] ?>" <?= (isset($_POST['id_layanan']) && is_array($_POST['id_layanan']) && in_array($l['Id_Layanan'], $_POST['id_layanan'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="layanan_<?= $l['Id_Layanan'] ?>"><?= htmlspecialchars($l['Nama_Layanan']) ?> <small class="text-muted">(<?= htmlspecialchars($l['Jenis_Layanan']) ?>)</small></label>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Mitra</button>
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
            previewPanel.html('<strong>Nama:</strong> ' + ($(selectedOption).data('nama_perusahaan')||'N/A') + '<br>' + '<strong>CEO:</strong> ' + ($(selectedOption).data('ceo_perusahaan')||'N/A') + '<br>' + '<strong>Kota:</strong> ' + ($(selectedOption).data('kota_perusahaan')||'N/A'));
        } else { previewPanel.html('<small class="text-muted">Detail perusahaan akan muncul.</small>'); }
    });
    $('#formTambahMitra').on('submit', function() { $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...'); });
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>