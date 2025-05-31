<?php
session_start(); 

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['login_admin']) && $_SESSION['login_admin'] === true) {
    if (!defined('BASE_URL')) define('BASE_URL', '/projekbasdat/');
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

require_once '../config.php'; // Path ke config.php yang berisi $koneksi

$login_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = "Email dan password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = "Format email tidak valid.";
    } else {
        $stmt = $koneksi->prepare("SELECT id_admin, nama_admin, email, password_hash, role FROM admin_users WHERE email = ? LIMIT 1");
        if ($stmt === false) {
            $login_error = "Terjadi kesalahan pada server (prepare failed).";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password_hash'])) {
                    // Regenerate session ID untuk keamanan
                    session_regenerate_id(true);
                    $_SESSION['login_admin'] = true;
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    $_SESSION['admin_nama'] = $admin['nama_admin'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    if (!defined('BASE_URL')) define('BASE_URL', '/projekbasdat/');
                    header("Location: " . BASE_URL . "dashboard.php");
                    exit;
                } else {
                    $login_error = "Login gagal. Email atau password salah.";
                }
            } else {
                $login_error = "Login gagal. Email atau password salah.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style_login.css"> </head>
<body>
    <div class="login-page-wrapper">
        <div class="background-collage-container">
            <div class="background-collage" id="backgroundCollage"></div>
            <div class="background-overlay" id="backgroundOverlay"></div>
        </div>
        <div class="login-content-container d-flex flex-column justify-content-center align-items-center">
            <div class="logo-area mb-4">
                <img src="../assets/img/logo_suruhsuruh.png" alt="Logo SuruhSuruh.com" class="login-logo-main">
            </div>
            <div class="login-form-card card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold">ADMIN MASUK</h2>
                    <?php 
                    if (isset($_SESSION['success_message_registrasi'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message_registrasi']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['success_message_registrasi']);
                    }
                    if (isset($_SESSION['success_message_reset'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message_reset']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['success_message_reset']);
                    }
                    ?>
                    <?php if (isset($login_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($login_error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="login.php" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="nama@contoh.com" required value="<?= isset($_POST['email']) && $login_error ? htmlspecialchars($_POST['email']) : '' ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Alamat Email</label>
                            <div class="invalid-feedback">Mohon masukkan email yang valid.</div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="invalid-feedback">Mohon masukkan password Anda.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold btn-login-custom">
                                <i class="fas fa-sign-in-alt me-2"></i>MASUK
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-4">
                        <small class="text-muted">Belum punya akun admin? <a href="registrasi.php">Daftar Admin di sini</a></small><br>
                        <small class="text-muted"><a href="lupa_password.php">Lupa Password Admin?</a></small>
                    </div>
                </div>
            </div>
            <footer class="login-footer mt-auto py-3 text-center"><small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com - Admin Panel</small></footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false) }); })();
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