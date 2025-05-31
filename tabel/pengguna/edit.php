<?php
session_start();
// ... (logika PHP untuk edit, sama seperti di atas, letakkan di sini) ...

$page_title = "Edit Pengguna";
require_once '../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 fw-bold text-primary"><i class="bi bi-pencil-square me-3"></i>Edit Pengguna</h1>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>Kembali ke Daftar
    </a>
</div>

<div class="card shadow-sm border-light-subtle">
    <div class="card-body p-4">
        <h5 class="card-title mb-4">Mengubah Data untuk: <span class="text-success fw-bold"><?= htmlspecialchars($data['Nama_Depan']); ?></span></h5>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label for="depan" class="form-label fw-semibold">Nama Depan</label>
                <input type="text" class="form-control" id="depan" name="depan" required value="<?= htmlspecialchars($data['Nama_Depan']) ?>">
            </div>
            <div class="col-md-4">
                <label for="tengah" class="form-label fw-semibold">Nama Tengah <span class="text-muted fw-normal">(Opsional)</span></label>
                <input type="text" class="form-control" id="tengah" name="tengah" value="<?= htmlspecialchars($data['Nama_Tengah']) ?>">
            </div>
            <div class="col-md-4">
                <label for="belakang" class="form-label fw-semibold">Nama Belakang</label>
                <input type="text" class="form-control" id="belakang" name="belakang" required value="<?= htmlspecialchars($data['Nama_Belakang']) ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold">Alamat Email</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($data['Email']) ?>">
            </div>
            <div class="col-md-6">
                <label for="telp" class="form-label fw-semibold">No. Telepon</label>
                <input type="text" class="form-control" id="telp" name="telp" required value="<?= htmlspecialchars($data['No_Telp']) ?>">
            </div>
            <div class="col-12">
                <label for="alamat" class="form-label fw-semibold">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($data['Alamat']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label for="lahir" class="form-label fw-semibold">Tanggal Lahir</label>
                <input type="date" class="form-control" id="lahir" name="lahir" required value="<?= htmlspecialchars($data['Tanggal_Lahir']) ?>">
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="bi bi-save-fill me-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer.php'; ?>