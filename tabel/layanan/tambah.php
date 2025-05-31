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

$mitra_list_all = $koneksi->query("SELECT Id_Mitra, Nama_Mitra, Spesialis_Mitra FROM mitra ORDER BY Nama_Mitra ASC");
if (!$mitra_list_all) { die("Error fetching mitra list: " . $koneksi->error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan = trim($_POST['nama_layanan']);
    $jenis_layanan = $_POST['jenis_layanan'];
    $deskripsi_umum = trim($_POST['deskripsi_umum']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $mitra_terpilih = isset($_POST['id_mitra']) && is_array($_POST['id_mitra']) ? $_POST['id_mitra'] : [];

    if (empty($nama_layanan) || empty($jenis_layanan)) {
        $error_message = "Nama Layanan dan Jenis Layanan wajib diisi.";
    } else {
        $koneksi->begin_transaction();
        try {
            $sql_layanan = "INSERT INTO layanan (Nama_Layanan, Jenis_Layanan, Deskripsi_Umum, Status_Aktif) VALUES (?, ?, ?, ?)";
            $stmt_layanan = $koneksi->prepare($sql_layanan);
            if ($stmt_layanan === false) throw new Exception("Prepare layanan gagal: " . $koneksi->error);
            $stmt_layanan->bind_param("sssi", $nama_layanan, $jenis_layanan, $deskripsi_umum, $status_aktif);
            if (!$stmt_layanan->execute()) throw new Exception("Eksekusi layanan gagal: " . $stmt_layanan->error);
            
            $new_layanan_id = $koneksi->insert_id;
            $stmt_layanan->close();

            if (!empty($mitra_terpilih) && $new_layanan_id > 0) {
                $sql_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES (?, ?)";
                $stmt_terikat = $koneksi->prepare($sql_terikat);
                if ($stmt_terikat === false) throw new Exception("Prepare terikat gagal: " . $koneksi->error);
                
                foreach ($mitra_terpilih as $id_mitra_single) {
                    $id_mitra_int = (int)$id_mitra_single;
                    $stmt_terikat->bind_param("ii", $id_mitra_int, $new_layanan_id);
                    if (!$stmt_terikat->execute()) throw new Exception("Eksekusi terikat gagal: " . $stmt_terikat->error);
                }
                $stmt_terikat->close();
            }
            $koneksi->commit();
            $_SESSION['success_message'] = "Layanan baru berhasil ditambahkan!";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = $e->getMessage();
        }
    }
}
$page_title = "Tambah Layanan Baru";
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-plus-circle-fill me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formTambahLayanan" novalidate>
            <div class="col-md-6"><label for="nama_layanan" class="form-label fw-semibold"><i class="bi bi-card-text me-2"></i>Nama Layanan</label><input type="text" name="nama_layanan" id="nama_layanan" class="form-control" required value="<?= isset($_POST['nama_layanan']) ? htmlspecialchars($_POST['nama_layanan']) : '' ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="jenis_layanan" class="form-label fw-semibold"><i class="bi bi-tag-fill me-2"></i>Jenis Layanan</label>
                <select name="jenis_layanan" id="jenis_layanan" class="form-select" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Makanan" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                    <option value="Kesehatan" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Kesehatan') ? 'selected' : '' ?>>Kesehatan</option>
                    <option value="Layanan Rumah" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Layanan Rumah') ? 'selected' : '' ?>>Layanan Rumah</option>
                    <option value="Lainnya" <?= (isset($_POST['jenis_layanan']) && $_POST['jenis_layanan'] == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                </select><div class="invalid-feedback">Wajib dipilih.</div>
            </div>
            <div class="col-12"><label for="deskripsi_umum" class="form-label fw-semibold"><i class="bi bi-info-square-fill me-2"></i>Deskripsi Umum (Opsional)</label><textarea name="deskripsi_umum" id="deskripsi_umum" class="form-control" rows="3"><?= isset($_POST['deskripsi_umum']) ? htmlspecialchars($_POST['deskripsi_umum']) : '' ?></textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status_aktif" id="status_aktif" value="1" <?= (isset($_POST['status_aktif']) && $_POST['status_aktif'] == 1) || !isset($_POST['status_aktif']) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="status_aktif">Layanan Aktif</label></div></div>

            <div class="col-12 mt-4">
                <label class="form-label fw-semibold"><i class="bi bi-people-fill me-2"></i>Disediakan oleh Mitra (Opsional):</label>
                <div class="row ps-2" style="max-height: 200px; overflow-y: auto;">
                    <?php if($mitra_list_all) mysqli_data_seek($mitra_list_all,0); while ($m = $mitra_list_all->fetch_assoc()) : ?>
                    <div class="col-md-4"><div class="form-check">
                        <input class="form-check-input" type="checkbox" name="id_mitra[]" value="<?= $m['Id_Mitra'] ?>" id="mitra_<?= $m['Id_Mitra'] ?>" <?= (isset($_POST['id_mitra']) && is_array($_POST['id_mitra']) && in_array($m['Id_Mitra'], $_POST['id_mitra'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mitra_<?= $m['Id_Mitra'] ?>"><?= htmlspecialchars($m['Nama_Mitra']) ?> <small class="text-muted">(<?= htmlspecialchars($m['Spesialis_Mitra']) ?>)</small></label>
                    </div></div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>
<script>
// Pencegahan double submit & validasi Bootstrap standar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formTambahLayanan')?.addEventListener('submit', function() {
        document.getElementById('submitButton').setAttribute('disabled', 'true');
        document.getElementById('submitButton').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
    });
    (function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
});
</script>
<?php require_once '../../templates/footer.php'; ?>