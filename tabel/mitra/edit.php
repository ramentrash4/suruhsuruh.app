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

$id_mitra = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_mitra <= 0) { $_SESSION['error_message'] = "ID Mitra tidak valid."; header("Location: index.php"); exit; }

$stmt_mitra = $koneksi->prepare("SELECT * FROM mitra WHERE Id_Mitra = ?");
if(!$stmt_mitra) { die("Prepare mitra gagal: ".$koneksi->error); }
$stmt_mitra->bind_param("i", $id_mitra);
$stmt_mitra->execute();
$result_mitra = $stmt_mitra->get_result();
if ($result_mitra->num_rows === 0) { $_SESSION['error_message'] = "Data mitra tidak ditemukan."; header("Location: index.php"); exit; }
$mitra = $result_mitra->fetch_assoc();
$stmt_mitra->close();

$perusahaan_list = $koneksi->query("SELECT Id_Perusahaan, Nama, CEO, Kota FROM perusahaan ORDER BY Nama ASC");
$layanan_list_all = $koneksi->query("SELECT Id_Layanan, Nama_Layanan, Jenis_Layanan FROM layanan WHERE Status_Aktif = 1 ORDER BY Nama_Layanan ASC");

$linked_layanan_ids = [];
$stmt_linked = $koneksi->prepare("SELECT Id_Layanan FROM terikat WHERE Id_Mitra = ?");
if($stmt_linked){
    $stmt_linked->bind_param("i", $id_mitra);
    $stmt_linked->execute();
    $result_linked = $stmt_linked->get_result();
    while($row = $result_linked->fetch_assoc()){ $linked_layanan_ids[] = $row['Id_Layanan']; }
    $stmt_linked->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_mitra = trim($_POST['nama_mitra']);
    $no_telp = trim($_POST['no_telp']);
    $spesialis_mitra = trim($_POST['spesialis_mitra']);
    $id_perusahaan = isset($_POST['id_perusahaan']) && !empty($_POST['id_perusahaan']) ? (int)$_POST['id_perusahaan'] : null;
    $layanan_terpilih_baru = isset($_POST['id_layanan']) && is_array($_POST['id_layanan']) ? $_POST['id_layanan'] : [];

    if (empty($nama_mitra) || empty($no_telp) || empty($spesialis_mitra)) {
        $error_message = "Nama Mitra, No. Telepon, dan Spesialis Mitra wajib diisi.";
    } else {
        $koneksi->begin_transaction();
        try {
            $sql_update_mitra = "UPDATE mitra SET Nama_Mitra = ?, No_Telp = ?, Spesialis_Mitra = ?, Id_Perusahaan = ? WHERE Id_Mitra = ?";
            $stmt_update = $koneksi->prepare($sql_update_mitra);
            if($stmt_update === false) throw new Exception("Prepare update mitra gagal: ".$koneksi->error);
            $stmt_update->bind_param("sssii", $nama_mitra, $no_telp, $spesialis_mitra, $id_perusahaan, $id_mitra);
            if(!$stmt_update->execute()) throw new Exception("Eksekusi update mitra gagal: ".$stmt_update->error);
            $stmt_update->close();

            $stmt_delete_terikat = $koneksi->prepare("DELETE FROM terikat WHERE Id_Mitra = ?");
            if($stmt_delete_terikat === false) throw new Exception("Prepare delete terikat gagal: ".$koneksi->error);
            $stmt_delete_terikat->bind_param("i", $id_mitra);
            if(!$stmt_delete_terikat->execute()) throw new Exception("Eksekusi delete terikat gagal: ".$stmt_delete_terikat->error);
            $stmt_delete_terikat->close();

            if (!empty($layanan_terpilih_baru)) {
                $sql_insert_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES (?, ?)";
                $stmt_insert_terikat = $koneksi->prepare($sql_insert_terikat);
                if($stmt_insert_terikat === false) throw new Exception("Prepare insert terikat gagal: ".$koneksi->error);
                foreach ($layanan_terpilih_baru as $id_layanan_single) {
                    $id_layanan_int = (int)$id_layanan_single;
                    $stmt_insert_terikat->bind_param("ii", $id_mitra, $id_layanan_int);
                    if(!$stmt_insert_terikat->execute()) throw new Exception("Eksekusi insert terikat gagal: ".$stmt_insert_terikat->error);
                }
                $stmt_insert_terikat->close();
            }
            $koneksi->commit();
            $_SESSION['success_message'] = "Data mitra dan layanan terkait berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = $e->getMessage();
        }
    }
    // Re-populate for form
    $mitra['Nama_Mitra'] = $nama_mitra; $mitra['No_Telp'] = $no_telp; 
    $mitra['Spesialis_Mitra'] = $spesialis_mitra; $mitra['Id_Perusahaan'] = $id_perusahaan;
    $linked_layanan_ids = $layanan_terpilih_baru;
}
$page_title = "Edit Data Mitra #" . $mitra['Id_Mitra'];
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
     <div class="card-header bg-light-subtle"><h5 class="mb-0">Mengedit: <?= htmlspecialchars($mitra['Nama_Mitra']) ?></h5></div>
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formEditMitra" novalidate>
            <div class="col-md-6"><label for="nama_mitra" class="form-label fw-semibold"><i class="bi bi-person-badge me-2"></i>Nama Mitra</label><input type="text" name="nama_mitra" id="nama_mitra" class="form-control" required value="<?= htmlspecialchars($mitra['Nama_Mitra']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="no_telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" name="no_telp" id="no_telp" class="form-control" required value="<?= htmlspecialchars($mitra['No_Telp']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-12"><label for="spesialis_mitra" class="form-label fw-semibold"><i class="bi bi-star-fill me-2"></i>Spesialis Mitra</label><input type="text" name="spesialis_mitra" id="spesialis_mitra" class="form-control" required value="<?= htmlspecialchars($mitra['Spesialis_Mitra']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            
            <div class="col-md-7">
                <label for="id_perusahaan_select" class="form-label fw-semibold"><i class="bi bi-building me-2"></i>Perusahaan Afiliasi (Opsional)</label>
                <select name="id_perusahaan" id="id_perusahaan_select" class="form-select select2-init" data-preview-target="#detail_perusahaan_panel">
                    <option value="">-- Pilih Perusahaan --</option>
                    <?php if($perusahaan_list) mysqli_data_seek($perusahaan_list,0); while ($p = $perusahaan_list->fetch_assoc()) : ?><option value="<?= $p['Id_Perusahaan'] ?>" <?= ($p['Id_Perusahaan'] == $mitra['Id_Perusahaan']) ? 'selected' : '' ?> data-nama_perusahaan="<?= htmlspecialchars($p['Nama']) ?>" data-ceo_perusahaan="<?= htmlspecialchars($p['CEO']) ?>" data-kota_perusahaan="<?= htmlspecialchars($p['Kota']) ?>"><?= htmlspecialchars($p['Nama']) ?></option><?php endwhile; ?>
                </select>
            </div>
             <div class="col-md-5">
                <label class="form-label fw-semibold">Detail Perusahaan:</label>
                <div id="detail_perusahaan_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:70px;"><small class="text-muted">Pilih perusahaan untuk detail.</small></div>
            </div>

            <div class="col-12 mt-4">
                <label class="form-label fw-semibold"><i class="bi bi-card-checklist me-2"></i>Layanan yang Disediakan:</label>
                <div class="row ps-2" style="max-height: 200px; overflow-y: auto;">
                    <?php if($layanan_list_all) mysqli_data_seek($layanan_list_all,0); while ($l = $layanan_list_all->fetch_assoc()) : ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="id_layanan[]" value="<?= $l['Id_Layanan'] ?>" id="layanan_<?= $l['Id_Layanan'] ?>" <?= (in_array($l['Id_Layanan'], $linked_layanan_ids)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="layanan_<?= $l['Id_Layanan'] ?>"><?= htmlspecialchars($l['Nama_Layanan']) ?> <small class="text-muted">(<?= htmlspecialchars($l['Jenis_Layanan']) ?>)</small></label>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
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
    var selectPerusahaan = $('#id_perusahaan_select');
    selectPerusahaan.select2({ theme: 'bootstrap-5', placeholder: "-- Pilih Perusahaan --", allowClear: true })
    .on('select2:select select2:unselect', function (e) {
        var selectedOption = e.params.data ? e.params.data.element : null;
        var previewPanel = $($(this).data('preview-target'));
        if (selectedOption && $(selectedOption).val() !== "") {
            previewPanel.html('<strong>Nama:</strong> ' + ($(selectedOption).data('nama_perusahaan')||'N/A') + '<br>' + '<strong>CEO:</strong> ' + ($(selectedOption).data('ceo_perusahaan')||'N/A') + '<br>' + '<strong>Kota:</strong> ' + ($(selectedOption).data('kota_perusahaan')||'N/A'));
        } else { previewPanel.html('<small class="text-muted">Detail perusahaan akan muncul.</small>'); }
    });
    if(selectPerusahaan.val() && selectPerusahaan.val() !== "") { // Trigger for initial load
         selectPerusahaan.trigger({ type: 'select2:select', params: { data: { element: selectPerusahaan.find('option:selected')[0] } } });
    }
    $('#formEditMitra').on('submit', function() { $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...'); });
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>