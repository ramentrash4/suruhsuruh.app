<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?> - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style_dashboard.css">
</head>
<body>
    <div class="d-flex" id="wrapper">

        <div class="border-end" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-3">
                <a href="<?= BASE_URL ?>dashboard.php" class="text-decoration-none d-flex align-items-center justify-content-center">
                    <img src="<?= BASE_URL ?>assets/img/logo_suruhsuruh_putih.png" alt="Logo SuruhSuruh" width="45" class="me-2 align-middle">
                    <span class="fs-5 fw-semibold text-light align-middle">SuruhSuruh</span>
                </a>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= BASE_URL ?>dashboard.php" class="list-group-item list-group-item-action list-group-item-primary-dark <?php
                    // Cek apakah path saat ini mengandung 'dashboard.php' di akhir
                    if (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/tabel/') === false) echo 'active';
                ?>">
                    <i class="bi bi-house-door-fill me-2"></i>Dashboard
                </a>
                <a href="#crudSubmenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action list-group-item-primary-dark <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/')) echo 'active'; ?>" aria-expanded="<?= (strpos($_SERVER['PHP_SELF'], '/tabel/')) ? 'true' : 'false' ?>" aria-controls="crudSubmenu">
                    <i class="bi bi-pencil-square me-2"></i>Manajemen Data <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/')) echo 'show'; ?>" id="crudSubmenu">
                    <a href="<?= BASE_URL ?>tabel/pengguna/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/pengguna/')) echo 'active'; ?>"><i class="bi bi-people-fill me-2"></i>Pengguna</a>
                    <a href="<?= BASE_URL ?>tabel/pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/pesanan/')) echo 'active'; ?>"><i class="bi bi-box-seam-fill me-2"></i>Pesanan</a>
                    <a href="<?= BASE_URL ?>tabel/detail_pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/detail_pesanan/')) echo 'active'; ?>"><i class="bi bi-file-earmark-text-fill me-2"></i>Detail Pesanan</a>
                    <a href="<?= BASE_URL ?>tabel/layanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/layanan/')) echo 'active'; ?>"><i class="bi bi-tools me-2"></i>Layanan</a>
                    <a href="<?= BASE_URL ?>tabel/mitra/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/mitra/')) echo 'active'; ?>"><i class="bi bi-person-badge-fill me-2"></i>Mitra</a>
                    <a href="<?= BASE_URL ?>tabel/perusahaan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/perusahaan/')) echo 'active'; ?>"><i class="bi bi-building-fill me-2"></i>Perusahaan</a>
                    <a href="<?= BASE_URL ?>tabel/pekerja/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/pekerja/')) echo 'active'; ?>"><i class="bi bi-person-workspace me-2"></i>Pekerja</a>
                    <a href="<?= BASE_URL ?>tabel/pembayaran/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/pembayaran/')) echo 'active'; ?>"><i class="bi bi-credit-card-2-front-fill me-2"></i>Pembayaran</a>
                    <a href="<?= BASE_URL ?>tabel/profit/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?php if (strpos($_SERVER['PHP_SELF'], '/tabel/profit/')) echo 'active'; ?>"><i class="bi bi-graph-up-arrow me-2"></i>Profit</a>
                </div>
                 <a href="#" class="list-group-item list-group-item-action list-group-item-primary-dark">
                    <i class="bi bi-person-circle me-2"></i>Profil Saya
                </a>
                <a href="#" class="list-group-item list-group-item-action list-group-item-primary-dark">
                    <i class="bi bi-gear-fill me-2"></i>Pengaturan Aplikasi
                </a>
            </div>
            <div class="sidebar-footer p-3 text-center">
                 <small class="text-white-50">&copy; <?= date("Y"); ?> SuruhSuruh.com</small>
            </div>
        </div>
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
                <div class="container-fluid">
                    <button class="btn btn-primary-outline" id="menu-toggle" title="Toggle Menu"><i class="bi bi-list fs-3"></i></button>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                            <li class="nav-item">
                                <span class="navbar-text me-3">
                                    Halo, <strong><?= isset($_SESSION['user']) ? htmlspecialchars(explode('@', $_SESSION['user'])[0]) : 'Pengguna'; ?></strong>!
                                </span>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-fill me-2"></i>Profil Saya</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="container-fluid p-lg-4 p-3" id="main-content">