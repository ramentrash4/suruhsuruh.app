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

$id_layanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_layanan <= 0) { $_SESSION['error_message'] = "ID Layanan tidak valid."; header("Location: index.php"); exit; }

$stmt_layanan = $koneksi->prepare("SELECT * FROM layanan WHERE Id_Layanan = ?");
if(!$stmt_layanan) { die("Prepare layanan gagal: ".$koneksi->error); }
$stmt_layanan->bind_param("i", $id_layanan);
$stmt_layanan->execute();
$result_layanan = $stmt_layanan->get_result();
if ($result_layanan->num_rows === 0) { $_SESSION['error_message'] = "Data layanan tidak ditemukan."; header("Location: index.php"); exit; }
$layanan = $result_layanan->fetch_assoc();
$stmt_layanan->close();

$mitra_list_all = $koneksi->query("SELECT Id_Mitra, Nama_Mitra, Spesialis_Mitra FROM mitra ORDER BY Nama_Mitra ASC");
$linked_mitra_ids = [];
$stmt_linked = $koneksi->prepare("SELECT Id_Mitra FROM terikat WHERE Id_Layanan = ?");
if($stmt_linked){
    $stmt_linked->bind_param("i", $id_layanan); $stmt_linked->execute();
    $result_linked = $stmt_linked->get_result();
    while($row = $result_linked->fetch_assoc()){ $linked_mitra_ids[] = $row['Id_Mitra']; }
    $stmt_linked->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan = trim($_POST['nama_layanan']);
    $jenis_layanan = $_POST['jenis_layanan']; // Jenis layanan bisa diubah di sini
    $deskripsi_umum = trim($_POST['deskripsi_umum']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $mitra_terpilih_baru = isset($_POST['id_mitra']) && is_array($_POST['id_mitra']) ? $_POST['id_mitra'] : [];

    if (empty($nama_layanan) || empty($jenis_layanan)) {
        $error_message = "Nama Layanan dan Jenis Layanan wajib diisi.";
    } else {
        $koneksi->begin_transaction();
        try {
            $sql_update_layanan = "UPDATE layanan SET Nama_Layanan = ?, Jenis_Layanan = ?, Deskripsi_Umum = ?, Status_Aktif = ? WHERE Id_Layanan = ?";
            $stmt_update = $koneksi->prepare($sql_update_layanan);
            if($stmt_update === false) throw new Exception("Prepare update layanan gagal: ".$koneksi->error);
            $stmt_update->bind_param("sssii", $nama_layanan, $jenis_layanan, $deskripsi_umum, $status_aktif, $id_layanan);
            if(!$stmt_update->execute()) throw new Exception("Eksekusi update layanan gagal: ".$stmt_update->error);
            $stmt_update->close();

            $stmt_delete_terikat = $koneksi->prepare("DELETE FROM terikat WHERE Id_Layanan = ?");
            if($stmt_delete_terikat === false) throw new Exception("Prepare delete terikat gagal: ".$koneksi->error);
            $stmt_delete_terikat->bind_param("i", $id_layanan);
            if(!$stmt_delete_terikat->execute()) throw new Exception("Eksekusi delete terikat gagal: ".$stmt_delete_terikat->error);
            $stmt_delete_terikat->close();

            if (!empty($mitra_terpilih_baru)) {
                $sql_insert_terikat = "INSERT INTO terikat (Id_Mitra, Id_Layanan) VALUES (?, ?)";
                $stmt_insert_terikat = $koneksi->prepare($sql_insert_terikat);
                if($stmt_insert_terikat === false) throw new Exception("Prepare insert terikat gagal: ".$koneksi->error);
                foreach ($mitra_terpilih_baru as $id_mitra_single) {
                    $id_mitra_int = (int)$id_mitra_single;
                    $stmt_insert_terikat->bind_param("ii", $id_mitra_int, $id_layanan);
                    if(!$stmt_insert_terikat->execute()) throw new Exception("Eksekusi insert terikat gagal: ".$stmt_insert_terikat->error);
                }
                $stmt_insert_terikat->close();
            }
            $koneksi->commit();
            $_SESSION['success_message'] = "Data layanan dan relasi mitra berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $koneksi->rollback();
            $error_message = $e->getMessage();
        }
    }
    // Re-populate for form
    $layanan['Nama_Layanan'] = $nama_layanan; $layanan['Jenis_Layanan'] = $jenis_layanan;
    $layanan['Deskripsi_Umum'] = $deskripsi_umum; $layanan['Status_Aktif'] = $status_aktif;
    $linked_mitra_ids = $mitra_terpilih_baru;
}
$page_title = "Edit Layanan #" . $layanan['Id_Layanan'];
require_once '../../templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i><?= $page_title; ?></h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>
<div class="card shadow-sm border-light-subtle">
     <div class="card-header bg-light-subtle"><h5 class="mb-0">Mengedit: <?= htmlspecialchars($layanan['Nama_Layanan']) ?></h5></div>
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <form method="post" class="row g-3 needs-validation" id="formEditLayanan" novalidate>
            <div class="col-md-6"><label for="nama_layanan" class="form-label fw-semibold"><i class="bi bi-card-text me-2"></i>Nama Layanan</label><input type="text" name="nama_layanan" id="nama_layanan" class="form-control" required value="<?= htmlspecialchars($layanan['Nama_Layanan']) ?>"><div class="invalid-feedback">Wajib diisi.</div></div>
            <div class="col-md-6"><label for="jenis_layanan" class="form-label fw-semibold"><i class="bi bi-tag-fill me-2"></i>Jenis Layanan</label>
                <select name="jenis_layanan" id="jenis_layanan" class="form-select" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Makanan" <?= ($layanan['Jenis_Layanan'] == 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                    <option value="Kesehatan" <?= ($layanan['Jenis_Layanan'] == 'Kesehatan') ? 'selected' : '' ?>>Kesehatan</option>
                    <option value="Layanan Rumah" <?= ($layanan['Jenis_Layanan'] == 'Layanan Rumah') ? 'selected' : '' ?>>Layanan Rumah</option>
                    <option value="Lainnya" <?= ($layanan['Jenis_Layanan'] == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                </select><div class="invalid-feedback">Wajib dipilih.</div>
            </div>
            <div class="col-12"><label for="deskripsi_umum" class="form-label fw-semibold"><i class="bi bi-info-square-fill me-2"></i>Deskripsi Umum (Opsional)</label><textarea name="deskripsi_umum" id="deskripsi_umum" class="form-control" rows="3"><?= htmlspecialchars($layanan['Deskripsi_Umum']) ?></textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status_aktif" id="status_aktif" value="1" <?= ($layanan['Status_Aktif'] == 1) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="status_aktif">Layanan Aktif</label></div></div>

            <div class="col-12 mt-4">
                <label class="form-label fw-semibold"><i class="bi bi-people-fill me-2"></i>Kaitkan dengan Mitra (Opsional):</label>
                <div class="row ps-2" style="max-height: 200px; overflow-y: auto;">
                    <?php if($mitra_list_all) mysqli_data_seek($mitra_list_all,0); while ($m = $mitra_list_all->fetch_assoc()) : ?>
                    <div class="col-md-4"><div class="form-check">
                        <input class="form-check-input" type="checkbox" name="id_mitra[]" value="<?= $m['Id_Mitra'] ?>" id="mitra_<?= $m['Id_Mitra'] ?>" <?= (in_array($m['Id_Mitra'], $linked_mitra_ids)) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mitra_<?= $m['Id_Mitra'] ?>"><?= htmlspecialchars($m['Nama_Mitra']) ?> <small class="text-muted">(<?= htmlspecialchars($m['Spesialis_Mitra']) ?>)</small></label>
                    </div></div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="col-12 text-end mt-4 border-top pt-4">
                <button type="submit" id="submitButton" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<script>
// Pencegahan double submit & validasi Bootstrap standar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formEditLayanan')?.addEventListener('submit', function() {
        document.getElementById('submitButton').setAttribute('disabled', 'true');
        document.getElementById('submitButton').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
    });
    (function () { 'use strict'; var forms = document.querySelectorAll('.needs-validation'); Array.prototype.slice.call(forms).forEach(function (form) { form.addEventListener('submit', function (event) { if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); } form.classList.add('was-validated'); }, false); }); })();
});
</script>
<?php require_once '../../templates/footer.php'; ?>