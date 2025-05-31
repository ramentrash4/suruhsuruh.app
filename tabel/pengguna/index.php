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

// ... (SEMUA LOGIKA PHP UNTUK PAGINASI, FILTER, SORTING, SEARCH DI ATAS TETAP SAMA SEPERTI JAWABAN SEBELUMNYA) ...
// Saya sertakan lagi di sini agar lengkap
$results_per_page = 6;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) && in_array($_GET['status'], ['aktif', 'nonaktif']) ? $_GET['status'] : '';
$sort_options = ['id_desc' => 'Id_pengguna DESC', 'id_asc' => 'Id_pengguna ASC', 'nama_asc' => 'Nama_Depan ASC, Nama_Belakang ASC', 'nama_desc' => 'Nama_Depan DESC, Nama_Belakang DESC'];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'id_desc';
$order_by = $sort_options[$sort_key];
$base_sql = "FROM pengguna";
$where_clauses = [];
$params = [];
$types = '';
if (!empty($search_term)) {
    $where_clauses[] = "(Nama_Depan LIKE ? OR Nama_Belakang LIKE ? OR Email LIKE ?)";
    $like_term = "%" . $search_term . "%";
    array_push($params, $like_term, $like_term, $like_term);
    $types .= 'sss';
}
if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}
$total_result_sql = "SELECT COUNT(Id_pengguna) AS total " . $base_sql . $sql_where;
$stmt_total = $koneksi->prepare($total_result_sql);
if (!empty($params)) $stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$total_results = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $results_per_page);
$stmt_total->close();
$offset = ($current_page - 1) * $results_per_page;
$data_sql = "SELECT * " . $base_sql . $sql_where . " ORDER BY $order_by LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$types .= 'ii';
$stmt_data = $koneksi->prepare($data_sql);
$stmt_data->bind_param($types, ...$params);
$stmt_data->execute();
$result = $stmt_data->get_result();

$page_title = "Manajemen Pengguna";
require_once '../../templates/header.php';
?>

<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7"><h1 class="h2 fw-bolder text-primary d-flex align-items-center"><i class="bi bi-people-fill me-3" style="font-size: 2.5rem;"></i>Manajemen Pengguna</h1><p class="text-muted">Kelola, tambahkan, atau edit data pengguna aplikasi SuruhSuruh.com.</p></div>
    <div class="col-md-5 text-md-end"><img src="<?= BASE_URL ?>assets/img/illustration_users.png" alt="Ilustrasi Pengguna" style="max-height: 120px;"></div>
</div>
<?php
if (isset($_SESSION['success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['success_message']); }
if (isset($_SESSION['error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; unset($_SESSION['error_message']); }
?>
<div class="card shadow-sm border-light-subtle">
    <div class="card-header bg-light-subtle d-flex flex-wrap justify-content-between align-items-center gap-3">
        <a href="tambah.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Pengguna</a>
        <form action="" method="GET" class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end">
            <select name="status" class="form-select" style="width: auto;" onchange="this.form.submit()"><option value="">Semua Status</option><option value="aktif" <?= ($filter_status == 'aktif') ? 'selected' : ''; ?>>Aktif</option><option value="nonaktif" <?= ($filter_status == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option></select>
            <select name="sort" class="form-select" style="width: auto;" onchange="this.form.submit()"><option value="id_desc" <?= ($sort_key == 'id_desc') ? 'selected' : ''; ?>>Terbaru</option><option value="id_asc" <?= ($sort_key == 'id_asc') ? 'selected' : ''; ?>>Terlama</option><option value="nama_asc" <?= ($sort_key == 'nama_asc') ? 'selected' : ''; ?>>Nama (A-Z)</option><option value="nama_desc" <?= ($sort_key == 'nama_desc') ? 'selected' : ''; ?>>Nama (Z-A)</option></select>
            <div class="input-group" style="width: 250px;"><input class="form-control" type="search" placeholder="Cari..." name="search" value="<?= htmlspecialchars($search_term) ?>"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button></div>
        </form>
    </div>
    <div class="card-body">
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($d = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100 shadow-hover border-light-subtle text-center user-card position-relative">
                        <div class="card-body">
                            <span class="badge bg-<?= $d['status'] == 'aktif' ? 'success' : 'danger' ?> bg-opacity-25 text-<?= $d['status'] == 'aktif' ? 'success' : 'danger' ?>-emphasis rounded-pill position-absolute top-0 start-0 mt-2 ms-2"><?= ucfirst($d['status']) ?></span>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary-emphasis rounded-pill position-absolute top-0 end-0 mt-2 me-2" style="font-size: 0.75rem;">ID: <?= $d['Id_pengguna']; ?></span>
                            <div class="avatar-circle-lg bg-primary text-white mx-auto mb-3 mt-5"><span><?= strtoupper(substr($d['Nama_Depan'], 0, 1) . substr($d['Nama_Belakang'], 0, 1)); ?></span></div>
                            <h5 class="card-title fw-bold"><?= htmlspecialchars(trim($d['Nama_Depan'] . ' ' . $d['Nama_Tengah'] . ' ' . $d['Nama_Belakang'])); ?></h5>
                            <p class="card-text text-muted mb-1"><i class="bi bi-envelope-fill me-1 text-primary"></i> <?= htmlspecialchars($d['Email']); ?></p>
                            <p class="card-text text-muted"><i class="bi bi-telephone-fill me-1 text-success"></i> <?= htmlspecialchars($d['No_Telp']); ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pb-3">
                            <div class="btn-group" role="group">
                                <a href="edit.php?id=<?= $d['Id_pengguna'] ?>" class="btn btn-warning" title="Edit Pengguna"><i class="bi bi-pencil-square me-1"></i> Edit Lengkap</a>
                                <a href="hapus.php?id=<?= $d['Id_pengguna'] ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus pengguna: <?= htmlspecialchars($d['Nama_Depan']) ?>?')" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5"><div class="text-muted"><i class="bi bi-search-heart fs-1"></i><h5 class="mt-2">Data tidak ditemukan.</h5><p>Coba ubah filter atau kata kunci pencarian Anda.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-light-subtle">
        <nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">
                <?php $query_params = ['search' => $search_term, 'status' => $filter_status, 'sort' => $sort_key]; $base_link = '?' . http_build_query(array_filter($query_params)) . '&page='; ?>
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link . ($current_page - 1) ?>">Previous</a></li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="<?= $base_link . $i ?>"><?= $i ?></a></li><?php endfor; ?>
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="<?= $base_link . ($current_page + 1) ?>">Next</a></li>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
<style>.user-card{transition:transform .2s ease-in-out,box-shadow .2s ease-in-out}.user-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.1)}.avatar-circle-lg{width:70px;height:70px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.8rem;flex-shrink:0;border:3px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,.1)}</style>
<?php
$stmt_data->close();
require_once '../../templates/footer.php';
?>