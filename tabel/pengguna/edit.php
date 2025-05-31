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
$data = null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) { $_SESSION['error_message'] = "ID tidak valid."; header("Location: index.php"); exit; }

$stmt_fetch = $koneksi->prepare("SELECT * FROM pengguna WHERE Id_pengguna = ?");
$stmt_fetch->bind_param("i", $id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Data pengguna tidak ditemukan."; header("Location: index.php"); exit;
}
$stmt_fetch->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $depan = trim($_POST['depan']);
    $tengah = trim($_POST['tengah']);
    $belakang = trim($_POST['belakang']);
    $lahir = trim($_POST['lahir']);
    $alamat = trim($_POST['alamat']);
    $email = trim($_POST['email']);
    $telp = trim($_POST['telp']);
    $status = isset($_POST['status']) && in_array($_POST['status'], ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';

    if (empty($depan) || empty($belakang) || empty($lahir) || empty($alamat) || empty($email) || empty($telp)) {
        $error_message = "Semua field wajib diisi, kecuali Nama Tengah.";
        $data = $_POST; // Muat ulang data yang diinput ke form jika terjadi error
    } else {
        $sql = "UPDATE pengguna SET Nama_Depan=?, Nama_Tengah=?, Nama_Belakang=?, Tanggal_Lahir=?, Alamat=?, Email=?, No_Telp=?, status=? WHERE Id_pengguna=?";
        $stmt = $koneksi->prepare($sql);
        // Tambahkan 's' untuk status di bind_param
        $stmt->bind_param("ssssssssi", $depan, $tengah, $belakang, $lahir, $alamat, $email, $telp, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Data pengguna '" . htmlspecialchars($depan) . "' berhasil diperbarui!";
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui data: " . $stmt->error;
        }
        $stmt->close();
    }
}

$page_title = "Edit Pengguna";
require_once '../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i>Edit Pengguna</h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali</a>
</div>

<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4 p-md-5">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>
        <form method="post" class="row g-4">
             <div class="col-lg-6">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-person-badge-fill me-2"></i>INFORMASI PRIBADI</h6>
                <div class="row g-3">
                    <div class="col-md-6"><label for="depan" class="form-label fw-semibold"><i class="bi bi-person-fill me-2"></i>Nama Depan</label><input type="text" class="form-control" id="depan" name="depan" required value="<?= htmlspecialchars($data['Nama_Depan']) ?>"></div>
                    <div class="col-md-6"><label for="belakang" class="form-label fw-semibold">Nama Belakang</label><input type="text" class="form-control" id="belakang" name="belakang" required value="<?= htmlspecialchars($data['Nama_Belakang']) ?>"></div>
                    <div class="col-12"><label for="tengah" class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Nama Tengah <span class="text-muted fw-normal">(Opsional)</span></label><input type="text" class="form-control" id="tengah" name="tengah" value="<?= htmlspecialchars($data['Nama_Tengah']) ?>"></div>
                    <div class="col-12"><label for="lahir" class="form-label fw-semibold"><i class="bi bi-calendar-event-fill me-2"></i>Tanggal Lahir</label><input type="date" class="form-control" id="lahir" name="lahir" required value="<?= htmlspecialchars($data['Tanggal_Lahir']) ?>"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-telephone-inbound-fill me-2"></i>INFORMASI KONTAK & STATUS</h6>
                <div class="row g-3">
                    <div class="col-12"><label for="email" class="form-label fw-semibold"><i class="bi bi-envelope-fill me-2"></i>Alamat Email</label><input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($data['Email']) ?>"></div>
                    <div class="col-12"><label for="telp" class="form-label fw-semibold"><i class="bi bi-telephone-fill me-2"></i>No. Telepon</label><input type="text" class="form-control" id="telp" name="telp" required value="<?= htmlspecialchars($data['No_Telp']) ?>"></div>
                    <div class="col-12"><label for="alamat" class="form-label fw-semibold"><i class="bi bi-geo-alt-fill me-2"></i>Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="2" required><?= htmlspecialchars($data['Alamat']) ?></textarea></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold"><i class="bi bi-toggle-on me-2"></i>Status</label>
                        <div class="form-check"><input class="form-check-input" type="radio" name="status" id="status_aktif" value="aktif" <?= ($data['status'] == 'aktif') ? 'checked' : ''; ?>><label class="form-check-label" for="status_aktif">Aktif</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="status" id="status_nonaktif" value="nonaktif" <?= ($data['status'] == 'nonaktif') ? 'checked' : ''; ?>><label class="form-check-label" for="status_nonaktif">Nonaktif</label></div>
                    </div>
                </div>
            </div>
            <div class="col-12 text-end mt-5 border-top pt-4">
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php require_once '../../templates/footer.php'; ?>