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

$id_perusahaan = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_perusahaan = intval($_GET['id']);
} else {
    // Jika tidak ada ID, coba ambil ID perusahaan pertama sebagai default
    $stmt_first = $koneksi->prepare("SELECT Id_Perusahaan FROM perusahaan ORDER BY Id_Perusahaan ASC LIMIT 1");
    if ($stmt_first) {
        $stmt_first->execute();
        $result_first = $stmt_first->get_result();
        if ($result_first && $result_first->num_rows > 0) {
            $id_perusahaan = $result_first->fetch_assoc()['Id_Perusahaan'];
        }
        $stmt_first->close();
    }
}

if ($id_perusahaan === null) {
    $_SESSION['error_message'] = "ID Perusahaan tidak valid atau tidak ditemukan.";
    header("Location: index.php"); // Arahkan ke index jika tidak ada perusahaan untuk diedit
    exit;
}

$stmt_current = $koneksi->prepare("SELECT * FROM perusahaan WHERE Id_Perusahaan = ?");
if($stmt_current === false) { die("Prepare failed: (" . $koneksi->errno . ") " . $koneksi->error); }
$stmt_current->bind_param("i", $id_perusahaan);
$stmt_current->execute();
$result_current = $stmt_current->get_result();
if ($result_current->num_rows === 0) {
    $_SESSION['error_message'] = "Data perusahaan dengan ID #".$id_perusahaan." tidak ditemukan.";
    header("Location: index.php");
    exit;
}
$perusahaan = $result_current->fetch_assoc();
$stmt_current->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $ceo = trim($_POST['ceo']);
    $kota = trim($_POST['kota']);
    $jalan = trim($_POST['jalan']);
    $kode_pos = trim($_POST['kode_pos']);

    if (empty($nama) || empty($ceo) || empty($kota) || empty($jalan) || empty($kode_pos)) {
        $error_message = "Semua field wajib diisi.";
    } else {
        $sql = "UPDATE perusahaan SET Nama = ?, CEO = ?, Kota = ?, Jalan = ?, Kode_Pos = ? WHERE Id_Perusahaan = ?";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
        else {
            $stmt->bind_param("sssssi", $nama, $ceo, $kota, $jalan, $kode_pos, $id_perusahaan);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Data perusahaan berhasil diperbarui!";
                header("Location: index.php");
                exit;
            } else { $error_message = "Gagal memperbarui data perusahaan: " . $stmt->error; }
            $stmt->close();
        }
    }
    // Re-populate $perusahaan with submitted data for form display on error
    $perusahaan['Nama'] = $nama; $perusahaan['CEO'] = $ceo; $perusahaan['Kota'] = $kota; 
    $perusahaan['Jalan'] = $jalan; $perusahaan['Kode_Pos'] = $kode_pos;
}
$page_title = "Edit Data Perusahaan";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle"><h5 class="mb-0">Mengedit: <?= htmlspecialchars($perusahaan['Nama']) ?></h5></div>
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formEditPerusahaan" novalidate>
            <div class="col-md-12">
                <label for="nama" class="form-label fw-semibold"><i class="bi bi-building me-2"></i>Nama Perusahaan</label>
                <input type="text" name="nama" id="nama" class="form-control" required value="<?= htmlspecialchars($perusahaan['Nama']) ?>">
                <div class="invalid-feedback">Nama perusahaan wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="ceo" class="form-label fw-semibold"><i class="bi bi-person-badge me-2"></i>CEO</label>
                <input type="text" name="ceo" id="ceo" class="form-control" required value="<?= htmlspecialchars($perusahaan['CEO']) ?>">
                <div class="invalid-feedback">Nama CEO wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="kota" class="form-label fw-semibold"><i class="bi bi-pin-map-fill me-2"></i>Kota</label>
                <input type="text" name="kota" id="kota" class="form-control" required value="<?= htmlspecialchars($perusahaan['Kota']) ?>">
                <div class="invalid-feedback">Kota wajib diisi.</div>
            </div>
            <div class="col-12">
                <label for="jalan" class="form-label fw-semibold"><i class="bi bi-signpost-2-fill me-2"></i>Alamat Jalan</label>
                <input type="text" name="jalan" id="jalan" class="form-control" required value="<?= htmlspecialchars($perusahaan['Jalan']) ?>">
                <div class="invalid-feedback">Alamat jalan wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="kode_pos" class="form-label fw-semibold"><i class="bi bi-mailbox me-2"></i>Kode Pos</label>
                <input type="text" name="kode_pos" id="kode_pos" class="form-control" required pattern="[0-9]{5}" value="<?= htmlspecialchars($perusahaan['Kode_Pos']) ?>">
                <div class="invalid-feedback">Kode pos wajib diisi dengan 5 digit angka.</div>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<script>
// Pencegahan double submit
document.getElementById('formEditPerusahaan')?.addEventListener('submit', function() {
    document.getElementById('submitButton').setAttribute('disabled', 'true');
    document.getElementById('submitButton').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>