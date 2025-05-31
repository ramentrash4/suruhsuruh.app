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

$id_pekerja = isset($_GET['id_pekerja']) ? (int)$_GET['id_pekerja'] : 0;
$id_perusahaan_lama = isset($_GET['id_perusahaan']) ? (int)$_GET['id_perusahaan'] : 0;

if ($id_pekerja <= 0 || $id_perusahaan_lama <= 0) {
    $_SESSION['error_message'] = "ID Pekerja atau ID Perusahaan Lama tidak valid."; header("Location: index.php"); exit;
}

$stmt_current = $koneksi->prepare("SELECT * FROM pekerja WHERE Id_Pekerja = ? AND Id_Perusahaan = ?");
if($stmt_current === false) { die("Prepare failed: (" . $koneksi->errno . ") " . $koneksi->error); }
$stmt_current->bind_param("ii", $id_pekerja, $id_perusahaan_lama);
$stmt_current->execute();
$result_current = $stmt_current->get_result();
if ($result_current->num_rows === 0) { $_SESSION['error_message'] = "Data pekerja tidak ditemukan."; header("Location: index.php"); exit; }
$pekerja = $result_current->fetch_assoc();
$stmt_current->close();

$perusahaan_list_query = $koneksi->query("SELECT * FROM perusahaan ORDER BY Nama ASC");
if (!$perusahaan_list_query) { die("Error fetching perusahaan list: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_perusahaan_baru = isset($_POST['id_perusahaan']) ? (int)$_POST['id_perusahaan'] : 0; // Id_Perusahaan yang baru dari form
    $nama_depan = trim($_POST['nama_depan']);
    $nama_tengah = trim($_POST['nama_tengah']);
    $nama_belakang = trim($_POST['nama_belakang']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $no_telp = trim($_POST['no_telp']);

    if ($id_perusahaan_baru <= 0 || empty($nama_depan) || empty($nama_belakang) || empty($tanggal_lahir) || empty($no_telp)) {
        $error_message = "Semua field (kecuali Nama Tengah) dan Perusahaan wajib diisi.";
    } else {
        // Jika Id_Perusahaan tidak berubah, lakukan UPDATE biasa.
        // Jika Id_Perusahaan berubah, ini lebih kompleks karena bagian dari PK berubah.
        // Solusi paling aman jika PK bisa berubah adalah DELETE lalu INSERT, atau pastikan kombinasi baru belum ada.
        // Untuk sekarang, kita asumsikan Id_Pekerja tetap, dan Id_Perusahaan bisa diubah.
        // Perlu penanganan khusus jika kombinasi (Id_Pekerja, Id_Perusahaan_Baru) sudah ada.

        $sql = "UPDATE pekerja SET Id_Perusahaan = ?, Nama_Depan = ?, Nama_Tengah = ?, Nama_Belakang = ?, Tanggal_lahir = ?, NO_Telp = ? 
                WHERE Id_Pekerja = ? AND Id_Perusahaan = ?";
        $stmt_update = $koneksi->prepare($sql);
        if ($stmt_update === false) { $error_message = "Error preparing update: " . $koneksi->error; }
        else {
            $stmt_update->bind_param("isssssii", $id_perusahaan_baru, $nama_depan, $nama_tengah, $nama_belakang, $tanggal_lahir, $no_telp, $id_pekerja, $id_perusahaan_lama);
            if($stmt_update->execute()){
                $_SESSION['success_message'] = "Data pekerja berhasil diperbarui!";
                // Jika Id_Perusahaan berubah, link di index.php perlu Id_Perusahaan baru
                header("Location: index.php?id_perusahaan_highlight=" . $id_perusahaan_baru . "&id_pekerja_highlight=" . $id_pekerja); 
                exit;
            } else {
                if($koneksi->errno == 1062) { // Error duplikasi Primary Key
                     $error_message = "Gagal memperbarui: Kombinasi ID Pekerja #".$id_pekerja." dan ID Perusahaan #".$id_perusahaan_baru." sudah ada untuk pekerja lain.";
                } else {
                    $error_message = "Gagal memperbarui data pekerja: " . $stmt_update->error;
                }
            }
            $stmt_update->close();
        }
    }
    // Re-populate $pekerja with submitted data
    $pekerja['Id_Perusahaan'] = $id_perusahaan_baru;
    $pekerja['Nama_Depan'] = $nama_depan; $pekerja['Nama_Tengah'] = $nama_tengah; $pekerja['Nama_Belakang'] = $nama_belakang;
    $pekerja['Tanggal_lahir'] = $tanggal_lahir; $pekerja['NO_Telp'] = $no_telp;
}

$page_title = "Edit Data Pekerja #" . $pekerja['Id_Pekerja'];
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-person-gear me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formEditPekerja" novalidate>
            <div class="col-md-7">
                <label for="id_perusahaan_select" class="form-label fw-semibold"><i class="bi bi-building-check me-2"></i>Perusahaan (Wajib)</label>
                <select name="id_perusahaan" id="id_perusahaan_select" class="form-select select2-init" required data-preview-target="#detail_perusahaan_panel">
                    <option value="">-- Pilih Perusahaan --</option>
                    <?php if($perusahaan_list_query) mysqli_data_seek($perusahaan_list_query, 0); while ($p = $perusahaan_list_query->fetch_assoc()) : ?><option value="<?= $p['Id_Perusahaan'] ?>" <?= ($p['Id_Perusahaan'] == $pekerja['Id_Perusahaan']) ? 'selected' : '' ?> data-nama_perusahaan="<?= htmlspecialchars($p['Nama']) ?>" data-ceo_perusahaan="<?= htmlspecialchars($p['CEO']) ?>" data-kota_perusahaan="<?= htmlspecialchars($p['Kota']) ?>"><?= htmlspecialchars($p['Nama']) ?> (ID: <?= $p['Id_Perusahaan'] ?>)</option><?php endwhile; ?>
                </select><div class="invalid-feedback">Perusahaan wajib dipilih.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Detail Perusahaan Terpilih:</label>
                <div id="detail_perusahaan_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:70px;"><small class="text-muted">Detail perusahaan akan muncul.</small></div>
            </div>

            <div class="col-md-4"><label for="nama_depan" class="form-label fw-semibold"><i class="bi bi-person-fill me-2"></i>Nama Depan</label><input type="text" name="nama_depan" id="nama_depan" class="form-control" required value="<?= htmlspecialchars($pekerja['Nama_Depan']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-4"><label for="nama_tengah" class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Nama Tengah <span class="text-muted fw-normal">(Ops.)</span></label><input type="text" name="nama_tengah" id="nama_tengah" class="form-control" value="<?= htmlspecialchars($pekerja['Nama_Tengah']) ?>"></div>
            <div class="col-md-4"><label for="nama_belakang" class="form-label fw-semibold">Nama Belakang</label><input type="text" name="nama_belakang" id="nama_belakang" class="form-control" required value="<?= htmlspecialchars($pekerja['Nama_Belakang']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            
            <div class="col-md-6"><label for="tanggal_lahir" class="form-label fw-semibold"><i class="bi bi-calendar-event me-2"></i>Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control" required value="<?= htmlspecialchars($pekerja['Tanggal_lahir']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="no_telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" name="no_telp" id="no_telp" class="form-control" required value="<?= htmlspecialchars($pekerja['NO_Telp']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>

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
    $('#formEditPekerja').on('submit', function() { $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...'); });
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>