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

// --- PENGATURAN PAGINASI & FILTER ---
$results_per_page = 6; // Jumlah item per halaman, sesuaikan jika perlu
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Opsi sorting dan sorting default
$sort_options = [
    'tgl_desc' => 'b.Tanggal DESC, b.Id_Pembayaran DESC', 
    'tgl_asc' => 'b.Tanggal ASC, b.Id_Pembayaran ASC', 
    'jml_desc' => 'CAST(REPLACE(REPLACE(b.Jumlah, "RP. ", ""), ".", "") AS UNSIGNED) DESC, b.Id_Pembayaran DESC', 
    'jml_asc' => 'CAST(REPLACE(REPLACE(b.Jumlah, "RP. ", ""), ".", "") AS UNSIGNED) ASC, b.Id_Pembayaran ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'tgl_desc';
$order_by = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql = "FROM bayaran b LEFT JOIN pengguna p ON b.Id_Pengguna = p.Id_pengguna";
$where_clauses = [];
$filter_params = []; // Parameter HANYA untuk klausa WHERE
$filter_types = '';  // Tipe HANYA untuk klausa WHERE

if (!empty($search_term)) {
    $where_clauses[] = "(p.Nama_Depan LIKE ? OR p.Nama_Belakang LIKE ? OR p.Email LIKE ? OR b.Jumlah LIKE ?)";
    $like_term = "%" . $search_term . "%";
    array_push($filter_params, $like_term, $like_term, $like_term, $like_term);
    $filter_types .= 'ssss'; // Disesuaikan jumlah 's'
}
if (!empty($start_date)) {
    $where_clauses[] = "b.Tanggal >= ?";
    $filter_params[] = $start_date;
    $filter_types .= 's';
}
if (!empty($end_date)) {
    $where_clauses[] = "b.Tanggal <= ?";
    $filter_params[] = $end_date;
    $filter_types .= 's';
}
$sql_where = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- Menghitung Total Data untuk Paginasi (menggunakan filter_params) ---
$total_result_sql = "SELECT COUNT(DISTINCT b.Id_Pembayaran) AS total " . $base_sql . $sql_where;
$stmt_total = $koneksi->prepare($total_result_sql);
if ($stmt_total === false) { die("Error preparing total count query: " . $koneksi->error . " <br>SQL: " . htmlspecialchars($total_result_sql)); }

if (!empty($filter_params)) { // Bind hanya jika ada parameter filter
    if (!$stmt_total->bind_param($filter_types, ...$filter_params)) {
        die("Error binding params for total count: " . $stmt_total->error);
    }
}
if (!$stmt_total->execute()) { die("Error executing total count query: " . $stmt_total->error); }
$total_result_obj = $stmt_total->get_result();
if ($total_result_obj === false) { die("Error getting result for total count: " . $stmt_total->error); }
$total_row_data = $total_result_obj->fetch_assoc();
$total_results = $total_row_data ? $total_row_data['total'] : 0;
$total_pages = $total_results > 0 ? ceil($total_results / $results_per_page) : 0;
$stmt_total->close();

// --- Mengambil Data untuk Halaman Saat Ini ---
$offset = ($current_page - 1) * $results_per_page;
$data_sql = "SELECT b.*, p.Nama_Depan, p.Nama_Tengah, p.Nama_Belakang, p.Email AS Pengguna_Email, p.No_Telp AS Pengguna_No_Telp, p.Alamat AS Pengguna_Alamat, p.Tanggal_Lahir AS Pengguna_Tanggal_Lahir " 
            . $base_sql . $sql_where . " ORDER BY $order_by LIMIT ? OFFSET ?";

$data_params = $filter_params; // Salin parameter filter yang sudah ada
$data_types = $filter_types;   // Salin tipe filter yang sudah ada

$data_params[] = $results_per_page; // Tambah parameter untuk LIMIT
$data_params[] = $offset;           // Tambah parameter untuk OFFSET
$data_types .= 'ii';                // Tambah tipe untuk LIMIT dan OFFSET ('i' untuk integer)

$stmt_data = $koneksi->prepare($data_sql);
if ($stmt_data === false) { die("Error preparing data fetch query: " . $koneksi->error . " <br>SQL: " . htmlspecialchars($data_sql)); }

// bind_param harus dipanggil jika $data_types tidak kosong (selalu ada 'ii' dari LIMIT/OFFSET)
if (!$stmt_data->bind_param($data_types, ...$data_params)) {
    die("Error binding params for data fetch: " . $stmt_data->error . " <br>Types: " . $data_types . " <br>Param count: " . count($data_params));
}

if (!$stmt_data->execute()) { die("Error executing data fetch query: " . $stmt_data->error); }
$result = $stmt_data->get_result();
if ($result === false) { die("Error getting result set for data fetch: " . $stmt_data->error); }

/*
// --- BLOK DEBUGGING YANG BISA ANDA AKTIFKAN JIKA MASALAH BERLANJUT ---
echo "<div class='alert alert-warning mt-3 p-3' style='font-size: 0.9rem; text-align:left;'>";
echo "<strong>DEBUGGING INFORMATION:</strong><hr>";
echo "<strong>URL Parameters:</strong><br><pre>"; print_r($_GET); echo "</pre>";
echo "<strong>Search Term:</strong> " . htmlspecialchars($search_term) . "<br>";
echo "<strong>Start Date:</strong> " . htmlspecialchars($start_date) . "<br>";
echo "<strong>End Date:</strong> " . htmlspecialchars($end_date) . "<br>";
echo "<strong>Sort Key:</strong> " . htmlspecialchars($sort_key) . " (ORDER BY: " . htmlspecialchars($order_by) . ")<br>";
echo "<strong>Current Page:</strong> " . $current_page . "<br>";
echo "<strong>Total Results:</strong> " . $total_results . "<br>";
echo "<strong>Total Pages:</strong> " . $total_pages . "<br>";
echo "<hr><strong>Total Count Query:</strong><br>" . htmlspecialchars($total_result_sql) . "<br>";
echo "<strong>Filter Params (for count):</strong><br><pre>"; print_r($filter_params); echo "</pre>"; // Ini adalah filter_params sebelum paginasi
echo "<strong>Filter Types (for count):</strong> " . htmlspecialchars($filter_types) . "<br>";
echo "<hr><strong>Data Fetch Query:</strong><br>" . htmlspecialchars($data_sql) . "<br>";
echo "<strong>Data Params (with pagination):</strong><br><pre>"; print_r($data_params); echo "</pre>";
echo "<strong>Data Types (with pagination):</strong> " . htmlspecialchars($data_types) . "<br>";
echo "<strong>Number of Rows Fetched:</strong> " . ($result ? $result->num_rows : 'Query Gagal') . "<br>";
echo "</div>";
// --- AKHIR BLOK DEBUGGING ---
*/

$page_title = "Manajemen Bayaran";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-wallet2 me-3" style="font-size: 2.5rem;"></i>Manajemen Bayaran</h1><p class="text-muted">Lacak dan kelola semua transaksi bayaran dalam sistem.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_payments.svg" alt="Ilustrasi Bayaran" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle d-flex flex-wrap justify-content-between align-items-center gap-3">
        <a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Catat Bayaran</a>
        <form action="" method="GET" class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end">
            <input type="date" name="start_date" class="form-control" style="width: auto;" value="<?= htmlspecialchars($start_date) ?>" title="Tanggal Mulai">
            <span>-</span>
            <input type="date" name="end_date" class="form-control" style="width: auto;" value="<?= htmlspecialchars($end_date) ?>" title="Tanggal Akhir">
             <select name="sort" class="form-select" style="width: auto;" onchange="this.form.submit()" title="Urutkan">
                <option value="tgl_desc" <?= ($sort_key == 'tgl_desc') ? 'selected' : ''; ?>>Tanggal (Terbaru)</option>
                <option value="tgl_asc" <?= ($sort_key == 'tgl_asc') ? 'selected' : ''; ?>>Tanggal (Terlama)</option>
                <option value="jml_desc" <?= ($sort_key == 'jml_desc') ? 'selected' : ''; ?>>Jumlah (Tertinggi)</option>
                <option value="jml_asc" <?= ($sort_key == 'jml_asc') ? 'selected' : ''; ?>>Jumlah (Terendah)</option>
            </select>
            <div class="input-group" style="width: 250px;">
                <input class="form-control" type="search" placeholder="Cari nama/email/jumlah..." name="search" value="<?= htmlspecialchars($search_term) ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th scope="col">ID Bayaran</th><th scope="col">Nama Pengguna</th><th scope="col">Tanggal</th><th scope="col" class="text-end">Jumlah</th><th scope="col" class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary bg-opacity-25 text-primary-emphasis">#<?= $d['Id_Pembayaran'] ?></span></td>
                            <td>
                                <?= htmlspecialchars(trim($d['Nama_Depan'] . ' ' . $d['Nama_Tengah'] . ' ' . $d['Nama_Belakang'])) ?: '<span class="text-muted fst-italic">Pengguna Dihapus</span>' ?>
                                <?php if ($d['Id_Pengguna']): ?>
                                <button type="button" class="btn btn-outline-info btn-sm ms-1 py-0 px-1 view-pengguna-details" 
                                        data-bs-toggle="modal" data-bs-target="#penggunaDetailModal"
                                        data-id_pengguna="<?= $d['Id_Pengguna'] ?>"
                                        data-nama_lengkap="<?= htmlspecialchars(trim($d['Nama_Depan'] . ' ' . $d['Nama_Tengah'] . ' ' . $d['Nama_Belakang'])) ?>"
                                        data-email="<?= htmlspecialchars($d['Pengguna_Email']) ?>"
                                        data-telp="<?= htmlspecialchars($d['Pengguna_No_Telp']) ?>"
                                        data-alamat="<?= htmlspecialchars($d['Pengguna_Alamat']) ?>"
                                        data-tgl_lahir="<?= htmlspecialchars(isset($d['Pengguna_Tanggal_Lahir']) ? date('d M Y', strtotime($d['Pengguna_Tanggal_Lahir'])) : 'N/A') ?>"
                                        title="Lihat Detail Pengguna"><i class="bi bi-eye-fill"></i></button>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($d['Tanggal']))) ?></td>
                            <td class="text-end fw-bold text-success">Rp <?= number_format(preg_replace("/[^0-9]/", "", $d['Jumlah']), 0, ',', '.') ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_Pembayaran'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="cetak_kuitansi.php?id=<?= $d['Id_Pembayaran'] ?>" class="btn btn-success btn-sm" title="Cetak Kuitansi" target="_blank"><i class="bi bi-printer-fill"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_Pembayaran'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data bayaran ini?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-wallet fs-1"></i><h5 class="mt-2">Belum ada data bayaran.</h5><p>Coba ubah filter atau kata kunci pencarian Anda, atau tambahkan data baru.</p></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php $query_params = ['search' => $search_term, 'start_date' => $start_date, 'end_date' => $end_date, 'sort' => $sort_key]; $base_link = '?' . http_build_query(array_filter($query_params)) . '&page='; ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link . ($current_page - 1) ?>">Previous</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="penggunaDetailModal" tabindex="-1" aria-labelledby="penggunaDetailModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-primary text-white"><h5 class="modal-title" id="penggunaDetailModalLabel"><i class="bi bi-person-lines-fill me-2"></i>Detail Pengguna</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body"><div class="row">
<div class="col-md-4 text-center"><div id="modalAvatar" class="avatar-circle-xl bg-secondary text-white mx-auto mb-3"><span>XX</span></div><h4 id="modalNamaLengkap" class="fw-bold">Nama Pengguna</h4><p class="text-muted mb-0" id="modalIdPengguna">ID: #</p></div>
<div class="col-md-8"><h5><i class="bi bi-info-circle-fill text-info me-2"></i>Informasi Kontak:</h5><p class="mb-1"><strong>Email:</strong> <span id="modalEmail"></span></p><p class="mb-1"><strong>No. Telepon:</strong> <span id="modalTelp"></span></p><p class="mb-3"><strong>Alamat:</strong> <span id="modalAlamat" style="white-space: pre-wrap;"></span></p><h5><i class="bi bi-calendar-event-fill text-info me-2"></i>Data Pribadi:</h5><p class="mb-1"><strong>Tanggal Lahir:</strong> <span id="modalTglLahir"></span></p></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditPenggunaLink" class="btn btn-warning rounded-pill px-4"><i class="bi bi-pencil-square me-1"></i>Edit Pengguna Ini</a></div>
</div></div></div>
<style>.avatar-circle-xl{width:100px;height:100px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:2.5rem;border:3px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,.1)}</style>

<?php if(isset($stmt_data)) $stmt_data->close(); require_once '../../templates/footer.php'; ?>
<script>
// (JavaScript untuk Modal Detail Pengguna tetap sama)
document.addEventListener('DOMContentLoaded', function () {
    var penggunaDetailModal = document.getElementById('penggunaDetailModal');
    if (penggunaDetailModal) {
        penggunaDetailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var idPengguna = button.getAttribute('data-id_pengguna');
            var namaLengkap = button.getAttribute('data-nama_lengkap');
            var email = button.getAttribute('data-email');
            var telp = button.getAttribute('data-telp');
            var alamat = button.getAttribute('data-alamat');
            var tglLahir = button.getAttribute('data-tgl_lahir');
            var inisial = "XX";
            if (namaLengkap && namaLengkap !== 'N/A') { var namaParts = namaLengkap.split(' '); inisial = namaParts[0].charAt(0).toUpperCase(); if (namaParts.length > 1 && namaParts[namaParts.length - 1]) { inisial += namaParts[namaParts.length - 1].charAt(0).toUpperCase(); }}
            var modal = this;
            modal.querySelector('#modalIdPengguna').textContent = 'ID: #' + (idPengguna || 'N/A');
            modal.querySelector('#modalNamaLengkap').textContent = namaLengkap || 'N/A';
            modal.querySelector('#modalEmail').textContent = email || 'N/A';
            modal.querySelector('#modalTelp').textContent = telp || 'N/A';
            modal.querySelector('#modalAlamat').textContent = alamat || 'N/A';
            modal.querySelector('#modalTglLahir').textContent = tglLahir || 'N/A';
            modal.querySelector('#modalAvatar span').textContent = inisial;
            var editLink = modal.querySelector('#modalEditPenggunaLink');
            if (editLink) { if (idPengguna && idPengguna !== 'N/A') { editLink.href = '<?= BASE_URL ?>tabel/pengguna/edit.php?id=' + idPengguna; editLink.style.display = ''; } else { editLink.style.display = 'none';}}
        });
    }
});
</script>