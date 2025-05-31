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
    $depan = trim($_POST['depan']);
    $tengah = trim($_POST['tengah']);
    $belakang = trim($_POST['belakang']);
    $lahir = trim($_POST['lahir']);
    $alamat = trim($_POST['alamat']);
    $email = trim($_POST['email']);
    $telp = trim($_POST['telp']);

    if(empty($depan) || empty($belakang) || empty($lahir) || empty($alamat) || empty($email) || empty($telp)) {
        $error_message = "Semua field wajib diisi, kecuali Nama Tengah.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        $stmt_check = $koneksi->prepare("SELECT Id_pengguna FROM pengguna WHERE Email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = "Email '" . htmlspecialchars($email) . "' sudah terdaftar.";
        } else {
            $sql = "INSERT INTO pengguna (Nama_Depan, Nama_Tengah, Nama_Belakang, Tanggal_Lahir, Alamat, Email, No_Telp) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("sssssss", $depan, $tengah, $belakang, $lahir, $alamat, $email, $telp);
            
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Pengguna baru '" . htmlspecialchars($depan) . "' berhasil ditambahkan!";
                header("Location: index.php");
                exit;
            } else {
                $error_message = "Gagal menyimpan data: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$page_title = "Tambah Pengguna Baru";
require_once '../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-person-plus-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>

<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $error_message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>

        <form method="post" class="row g-4 needs-validation" novalidate>
            <div class="col-lg-6">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-person-badge-fill me-2"></i>INFORMASI PRIBADI</h6>
                <div class="row g-3">
                    <div class="col-md-6"><label for="depan" class="form-label fw-semibold"><i class="bi bi-person-fill me-2"></i>Nama Depan</label><input type="text" class="form-control" id="depan" name="depan" required value="<?= isset($_POST['depan']) ? htmlspecialchars($_POST['depan']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
                    <div class="col-md-6"><label for="belakang" class="form-label fw-semibold">Nama Belakang</label><input type="text" class="form-control" id="belakang" name="belakang" required value="<?= isset($_POST['belakang']) ? htmlspecialchars($_POST['belakang']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
                    <div class="col-12"><label for="tengah" class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Nama Tengah <span class="text-muted fw-normal">(Opsional)</span></label><input type="text" class="form-control" id="tengah" name="tengah" value="<?= isset($_POST['tengah']) ? htmlspecialchars($_POST['tengah']) : '' ?>"></div>
                    <div class="col-12"><label for="lahir" class="form-label fw-semibold"><i class="bi bi-calendar-event-fill me-2"></i>Tanggal Lahir</label><input type="date" class="form-control" id="lahir" name="lahir" required value="<?= isset($_POST['lahir']) ? htmlspecialchars($_POST['lahir']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-telephone-inbound-fill me-2"></i>INFORMASI KONTAK</h6>
                <div class="row g-3">
                    <div class="col-12"><label for="email" class="form-label fw-semibold"><i class="bi bi-envelope-fill me-2"></i>Alamat Email</label><input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"><div class="invalid-feedback">Wajib diisi dengan format email yang benar.</div></div>
                    <div class="col-12"><label for="telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" class="form-control" id="telp" name="telp" required value="<?= isset($_POST['telp']) ? htmlspecialchars($_POST['telp']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
                    <div class="col-12"><label for="alamat" class="form-label fw-semibold"><i class="bi bi-geo-alt-fill me-2"></i>Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea><div class="invalid-feedback">Wajib diisi.</div></div>
                </div>
            </div>
            <div class="col-12 text-end mt-5 border-top pt-4">
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) {form.addEventListener('submit', function (event) {if (!form.checkValidity()) {event.preventDefault(); event.stopPropagation();} form.classList.add('was-validated');}, false);});})();
</script>

<?php require_once '../../templates/footer.php'; ?>