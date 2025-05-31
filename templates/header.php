<?php
// File: projekbasdat/templates/header.php

// config.php (yang berisi session_start() dan BASE_URL)
// SEHARUSNYA SUDAH DIPANGGIL OLEH FILE YANG MENG-INCLUDE HEADER INI.
// Jadi, kita bisa langsung menggunakan BASE_URL dan $_SESSION.

// Pastikan BASE_URL ada, jika tidak, halaman tidak bisa berfungsi dengan benar
if (!defined('BASE_URL')) {
    // Cobalah untuk memuat config.php jika belum termuat (ini sebagai fallback)
    // Ini mengasumsikan header.php ada di dalam folder 'templates' satu level di bawah root proyek
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } else {
        // Jika config.php masih tidak ditemukan, berikan pesan error fatal.
        die("Konstanta BASE_URL belum terdefinisi dan config.php tidak ditemukan. Hentikan eksekusi.");
    }
}

// Pengecekan login admin (kecuali untuk halaman auth itu sendiri)
$current_script_path = $_SERVER['PHP_SELF'];
// Cek apakah kita berada di dalam folder /auth/ relatif terhadap BASE_URL
$is_auth_page = (strpos($current_script_path, BASE_URL . 'auth/') === 0);


if (!$is_auth_page && (!isset($_SESSION['login_admin']) || $_SESSION['login_admin'] !== true)) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Ambil nama admin untuk sapaan
$nama_sapaan_admin = 'Admin'; 
if (isset($_SESSION['admin_nama']) && !empty(trim($_SESSION['admin_nama']))) {
    $nama_sapaan_admin = htmlspecialchars($_SESSION['admin_nama']);
} elseif (isset($_SESSION['admin_email'])) {
    $nama_sapaan_admin = htmlspecialchars(explode('@', $_SESSION['admin_email'])[0]);
}
if (strlen($nama_sapaan_admin) > 15) { 
    $nama_sapaan_admin = substr($nama_sapaan_admin, 0, 12) . "...";
}

// Variabel $current_path_for_active untuk fungsi isActive, dll.
$current_path_for_active = $_SERVER['PHP_SELF'];
function isActive($path_segment, $exact_file = false) { 
    global $current_path_for_active; 
    if ($exact_file) { 
        // Cek apakah path saat ini sama persis dengan BASE_URL + path_segment
        return ($current_path_for_active === BASE_URL . $path_segment); 
    } 
    // Cek apakah path saat ini ada di dalam folder tabel yang dimaksud
    return (strpos($current_path_for_active, BASE_URL . 'tabel/' . $path_segment . '/') === 0); 
}
function isAuthPageActive($page_name) { global $current_path_for_active; return (strpos($current_path_for_active, BASE_URL . 'auth/' . $page_name) === 0); }
function isCrudParentActive() { global $current_path_for_active; return (strpos($current_path_for_active, BASE_URL . 'tabel/') === 0) ? 'active' : ''; }
function isCrudSubmenuShow() { global $current_path_for_active; return (strpos($current_path_for_active, BASE_URL . 'tabel/') === 0) ? 'show' : ''; }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin Dashboard'; ?> - SuruhSuruh.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style_dashboard.css">
    <?php if (isset($additional_css)): foreach ($additional_css as $css_file): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?><?= $css_file ?>">
    <?php endforeach; endif; ?>
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
                <a href="<?= BASE_URL ?>dashboard.php" class="list-group-item list-group-item-action list-group-item-primary-dark <?= isActive('dashboard.php', true) ?>">
                    <i class="bi bi-house-door-fill me-2"></i>Dashboard
                </a>
                <a href="#crudSubmenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action list-group-item-primary-dark <?= isCrudParentActive() ?>" 
                   aria-expanded="<?= (isCrudParentActive() === 'active') ? 'true' : 'false' ?>" aria-controls="crudSubmenu">
                    <i class="bi bi-pencil-square me-2"></i>Manajemen Data <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div class="collapse <?= isCrudSubmenuShow() ?>" id="crudSubmenu">
                    <a href="<?= BASE_URL ?>tabel/pengguna/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('pengguna') ?>"><i class="bi bi-people-fill me-2"></i>Pengguna (Pelanggan)</a>
                    <a href="<?= BASE_URL ?>tabel/pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('pesanan') ?>"><i class="bi bi-box-seam-fill me-2"></i>Pesanan</a>
                    <a href="<?= BASE_URL ?>tabel/detail_pesanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('detail_pesanan') ?>"><i class="bi bi-file-earmark-text-fill me-2"></i>Detail Pesanan</a>
                    <a href="<?= BASE_URL ?>tabel/layanan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('layanan') ?>"><i class="bi bi-tools me-2"></i>Layanan</a>
                    <a href="<?= BASE_URL ?>tabel/mitra/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('mitra') ?>"><i class="bi bi-person-badge-fill me-2"></i>Mitra</a>
                    <a href="<?= BASE_URL ?>tabel/perusahaan/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('perusahaan') ?>"><i class="bi bi-building-fill me-2"></i>Perusahaan</a>
                    <a href="<?= BASE_URL ?>tabel/pekerja/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('pekerja') ?>"><i class="bi bi-person-workspace me-2"></i>Pekerja</a>
                    <a href="<?= BASE_URL ?>tabel/pembayaran/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('bayaran') ?>"><i class="bi bi-credit-card-2-front-fill me-2"></i>pembayaran</a>
                    <a href="<?= BASE_URL ?>tabel/profit/" class="list-group-item list-group-item-action list-group-item-primary-dark ps-4 <?= isActive('profit') ?>"><i class="bi bi-graph-up-arrow me-2"></i>Profit</a>
                </div>
                 <a href="<?= BASE_URL ?>auth/profil_admin.php" class="list-group-item list-group-item-action list-group-item-primary-dark <?= isAuthPageActive('profil_admin.php') ?>">
                    <i class="bi bi-person-circle me-2"></i>Profil Admin Saya
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
                                    Halo, <strong><?= $nama_sapaan_admin; ?></strong>!
                                </span>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/profil_admin.php"><i class="bi bi-person-fill me-2"></i>Profil Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <main class="container-fluid p-lg-4 p-3" id="main-content">