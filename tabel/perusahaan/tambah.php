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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $ceo = trim($_POST['ceo']);
    $kota = trim($_POST['kota']);
    $jalan = trim($_POST['jalan']);
    $kode_pos = trim($_POST['kode_pos']);

    if (empty($nama) || empty($ceo) || empty($kota) || empty($jalan) || empty($kode_pos)) {
        $error_message = "Semua field wajib diisi.";
    } else {
        // Cek apakah sudah ada data perusahaan (opsional, jika hanya boleh 1 perusahaan)
        // $check_stmt = $koneksi->prepare("SELECT Id_Perusahaan FROM perusahaan LIMIT 1");
        // $check_stmt->execute();
        // $check_result = $check_stmt->get_result();
        // if ($check_result->num_rows > 0 && JIKA_HANYA_SATU_PERUSAHAAN) {
        //     $error_message = "Data perusahaan utama sudah ada. Anda hanya bisa mengeditnya.";
        // } else {
            $sql = "INSERT INTO perusahaan (Nama, CEO, Kota, Jalan, Kode_Pos) VALUES (?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($sql);
            if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
            else {
                $stmt->bind_param("sssss", $nama, $ceo, $kota, $jalan, $kode_pos);
                if($stmt->execute()) {
                    $_SESSION['success_message'] = "Data perusahaan baru berhasil ditambahkan!";
                    header("Location: index.php");
                    exit;
                } else { $error_message = "Gagal menambahkan data perusahaan: " . $stmt->error; }
                $stmt->close();
            }
        // }
        // if(isset($check_stmt)) $check_stmt->close();
    }
}
$page_title = "Tambah Data Perusahaan";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-building-add me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahPerusahaan" novalidate>
            <div class="col-md-12">
                <label for="nama" class="form-label fw-semibold"><i class="bi bi-building me-2"></i>Nama Perusahaan</label>
                <input type="text" name="nama" id="nama" class="form-control" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
                <div class="invalid-feedback">Nama perusahaan wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="ceo" class="form-label fw-semibold"><i class="bi bi-person-badge me-2"></i>CEO</label>
                <input type="text" name="ceo" id="ceo" class="form-control" required value="<?= isset($_POST['ceo']) ? htmlspecialchars($_POST['ceo']) : '' ?>">
                <div class="invalid-feedback">Nama CEO wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="kota" class="form-label fw-semibold"><i class="bi bi-pin-map-fill me-2"></i>Kota</label>
                <input type="text" name="kota" id="kota" class="form-control" required value="<?= isset($_POST['kota']) ? htmlspecialchars($_POST['kota']) : '' ?>">
                <div class="invalid-feedback">Kota wajib diisi.</div>
            </div>
            <div class="col-12">
                <label for="jalan" class="form-label fw-semibold"><i class="bi bi-signpost-2-fill me-2"></i>Alamat Jalan</label>
                <input type="text" name="jalan" id="jalan" class="form-control" required value="<?= isset($_POST['jalan']) ? htmlspecialchars($_POST['jalan']) : '' ?>">
                <div class="invalid-feedback">Alamat jalan wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="kode_pos" class="form-label fw-semibold"><i class="bi bi-mailbox me-2"></i>Kode Pos</label>
                <input type="text" name="kode_pos" id="kode_pos" class="form-control" required pattern="[0-9]{5}" value="<?= isset($_POST['kode_pos']) ? htmlspecialchars($_POST['kode_pos']) : '' ?>">
                <div class="invalid-feedback">Kode pos wajib diisi dengan 5 digit angka.</div>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perusahaan</button>
            </div>
        </form>
    </div>
</div>
<script>
// Pencegahan double submit
document.getElementById('formTambahPerusahaan')?.addEventListener('submit', function() {
    document.getElementById('submitButton').setAttribute('disabled', 'true');
    document.getElementById('submitButton').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
});
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php require_once '../../templates/footer.php'; ?>