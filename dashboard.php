<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: auth/login.php");
    exit;
}
$user_email = isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : 'Pengguna';
$user_greeting_name = explode('@', $user_email)[0]; // Ambil bagian sebelum @ untuk sapaan
if (strlen($user_greeting_name) > 15) { // Batasi panjang nama sapaan
    $user_greeting_name = substr($user_greeting_name, 0, 12) . "...";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style_dashboard.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="border-end" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-3">
                <img src="assets/img/logo_suruhsuruh_putih.png" alt="Logo SuruhSuruh" width="45" class="me-2 align-middle">
                <span class="fs-5 fw-semibold text-light align-middle">SuruhSuruh</span>
            </div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action list-group-item-primary-dark active">
                    <i class="bi bi-house-door-fill"></i>Dashboard
                </a>
                <a href="#crudSubmenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action list-group-item-primary-dark collapsed" aria-expanded="false">
                    <i class="bi bi-pencil-square"></i>Manajemen Data <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse" id="crudSubmenu">
                    <a href="tabel/pengguna/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-people-fill"></i>Pengguna</a>
                    <a href="tabel/pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-box-seam-fill"></i>Pesanan</a>
                    <a href="tabel/detail_pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-file-earmark-text-fill"></i>Detail Pesanan</a>
                    <a href="tabel/layanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-tools"></i>Layanan</a>
                    <a href="tabel/mitra/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-person-badge-fill"></i>Mitra</a>
                    <a href="tabel/perusahaan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-building-fill"></i>Perusahaan</a>
                    <a href="tabel/pekerja/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-person-workspace"></i>Pekerja</a>
                    <a href="tabel/pembayaran/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-credit-card-2-front-fill"></i>Pembayaran</a>
                    <a href="tabel/profit/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4"><i class="bi bi-graph-up-arrow"></i>Profit</a>
                </div>
                 <a href="#" class="list-group-item list-group-item-action list-group-item-primary-dark">
                    <i class="bi bi-person-circle"></i>Profil Saya
                </a>
                <a href="#" class="list-group-item list-group-item-action list-group-item-primary-dark">
                    <i class="bi bi-gear-fill"></i>Pengaturan Aplikasi
                </a>
            </div>
            <div class="sidebar-footer p-3 text-center">
                 <small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com</small>
            </div>
        </div>
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
                <div class="container-fluid">
                    <button class="btn btn-primary-outline" id="menu-toggle" title="Toggle Menu">
                        <i class="bi bi-list fs-3"></i>
                    </button>

                    <a class="navbar-brand d-lg-none mx-auto" href="dashboard.php">
                        <img src="assets/img/logo_suruhsuruh_navbar.png" alt="Logo" width="35" class="align-middle">
                        <span class="fs-5 fw-semibold align-middle ms-1">SuruhSuruh</span>
                    </a>


                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                            <li class="nav-item">
                                <span class="navbar-text me-3">
                                    Halo, <strong><?= $user_greeting_name; ?></strong>!
                                </span>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-fill me-2"></i>Profil Saya</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-envelope-fill me-2"></i>Pesan</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="container-fluid p-lg-4 p-3" id="main-content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="welcome-card p-4 p-md-5 mb-4 rounded-3 shadow-sm text-md-start">
                            <div class="row align-items-center">
                                <div class="col-md-7 col-lg-8">
                                    <h1 class="display-5 fw-bolder">Halo, <?= $user_greeting_name; ?>!</h1>
                                    <p class="fs-5 lead col-md-10">Selamat datang kembali di Dashboard SuruhSuruh.com. Semua jadi mudah dan cepat!</p>
                                    <div class="mt-4">
                                        <a href="tabel/pesanan/tambah.php" class="btn btn-lg btn-warning me-md-2 mb-2 mb-md-0 shadow-sm">
                                            <i class="bi bi-plus-circle-dotted me-2"></i>Buat Pesanan Baru
                                        </a>
                                        <button class="btn btn-lg btn-outline-light mb-2 mb-md-0" data-bs-toggle="collapse" data-bs-target="#crudSubmenu" aria-expanded="false">
                                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Akses Cepat Data
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-5 col-lg-4 text-center d-none d-md-block">
                                    <img src="assets/img/dashboard_illustration.png" alt="Ilustrasi" class="img-fluid rounded dashboard-illustration">
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6 col-lg-4">
                                <div class="card text-center shadow-hover h-100">
                                    <div class="card-body p-4">
                                        <i class="bi bi-box-seam-fill display-3 text-success mb-3"></i>
                                        <h5 class="card-title fw-semibold">Total Pesanan Aktif</h5>
                                        <p class="card-text fs-2 fw-bold text-success">15</p> <a href="tabel/pesanan/" class="btn btn-success mt-2"><i class="bi bi-eye-fill me-1"></i>Lihat Detail</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card text-center shadow-hover h-100">
                                    <div class="card-body p-4">
                                        <i class="bi bi-tools display-3 text-info mb-3"></i>
                                        <h5 class="card-title fw-semibold">Layanan Tersedia</h5>
                                        <p class="card-text fs-2 fw-bold text-info">25</p> <a href="tabel/layanan/" class="btn btn-info mt-2"><i class="bi bi-pencil-fill me-1"></i>Kelola Layanan</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card text-center shadow-hover h-100">
                                    <div class="card-body p-4">
                                        <i class="bi bi-person-badge-fill display-3 text-warning mb-3"></i>
                                        <h5 class="card-title fw-semibold">Mitra Terdaftar</h5>
                                        <p class="card-text fs-2 fw-bold text-warning">8</p> <a href="tabel/mitra/" class="btn btn-warning mt-2"><i class="bi bi-search me-1"></i>Lihat Mitra</a>
                                    </div>
                                </div>
                            </div>
                             </div>
                    </div>
                </div>
            </main>
        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const wrapper = document.getElementById('wrapper');
        const crudSubmenu = new bootstrap.Collapse(document.getElementById('crudSubmenu'), {
            toggle: false // Jangan otomatis buka/tutup saat dibuat
        });

        if (menuToggle) {
            menuToggle.addEventListener('click', function () {
                wrapper.classList.toggle('toggled');
                // Jika sidebar tertutup, pastikan submenu juga tertutup
                if (!wrapper.classList.contains('toggled')) {
                    if (document.getElementById('crudSubmenu').classList.contains('show')) {
                        crudSubmenu.hide();
                    }
                }
            });
        }

        // Opcional: Jika ingin menutup submenu CRUD saat item lain di sidebar diklik
        const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item-action:not([data-bs-toggle="collapse"])');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992 && wrapper.classList.contains('toggled')) { // Hanya untuk mobile jika sidebar terbuka
                     if (document.getElementById('crudSubmenu').classList.contains('show')) {
                        crudSubmenu.hide();
                    }
                    wrapper.classList.remove('toggled'); // Tutup sidebar setelah klik di mobile
                }
            });
        });

        // Animasi untuk card saat di-hover
        const cards = document.querySelectorAll('.shadow-hover');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => card.classList.add('shadow-lg'));
            card.addEventListener('mouseleave', () => card.classList.remove('shadow-lg'));
        });
    </script>
</body>
</html>