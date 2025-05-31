<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}
require '../../config/database.php';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $depan = mysqli_real_escape_string($koneksi, $_POST['depan']);
    $tengah = mysqli_real_escape_string($koneksi, $_POST['tengah']);
    $belakang = mysqli_real_escape_string($koneksi, $_POST['belakang']);
    $lahir = mysqli_real_escape_string($koneksi, $_POST['lahir']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);

    if(empty($depan) || empty($belakang) || empty($lahir) || empty($alamat) || empty($email) || empty($telp)) {
        $error_message = "Semua field wajib diisi, kecuali Nama Tengah.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        $query = "INSERT INTO pengguna (Nama_Depan, Nama_Tengah, Nama_Belakang, Tanggal_Lahir, Alamat, Email, No_Telp)
                  VALUES ('$depan', '$tengah', '$belakang', '$lahir', '$alamat', '$email', '$telp')";
        
        if(mysqli_query($koneksi, $query)) {
            $_SESSION['success_message'] = "Pengguna baru '" . htmlspecialchars($depan) . "' berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

$page_title = "Tambah Pengguna Baru";
require_once '../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-person-plus-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali ke Daftar
    </a>
</div>

<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3 needs-validation" novalidate>
            <div class="col-md-4">
                <label for="depan" class="form-label fw-semibold">Nama Depan</label>
                <input type="text" class="form-control" id="depan" name="depan" required value="<?= isset($_POST['depan']) ? htmlspecialchars($_POST['depan']) : '' ?>">
                <div class="invalid-feedback">Nama depan wajib diisi.</div>
            </div>
            <div class="col-md-4">
                <label for="tengah" class="form-label fw-semibold">Nama Tengah <span class="text-muted fw-normal">(Opsional)</span></label>
                <input type="text" class="form-control" id="tengah" name="tengah" value="<?= isset($_POST['tengah']) ? htmlspecialchars($_POST['tengah']) : '' ?>">
            </div>
            <div class="col-md-4">
                <label for="belakang" class="form-label fw-semibold">Nama Belakang</label>
                <input type="text" class="form-control" id="belakang" name="belakang" required value="<?= isset($_POST['belakang']) ? htmlspecialchars($_POST['belakang']) : '' ?>">
                 <div class="invalid-feedback">Nama belakang wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                 <div class="invalid-feedback">Email wajib diisi dengan format yang benar.</div>
            </div>
            <div class="col-md-6">
                <label for="telp" class="form-label fw-semibold">No. Telepon</label>
                <input type="text" class="form-control" id="telp" name="telp" required value="<?= isset($_POST['telp']) ? htmlspecialchars($_POST['telp']) : '' ?>">
                 <div class="invalid-feedback">No. telepon wajib diisi.</div>
            </div>
            <div class="col-12">
                <label for="alamat" class="form-label fw-semibold">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                 <div class="invalid-feedback">Alamat wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="lahir" class="form-label fw-semibold">Tanggal Lahir</label>
                <input type="date" class="form-control" id="lahir" name="lahir" required value="<?= isset($_POST['lahir']) ? htmlspecialchars($_POST['lahir']) : '' ?>">
                 <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="bi bi-save-fill me-2"></i>Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Tambahkan script validasi Bootstrap di footer atau di sini
$custom_script = "
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (function () {
      'use strict'
      var forms = document.querySelectorAll('.needs-validation')
      Array.prototype.slice.call(forms)
        .forEach(function (form) {
          form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }
            form.classList.add('was-validated')
          }, false)
        })
    })()
</script>
";

require_once '../../templates/footer.php';
// Jika footer Anda bisa menerima variabel, Anda bisa meneruskan $custom_script
?>