<?php
// 1. Mulai session di baris PALING ATAS, sebelum karakter atau spasi apapun.
session_start();

// 2. Daftar akun yang diizinkan (email => password)
$akun_valid = [
    "ramentrash4@gmail.com" => "topdantampan",
    "mulkanyaw@upi.edu" => "tampandanberotot",
    "admin3@gmail.com" => "rahasia789"
];

// 3. Inisialisasi variabel untuk pesan error login
$login_error = null;

// 4. Proses form jika metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pw = $_POST['password'] ?? '';

    if (isset($akun_valid[$email]) && $akun_valid[$email] === $pw) {
        $_SESSION['login'] = true;
        $_SESSION['user'] = $email;
        header("Location: ../dashboard.php");
        exit;
    } else {
        $login_error = "Login gagal. Email atau password salah.";
    }
}

// 5. Jika pengguna sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: ../dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style_login.css">
</head>
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
                    <h2 class="card-title text-center mb-4 fw-bold">MASUK</h2>

                    <?php if (isset($login_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($login_error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="nama@contoh.com" required value="<?= isset($_POST['email']) && $login_error ? htmlspecialchars($_POST['email']) : '' ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Alamat Email</label>
                            <div class="invalid-feedback">
                                Mohon masukkan email yang valid.
                            </div>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="invalid-feedback">
                                Mohon masukkan password Anda.
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold btn-login-custom">
                                <i class="fas fa-sign-in-alt me-2"></i>MASUK
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-4">
                        <small class="text-muted">Belum punya akun? <a href="registrasi.php">Daftar di sini</a></small><br>
                        <small class="text-muted"><a href="lupa_password.php">Lupa Password?</a></small>
                    </div>
                </div>
            </div>
            <footer class="login-footer mt-auto py-3 text-center">
                <small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com - Semua Layanan Jadi Mudah.</small>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // Script untuk animasi & validasi form Bootstrap
        (function () {
            'use strict'
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            // Loop over them and prevent submission
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
        })();

        document.addEventListener('DOMContentLoaded', function() {
            const loginContentContainer = document.querySelector('.login-content-container'); // Target wrapper konten
            const backgroundCollage = document.getElementById('backgroundCollage');
            const backgroundOverlay = document.getElementById('backgroundOverlay');

            setTimeout(() => {
                if (loginContentContainer) { // Pemicu animasi konten
                    loginContentContainer.classList.add('show');
                }
                if (backgroundCollage) {
                    backgroundCollage.classList.add('darken');
                }
                if (backgroundOverlay) {
                    backgroundOverlay.classList.add('show');
                }
            }, 300); // Sedikit percepat delay
        });
    </script>
</body>
</html>