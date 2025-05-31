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
$error_message = '';

$pengguna_list = $koneksi->query("SELECT Id_pengguna, Nama_Depan, Nama_Belakang, Email, Alamat, No_Telp FROM pengguna WHERE status = 'aktif' ORDER BY Nama_Depan");
$bayaran_list = $koneksi->query("SELECT Id_Pembayaran, Jumlah, Tanggal, Id_Pengguna FROM bayaran ORDER BY Tanggal DESC");
$layanan_list = $koneksi->query("SELECT Id_Layanan, Nama_Layanan, Jenis_Layanan, Deskripsi_Umum FROM layanan WHERE Status_Aktif = 1 ORDER BY Nama_Layanan");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = isset($_POST['id_pengguna']) && !empty($_POST['id_pengguna']) ? (int)$_POST['id_pengguna'] : null;
    $id_pembayaran = isset($_POST['id_pembayaran']) && !empty($_POST['id_pembayaran']) ? (int)$_POST['id_pembayaran'] : null;
    $id_layanan = isset($_POST['id_layanan']) && !empty($_POST['id_layanan']) ? (int)$_POST['id_layanan'] : null;
    $tanggal = $_POST['tanggal'];
    $status_pesanan = $_POST['status_pesanan'];

    if (empty($tanggal) || empty($status_pesanan) || $id_pengguna === null || $id_layanan === null) { 
        $error_message = "Pengguna, Layanan, Tanggal Pesan, dan Status Pesanan wajib diisi.";
    } else {
        $sql = "INSERT INTO pesanan (Id_Pengguna, Id_Pembayaran, Id_Layanan, Tanggal, Status_Pesanan) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        if ($stmt === false) { $error_message = "Error preparing statement: " . $koneksi->error; }
        else {
            $stmt->bind_param("iiiss", $id_pengguna, $id_pembayaran, $id_layanan, $tanggal, $status_pesanan);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Data pesanan baru berhasil ditambahkan!";
                header("Location: index.php");
                exit; // Pastikan exit dieksekusi
            } else { $error_message = "Gagal menambahkan pesanan: " . $stmt->error; }
            $stmt->close();
        }
    }
}
$page_title = "Buat Pesanan Baru";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-cart-plus-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahPesanan" novalidate>
            <div class="col-md-6">
                <label for="id_pengguna_select" class="form-label fw-semibold"><i class="bi bi-person-check-fill me-2"></i>Pengguna (Wajib)</label>
                <select name="id_pengguna" id="id_pengguna_select" class="form-select select2-init" required data-preview-target="#detail_pengguna_panel" data-preview-type="pengguna">
                    <option value="">-- Pilih Pengguna --</option>
                    <?php if($pengguna_list) while ($p = $pengguna_list->fetch_assoc()) : ?><option value="<?= $p['Id_pengguna'] ?>" data-nama_lengkap="<?= htmlspecialchars(trim($p['Nama_Depan'].' '.$p['Nama_Belakang'])) ?>" data-email="<?= htmlspecialchars($p['Email']) ?>" data-telp="<?= htmlspecialchars($p['No_Telp']) ?>" data-alamat="<?= htmlspecialchars($p['Alamat']) ?>"><?= htmlspecialchars(trim($p['Nama_Depan'].' '.$p['Nama_Belakang'])) ?> (ID: <?= $p['Id_pengguna'] ?>)</option><?php endwhile; ?>
                </select><div class="invalid-feedback">Pengguna wajib dipilih.</div>
                <div id="detail_pengguna_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:80px;"><small class="text-muted">Detail pengguna akan muncul di sini.</small></div>
            </div>
            <div class="col-md-6">
                <label for="id_layanan_select" class="form-label fw-semibold"><i class="bi bi-tools me-2"></i>Layanan (Wajib)</label>
                <select name="id_layanan" id="id_layanan_select" class="form-select select2-init" required data-preview-target="#detail_layanan_panel" data-preview-type="layanan">
                    <option value="">-- Pilih Layanan --</option>
                    <?php if($layanan_list) mysqli_data_seek($layanan_list, 0); while ($l = $layanan_list->fetch_assoc()) : ?><option value="<?= $l['Id_Layanan'] ?>" data-nama_layanan="<?= htmlspecialchars($l['Nama_Layanan']) ?>" data-jenis_layanan="<?= htmlspecialchars($l['Jenis_Layanan']) ?>" data-deskripsi_layanan="<?= htmlspecialchars($l['Deskripsi_Umum']?:'Tidak ada deskripsi.') ?>"><?= htmlspecialchars($l['Nama_Layanan']) ?> (<?= htmlspecialchars($l['Jenis_Layanan']) ?>)</option><?php endwhile; ?>
                </select><div class="invalid-feedback">Layanan wajib dipilih.</div>
                <div id="detail_layanan_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:80px;"><small class="text-muted">Detail layanan akan muncul di sini.</small></div>
            </div>
            <div class="col-md-6">
                <label for="id_pembayaran_select" class="form-label fw-semibold"><i class="bi bi-credit-card-2-front-fill me-2"></i>Pembayaran (Opsional)</label>
                <select name="id_pembayaran" id="id_pembayaran_select" class="form-select select2-init" data-preview-target="#detail_pembayaran_panel" data-preview-type="bayaran">
                    <option value="">-- Pilih Pembayaran (Jika Sudah Ada) --</option>
                    <?php if($bayaran_list) mysqli_data_seek($bayaran_list, 0); while ($b = $bayaran_list->fetch_assoc()) : ?><option value="<?= $b['Id_Pembayaran'] ?>" data-id_pengguna_bayaran="<?= $b['Id_Pengguna'] ?>" data-jumlah_bayaran="Rp <?= number_format(preg_replace("/[^0-9]/","",$b['Jumlah']),0,',','.') ?>" data-tanggal_bayaran="<?= htmlspecialchars(date('d M Y', strtotime($b['Tanggal']))) ?>">ID: <?= $b['Id_Pembayaran'] ?> - Rp <?= number_format(preg_replace("/[^0-9]/","",$b['Jumlah']),0,',','.') ?> (Pengguna ID: <?= $b['Id_Pengguna'] ?: 'N/A' ?>)</option><?php endwhile; ?>
                </select>
                <div id="detail_pembayaran_panel" class="detail-preview-panel mt-2 p-2 border rounded bg-light" style="font-size:0.85rem; min-height:80px;"><small class="text-muted">Detail pembayaran akan muncul di sini.</small></div>
            </div>
             <div class="col-md-3">
                <label for="tanggal" class="form-label fw-semibold"><i class="bi bi-calendar-event me-2"></i>Tanggal Pesan</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label for="status_pesanan" class="form-label fw-semibold"><i class="bi bi-bar-chart-steps me-2"></i>Status Pesanan</label>
                <select name="status_pesanan" id="status_pesanan" class="form-select" required>
                    <option value="Baru" selected>Baru</option><option value="Diproses">Diproses</option><option value="Dikirim">Dikirim</option><option value="Selesai">Selesai</option><option value="Dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Pesanan</button>
            </div>
        </form>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-init').each(function() { /* ... (kode select2 sama seperti sebelumnya) ... */ });

    // Pencegahan double submit
    $('#formTambahPesanan').on('submit', function() {
        $('#submitButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
    });
});
</script>
<?php require_once '../../templates/footer.php'; ?>