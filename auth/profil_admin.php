<?php
// config.php sudah memanggil session_start()
require_once '../config.php'; 

// Pastikan admin sudah login
if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin'] !== true || !isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$error_message = null;
$success_message = null;

// Ambil data admin saat ini
$stmt_admin = $koneksi->prepare("SELECT id_admin, nama_admin, email, role FROM admin_users WHERE id_admin = ?");
if ($stmt_admin === false) {
    die("Error preparing select admin query: " . $koneksi->error);
}
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
if ($result_admin->num_rows === 0) {
    // Jika data admin tidak ditemukan di DB padahal session ada, ini aneh. Logout paksa.
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php?message=sesinotfound");
    exit;
}
$admin_data = $result_admin->fetch_assoc();
$stmt_admin->close();

// Proses Update Profil (Nama & Email)
if (isset($_POST['update_profil'])) {
    $nama_admin_baru = trim($_POST['nama_admin']);
    $email_baru = trim($_POST['email']);

    if (empty($nama_admin_baru) || empty($email_baru)) {
        $error_message = "Nama Admin dan Email tidak boleh kosong.";
    } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        // Cek apakah email baru (jika berubah) sudah digunakan oleh admin lain
        if (strtolower($email_baru) !== strtolower($admin_data['email'])) {
            $stmt_check_email = $koneksi->prepare("SELECT id_admin FROM admin_users WHERE email = ? AND id_admin != ?");
            if($stmt_check_email) {
                $stmt_check_email->bind_param("si", $email_baru, $admin_id);
                $stmt_check_email->execute();
                if ($stmt_check_email->get_result()->num_rows > 0) {
                    $error_message = "Email '" . htmlspecialchars($email_baru) . "' sudah digunakan oleh admin lain.";
                }
                $stmt_check_email->close();
            } else {
                $error_message = "Gagal cek email: " . $koneksi->error;
            }
        }

        if (empty($error_message)) {
            $stmt_update = $koneksi->prepare("UPDATE admin_users SET nama_admin = ?, email = ? WHERE id_admin = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("ssi", $nama_admin_baru, $email_baru, $admin_id);
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Profil berhasil diperbarui!";
                    // Update session jika email atau nama berubah
                    $_SESSION['admin_nama'] = $nama_admin_baru;
                    $_SESSION['admin_email'] = $email_baru;
                    // Refresh halaman untuk menampilkan data baru dari DB (atau langsung update $admin_data)
                    header("Location: profil_admin.php"); // Refresh
                    exit;
                } else { $error_message = "Gagal memperbarui profil: " . $stmt_update->error; }
                $stmt_update->close();
            } else { $error_message = "Gagal mempersiapkan update profil: " . $koneksi->error; }
        }
    }
     // Jika ada error, muat ulang data dari DB untuk field form
    if($error_message){
        $admin_data['nama_admin'] = $nama_admin_baru; // Tampilkan inputan terakhir jika error
        $admin_data['email'] = $email_baru;
    }
}

// Proses Ubah Password
if (isset($_POST['ubah_password'])) {
    $password_lama = $_POST['password_lama'] ?? ''; // Opsional, bisa dihilangkan jika tidak ingin verifikasi pass lama
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password_baru = $_POST['konfirmasi_password_baru'] ?? '';

    if (empty($password_baru) || empty($konfirmasi_password_baru)) {
        $error_message = "Password Baru dan Konfirmasi Password tidak boleh kosong.";
    } elseif ($password_baru !== $konfirmasi_password_baru) {
        $error_message = "Password Baru dan Konfirmasi tidak cocok.";
    } elseif (strlen($password_baru) < 6) {
        $error_message = "Password baru minimal 6 karakter.";
    } else {
        // Jika ingin verifikasi password lama:
        // $stmt_pass_check = $koneksi->prepare("SELECT password_hash FROM admin_users WHERE id_admin = ?");
        // $stmt_pass_check->bind_param("i", $admin_id);
        // $stmt_pass_check->execute();
        // $current_admin_data = $stmt_pass_check->get_result()->fetch_assoc();
        // $stmt_pass_check->close();
        // if (!$current_admin_data || !password_verify($password_lama, $current_admin_data['password_hash'])) {
        //     $error_message = "Password lama salah.";
        // }

        if (empty($error_message)) { // Lanjut jika tidak ada error password lama
            $new_password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_change_pass = $koneksi->prepare("UPDATE admin_users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id_admin = ?");
            if ($stmt_change_pass) {
                $stmt_change_pass->bind_param("si", $new_password_hash, $admin_id);
                if ($stmt_change_pass->execute()) {
                    $_SESSION['success_message'] = "Password berhasil diubah!";
                     // Refresh halaman
                    header("Location: profil_admin.php");
                    exit;
                } else { $error_message = "Gagal mengubah password: " . $stmt_change_pass->error; }
                $stmt_change_pass->close();
            } else { $error_message = "Gagal mempersiapkan ubah password: " . $koneksi->error; }
        }
    }
}


$page_title = "Profil Admin Saya";
// Menggunakan path relatif dari folder 'auth' ke 'templates'
require_once '../templates/header.php'; 
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="page-header mb-4">
            <h1 class="h2 fw-bolder text-primary"><i class="bi bi-person-circle me-3"></i><?= htmlspecialchars($page_title) ?></h1>
            <p class="text-muted">Kelola informasi profil dan keamanan akun admin Anda.</p>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4 border-light-subtle">
            <div class="card-header bg-light-subtle">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Informasi Profil</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="profil_admin.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="nama_admin" class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_admin" name="nama_admin" value="<?= htmlspecialchars($admin_data['nama_admin']) ?>" required>
                        <div class="invalid-feedback">Nama tidak boleh kosong.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Alamat Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin_data['email']) ?>" required>
                        <div class="invalid-feedback">Format email tidak valid.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($admin_data['role'])) ?>" readonly disabled>
                    </div>
                    <button type="submit" name="update_profil" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-2"></i>Simpan Perubahan Profil</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-light-subtle">
            <div class="card-header bg-light-subtle">
                <h5 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Ubah Password</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="profil_admin.php" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="password_baru" class="form-label fw-semibold">Password Baru</label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="6">
                        <div class="invalid-feedback">Password baru minimal 6 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label for="konfirmasi_password_baru" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="konfirmasi_password_baru" name="konfirmasi_password_baru" required>
                        <div class="invalid-feedback">Konfirmasi password baru wajib diisi.</div>
                    </div>
                    <button type="submit" name="ubah_password" class="btn btn-warning rounded-pill px-4"><i class="bi bi-key-fill me-2"></i>Ubah Password Saya</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// Script validasi Bootstrap standar
(function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
</script>
<?php
// Menggunakan path relatif dari folder 'auth' ke 'templates'
require_once '../templates/footer.php'; 
?>