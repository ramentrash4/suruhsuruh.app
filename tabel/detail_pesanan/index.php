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

// Ambil dan escape nilai filter RAW untuk query langsung dan prepared statement
$search_term_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_id_pesanan_raw = isset($_GET['id_pesanan']) && is_numeric($_GET['id_pesanan']) ? (int)$_GET['id_pesanan'] : '';

// Escape untuk query langsung
$search_term_escaped = $koneksi->real_escape_string($search_term_raw);
// $filter_id_pesanan_raw sudah integer, tidak perlu escape khusus untuk query direct jika digunakan sbg angka

$sort_options = [
    'id_desc' => 'dp.Id_DetailPesanan DESC', 
    'id_asc' => 'dp.Id_DetailPesanan ASC',
    'pesanan_asc' => 'dp.Id_Pesanan ASC, dp.Id_DetailPesanan ASC',
    'pesanan_desc' => 'dp.Id_Pesanan DESC, dp.Id_DetailPesanan DESC',
    'harga_asc' => 'dp.Harga ASC, dp.Id_DetailPesanan ASC',
    'harga_desc' => 'dp.Harga DESC, dp.Id_DetailPesanan ASC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "dp.*, 
                           ps.Id_Pesanan AS Pesanan_Id_Pesanan_FK, ps.Tanggal AS Pesanan_Tanggal, ps.Status_Pesanan AS Pesanan_Status, /* Alias untuk Id_Pesanan dari tabel pesanan */
                           pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Belakang AS Pengguna_Nama_Belakang, pg.Id_pengguna AS Pesanan_Id_Pengguna, /* Ambil Id_pengguna untuk modal */
                           l.Nama_Layanan AS Layanan_Nama, l.Id_Layanan AS Pesanan_Id_Layanan, /* Ambil Id_Layanan untuk modal */
                           b.Id_Pembayaran AS Pesanan_Id_Pembayaran /* Ambil Id_Pembayaran untuk modal */";
$base_sql_from_join = "FROM detail_pesanan dp
                       JOIN pesanan ps ON dp.Id_Pesanan = ps.Id_Pesanan
                       LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
                       LEFT JOIN layanan l ON ps.Id_Layanan = l.Id_Layanan
                       LEFT JOIN bayaran b ON ps.Id_Pembayaran = b.Id_Pembayaran"; // Tambah join ke bayaran untuk info modal pesanan

// Klausa WHERE untuk Prepared Statement (Query COUNT)
$where_clauses_prepared = [];
$filter_params_prepared = []; 
$filter_types_prepared = '';  

// Klausa WHERE untuk Query Langsung (Query Data Utama)
$where_clauses_direct = [];

if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(l.Nama_Layanan LIKE ? OR pg.Nama_Depan LIKE ? OR pg.Nama_Belakang LIKE ? OR dp.Id_Pesanan LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ssss';
    
    $like_term_direct = "%" . $search_term_escaped . "%";
    $where_clauses_direct[] = "(l.Nama_Layanan LIKE '$like_term_direct' OR pg.Nama_Depan LIKE '$like_term_direct' OR pg.Nama_Belakang LIKE '$like_term_direct' OR dp.Id_Pesanan LIKE '$like_term_direct')";
}
if (!empty($filter_id_pesanan_raw)) {
    $where_clauses_prepared[] = "dp.Id_Pesanan = ?";
    $filter_params_prepared[] = $filter_id_pesanan_raw;
    $filter_types_prepared .= 'i';
    $where_clauses_direct[] = "dp.Id_Pesanan = " . $filter_id_pesanan_raw; // Sudah integer
}
$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data untuk Paginasi (Tetap menggunakan Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(dp.Id_DetailPesanan) AS total " . $base_sql_from_join . $sql_where_prepared;
$stmt_total = $koneksi->prepare($total_result_sql);
if ($stmt_total === false) { echo "<div class='alert alert-danger'>Error preparing total count query: " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($total_result_sql) . "</div>"; }
else {
    if (!empty($filter_types_prepared)) { if (!$stmt_total->bind_param($filter_types_prepared, ...$filter_params_prepared)) { echo "<div class='alert alert-danger'>Error binding params for total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; /* Tandai error */}}
    if ($total_results === 0 && !$stmt_total->execute()) { echo "<div class='alert alert-danger'>Error executing total count query: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1; }
    if ($total_results === 0) {
        $total_result_obj = $stmt_total->get_result();
        if ($total_result_obj === false) { echo "<div class='alert alert-danger'>Error getting result for total count: " . htmlspecialchars($stmt_total->error) . "</div>"; $total_results = -1;}
        else { $total_row_data = $total_result_obj->fetch_assoc(); $total_results = $total_row_data ? (int)$total_row_data['total'] : 0; }
    }
    $stmt_total->close();
}
if($total_results === -1) $total_results = 0; // Reset jika ada error sebelumnya
$total_pages = $total_results > 0 ? (int)ceil($total_results / $results_per_page) : 0; 
if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
$offset = ($current_page - 1) * $results_per_page;

// --- Mengambil Data untuk Halaman Saat Ini (MENGGUNAKAN QUERY LANGSUNG) ---
$results_per_page_int = (int)$results_per_page;
$offset_int = (int)$offset;
$data_sql_direct_final = "SELECT " . $base_sql_select_fields . " " . $base_sql_from_join . $sql_where_direct . " ORDER BY $order_by_clause LIMIT $results_per_page_int OFFSET $offset_int";

$result = $koneksi->query($data_sql_direct_final);

if ($result === false) {
    echo "<div class='alert alert-danger'>Query data utama GAGAL (Direct Query): " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($data_sql_direct_final) . "</div>";
    $result = null; 
}

/* // --- BLOK DEBUGGING BISA DIAKTIFKAN KEMBALI JIKA MASIH ADA MASALAH ---
if ($result !== null) { // Hanya tampilkan debug jika query tidak gagal total
    echo "<div class='alert alert-info mt-3 p-3' style='font-size: 0.9rem; text-align:left; word-break: break-all;'>";
    echo "<strong>DEBUGGING INFORMATION (DETAIL PESANAN INDEX):</strong><hr>";
    echo "<strong>URL Parameters:</strong><br><pre>"; print_r($_GET); echo "</pre>";
    echo "<strong>Total Results Calculated:</strong> " . $total_results . "<br>";
    echo "<hr><strong>Data Fetch Query (Direct):</strong><br>" . htmlspecialchars($data_sql_direct_final) . "<br>";
    echo "<strong>Number of Rows Fetched:</strong> " . ($result ? $result->num_rows : 'Query Gagal atau \$result bukan objek') . "<br>";
    if ($result && $result->num_rows > 0) {
        $temp_row = $result->fetch_assoc();
        echo "<br><strong>Sample First Row Data:</strong><br><pre>"; print_r($temp_row); echo "</pre>";
        if ($temp_row) $result->data_seek(0); 
    }
    echo "</div>";
}
*/

$page_title = "Manajemen Detail Pesanan";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-list-ol me-3" style="font-size: 2.5rem;"></i>Manajemen Detail Pesanan</h1><p class="text-muted">Lihat dan kelola item-item dalam setiap pesanan.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_order_details.svg" alt="Ilustrasi Detail Pesanan" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Detail</a></div>
            <div class="col-md-3">
                <input type="number" name="id_pesanan" class="form-control form-control-sm" placeholder="Filter ID Pesanan..." value="<?= htmlspecialchars($filter_id_pesanan_raw) ?>" title="Filter by ID Pesanan">
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : '' ?>>ID Detail (Terbaru)</option>
                    <option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : '' ?>>ID Detail (Terlama)</option>
                    <option value="pesanan_asc" <?= ($sort_key == 'pesanan_asc') ? 'selected' : '' ?>>ID Pesanan (A-Z)</option>
                    <option value="pesanan_desc" <?= ($sort_key == 'pesanan_desc') ? 'selected' : '' ?>>ID Pesanan (Z-A)</option>
                    <option value="harga_desc" <?= ($sort_key == 'harga_desc') ? 'selected' : '' ?>>Harga (Tertinggi)</option>
                    <option value="harga_asc" <?= ($sort_key == 'harga_asc') ? 'selected' : '' ?>>Harga (Terendah)</option>
                </select>
            </div>
            <div class="col-md-4"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari Layanan, Pengguna, ID Pesanan..." name="search" value="<?= htmlspecialchars($search_term_raw) ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID Detail</th><th>Info Pesanan Induk</th><th>Item Layanan (dari Pesanan Induk)</th><th>Harga Item</th><th>Jumlah Item</th><th>Subtotal</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-info bg-opacity-25 text-info-emphasis">#<?= $d['Id_DetailPesanan'] ?></span></td>
                            <td>
                                <strong>ID Pesanan:</strong> #<?= htmlspecialchars($d['Id_Pesanan']) ?><br>
                                <small class="text-muted">Tgl: <?= htmlspecialchars(date('d M Y', strtotime($d['Pesanan_Tanggal']))) ?> | Status: <?= htmlspecialchars($d['Pesanan_Status']) ?></small><br>
                                <small>Oleh: <?= htmlspecialchars(trim($d['Pengguna_Nama_Depan'].' '.$d['Pengguna_Nama_Belakang'])) ?: "<span class='text-muted'>N/A</span>" ?></small>
                                <button type="button" class="btn btn-link btn-sm p-0 view-pesanan-details" 
                                        data-bs-toggle="modal" data-bs-target="#pesananDetailModal"
                                        data-id_pesanan="<?= $d['Id_Pesanan'] ?>"
                                        data-tanggal_pesanan="<?= htmlspecialchars(date('d M Y', strtotime($d['Pesanan_Tanggal']))) ?>"
                                        data-status_pesanan="<?= htmlspecialchars($d['Pesanan_Status']) ?>"
                                        data-nama_pengguna="<?= htmlspecialchars(trim($d['Pengguna_Nama_Depan'].' '.$d['Pengguna_Nama_Belakang'])) ?>"
                                        data-nama_layanan="<?= htmlspecialchars($d['Layanan_Nama']) ?>"
                                        data-id_pengguna="<?= htmlspecialchars($d['Pesanan_Id_Pengguna'])?>"
                                        data-id_layanan="<?= htmlspecialchars($d['Pesanan_Id_Layanan'])?>"
                                        data-id_pembayaran="<?= htmlspecialchars($d['Pesanan_Id_Pembayaran'])?>"
                                        title="Lihat Detail Pesanan Induk">
                                    <i class="bi bi-info-circle-fill"></i>
                                </button>
                            </td>
                            <td><?= htmlspecialchars($d['Layanan_Nama'] ?: "<span class='text-muted'>Layanan tidak terkait</span>") ?></td>
                            <td class="text-end">Rp <?= number_format($d['Harga'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= htmlspecialchars($d['Jumlah']) ?></td>
                            <td class="text-end fw-bold">Rp <?= number_format($d['Harga'] * $d['Jumlah'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_DetailPesanan'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_DetailPesanan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus item detail pesanan ini?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-list-ul fs-1"></i><h5 class="mt-2">Belum ada data detail pesanan.</h5><p>Coba ubah filter atau tambahkan data baru.</p></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php $query_params_pagination = ['search' => $search_term_raw, 'id_pesanan' => $filter_id_pesanan_raw, 'sort' => $sort_key]; $base_link_pagination = '?' . http_build_query(array_filter($query_params_pagination)) . '&page='; ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page - 1) ?>">Prev</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="pesananDetailModal" tabindex="-1" aria-labelledby="pesananDetailModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header bg-info text-white"><h5 class="modal-title" id="pesananDetailModalLabel"><i class="bi bi-receipt me-2"></i>Detail Pesanan Induk</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="pesananDetailModalBody"> Memuat detail... </div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditPesananLink" class="btn btn-warning" style="display:none;"><i class="bi bi-pencil-square me-1"></i> Edit Pesanan Induk</a></div>
</div></div></div>

<?php if(isset($result) && is_object($result)) $result->close(); require_once '../../templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pesananDetailModal = document.getElementById('pesananDetailModal');
    if (pesananDetailModal) {
        pesananDetailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var modalBody = pesananDetailModal.querySelector('#pesananDetailModalBody');
            var modalEditLink = pesananDetailModal.querySelector('#modalEditPesananLink');
            
            var idPesanan = button.getAttribute('data-id_pesanan');
            modalBody.innerHTML = `
                <p><strong>ID Pesanan:</strong> #${idPesanan || 'N/A'}</p>
                <p><strong>Tanggal Pesan:</strong> ${button.getAttribute('data-tanggal_pesanan') || 'N/A'}</p>
                <p><strong>Status Pesanan:</strong> ${button.getAttribute('data-status_pesanan') || 'N/A'}</p>
                <hr>
                <p><strong>Dipesan oleh:</strong> ${button.getAttribute('data-nama_pengguna') || 'N/A'} (ID Pengguna: ${button.getAttribute('data-id_pengguna') || 'N/A'})</p>
                <p><strong>Layanan yang dipesan:</strong> ${button.getAttribute('data-nama_layanan') || 'N/A'} (ID Layanan: ${button.getAttribute('data-id_layanan') || 'N/A'})</p>
                <p><strong>ID Pembayaran Terkait:</strong> ${button.getAttribute('data-id_pembayaran') ? '#' + button.getAttribute('data-id_pembayaran') : 'Belum ada'} </p>
            `;
            if (idPesanan) {
                modalEditLink.href = '<?= BASE_URL ?>tabel/pesanan/edit.php?id=' + idPesanan;
                modalEditLink.style.display = 'inline-block';
            } else {
                modalEditLink.style.display = 'none';
            }
        });
    }
});
</script>