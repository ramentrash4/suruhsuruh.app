<?php
// Pastikan error reporting aktif di paling atas
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin'] !== true) {
    if (!defined('BASE_URL')) define('BASE_URL', '/projekbasdat/');
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
require_once '../../config.php';

$perusahaan = null;
$grand_total_profit = 0;

// Ambil data perusahaan pertama yang ada (asumsi hanya ada satu atau data utama)
$stmt_perusahaan = $koneksi->prepare("SELECT * FROM perusahaan ORDER BY Id_Perusahaan ASC LIMIT 1");
if ($stmt_perusahaan) {
    if (!$stmt_perusahaan->execute()) {
        // Tampilkan pesan error, tapi jangan hentikan skrip agar halaman tetap render
        echo "<div class='alert alert-danger'>Error executing perusahaan query: " . htmlspecialchars($stmt_perusahaan->error) . "</div>";
    } else {
        $result_perusahaan = $stmt_perusahaan->get_result();
        if ($result_perusahaan === false) {
            echo "<div class='alert alert-danger'>Error getting result for perusahaan: " . htmlspecialchars($stmt_perusahaan->error) . "</div>";
        } else {
            $perusahaan = $result_perusahaan->fetch_assoc();
        }
    }
    $stmt_perusahaan->close();
} else {
    echo "<div class='alert alert-danger'>Error preparing perusahaan query: " . htmlspecialchars($koneksi->error) . "</div>";
}

// Hitung total profit keseluruhan
$stmt_profit = $koneksi->prepare("SELECT SUM(CAST(REPLACE(REPLACE(total_Profit, 'RP. ', ''), '.', '') AS DECIMAL(15,2))) AS Grand_Total_Profit FROM profit");
if ($stmt_profit) {
    if (!$stmt_profit->execute()) {
        echo "<div class='alert alert-danger'>Error executing profit sum query: " . htmlspecialchars($stmt_profit->error) . "</div>";
    } else {
        $result_profit = $stmt_profit->get_result();
        if ($result_profit === false) {
            echo "<div class='alert alert-danger'>Error getting result for profit sum: " . htmlspecialchars($stmt_profit->error) . "</div>";
        } else {
            $profit_row = $result_profit->fetch_assoc();
            $grand_total_profit = $profit_row && $profit_row['Grand_Total_Profit'] ? (float)$profit_row['Grand_Total_Profit'] : 0;
        }
    }
    $stmt_profit->close();
} else {
    echo "<div class='alert alert-danger'>Error preparing profit sum query: " . htmlspecialchars($koneksi->error) . "</div>";
}

$page_title = "Profil Perusahaan";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <h1 class="h2 fw-bolder text-primary d-flex align-items-center">
            <i class="bi bi-buildings-fill me-3" style="font-size: 2.5rem;"></i>Profil Perusahaan Utama
        </h1>
        <p class="text-muted">Informasi detail mengenai entitas perusahaan Anda.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <img src="<?= BASE_URL ?>assets/img/illustration_company_profile.svg" alt="Ilustrasi Profil Perusahaan" style="max-height: 140px;">
    </div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<?php if ($perusahaan): ?>
<div class="card shadow-lg border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-building me-2"></i><?= htmlspecialchars($perusahaan['Nama']) ?></h5>
        <a href="edit.php?id=<?= $perusahaan['Id_Perusahaan'] ?>" class="btn btn-light btn-sm rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i>Edit Data</a>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <p class="mb-2"><strong><i class="bi bi-person-badge me-2 text-muted"></i>CEO:</strong></p>
                <p class="lead ms-4"><?= htmlspecialchars($perusahaan['CEO']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong><i class="bi bi-geo-alt-fill me-2 text-muted"></i>Kota:</strong></p>
                <p class="lead ms-4"><?= htmlspecialchars($perusahaan['Kota']) ?></p>
            </div>
            <div class="col-12">
                <p class="mb-2"><strong><i class="bi bi-signpost-split-fill me-2 text-muted"></i>Alamat Jalan:</strong></p>
                <p class="lead ms-4"><?= htmlspecialchars($perusahaan['Jalan']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong><i class="bi bi-mailbox2 me-2 text-muted"></i>Kode Pos:</strong></p>
                <p class="lead ms-4"><?= htmlspecialchars($perusahaan['Kode_Pos']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong><i class="bi bi-bar-chart-line-fill me-2 text-muted"></i>ID Perusahaan:</strong></p>
                <p class="lead ms-4">#<?= htmlspecialchars($perusahaan['Id_Perusahaan']) ?></p>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center bg-light-subtle p-3 rounded">
            <h5 class="text-success fw-bold"><i class="bi bi-cash-coin me-2"></i>Total Akumulasi Profit Sistem</h5>
            <p class="display-5 text-success fw-bolder mb-0">Rp <?= htmlspecialchars(number_format($grand_total_profit, 2, ',', '.')) ?></p>
        </div>
    </div>
    </div>
<?php else: ?>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body text-center p-5">
        <i class="bi bi-buildings fs-1 text-muted mb-3"></i>
        <h5 class="text-muted">Data perusahaan utama belum ada.</h5>
        <p>Silakan tambahkan data perusahaan utama untuk sistem Anda.</p>
        <a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Tambahkan Data Perusahaan</a>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../templates/footer.php'; ?>