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

$sort_options = [
    'id_desc' => 'm.Id_Mitra DESC', 
    'id_asc' => 'm.Id_Mitra ASC',
    'nama_asc' => 'm.Nama_Mitra ASC',
    'nama_desc' => 'm.Nama_Mitra DESC',
    'spesialis_asc' => 'm.Spesialis_Mitra ASC',
    'perusahaan_asc' => 'pr.Nama ASC, m.Nama_Mitra ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "m.*, 
                           pr.Nama AS Perusahaan_Nama, pr.CEO AS Perusahaan_CEO, pr.Kota AS Perusahaan_Kota, 
                           (SELECT COUNT(*) FROM terikat t WHERE t.Id_Mitra = m.Id_Mitra) AS Jumlah_Layanan_Terkait";
$base_sql_from_join = "FROM mitra m
                       LEFT JOIN perusahaan pr ON m.Id_Perusahaan = pr.Id_Perusahaan";

$where_clauses_prepared = []; $filter_params_prepared = []; $filter_types_prepared = '';  
$where_clauses_direct = [];

if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(m.Nama_Mitra LIKE ? OR m.Spesialis_Mitra LIKE ? OR m.No_Telp LIKE ? OR pr.Nama LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ssss';
    
    $like_term_direct = "%" . $search_term_escaped . "%";
    $where_clauses_direct[] = "(m.Nama_Mitra LIKE '$like_term_direct' OR m.Spesialis_Mitra LIKE '$like_term_direct' OR m.No_Telp LIKE '$like_term_direct' OR pr.Nama LIKE '$like_term_direct')";
}
if (!empty($filter_id_perusahaan_raw)) {
    $where_clauses_prepared[] = "m.Id_Perusahaan = ?";
    $filter_params_prepared[] = $filter_id_perusahaan_raw;
    $filter_types_prepared .= 'i';
    $where_clauses_direct[] = "m.Id_Perusahaan = " . $filter_id_perusahaan_raw;
}
$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data (Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(m.Id_Mitra) AS total " . $base_sql_from_join . $sql_where_prepared;
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

$page_title = "Manajemen Mitra";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-people-fill me-3" style="font-size: 2.5rem;"></i>Manajemen Mitra</h1><p class="text-muted">Kelola data mitra kerjasama SuruhSuruh.com.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_partners.svg" alt="Ilustrasi Mitra" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-person-plus-fill me-2"></i>Tambah Mitra</a></div>
             <div class="col-md-3">
                 <?php $perusahaan_filter_list = $koneksi->query("SELECT Id_Perusahaan, Nama FROM perusahaan ORDER BY Nama"); ?>
                <select name="id_perusahaan" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter Perusahaan Afiliasi">
                    <option value="">Semua Perusahaan</option>
                    <?php if ($perusahaan_filter_list && $perusahaan_filter_list->num_rows > 0): while($pf = $perusahaan_filter_list->fetch_assoc()): ?>
                    <option value="<?= $pf['Id_Perusahaan'] ?>" <?= ($filter_id_perusahaan_raw == $pf['Id_Perusahaan']) ? 'selected' : '' ?>><?= htmlspecialchars($pf['Nama']) ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : '' ?>>ID Mitra (Terbaru)</option>
                    <option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : '' ?>>ID Mitra (Terlama)</option>
                    <option value="nama_asc" <?= ($sort_key == 'nama_asc') ? 'selected' : '' ?>>Nama Mitra (A-Z)</option>
                    <option value="nama_desc" <?= ($sort_key == 'nama_desc') ? 'selected' : '' ?>>Nama Mitra (Z-A)</option>
                    <option value="spesialis_asc" <?= ($sort_key == 'spesialis_asc') ? 'selected' : '' ?>>Spesialis (A-Z)</option>
                    <option value="perusahaan_asc" <?= ($sort_key == 'perusahaan_asc') ? 'selected' : '' ?>>Perusahaan (A-Z)</option>
                </select>
            </div>
            <div class="col-md-4"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari Nama Mitra, Spesialis, Telp..." name="search" value="<?= htmlspecialchars($search_term_raw) ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Nama Mitra</th><th>Kontak</th><th>Spesialis</th><th>Afiliasi Perusahaan</th><th>Layanan</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis">#<?= $d['Id_Mitra'] ?></span></td>
                            <td><?= htmlspecialchars($d['Nama_Mitra']) ?></td>
                            <td><?= htmlspecialchars($d['No_Telp']) ?></td>
                            <td><?= htmlspecialchars($d['Spesialis_Mitra']) ?></td>
                            <td>
                                <?php if ($d['Id_Perusahaan']): ?>
                                    <?= htmlspecialchars($d['Perusahaan_Nama']) ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 view-perusahaan-details" 
                                            data-bs-toggle="modal" data-bs-target="#perusahaanDetailModal"
                                            data-id_perusahaan="<?= $d['Id_Perusahaan'] ?>"
                                            data-nama_perusahaan="<?= htmlspecialchars($d['Perusahaan_Nama'])?>"
                                            data-ceo_perusahaan="<?= htmlspecialchars($d['Perusahaan_CEO'])?>"
                                            data-kota_perusahaan="<?= htmlspecialchars($d['Perusahaan_Kota'])?>"
                                            title="Lihat Detail Perusahaan"><i class="bi bi-info-circle"></i></button>
                                <?php else: echo "<span class='text-muted fst-italic'>N/A</span>"; endif; ?>
                            </td>
                            <td>
                                <?= $d['Jumlah_Layanan_Terkait'] ?> Layanan 
                                <?php if($d['Jumlah_Layanan_Terkait'] > 0): ?>
                                <button type="button" class="btn btn-link btn-sm p-0 view-layanan-mitra" 
                                        data-bs-toggle="modal" data-bs-target="#layananMitraModal"
                                        data-id_mitra="<?= $d['Id_Mitra'] ?>"
                                        data-nama_mitra="<?= htmlspecialchars($d['Nama_Mitra'])?>"
                                        title="Lihat Layanan Terkait"><i class="bi bi-list-task"></i></button>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_Mitra'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_Mitra'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus mitra <?= htmlspecialchars($d['Nama_Mitra']) ?>?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-person-bounding-box fs-1"></i><h5 class="mt-2">Belum ada data mitra.</h5></td></tr>
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

<div class="modal fade" id="perusahaanDetailModal" tabindex="-1" aria-labelledby="perusahaanDetailModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-primary text-white"><h5 class="modal-title" id="perusahaanDetailModalLabel">Detail Perusahaan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="perusahaanDetailModalBody"></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditPerusahaanLink" class="btn btn-warning" style="display:none;"><i class="bi bi-pencil-square"></i> Edit Perusahaan</a></div>
</div></div></div>

<div class="modal fade" id="layananMitraModal" tabindex="-1" aria-labelledby="layananMitraModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-info text-white"><h5 class="modal-title" id="layananMitraModalLabel">Layanan Terkait Mitra</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="layananMitraModalBody">Memuat layanan...</div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
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
            var idPerusahaan = button.getAttribute('data-id_perusahaan');
            modalBody.innerHTML = `
                <p><strong>ID:</strong> #${idPerusahaan || 'N/A'}</p>
                <p><strong>Nama:</strong> ${button.getAttribute('data-nama_perusahaan') || 'N/A'}</p>
                <p><strong>CEO:</strong> ${button.getAttribute('data-ceo_perusahaan') || 'N/A'}</p>
                <p><strong>Kota:</strong> ${button.getAttribute('data-kota_perusahaan') || 'N/A'}</p>`;
            if (idPerusahaan) {
                modalEditLink.href = '<?= BASE_URL ?>tabel/perusahaan/edit.php?id=' + idPerusahaan;
                modalEditLink.style.display = 'inline-block';
            } else { modalEditLink.style.display = 'none'; }
        });
    }

    var layananModal = document.getElementById('layananMitraModal');
    if (layananModal) {
        layananModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var idMitra = button.getAttribute('data-id_mitra');
            var namaMitra = button.getAttribute('data-nama_mitra');
            var modalTitle = layananModal.querySelector('.modal-title');
            var modalBody = layananModal.querySelector('#layananMitraModalBody');

            modalTitle.textContent = 'Layanan untuk Mitra: ' + namaMitra;
            modalBody.innerHTML = '<p>Memuat daftar layanan...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            
            // Ambil layanan via AJAX
            fetch('ajax_get_layanan_mitra.php?id_mitra=' + idMitra)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.layanan.length > 0) {
                        let html = '<ul class="list-group">';
                        data.layanan.forEach(item => {
                            html += `<li class="list-group-item">${item.Nama_Layanan} (Jenis: ${item.Jenis_Layanan})</li>`;
                        });
                        html += '</ul>';
                        modalBody.innerHTML = html;
                    } else if (data.success && data.layanan.length === 0) {
                        modalBody.innerHTML = '<p class="text-muted">Mitra ini belum memiliki layanan terkait.</p>';
                    } else {
                        modalBody.innerHTML = '<p class="text-danger">Gagal memuat layanan: ' + (data.message || 'Error tidak diketahui') + '</p>';
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = '<p class="text-danger">Terjadi kesalahan saat mengambil data layanan.</p>';
                    console.error('Error fetching layanan:', error);
                });
        });
    }
});
</script>