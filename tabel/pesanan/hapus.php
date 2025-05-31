<?php
require_once __DIR__ . '/../../config.php';

$id_pesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pesanan <= 0) {
    $_SESSION['error_message'] = "ID Pesanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

// Check for related records in `detail_pesanan` (FK constraint: detail_pesanan_ibfk_1)
$check_detail_query = "SELECT COUNT(*) as count FROM detail_pesanan WHERE Id_Pesanan = $id_pesanan";
$detail_result = mysqli_query($koneksi, $check_detail_query);
$detail_count = mysqli_fetch_assoc($detail_result)['count'];

if ($detail_count > 0) {
    $_SESSION['error_message'] = "Tidak dapat menghapus pesanan ini karena masih memiliki data detail pesanan terkait ($detail_count item). Hapus detail pesanan terlebih dahulu.";
    header("Location: index.php");
    exit;
}


$query_delete = "DELETE FROM pesanan WHERE Id_Pesanan = $id_pesanan";

if (mysqli_query($koneksi, $query_delete)) {
    if (mysqli_affected_rows($koneksi) > 0) {
        $_SESSION['success_message'] = "Data pesanan berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Data pesanan tidak ditemukan atau sudah dihapus.";
    }
} else {
    $_SESSION['error_message'] = "Gagal menghapus data pesanan: " . mysqli_error($koneksi);
}

header("Location: index.php");
exit;
?>