<?php
// config.php sudah memanggil session_start()
require_once '../config.php'; 

$error_message = null;
$success_message = null;
$kode_rahasia_benar = "ilovebasdat";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_admin = trim($_POST['nama_admin'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $kode_verifikasi = trim($_POST['kode_verifikasi'] ?? '');

    if (empty($nama_admin) || empty($email) || empty($password) || empty($konfirmasi_password) || empty($kode_verifikasi)) {
        $error_message = "Semua field wajib diisi!";
    } elseif ($password !== $konfirmasi_password) {
        $error_message = "Password dan Konfirmasi Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal harus 6 karakter.";
    } elseif ($kode_verifikasi !== $kode_rahasia_benar) {
        $error_message = "Kode Verifikasi Rahasia salah!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        $stmt_check = $koneksi->prepare("SELECT id_admin FROM admin_users WHERE email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $error_message = "Email '" . htmlspecialchars($email) . "' sudah terdaftar sebagai admin.";
            }
            $stmt_check->close();
        } else {
            $error_message = "Gagal mempersiapkan pengecekan email: " . $koneksi->error;
        }

        if (empty($error_message)) { // Lanjutkan jika tidak ada error dari pengecekan email
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'admin'; // Default role

            $sql_insert = "INSERT INTO admin_users (nama_admin, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $koneksi->prepare($sql_insert);

            if ($stmt_insert) {
                $stmt_insert->bind_param("ssss", $nama_admin, $email, $password_hash, $role);
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message_registrasi'] = "Registrasi admin berhasil untuk email: " . htmlspecialchars($email) . "! Silakan login.";
                    header("Location: login.php"); // Arahkan ke login setelah sukses
                    exit;
                } else {
                    $error_message = "Gagal menyimpan data admin: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $error_message = "Gagal mempersiapkan statement insert: " . $koneksi->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Admin - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style_login.css">
</head>
<body>
    <div class="login-page-wrapper">
        <div class="background-collage-container"><div class="background-collage" id="backgroundCollage"></div><div class="background-overlay" id="backgroundOverlay"></div></div>
        <div class="login-content-container d-flex flex-column justify-content-center align-items-center">
            <div class="logo-area mb-4"><img src="<?= BASE_URL ?>assets/img/logo_suruhsuruh.png" alt="Logo SuruhSuruh.com" class="login-logo-main"></div>
            <div class="login-form-card card shadow-lg" style="max-width: 550px;">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold">REGISTRASI ADMIN BARU</h2>
                    <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
                    
                    <form method="post" action="registrasi.php" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="nama_admin" name="nama_admin" placeholder="Nama Lengkap Admin" required value="<?= isset($_POST['nama_admin']) ? htmlspecialchars($_POST['nama_admin']) : '' ?>">
                            <label for="nama_admin"><i class="fas fa-user me-2"></i>Nama Lengkap Admin</label>
                            <div class="invalid-feedback">Nama admin wajib diisi.</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email Admin" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Alamat Email</label>
                            <div class="invalid-feedback">Email wajib diisi dengan format yang benar.</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password Baru" required minlength="6">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password Baru (Min. 6 karakter)</label>
                            <div class="invalid-feedback">Password wajib diisi (minimal 6 karakter).</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Konfirmasi Password" required>
                            <label for="konfirmasi_password"><i class="fas fa-check-circle me-2"></i>Konfirmasi Password</label>
                            <div class="invalid-feedback">Konfirmasi password wajib diisi.</div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="kode_verifikasi" name="kode_verifikasi" placeholder="Kode Rahasia" required>
                            <label for="kode_verifikasi"><i class="fas fa-key me-2"></i>Kode Verifikasi Rahasia</label>
                            <div class="invalid-feedback">Kode verifikasi wajib diisi.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold btn-login-custom"><i class="fas fa-user-plus me-2"></i>DAFTAR AKUN ADMIN</button>
                        </div>
                    </form>
                    <div class="text-center mt-4"><small class="text-muted">Sudah punya akun admin? <a href="login.php">Masuk di sini</a></small></div>
                </div>
            </div>
            <footer class="login-footer mt-auto py-3 text-center"><small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com</small></footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
        document.addEventListener('DOMContentLoaded', function() {
            const loginContentContainer = document.querySelector('.login-content-container');
            const backgroundCollage = document.getElementById('backgroundCollage');
            const backgroundOverlay = document.getElementById('backgroundOverlay');
            setTimeout(() => {
                if (loginContentContainer) { loginContentContainer.classList.add('show'); }
                if (backgroundCollage) { backgroundCollage.classList.add('darken'); }
                if (backgroundOverlay) { backgroundOverlay.classList.add('show'); }
            }, 300); 
        });
    </script>
</body>
</html>