<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}
require '../../config/database.php';

$data_pengguna_query = mysqli_query($koneksi, "SELECT * FROM pengguna ORDER BY Id_pengguna ASC");
if (!$data_pengguna_query) {
    die("Error: " . mysqli_error($koneksi));
}

// Setel judul halaman untuk template
$page_title = "Manajemen Pengguna";

// Panggil template header
require_once '../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary">
        <i class="bi bi-people-fill me-2"></i>Manajemen Pengguna
    </h1>
    <a href="tambah.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="bi bi-plus-circle-fill me-2"></i>Tambah Pengguna Baru
    </a>
</div>

<?php
// Tampilkan pesan notifikasi dari session
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' .
         htmlspecialchars($_SESSION['success_message']) .
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
         '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <h5 class="card-title mb-0">Daftar Pengguna Terdaftar</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nama Pengguna</th>
                        <th scope="col">Email</th>
                        <th scope="col">No. Telepon</th>
                        <th scope="col">Bergabung Sejak</th>
                        <th scope="col" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($data_pengguna_query) > 0): ?>
                        <?php $nomor = 1; ?>
                        <?php while ($d = mysqli_fetch_assoc($data_pengguna_query)): ?>
                        <?php
                            $inisial = strtoupper(substr($d['Nama_Depan'], 0, 1) . substr($d['Nama_Belakang'], 0, 1));
                            $colors = ['bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-dark'];
                            $random_color = $colors[$d['Id_pengguna'] % count($colors)]; // Warna konsisten berdasarkan ID
                        ?>
                        <tr>
                            <th scope="row"><?= $nomor++; ?></th>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle <?= $random_color; ?> text-white me-3">
                                        <span><?= $inisial; ?></span>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars(trim($d['Nama_Depan'] . ' ' . $d['Nama_Tengah'] . ' ' . $d['Nama_Belakang'])); ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($d['Alamat']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><a href="mailto:<?= htmlspecialchars($d['Email']); ?>"><?= htmlspecialchars($d['Email']); ?></a></td>
                            <td><?= htmlspecialchars($d['No_Telp']); ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($d['Tanggal_Lahir']))); ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_pengguna'] ?>" class="btn btn-warning btn-sm" title="Edit Pengguna">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="hapus.php?id=<?= $d['Id_pengguna'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')" title="Hapus Pengguna">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-person-x-fill fs-1"></i>
                                    <h5 class="mt-2">Belum ada data pengguna.</h5>
                                    <p>Silakan tambahkan pengguna baru untuk memulai.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style> /* CSS tambahan khusus untuk halaman ini */
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
</style>

<?php
// Panggil template footer
require_once '../../templates/footer.php';
?>