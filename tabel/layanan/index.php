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
$results_per_page = 10; 
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$search_term_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_jenis_layanan_raw = isset($_GET['jenis_layanan']) && in_array($_GET['jenis_layanan'], ['Makanan','Kesehatan','Layanan Rumah','Lainnya']) ? $_GET['jenis_layanan'] : '';
$filter_status_aktif_raw = isset($_GET['status_aktif']) && in_array($_GET['status_aktif'], ['1', '0']) ? $_GET['status_aktif'] : '';


$search_term_escaped = $koneksi->real_escape_string($search_term_raw);
$filter_jenis_layanan_escaped = $koneksi->real_escape_string($filter_jenis_layanan_raw);
// $filter_status_aktif_raw sudah divalidasi, aman untuk query direct jika perlu

$sort_options = [
    'id_desc' => 'l.Id_Layanan DESC', 
    'id_asc' => 'l.Id_Layanan ASC',
    'nama_asc' => 'l.Nama_Layanan ASC',
    'nama_desc' => 'l.Nama_Layanan DESC',
    'jenis_asc' => 'l.Jenis_Layanan ASC, l.Nama_Layanan ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "l.*, 
                           (SELECT COUNT(*) FROM terikat t WHERE t.Id_Layanan = l.Id_Layanan) AS Jumlah_Mitra_Terkait";
$base_sql_from_join = "FROM layanan l"; // Tidak ada JOIN utama di sini, JOIN untuk count mitra ada di subquery

$where_clauses_prepared = []; $filter_params_prepared = []; $filter_types_prepared = '';  
$where_clauses_direct = [];

if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(l.Nama_Layanan LIKE ? OR l.Deskripsi_Umum LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ss';
    
    $like_term_direct = "%" . $search_term_escaped . "%";
    $where_clauses_direct[] = "(l.Nama_Layanan LIKE '$like_term_direct' OR l.Deskripsi_Umum LIKE '$like_term_direct')";
}
if (!empty($filter_jenis_layanan_raw)) {
    $where_clauses_prepared[] = "l.Jenis_Layanan = ?";
    $filter_params_prepared[] = $filter_jenis_layanan_raw;
    $filter_types_prepared .= 's';
    $where_clauses_direct[] = "l.Jenis_Layanan = '" . $filter_jenis_layanan_escaped . "'";
}
if ($filter_status_aktif_raw !== '') { // Bisa 0 (Tidak Aktif) atau 1 (Aktif)
    $where_clauses_prepared[] = "l.Status_Aktif = ?";
    $filter_params_prepared[] = (int)$filter_status_aktif_raw; // Pastikan integer
    $filter_types_prepared .= 'i';
    $where_clauses_direct[] = "l.Status_Aktif = " . (int)$filter_status_aktif_raw;
}

$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data (Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(l.Id_Layanan) AS total " . $base_sql_from_join . $sql_where_prepared;
$stmt_total = $koneksi->prepare($total_result_sql);
if ($stmt_total === false) { echo "<div class='alert alert-danger'>Err count prepare: " . htmlspecialchars($koneksi->error) . "</div>"; }
else {
    if (!empty($filter_types_prepared)) { if (!$stmt_total->bind_param($filter_types_prepared, ...$filter_params_prepared)) { echo "<div class='alert alert-danger'>Err count bind: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }}
    if ($total_results === 0 && !$stmt_total->execute()) { echo "<div class='alert alert-danger'>Err count execute: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }
    if ($total_results === 0) {
        $total_result_obj = $stmt_total->get_result();
        if ($total_result_obj === false) { echo "<div class='alert alert-danger'>Err count result: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1;}
        else { $total_row_data = $total_result_obj->fetch_assoc(); $total_results = $total_row_data ? (int)$total_row_data['total'] : 0; }
    }
    $stmt_total->close();
}
if($total_results === -1) $total_results = 0;
$total_pages = $total_results > 0 ? (int)ceil($total_results / $results_per_page) : 0; 
if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
$offset = ($current_page - 1) * $results_per_page;

// --- Mengambil Data (Query Langsung) ---
$results_per_page_int = (int)$results_per_page; $offset_int = (int)$offset;
$data_sql_direct_final = "SELECT " . $base_sql_select_fields . " " . $base_sql_from_join . $sql_where_direct . " ORDER BY $order_by_clause LIMIT $results_per_page_int OFFSET $offset_int";
$result = $koneksi->query($data_sql_direct_final);
if ($result === false) { echo "<div class='alert alert-danger'>Query data GAGAL: " . htmlspecialchars($koneksi->error) . "<br>SQL: ".htmlspecialchars($data_sql_direct_final)."</div>"; $result = null; }

$page_title = "Manajemen Layanan";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-tools me-3" style="font-size: 2.5rem;"></i>Manajemen Layanan</h1><p class="text-muted">Kelola semua jenis layanan yang ditawarkan.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_services.svg" alt="Ilustrasi Layanan" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { /* ... (notifikasi sukses) ... */ } ?>
<?php if (isset($_SESSION['error_message'])) { /* ... (notifikasi error) ... */ } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Layanan</a></div>
            <div class="col-md-2">
                <select name="jenis_layanan" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter Jenis Layanan">
                    <option value="">Semua Jenis</option>
                    <option value="Makanan" <?= ($filter_jenis_layanan_raw == 'Makanan') ? 'selected' : '' ?>>Makanan</option>
                    <option value="Kesehatan" <?= ($filter_jenis_layanan_raw == 'Kesehatan') ? 'selected' : '' ?>>Kesehatan</option>
                    <option value="Layanan Rumah" <?= ($filter_jenis_layanan_raw == 'Layanan Rumah') ? 'selected' : '' ?>>Layanan Rumah</option>
                    <option value="Lainnya" <?= ($filter_jenis_layanan_raw == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status_aktif" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter Status">
                    <option value="">Semua Status</option>
                    <option value="1" <?= ($filter_status_aktif_raw === '1') ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= ($filter_status_aktif_raw === '0') ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : '' ?>>ID (Terbaru)</option>
                    <option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : '' ?>>ID (Terlama)</option>
                    <option value="nama_asc" <?= ($sort_key == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                    <option value="nama_desc" <?= ($sort_key == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                    <option value="jenis_asc" <?= ($sort_key == 'jenis_asc') ? 'selected' : '' ?>>Jenis (A-Z)</option>
                </select>
            </div>
            <div class="col-md-3"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari Nama/Deskripsi..." name="search" value="<?= htmlspecialchars($search_term_raw) ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Nama Layanan</th><th>Jenis</th><th>Deskripsi</th><th>Status</th><th>Mitra Penyedia</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis">#<?= $d['Id_Layanan'] ?></span></td>
                            <td><?= htmlspecialchars($d['Nama_Layanan']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($d['Jenis_Layanan']) ?></span></td>
                            <td><?= nl2br(htmlspecialchars(mb_strimwidth($d['Deskripsi_Umum'], 0, 70, "..."))) ?></td>
                            <td><span class="badge <?= $d['Status_Aktif'] ? 'bg-success' : 'bg-danger' ?>"><?= $d['Status_Aktif'] ? 'Aktif' : 'Tidak Aktif' ?></span></td>
                            <td>
                                <?= $d['Jumlah_Mitra_Terkait'] ?> Mitra 
                                <?php if($d['Jumlah_Mitra_Terkait'] > 0): ?>
                                <button type="button" class="btn btn-link btn-sm p-0 view-mitra-layanan" 
                                        data-bs-toggle="modal" data-bs-target="#mitraLayananModal"
                                        data-id_layanan="<?= $d['Id_Layanan'] ?>"
                                        data-nama_layanan="<?= htmlspecialchars($d['Nama_Layanan'])?>"
                                        title="Lihat Mitra Penyedia"><i class="bi bi-people-fill"></i></button>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_Layanan'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_Layanan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus layanan <?= htmlspecialchars($d['Nama_Layanan']) ?>? Ini juga akan menghapus keterkaitannya dengan mitra dan pesanan.')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-tools fs-1"></i><h5 class="mt-2">Belum ada data layanan.</h5></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php $query_params_pagination = ['search' => $search_term_raw, 'jenis_layanan' => $filter_jenis_layanan_raw, 'status_aktif' => $filter_status_aktif_raw, 'sort' => $sort_key]; $base_link_pagination = '?' . http_build_query(array_filter($query_params_pagination, function($value) { return $value !== ''; })) . '&page='; ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page - 1) ?>">Prev</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="mitraLayananModal" tabindex="-1" aria-labelledby="mitraLayananModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-info text-white"><h5 class="modal-title" id="mitraLayananModalLabel">Mitra Penyedia Layanan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="mitraLayananModalBody">Memuat daftar mitra...</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
</div></div></div>

<?php if(isset($result) && is_object($result)) $result->close(); require_once '../../templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var layananModal = document.getElementById('mitraLayananModal');
    if (layananModal) {
        layananModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var idLayanan = button.getAttribute('data-id_layanan');
            var namaLayanan = button.getAttribute('data-nama_layanan');
            var modalTitle = layananModal.querySelector('.modal-title');
            var modalBody = layananModal.querySelector('#mitraLayananModalBody');

            modalTitle.textContent = 'Mitra Penyedia untuk: ' + namaLayanan;
            modalBody.innerHTML = '<p>Memuat daftar mitra...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            
            fetch('ajax_get_mitra_layanan.php?id_layanan=' + idLayanan)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.mitra.length > 0) {
                        let html = '<ul class="list-group">';
                        data.mitra.forEach(item => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${item.Nama_Mitra}</strong> (ID: ${item.Id_Mitra}) <br>
                                            <small class="text-muted">Spesialis: ${item.Spesialis_Mitra}</small>
                                        </div>
                                        <a href="<?= BASE_URL ?>tabel/mitra/edit.php?id=${item.Id_Mitra}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-pencil"></i> Edit Mitra</a>
                                     </li>`;
                        });
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    } else if (data.success && data.mitra.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">Layanan ini belum memiliki mitra terkait.</p>';
                    } else {
                        modalBody.innerHTML = '<p class="text-danger">Gagal memuat mitra: ' + (data.message || 'Error tidak diketahui') + '</p>';
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = '<p class="text-danger">Terjadi kesalahan saat mengambil data mitra.</p>';
                    console.error('Error fetching mitra:', error);
                });
        });
    }
});
</script>