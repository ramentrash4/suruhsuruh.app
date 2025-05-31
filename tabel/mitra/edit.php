<?php
// 1. Panggil config.php (session, BASE_URL, koneksi DB, fungsi check_login_status)
require_once __DIR__ . '/../../config.php';

// 2. Cek status login
check_login_status();

// 3. Logika PHP spesifik untuk halaman edit PENGGUNA
$error_message = '';
$data = null; // Inisialisasi data pengguna

$id_pengguna = isset($_GET['id']) ? intval($_GET['id']) : 0; // Ambil Id_pengguna
if ($id_pengguna <= 0) {
    $_SESSION['error_message'] = "ID Pengguna tidak valid.";
    header("Location: index.php");
    exit;
}

// Ambil data pengguna saat ini dari database
$query_current_pengguna = "SELECT * FROM pengguna WHERE Id_pengguna = $id_pengguna"; // Query ke tabel pengguna
$result_current_pengguna = mysqli_query($koneksi, $query_current_pengguna);
if ($result_current_pengguna) {
    $data = mysqli_fetch_assoc($result_current_pengguna);
}

if (!$data) {
    $_SESSION['error_message'] = "Data pengguna dengan ID $id_pengguna tidak ditemukan.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input dasar
    $depan = mysqli_real_escape_string($koneksi, $_POST['depan']);
    $tengah = mysqli_real_escape_string($koneksi, $_POST['tengah']);
    $belakang = mysqli_real_escape_string($koneksi, $_POST['belakang']);
    $lahir = mysqli_real_escape_string($koneksi, $_POST['lahir']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $telp = mysqli_real_escape_string($koneksi, $_POST['telp']);

    if(empty($depan) || empty($belakang) || empty($lahir) || empty($alamat) || empty($email) || empty($telp)) {
        $error_message = "Semua field wajib diisi, kecuali Nama Tengah.";
        $data = $_POST; // Repopulate dengan data POST
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
        $data = $_POST; // Repopulate
    } else {
        // Query UPDATE ke tabel PENGGUNA
        $query_update = "UPDATE pengguna SET 
                            Nama_Depan='$depan', 
                            Nama_Tengah='$tengah', 
                            Nama_Belakang='$belakang',
                            Tanggal_Lahir='$lahir', 
                            Alamat='$alamat', 
                            Email='$email', 
                            No_Telp='$telp' 
                         WHERE Id_pengguna=$id_pengguna";
        
        if (mysqli_query($koneksi, $query_update)) {
            $_SESSION['success_message'] = "Data pengguna berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui data pengguna: " . mysqli_error($koneksi);
            $data = $_POST; // Repopulate
        }
    }
}

$page_title = "Edit Pengguna";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i>Edit Pengguna</h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali ke Daftar
    </a>
</div>

<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4">
        <?php if ($data): // Pastikan $data ada sebelum mengaksesnya ?>
            <h5 class="card-title mb-4">Mengubah Data untuk: <span class="text-success fw-bold"><?= htmlspecialchars($data['Nama_Depan'] ?? ''); ?></span></h5>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($data): // Hanya tampilkan form jika $data ada ?>
        <form method="post" action="" class="row g-3 needs-validation" novalidate> <div class="col-md-4">
                <label for="depan" class="form-label fw-semibold">Nama Depan</label>
                <input type="text" class="form-control" id="depan" name="depan" required value="<?= htmlspecialchars($data['Nama_Depan'] ?? '') ?>">
                <div class="invalid-feedback">Nama depan wajib diisi.</div>
            </div>
            <div class="col-md-4">
                <label for="tengah" class="form-label fw-semibold">Nama Tengah <span class="text-muted fw-normal">(Opsional)</span></label>
                <input type="text" class="form-control" id="tengah" name="tengah" value="<?= htmlspecialchars($data['Nama_Tengah'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="belakang" class="form-label fw-semibold">Nama Belakang</label>
                <input type="text" class="form-control" id="belakang" name="belakang" required value="<?= htmlspecialchars($data['Nama_Belakang'] ?? '') ?>">
                <div class="invalid-feedback">Nama belakang wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($data['Email'] ?? '') ?>">
                <div class="invalid-feedback">Email wajib diisi dengan format yang benar.</div>
            </div>
            <div class="col-md-6">
                <label for="telp" class="form-label fw-semibold">No. Telepon</label>
                <input type="text" class="form-control" id="telp" name="telp" required value="<?= htmlspecialchars($data['No_Telp'] ?? '') ?>">
                <div class="invalid-feedback">No. telepon wajib diisi.</div>
            </div>
            <div class="col-12">
                <label for="alamat" class="form-label fw-semibold">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($data['Alamat'] ?? '') ?></textarea>
                <div class="invalid-feedback">Alamat wajib diisi.</div>
            </div>
            <div class="col-md-6">
                <label for="lahir" class="form-label fw-semibold">Tanggal Lahir</label>
                <input type="date" class="form-control" id="lahir" name="lahir" required value="<?= htmlspecialchars($data['Tanggal_Lahir'] ?? '') ?>">
                <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="bi bi-save-fill me-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
        <?php else: ?>
            <p class="text-danger">Data pengguna tidak dapat dimuat untuk diedit.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Tambahkan script validasi Bootstrap
$custom_script = "
<script>
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
require_once __DIR__ . '/../../templates/footer.php';
?>