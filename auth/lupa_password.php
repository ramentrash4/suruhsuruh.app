<?php
session_start();
require_once '../config.php'; 
$error_message = null;
$info_message = null;
$kode_rahasia_benar = "ilovebasdat";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $kode_verifikasi = trim($_POST['kode_verifikasi'] ?? '');

    if (empty($email) || empty($kode_verifikasi)) {
        $error_message = "Email dan Kode Verifikasi wajib diisi.";
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
            if ($result_check->num_rows === 1) {
                $admin = $result_check->fetch_assoc();
                $id_admin = $admin['id_admin'];

                $reset_token = bin2hex(random_bytes(32));
                $expires_at_timestamp = time() + 3600; // Token valid 1 jam
                $expires_at_datetime = date("Y-m-d H:i:s", $expires_at_timestamp);

                $stmt_update_token = $koneksi->prepare("UPDATE admin_users SET reset_token = ?, reset_token_expires_at = ? WHERE id_admin = ?");
                if ($stmt_update_token) {
                    $stmt_update_token->bind_param("ssi", $reset_token, $expires_at_datetime, $id_admin);
                    if ($stmt_update_token->execute()) {
                        // **PERUBAHAN DI SINI:** Menggunakan reset_password.php
                        $reset_link = BASE_URL . "auth/reset_password.php?token=" . $reset_token;
                        $info_message = "Jika email Anda terdaftar dan kode benar, instruksi reset password (simulasi) telah dibuat. <br>Silakan gunakan link berikut untuk melanjutkan: <br><a href='" . htmlspecialchars($reset_link) . "' class='btn btn-success btn-sm mt-2'>Reset Password Saya</a><br><small>(Dalam aplikasi nyata, link ini akan dikirim ke email Anda)</small>";
                    } else { $error_message = "Gagal menyimpan token reset: " . $stmt_update_token->error; }
                    $stmt_update_token->close();
                } else { $error_message = "Gagal mempersiapkan update token: " . $koneksi->error;}
            } else { $error_message = "Email admin tidak ditemukan."; }
            $stmt_check->close();
        } else { $error_message = "Gagal mempersiapkan pengecekan email: " . $koneksi->error; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password Admin - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style_login.css">
</head>
<body>
    <div class="login-page-wrapper">
        <div class="background-collage-container"><div class="background-collage" id="backgroundCollage"></div><div class="background-overlay" id="backgroundOverlay"></div></div>
        <div class="login-content-container d-flex flex-column justify-content-center align-items-center">
            <div class="logo-area mb-4"><img src="<?= BASE_URL ?>assets/img/logo_suruhsuruh.png" alt="Logo SuruhSuruh.com" class="login-logo-main"></div>
            <div class="login-form-card card shadow-lg" style="max-width: 500px;">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold">LUPA PASSWORD ADMIN</h2>
                    <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
                    <?php if ($info_message): ?><div class="alert alert-info"><?= $info_message // Sudah mengandung HTML ?></div><?php endif; ?>

                    <?php if (!$info_message): ?>
                    <form method="post" action="lupa_password.php" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email Admin Anda" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Admin Terdaftar</label>
                            <div class="invalid-feedback">Email admin wajib diisi.</div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="kode_verifikasi" name="kode_verifikasi" placeholder="Kode Rahasia" required>
                            <label for="kode_verifikasi"><i class="fas fa-key me-2"></i>Kode Verifikasi Rahasia</label>
                            <div class="invalid-feedback">Kode verifikasi wajib diisi.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold btn-login-custom"><i class="fas fa-paper-plane me-2"></i>KIRIM INSTRUKSI RESET</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    <div class="text-center mt-4"><small class="text-muted"><a href="login.php">Kembali ke Login</a></small></div>
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