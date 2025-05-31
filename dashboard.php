<?php
// Selalu include config.php di awal untuk session dan BASE_URL
require_once __DIR__ . '/config.php'; 
// Pengecekan login dan pengambilan nama sapaan sekarang sebagian besar dihandle oleh header.php

$page_title = "Dashboard Admin"; 
require_once __DIR__ . '/templates/header.php'; 
// Variabel $nama_sapaan_admin sudah tersedia dari header.php
?>

<div class="row">
    <div class="col-lg-12">
        <div class="welcome-card p-4 p-md-5 mb-4 rounded-3 shadow-sm text-md-start">
            <div class="row align-items-center">
                <div class="col-md-7 col-lg-8">
                    <h1 class="display-5 fw-bolder">Halo, <?= $nama_sapaan_admin; // Langsung gunakan dari header ?>!</h1>
                    <p class="fs-5 lead col-md-10">Selamat datang kembali di Dashboard SuruhSuruh.com. Semua jadi mudah dan cepat!</p>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>tabel/pesanan/tambah.php" class="btn btn-lg btn-warning me-md-2 mb-2 mb-md-0 shadow-sm">
                            <i class="bi bi-plus-circle-dotted me-2"></i>Buat Pesanan Baru
                        </a>
                        <button class="btn btn-lg btn-outline-light mb-2 mb-md-0" data-bs-toggle="collapse" data-bs-target="#crudSubmenu" 
                                aria-expanded="<?= (isCrudParentActive() === 'active') ? 'true' : 'false' // Pastikan fungsi ini tersedia atau sesuaikan ?>">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Akses Cepat Data
                        </button>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4 text-center d-none d-md-block">
                    <img src="<?= BASE_URL ?>assets/img/dashboard_illustration.png" alt="Ilustrasi" class="img-fluid rounded dashboard-illustration">
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card text-center shadow-hover h-100">
                    <div class="card-body p-4">
                        <i class="bi bi-box-seam-fill display-3 text-success mb-3"></i>
                        <h5 class="card-title fw-semibold">Total Pesanan Aktif</h5>
                        <p class="card-text fs-2 fw-bold text-success">15</p> 
                        <a href="<?= BASE_URL ?>tabel/pesanan/" class="btn btn-success mt-2"><i class="bi bi-eye-fill me-1"></i>Lihat Detail</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center shadow-hover h-100">
                    <div class="card-body p-4">
                        <i class="bi bi-tools display-3 text-info mb-3"></i>
                        <h5 class="card-title fw-semibold">Layanan Tersedia</h5>
                        <p class="card-text fs-2 fw-bold text-info">25</p> 
                        <a href="<?= BASE_URL ?>tabel/layanan/" class="btn btn-info mt-2"><i class="bi bi-pencil-fill me-1"></i>Kelola Layanan</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card text-center shadow-hover h-100">
                    <div class="card-body p-4">
                        <i class="bi bi-person-badge-fill display-3 text-warning mb-3"></i>
                        <h5 class="card-title fw-semibold">Mitra Terdaftar</h5>
                        <p class="card-text fs-2 fw-bold text-warning">8</p> 
                        <a href="<?= BASE_URL ?>tabel/mitra/" class="btn btn-warning mt-2"><i class="bi bi-search me-1"></i>Lihat Mitra</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php'; 
?>