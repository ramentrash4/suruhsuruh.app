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

// Ambil dan escape nilai filter untuk keamanan query langsung
$search_term_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date_raw = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date_raw = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filter_status_pesanan_raw = isset($_GET['status_pesanan']) && in_array($_GET['status_pesanan'], ['Baru', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan']) ? $_GET['status_pesanan'] : '';

// Escape untuk query langsung
$search_term = $koneksi->real_escape_string($search_term_raw);
$start_date = $koneksi->real_escape_string($start_date_raw);
$end_date = $koneksi->real_escape_string($end_date_raw);
$filter_status_pesanan = $koneksi->real_escape_string($filter_status_pesanan_raw);


$sort_options = [
    'tgl_desc' => 'ps.Tanggal DESC, ps.Id_Pesanan DESC', 
    'tgl_asc' => 'ps.Tanggal ASC, ps.Id_Pesanan ASC',
    'status_asc' => 'ps.Status_Pesanan ASC, ps.Tanggal DESC',
    'status_desc' => 'ps.Status_Pesanan DESC, ps.Tanggal DESC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'tgl_desc';
$order_by_clause = $sort_options[$sort_key];

// --- MEMBANGUN QUERY ---
$base_sql_select_fields = "ps.*, 
                           pg.Nama_Depan AS Pengguna_Nama_Depan, pg.Nama_Tengah AS Pengguna_Nama_Tengah, pg.Nama_Belakang AS Pengguna_Nama_Belakang, pg.Email AS Pengguna_Email, pg.No_Telp AS Pengguna_No_Telp, pg.Alamat AS Pengguna_Alamat, pg.Tanggal_Lahir AS Pengguna_Tanggal_Lahir,
                           b.Jumlah AS Bayaran_Jumlah, b.Tanggal AS Bayaran_Tanggal,
                           l.Nama_Layanan AS Layanan_Nama, l.Jenis_Layanan AS Layanan_Jenis, l.Deskripsi_Umum AS Layanan_Deskripsi";
$base_sql_from_join = "FROM pesanan ps 
                       LEFT JOIN pengguna pg ON ps.Id_Pengguna = pg.Id_pengguna
                       LEFT JOIN bayaran b ON ps.Id_Pembayaran = b.Id_Pembayaran
                       LEFT JOIN layanan l ON ps.Id_Layanan = l.Id_Layanan";

// Klausa WHERE untuk Prepared Statement (Query COUNT)
$where_clauses_prepared = [];
$filter_params_prepared = []; 
$filter_types_prepared = '';  

// Klausa WHERE untuk Query Langsung (Query Data Utama)
$where_clauses_direct = [];

// Gunakan nilai RAW (belum di-escape) untuk prepared statement
if (!empty($search_term_raw)) {
    $like_term_prepared = "%" . $search_term_raw . "%";
    $where_clauses_prepared[] = "(pg.Nama_Depan LIKE ? OR pg.Nama_Belakang LIKE ? OR l.Nama_Layanan LIKE ? OR ps.Id_Pesanan LIKE ?)";
    array_push($filter_params_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared, $like_term_prepared);
    $filter_types_prepared .= 'ssss';
    
    // Gunakan nilai yang SUDAH di-escape untuk query langsung
    $like_term_direct = "%" . $search_term . "%"; 
    $where_clauses_direct[] = "(pg.Nama_Depan LIKE '$like_term_direct' OR pg.Nama_Belakang LIKE '$like_term_direct' OR l.Nama_Layanan LIKE '$like_term_direct' OR ps.Id_Pesanan LIKE '$like_term_direct')";
}
if (!empty($start_date_raw)) { 
    $where_clauses_prepared[] = "ps.Tanggal >= ?"; 
    $filter_params_prepared[] = $start_date_raw; 
    $filter_types_prepared .= 's'; 
    $where_clauses_direct[] = "ps.Tanggal >= '" . $start_date . "'";
}
if (!empty($end_date_raw)) { 
    $where_clauses_prepared[] = "ps.Tanggal <= ?"; 
    $filter_params_prepared[] = $end_date_raw; 
    $filter_types_prepared .= 's'; 
    $where_clauses_direct[] = "ps.Tanggal <= '" . $end_date . "'";
}
if (!empty($filter_status_pesanan_raw)) { 
    $where_clauses_prepared[] = "ps.Status_Pesanan = ?"; 
    $filter_params_prepared[] = $filter_status_pesanan_raw; 
    $filter_types_prepared .= 's'; 
    $where_clauses_direct[] = "ps.Status_Pesanan = '" . $filter_status_pesanan . "'";
}
$sql_where_prepared = !empty($where_clauses_prepared) ? " WHERE " . implode(" AND ", $where_clauses_prepared) : "";
$sql_where_direct = !empty($where_clauses_direct) ? " WHERE " . implode(" AND ", $where_clauses_direct) : "";

// --- Menghitung Total Data untuk Paginasi (Tetap menggunakan Prepared Statement) ---
$total_results = 0; 
$total_result_sql = "SELECT COUNT(ps.Id_Pesanan) AS total " . $base_sql_from_join . $sql_where_prepared;
$stmt_total = $koneksi->prepare($total_result_sql);

if ($stmt_total === false) { 
    echo "<div class='alert alert-danger'>Error preparing total count query: " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($total_result_sql) . "</div>";
} else {
    $bind_success = true;
    if (!empty($filter_types_prepared)) { 
        if (!$stmt_total->bind_param($filter_types_prepared, ...$filter_params_prepared)) { 
            echo "<div class='alert alert-danger'>Error binding params for total count: " . htmlspecialchars($stmt_total->error) . "</div>";
            $bind_success = false;
        }
    }

    if ($bind_success) {
        if (!$stmt_total->execute()) { 
            echo "<div class='alert alert-danger'>Error executing total count query: " . htmlspecialchars($stmt_total->error) . "</div>";
        } else {
            $total_result_obj = $stmt_total->get_result();
            if ($total_result_obj === false) { 
                echo "<div class='alert alert-danger'>Error getting result for total count: " . htmlspecialchars($stmt_total->error) . "</div>";
            } else {
                $total_row_data = $total_result_obj->fetch_assoc();
                $total_results = $total_row_data ? (int)$total_row_data['total'] : 0;
            }
        }
    }
    $stmt_total->close();
}
$total_pages = $total_results > 0 ? (int)ceil($total_results / $results_per_page) : 0; 
if ($current_page > $total_pages && $total_pages > 0) { $current_page = $total_pages; }
$offset = ($current_page - 1) * $results_per_page;

// --- Mengambil Data untuk Halaman Saat Ini (MENGGUNAKAN QUERY LANGSUNG DENGAN ESCAPING) ---
$results_per_page_int = (int)$results_per_page;
$offset_int = (int)$offset;
$data_sql_direct_final = "SELECT " . $base_sql_select_fields . " " . $base_sql_from_join . $sql_where_direct . " ORDER BY $order_by_clause LIMIT $results_per_page_int OFFSET $offset_int";

$result = $koneksi->query($data_sql_direct_final);

if ($result === false) {
    echo "<div class='alert alert-danger'>Query data utama GAGAL (Direct Query): " . htmlspecialchars($koneksi->error) . " <br>SQL: " . htmlspecialchars($data_sql_direct_final) . "</div>";
    $result = null; 
}

/* // BLOK DEBUGGING BISA DIAKTIFKAN KEMBALI JIKA MASIH ADA MASALAH
if (!($result === false)) {
    echo "<div class='alert alert-info mt-3 p-3' style='font-size: 0.9rem; text-align:left; word-break: break-all;'>";
    echo "<strong>DEBUGGING INFORMATION (PESANAN INDEX):</strong><hr>";
    echo "<strong>URL Parameters:</strong><br><pre>"; print_r($_GET); echo "</pre>";
    echo "<strong>Escaped Search Term:</strong> " . htmlspecialchars($search_term) . "<br>";
    echo "<strong>Escaped Start Date:</strong> " . htmlspecialchars($start_date) . "<br>";
    echo "<strong>Escaped End Date:</strong> " . htmlspecialchars($end_date) . "<br>";
    echo "<strong>Escaped Filter Status Pesanan:</strong> " . htmlspecialchars($filter_status_pesanan) . "<br>";
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

$page_title = "Manajemen Pesanan";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-receipt-cutoff me-3" style="font-size: 2.5rem;"></i>Manajemen Pesanan</h1><p class="text-muted">Kelola semua pesanan layanan dari pengguna.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_orders.svg" alt="Ilustrasi Pesanan" style="max-height: 120px;"></div>
</div>

<?php if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); } ?>
<?php if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); } ?>

<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle">
        <form action="" method="GET" class="row g-3 align-items-center">
            <div class="col-md-auto"><a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Buat Pesanan</a></div>
            <div class="col-md-2"><input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start_date_raw) // Gunakan nilai raw untuk value input ?>" title="Tgl Pesan Mulai"></div>
            <div class="col-md-2"><input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end_date_raw) // Gunakan nilai raw untuk value input ?>" title="Tgl Pesan Akhir"></div>
            <div class="col-md-2">
                <select name="status_pesanan" class="form-select form-select-sm" onchange="this.form.submit()" title="Filter Status Pesanan">
                    <option value="">Semua Status</option>
                    <option value="Baru" <?= ($filter_status_pesanan_raw == 'Baru') ? 'selected' : '' ?>>Baru</option>
                    <option value="Diproses" <?= ($filter_status_pesanan_raw == 'Diproses') ? 'selected' : '' ?>>Diproses</option>
                    <option value="Dikirim" <?= ($filter_status_pesanan_raw == 'Dikirim') ? 'selected' : '' ?>>Dikirim</option>
                    <option value="Selesai" <?= ($filter_status_pesanan_raw == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                    <option value="Dibatalkan" <?= ($filter_status_pesanan_raw == 'Dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()" title="Urutkan">
                    <option value="tgl_desc" <?= ($sort_key == 'tgl_desc') ? 'selected' : '' ?>>Tgl (Terbaru)</option>
                    <option value="tgl_asc" <?= ($sort_key == 'tgl_asc') ? 'selected' : '' ?>>Tgl (Terlama)</option>
                    <option value="status_asc" <?= ($sort_key == 'status_asc') ? 'selected' : '' ?>>Status (A-Z)</option>
                    <option value="status_desc" <?= ($sort_key == 'status_desc') ? 'selected' : '' ?>>Status (Z-A)</option>
                </select>
            </div>
            <div class="col-md-3"><div class="input-group"><input class="form-control form-control-sm" type="search" placeholder="Cari ID Pesanan, Pengguna, Layanan..." name="search" value="<?= htmlspecialchars($search_term_raw) // Gunakan nilai raw untuk value input ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button></div></div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>ID</th><th>Pengguna</th><th>Layanan</th><th>Tgl Pesan</th><th>Status</th><th>Bayaran</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($d = $result->fetch_assoc()): 
                            $status_class = '';
                            switch ($d['Status_Pesanan']) {
                                case 'Baru': $status_class = 'text-primary'; break;
                                case 'Diproses': $status_class = 'text-info'; break;
                                case 'Dikirim': $status_class = 'text-warning'; break;
                                case 'Selesai': $status_class = 'text-success'; break;
                                case 'Dibatalkan': $status_class = 'text-danger'; break;
                            }
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis">#<?= $d['Id_Pesanan'] ?></span></td>
                            <td>
                                <?php if ($d['Id_Pengguna']): ?>
                                    <?= htmlspecialchars(trim($d['Pengguna_Nama_Depan'].' '.$d['Pengguna_Nama_Belakang'])) ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 view-details" data-bs-toggle="modal" data-bs-target="#detailModal" data-type="pengguna" 
                                            data-id="<?= $d['Id_Pengguna'] ?>" 
                                            data-nama_lengkap="<?= htmlspecialchars(trim($d['Pengguna_Nama_Depan'].' '.$d['Pengguna_Nama_Tengah'].' '.$d['Pengguna_Nama_Belakang'])) ?>" 
                                            data-email="<?= htmlspecialchars($d['Pengguna_Email']) ?>" 
                                            data-telp="<?= htmlspecialchars($d['Pengguna_No_Telp']) ?>" 
                                            data-alamat="<?= htmlspecialchars($d['Pengguna_Alamat']) ?>" 
                                            data-tgl_lahir="<?= htmlspecialchars(isset($d['Pengguna_Tanggal_Lahir']) ? date('d M Y', strtotime($d['Pengguna_Tanggal_Lahir'])) : 'N/A') ?>"><i class="bi bi-info-circle"></i></button>
                                <?php else: echo "<span class='text-muted fst-italic'>N/A</span>"; endif; ?>
                            </td>
                            <td>
                                <?php if ($d['Id_Layanan']): ?>
                                    <?= htmlspecialchars($d['Layanan_Nama']) ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 view-details" data-bs-toggle="modal" data-bs-target="#detailModal" data-type="layanan" 
                                            data-id="<?= $d['Id_Layanan'] ?>" 
                                            data-nama_layanan="<?= htmlspecialchars($d['Layanan_Nama'])?>" 
                                            data-jenis_layanan="<?= htmlspecialchars($d['Layanan_Jenis'])?>" 
                                            data-deskripsi_layanan="<?= htmlspecialchars($d['Layanan_Deskripsi'] ?: 'Tidak ada deskripsi.') ?>"><i class="bi bi-info-circle"></i></button>
                                <?php else: echo "<span class='text-muted fst-italic'>N/A</span>"; endif; ?>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($d['Tanggal']))) ?></td>
                            <td><span class="fw-bold <?= $status_class ?>"><?= htmlspecialchars($d['Status_Pesanan']) ?></span></td>
                            <td>
                                <?php if ($d['Id_Pembayaran'] && isset($d['Bayaran_Jumlah'])): ?>
                                    Rp <?= number_format(preg_replace("/[^0-9]/", "", $d['Bayaran_Jumlah']), 0, ',', '.') ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 view-details" data-bs-toggle="modal" data-bs-target="#detailModal" data-type="bayaran" 
                                            data-id="<?= $d['Id_Pembayaran'] ?>" 
                                            data-jumlah_bayaran="Rp <?= number_format(preg_replace("/[^0-9]/", "", $d['Bayaran_Jumlah']), 0, ',', '.') ?>" 
                                            data-tanggal_bayaran="<?= htmlspecialchars(isset($d['Bayaran_Tanggal']) ? date('d M Y', strtotime($d['Bayaran_Tanggal'])) : 'N/A') ?>"><i class="bi bi-info-circle"></i></button>
                                <?php elseif ($d['Id_Pembayaran']): ?>
                                    <span class='text-muted fst-italic'>Detail bayaran tidak lengkap</span>
                                <?php else: echo "<span class='text-muted fst-italic'>Belum Bayar</span>"; endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $d['Id_Pesanan'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus.php?id=<?= $d['Id_Pesanan'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pesanan #<?= $d['Id_Pesanan'] ?>?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-cart-x fs-1"></i><h5 class="mt-2">Belum ada data pesanan.</h5><p>Coba ubah filter atau tambahkan data baru.</p></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle"><nav><ul class="pagination justify-content-center mb-0">
        <?php 
        // Untuk link paginasi, kita gunakan nilai RAW dari GET agar tidak ada double escaping
        $query_params_pagination = [
            'search' => $search_term_raw, 
            'start_date' => $start_date_raw, 
            'end_date' => $end_date_raw, 
            'status_pesanan' => $filter_status_pesanan_raw, 
            'sort' => $sort_key
        ]; 
        $base_link_pagination = '?' . http_build_query(array_filter($query_params_pagination)) . '&page='; 
        ?>
        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page - 1) ?>">Prev</a></li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . $i ?>"><?= $i ?></a></li><?php endfor; ?>
        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link_pagination . ($current_page + 1) ?>">Next</a></li>
    </ul></nav></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title" id="detailModalLabel">Detail Informasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body" id="detailModalBody"> Memuat detail... </div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><a href="#" id="modalEditLink" class="btn btn-warning" style="display:none;"><i class="bi bi-pencil-square me-1"></i> Edit Item Ini</a></div>
</div></div></div>

<?php if(isset($result) && is_object($result)) $result->close(); require_once '../../templates/footer.php'; ?>
<script>
// (JavaScript untuk Modal Detail Universal tetap sama)
document.addEventListener('DOMContentLoaded', function () {
    var detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var type = button.getAttribute('data-type');
            var modalTitle = detailModal.querySelector('.modal-title');
            var modalBody = detailModal.querySelector('.modal-body');
            var modalEditLink = detailModal.querySelector('#modalEditLink');
            modalBody.innerHTML = 'Memuat...'; 
            modalEditLink.style.display = 'none'; 

            var itemId = button.getAttribute('data-id');

            if (type === 'pengguna') {
                modalTitle.innerHTML = '<i class="bi bi-person-lines-fill me-2"></i>Detail Pengguna';
                modalBody.innerHTML = `<p><strong>ID Pengguna:</strong> ${itemId || 'N/A'}</p>
                                       <p><strong>Nama:</strong> ${button.getAttribute('data-nama_lengkap') || 'N/A'}</p>
                                       <p><strong>Email:</strong> ${button.getAttribute('data-email') || 'N/A'}</p>
                                       <p><strong>No. Telp:</strong> ${button.getAttribute('data-telp') || 'N/A'}</p>
                                       <p><strong>Alamat:</strong> ${button.getAttribute('data-alamat') || 'N/A'}</p>
                                       <p><strong>Tgl Lahir:</strong> ${button.getAttribute('data-tgl_lahir') || 'N/A'}</p>`;
                if (itemId) { modalEditLink.href = '<?= BASE_URL ?>tabel/pengguna/edit.php?id=' + itemId; modalEditLink.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Pengguna'; modalEditLink.style.display = 'inline-block'; }
            } else if (type === 'layanan') {
                modalTitle.innerHTML = '<i class="bi bi-tools me-2"></i>Detail Layanan';
                modalBody.innerHTML = `<p><strong>ID Layanan:</strong> ${itemId || 'N/A'}</p>
                                       <p><strong>Nama:</strong> ${button.getAttribute('data-nama_layanan') || 'N/A'}</p>
                                       <p><strong>Jenis:</strong> ${button.getAttribute('data-jenis_layanan') || 'N/A'}</p>
                                       <p><strong>Deskripsi:</strong> ${button.getAttribute('data-deskripsi_layanan') || 'N/A'}</p>`;
                if (itemId) { modalEditLink.href = '<?= BASE_URL ?>tabel/layanan/edit.php?id=' + itemId; modalEditLink.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Layanan'; modalEditLink.style.display = 'inline-block'; }
            } else if (type === 'bayaran') {
                modalTitle.innerHTML = '<i class="bi bi-wallet2 me-2"></i>Detail Bayaran';
                modalBody.innerHTML = `<p><strong>ID Bayaran:</strong> ${itemId || 'N/A'}</p>
                                       <p><strong>Jumlah:</strong> ${button.getAttribute('data-jumlah_bayaran') || 'N/A'}</p>
                                       <p><strong>Tgl Bayar:</strong> ${button.getAttribute('data-tanggal_bayaran') || 'N/A'}</p>`;
                if (itemId) { modalEditLink.href = '<?= BASE_URL ?>tabel/bayaran/edit.php?id=' + itemId; modalEditLink.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Bayaran'; modalEditLink.style.display = 'inline-block'; }
            }
        });
    }
});
</script>