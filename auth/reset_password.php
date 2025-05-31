<?php
session_start();
require_once '../config.php'; // Path ke config.php
$error_message = null;
$success_message = null;
$token_valid = false;
$token = trim($_GET['token'] ?? '');
$admin_email_for_display = ''; 

if (empty($token)) {
    $error_message = "Token reset tidak valid atau tidak ditemukan.";
} else {
    $stmt_check_token = $koneksi->prepare("SELECT id_admin, email, reset_token_expires_at FROM admin_users WHERE reset_token = ?");
    if ($stmt_check_token) {
        $stmt_check_token->bind_param("s", $token);
        $stmt_check_token->execute();
        $result_token = $stmt_check_token->get_result();
        if ($result_token->num_rows === 1) {
            $admin = $result_token->fetch_assoc();
            $admin_email_for_display = $admin['email']; 
            if ($admin['reset_token_expires_at'] !== null && strtotime($admin['reset_token_expires_at']) > time()) {
                $token_valid = true;
            } else { $error_message = "Token reset sudah kedaluwarsa atau tidak valid."; }
        } else { $error_message = "Token reset tidak ditemukan."; }
        $stmt_check_token->close();
    } else { $error_message = "Gagal memvalidasi token: " . $koneksi->error; }
}

if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password_baru = $_POST['konfirmasi_password_baru'] ?? '';

    if (empty($password_baru) || empty($konfirmasi_password_baru)) {
        $error_message = "Password Baru dan Konfirmasi Password wajib diisi.";
    } elseif ($password_baru !== $konfirmasi_password_baru) {
        $error_message = "Password Baru dan Konfirmasi Password tidak cocok.";
    } elseif (strlen($password_baru) < 6) {
        $error_message = "Password baru minimal harus 6 karakter.";
    } else {
        $new_password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt_update_pass = $koneksi->prepare("UPDATE admin_users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token = ?");
        if ($stmt_update_pass) {
            $stmt_update_pass->bind_param("ss", $new_password_hash, $token);
            if ($stmt_update_pass->execute()) {
                $_SESSION['success_message_reset'] = "Password untuk email " . htmlspecialchars($admin_email_for_display) . " berhasil diperbarui! Silakan login dengan password baru Anda.";
                header("Location: login.php"); 
                exit;
            } else { $error_message = "Gagal memperbarui password: " . $stmt_update_pass->error; }
            $stmt_update_pass->close();
        } else { $error_message = "Gagal mempersiapkan update password: " . $koneksi->error; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Admin - SuruhSuruh.com</title>
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
                    <h2 class="card-title text-center mb-4 fw-bold">ATUR ULANG PASSWORD ADMIN</h2>
                    <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
                    
                    <?php if ($token_valid): ?>
                    <p class="text-center text-muted">Atur ulang password untuk email: <strong><?= htmlspecialchars($admin_email_for_display) ?></strong></p>
                    <form method="post" action="reset_password.php?token=<?= htmlspecialchars($token) ?>" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password_baru" name="password_baru" placeholder="Password Baru" required minlength="6">
                            <label for="password_baru"><i class="fas fa-lock me-2"></i>Password Baru (Min. 6 karakter)</label>
                            <div class="invalid-feedback">Password baru wajib diisi (minimal 6 karakter).</div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="konfirmasi_password_baru" name="konfirmasi_password_baru" placeholder="Konfirmasi Password Baru" required>
                            <label for="konfirmasi_password_baru"><i class="fas fa-check-circle me-2"></i>Konfirmasi Password Baru</label>
                            <div class="invalid-feedback">Konfirmasi password baru wajib diisi.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold btn-login-custom"><i class="fas fa-save me-2"></i>SIMPAN PASSWORD BARU</button>
                        </div>
                    </form>
                    <?php elseif(!$success_message): // Hanya tampilkan pesan ini jika token tidak valid DAN belum ada pesan sukses ?>
                         <p class="text-center text-danger">Link reset password tidak valid, tidak ditemukan, atau sudah kedaluwarsa.</p>
                    <?php endif; ?>
                     <?php if ($success_message): /* Pesan sukses sudah dihandle dengan redirect ke login.php, jadi ini mungkin tidak perlu, tapi jaga-jaga */ ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <div class="text-center mt-4"><small class="text-muted"><a href="login.php">Kembali ke Login</a></small></div>
                </div>
            </div>
             <footer class="login-footer mt-auto py-3 text-center"><small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com</small></footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script> /* ... (Script validasi & animasi sama seperti login.php) ... */ </script>
</body>
</html>