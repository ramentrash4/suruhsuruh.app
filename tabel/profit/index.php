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

// Ambil dan escape nilai filter RAW untuk prepared statement (COUNT)
$search_term_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_id_detail_pesanan_raw = isset($_GET['id_detail_pesanan']) && is_numeric($_GET['id_detail_pesanan']) ? (int)$_GET['id_detail_pesanan'] : '';

// Escape untuk query langsung (Data Utama)
$search_term_escaped = $koneksi->real_escape_string($search_term_raw);
// $filter_id_detail_pesanan_raw sudah integer, aman untuk query langsung

$sort_options = [
    'id_desc' => 'pr.Id_Profit DESC', 
    'id_asc' => 'pr.Id_Profit ASC',
    'tgl_desc' => 'pr.Tanggal_Profit DESC, pr.Id_Profit DESC',
    'tgl_asc' => 'pr.Tanggal_Profit ASC, pr.Id_Profit ASC',
    'total_desc' => 'pr.total_Profit DESC, pr.Id_Profit DESC',
    'total_asc' => 'pr.total_Profit ASC, pr.Id_Profit ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "pr.*, 
                           dp.Id_Pesanan AS Detail_Id_Pesanan, dp.Harga AS Detail_Harga, dp.Jumlah AS Detail_Jumlah,
                           ps.Tanggal AS Pesanan_Tanggal,
                           pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Belakang AS Pengguna_Nama_Belakang,
                           l.Nama_Layanan AS Layanan_Nama";
$base_sql_from_join = "FROM profit pr
                       LEFT JOIN detail_pesanan dp ON pr.Id_DetailPesanan = dp.Id_DetailPesanan
                       LEFT JOIN pesanan ps ON dp.Id_Pesanan = ps.Id_Pesanan
                       LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
                       LEFT JOIN layanan l ON ps.Id_Layanan = l.Id_Layanan";

$where_clauses_prepared = [];
$filter_params_prepared = []; 
$filter_types_prepared = '';  

$where_clauses_direct = [];

if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(pr.Id_Profit LIKE ? OR pr.total_Profit LIKE ? OR dp.Id_DetailPesanan LIKE ? OR ps.Id_Pesanan LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ssss';
    
    $like_term_direct = "%" . $search_term_escaped . "%";
    $where_clauses_direct[] = "(pr.Id_Profit LIKE '$like_term_direct' OR pr.total_Profit LIKE '$like_term_direct' OR dp.Id_DetailPesanan LIKE '$like_term_direct' OR ps.Id_Pesanan LIKE '$like_term_direct')";
}
if (!empty($filter_id_detail_pesanan_raw)) {
    $where_clauses_prepared[] = "pr.Id_DetailPesanan = ?";
    $filter_params_prepared[] = $filter_id_detail_pesanan_raw;
    $filter_types_prepared .= 'i';
    $where_clauses_direct[] = "pr.Id_DetailPesanan = " . $filter_id_detail_pesanan_raw;
}
$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data untuk Paginasi (Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(pr.Id_Profit) AS total " . $base_sql_from_join . $sql_where_prepared;
$stmt_total = $koneksi->prepare($total_result_sql);
if ($stmt_total === false) { echo "<div class='alert alert-danger'>Error preparing total count query: " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($total_result_sql) . "</div>"; }
else {
    if (!empty($filter_types_prepared)) { if (!$stmt_total->bind_param($filter_types_prepared, ...$filter_params_prepared)) { echo "<div class='alert alert-danger'>Error binding params for total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }}
    if ($total_results === 0 && !$stmt_total->execute()) { echo "<div class='alert alert-danger'>Error executing total count query: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }
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

// --- Mengambil Data untuk Halaman Saat Ini (Query Langsung) ---
$results_per_page_int = (int)$results_per_page; $offset_int = (int)$offset;
$data_sql_direct_final = "SELECT " . $base_sql_select_fields . " " . $base_sql_from_join . $sql_where_direct . " ORDER BY $order_by_clause LIMIT $results_per_page_int OFFSET $offset_int";
$result = $koneksi->query($data_sql_direct_final);
if ($result === false) { echo "<div class='alert alert-danger'>Query data utama GAGAL: " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($data_sql_direct_final) . "</div>"; $result = null; }

/* // BLOK DEBUGGING (Aktifkan jika perlu)
if ($result !== null) {
    echo "<div class='alert alert-info mt-3 p-3' style='font-size: 0.9rem; text-align:left; word-break: break-all;'><strong>DEBUG INFO (PROFIT INDEX):</strong><hr>";
    echo "Total Results: " . $total_results . " | Query: " . htmlspecialchars($data_sql_direct_final) . "<br>";
    echo "Num Rows: " . ($result ? $result->num_rows : 'N/A') . "</div>";
}
*/

$page_title = "Manajemen Profit";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-graph-up-arrow me-3" style="font-size: 2.5rem;"></i>Manajemen Profit</h1><p class="text-muted">Lacak dan kelola data profit dari transaksi.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_profit.svg" alt="Ilustrasi Profit" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { /* ... (notifikasi sukses) ... */ } ?>
<?php if (isset($_SESSION['error_message'])) { /* ... (notifikasi error) ... */ } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Data Profit</a></div>
            <div class="col-md-3">
                <input type="number" name="id_detail_pesanan" class="form-control form-control-sm" placeholder="Filter ID Detail Pesanan..." value="<?= htmlspecialchars($filter_id_detail_pesanan_raw) ?>">
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : '' ?>>ID Profit (Terbaru)</option>
                    <option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : '' ?>>ID Profit (Terlama)</option>
                    <option value="tgl_desc" <?= ($sort_key == 'tgl_desc') ? 'selected' : '' ?>>Tgl Profit (Terbaru)</option>
                    <option value="tgl_asc" <?= ($sort_key == 'tgl_asc') ? 'selected' : '' ?>>Tgl Profit (Terlama)</option>
                    <option value="total_desc" <?= ($sort_key == 'total_desc') ? 'selected' : '' ?>>Total (Tertinggi)</option>
                    <option value="total_asc" <?= ($sort_key == 'total_asc') ? 'selected' : '' ?>>Total (Terendah)</option>
                </select>
            </div>
            <div class="col-md-4"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari ID Profit/Total/ID Detail/ID Pesanan..." name="search" value="<?= htmlspecialchars($search_term_raw) ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID Profit</th><th>Detail Pesanan Terkait</th><th>Tgl Profit</th><th class="text-end">Total Profit</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-success bg-opacity-25 text-success-emphasis">#<?= $d['Id_Profit'] ?></span></td>
                            <td>
                                <?php if ($d['Id_DetailPesanan']): ?>
                                    ID Detail: <?= htmlspecialchars($d['Id_DetailPesanan']) ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 view-detail-pesanan" 
                                            data-bs-toggle="modal" data-bs-target="#detailPesananModal"
                                            data-id_detail_pesanan="<?= $d['Id_DetailPesanan'] ?>"
                                            data-id_pesanan="<?= htmlspecialchars($d['Detail_Id_Pesanan']) ?>"
                                            data-harga_item="Rp <?= number_format($d['Detail_Harga'], 0, ',', '.') ?>"
                                            data-jumlah_item="<?= htmlspecialchars($d['Detail_Jumlah']) ?>"
                                            data-subtotal_item="Rp <?= number_format($d['Detail_Harga'] * $d['Detail_Jumlah'], 0, ',', '.') ?>"
                                            data-pesanan_tanggal="<?= htmlspecialchars(date('d M Y', strtotime($d['Pesanan_Tanggal']))) ?>"
                                            data-pengguna_nama="<?= htmlspecialchars(trim($d['Pengguna_Nama_Depan'].' '.$d['Pengguna_Nama_Belakang'])) ?>"
                                            data-layanan_nama="<?= htmlspecialchars($d['Layanan_Nama']) ?>"
                                            title="Lihat Detail Item Pesanan">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </button>
                                <?php else: echo "<span class='text-muted fst-italic'>Tidak terkait detail pesanan</span>"; endif; ?>
                            </td>
                            <td><?= $d['Tanggal_Profit'] ? htmlspecialchars(date('d M Y', strtotime($d['Tanggal_Profit']))) : "<span class='text-muted fst-italic'>N/A</span>" ?></td>
                            <td class="text-end fw-bold">Rp <?= number_format(preg_replace("/[^0-9.]/", "", $d['total_Profit']), 2, ',', '.') // Hati-hati jika total_Profit ada RP. ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_Profit'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_Profit'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data profit #<?= $d['Id_Profit'] ?>?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-coin fs-1"></i><h5 class="mt-2">Belum ada data profit.</h5></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php $query_params_pagination = ['search' => $search_term_raw, 'id_detail_pesanan' => $filter_id_detail_pesanan_raw, 'sort' => $sort_key]; $base_link_pagination = '?' . http_build_query(array_filter($query_params_pagination)) . '&page='; ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page - 1) ?>">Prev</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="detailPesananModal" tabindex="-1" aria-labelledby="detailPesananModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-success text-white"><h5 class="modal-title" id="detailPesananModalLabel"><i class="bi bi-card-list me-2"></i>Detail Item dari Pesanan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="detailPesananModalBody"> Memuat detail... </div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditDetailPesananLink" class="btn btn-warning" style="display:none;"><i class="bi bi-pencil-square me-1"></i> Edit Detail Pesanan Ini</a></div>
</div></div></div>

<?php if(isset($result) && is_object($result)) $result->close(); require_once '../../templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var detailModal = document.getElementById('detailPesananModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var modalBody = detailModal.querySelector('#detailPesananModalBody');
            var modalEditLink = detailModal.querySelector('#modalEditDetailPesananLink');
            modalEditLink.style.display = 'none';
            
            var idDetailPesanan = button.getAttribute('data-id_detail_pesanan');
            modalBody.innerHTML = `
                <p><strong>ID Detail Pesanan:</strong> #${idDetailPesanan || 'N/A'}</p>
                <p><strong>ID Pesanan Induk:</strong> #${button.getAttribute('data-id_pesanan') || 'N/A'}</p>
                <p><strong>Harga Item:</strong> ${button.getAttribute('data-harga_item') || 'N/A'}</p>
                <p><strong>Jumlah Item:</strong> ${button.getAttribute('data-jumlah_item') || 'N/A'}</p>
                <p><strong>Subtotal Item:</strong> ${button.getAttribute('data-subtotal_item') || 'N/A'}</p>
                <hr>
                <p class="text-muted small">Info Pesanan Induk:</p>
                <p><small><strong>Tanggal Pesan:</strong> ${button.getAttribute('data-pesanan_tanggal') || 'N/A'}</small></p>
                <p><small><strong>Oleh Pengguna:</strong> ${button.getAttribute('data-pengguna_nama') || 'N/A'}</small></p>
                <p><small><strong>Untuk Layanan:</strong> ${button.getAttribute('data-layanan_nama') || 'N/A'}</small></p>
            `;
            if (idDetailPesanan) {
                modalEditLink.href = '<?= BASE_URL ?>tabel/detail_pesanan/edit.php?id=' + idDetailPesanan; // Nanti jika ada edit detail pesanan
                modalEditLink.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Detail Item Ini';
                modalEditLink.style.display = 'inline-block'; 
            }
        });
    }
});
</script>