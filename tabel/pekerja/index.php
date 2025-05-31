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
$filter_id_perusahaan_raw = isset($_GET['id_perusahaan']) && is_numeric($_GET['id_perusahaan']) ? (int)$_GET['id_perusahaan'] : '';

$search_term_escaped = $koneksi->real_escape_string($search_term_raw);
// $filter_id_perusahaan_raw sudah integer

$sort_options = [
    'id_desc' => 'pk.Id_Pekerja DESC', 
    'id_asc' => 'pk.Id_Pekerja ASC',
    'nama_asc' => 'pk.Nama_Depan ASC, pk.Nama_Belakang ASC',
    'nama_desc' => 'pk.Nama_Depan DESC, pk.Nama_Belakang DESC',
    'perusahaan_asc' => 'pr.Nama ASC, pk.Id_Pekerja ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "pk.*, 
                           pr.Nama AS Perusahaan_Nama, pr.CEO AS Perusahaan_CEO, pr.Kota AS Perusahaan_Kota, pr.Jalan AS Perusahaan_Jalan, pr.Kode_Pos AS Perusahaan_Kode_Pos";
$base_sql_from_join = "FROM pekerja pk
                       JOIN perusahaan pr ON pk.Id_Perusahaan = pr.Id_Perusahaan";

$where_clauses_prepared = [];
$filter_params_prepared = []; 
$filter_types_prepared = '';  
$where_clauses_direct = [];

if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(pk.Nama_Depan LIKE ? OR pk.Nama_Belakang LIKE ? OR pk.NO_Telp LIKE ? OR pr.Nama LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ssss';
    
    $like_term_direct = "%" . $search_term_escaped . "%";
    $where_clauses_direct[] = "(pk.Nama_Depan LIKE '$like_term_direct' OR pk.Nama_Belakang LIKE '$like_term_direct' OR pk.NO_Telp LIKE '$like_term_direct' OR pr.Nama LIKE '$like_term_direct')";
}
if (!empty($filter_id_perusahaan_raw)) {
    $where_clauses_prepared[] = "pk.Id_Perusahaan = ?";
    $filter_params_prepared[] = $filter_id_perusahaan_raw;
    $filter_types_prepared .= 'i';
    $where_clauses_direct[] = "pk.Id_Perusahaan = " . $filter_id_perusahaan_raw;
}
$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data (Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(pk.Id_Pekerja) AS total " . $base_sql_from_join . $sql_where_prepared;
$stmt_total = $koneksi->prepare($total_result_sql);
if ($stmt_total === false) { echo "<div class='alert alert-danger'>Error preparing total count: " . htmlspecialchars($koneksi->error) . "</div>"; }
else {
    if (!empty($filter_types_prepared)) { if (!$stmt_total->bind_param($filter_types_prepared, ...$filter_params_prepared)) { echo "<div class='alert alert-danger'>Error binding params for total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }}
    if ($total_results === 0 && !$stmt_total->execute()) { echo "<div class='alert alert-danger'>Error executing total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }
    if ($total_results === 0) {
        $total_result_obj = $stmt_total->get_result();
        if ($total_result_obj === false) { echo "<div class='alert alert-danger'>Error getting result for total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1;}
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
if ($result === false) { echo "<div class='alert alert-danger'>Query data utama GAGAL: " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($data_sql_direct_final) . "</div>"; $result = null; }

$page_title = "Manajemen Pekerja";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-person-vcard-fill me-3" style="font-size: 2.5rem;"></i>Manajemen Pekerja</h1><p class="text-muted">Kelola data pekerja internal perusahaan.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_team.svg" alt="Ilustrasi Tim Pekerja" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pekerja</a></div>
            <div class="col-md-3">
                 <?php $perusahaan_filter_list = $koneksi->query("SELECT Id_Perusahaan, Nama FROM perusahaan ORDER BY Nama"); ?>
                <select name="id_perusahaan" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter Perusahaan">
                    <option value="">Semua Perusahaan</option>
                    <?php if ($perusahaan_filter_list && $perusahaan_filter_list->num_rows > 0): while($pf = $perusahaan_filter_list->fetch_assoc()): ?>
                    <option value="<?= $pf['Id_Perusahaan'] ?>" <?= ($filter_id_perusahaan_raw == $pf['Id_Perusahaan']) ? 'selected' : '' ?>><?= htmlspecialchars($pf['Nama']) ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : '' ?>>ID Pekerja (Terbaru)</option>
                    <option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : '' ?>>ID Pekerja (Terlama)</option>
                    <option value="nama_asc" <?= ($sort_key == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                    <option value="nama_desc" <?= ($sort_key == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                    <option value="perusahaan_asc" <?= ($sort_key == 'perusahaan_asc') ? 'selected' : '' ?>>Perusahaan (A-Z)</option>
                </select>
            </div>
            <div class="col-md-4"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari Nama, Telp, Perusahaan..." name="search" value="<?= htmlspecialchars($search_term_raw) ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Nama Lengkap</th><th>Tgl Lahir</th><th>No. Telp</th><th>Perusahaan</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis">#<?= $d['Id_Pekerja'] ?></span></td>
                            <td><?= htmlspecialchars(trim($d['Nama_Depan'].' '.$d['Nama_Tengah'].' '.$d['Nama_Belakang'])) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($d['Tanggal_lahir']))) ?></td>
                            <td><?= htmlspecialchars($d['NO_Telp']) ?></td>
                            <td>
                                <?= htmlspecialchars($d['Perusahaan_Nama']) ?> (ID: <?= $d['Id_Perusahaan'] ?>)
                                <button type="button" class="btn btn-link btn-sm p-0 view-perusahaan-details" 
                                        data-bs-toggle="modal" data-bs-target="#perusahaanDetailModal"
                                        data-id_perusahaan="<?= $d['Id_Perusahaan'] ?>"
                                        data-nama_perusahaan="<?= htmlspecialchars($d['Perusahaan_Nama'])?>"
                                        data-ceo_perusahaan="<?= htmlspecialchars($d['Perusahaan_CEO'])?>"
                                        data-kota_perusahaan="<?= htmlspecialchars($d['Perusahaan_Kota'])?>"
                                        data-jalan_perusahaan="<?= htmlspecialchars($d['Perusahaan_Jalan'])?>"
                                        data-kodepos_perusahaan="<?= htmlspecialchars($d['Perusahaan_Kode_Pos'])?>"
                                        title="Lihat Detail Perusahaan">
                                    <i class="bi bi-info-circle-fill"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <a href="edit.php?id_pekerja=<?= $d['Id_Pekerja'] ?>&id_perusahaan=<?= $d['Id_Perusahaan'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id_pekerja=<?= $d['Id_Pekerja'] ?>&id_perusahaan=<?= $d['Id_Perusahaan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pekerja: <?= htmlspecialchars(trim($d['Nama_Depan'].' '.$d['Nama_Belakang'])) ?>?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-person-workspace fs-1"></i><h5 class="mt-2">Belum ada data pekerja.</h5></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php $query_params_pagination = ['search' => $search_term_raw, 'id_perusahaan' => $filter_id_perusahaan_raw, 'sort' => $sort_key]; $base_link_pagination = '?' . http_build_query(array_filter($query_params_pagination)) . '&page='; ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page - 1) ?>">Prev</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="perusahaanDetailModal" tabindex="-1" aria-labelledby="perusahaanDetailModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-primary text-white"><h5 class="modal-title" id="perusahaanDetailModalLabel"><i class="bi bi-buildings-fill me-2"></i>Detail Perusahaan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="perusahaanDetailModalBody"> Memuat detail... </div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditPerusahaanLink" class="btn btn-warning" style="display:none;"><i class="bi bi-pencil-square me-1"></i> Edit Perusahaan Ini</a></div>
</div></div></div>

<?php if(isset($result) && is_object($result)) $result->close(); require_once '../../templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var perusahaanModal = document.getElementById('perusahaanDetailModal');
    if (perusahaanModal) {
        perusahaanModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var modalBody = perusahaanModal.querySelector('#perusahaanDetailModalBody');
            var modalEditLink = perusahaanModal.querySelector('#modalEditPerusahaanLink');
            modalEditLink.style.display = 'none';
            
            var idPerusahaan = button.getAttribute('data-id_perusahaan');
            modalBody.innerHTML = `
                <p><strong>ID Perusahaan:</strong> #${idPerusahaan || 'N/A'}</p>
                <p><strong>Nama Perusahaan:</strong> ${button.getAttribute('data-nama_perusahaan') || 'N/A'}</p>
                <p><strong>CEO:</strong> ${button.getAttribute('data-ceo_perusahaan') || 'N/A'}</p>
                <p><strong>Kota:</strong> ${button.getAttribute('data-kota_perusahaan') || 'N/A'}</p>
                <p><strong>Jalan:</strong> ${button.getAttribute('data-jalan_perusahaan') || 'N/A'}</p>
                <p><strong>Kode Pos:</strong> ${button.getAttribute('data-kodepos_perusahaan') || 'N/A'}</p>
            `;
            if (idPerusahaan) {
                modalEditLink.href = '<?= BASE_URL ?>tabel/perusahaan/edit.php?id=' + idPerusahaan;
                modalEditLink.style.display = 'inline-block';
            }
        });
    }
});
</script>