<?php
session_start();
require '../../config/database.php';

$id_layanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_layanan <= 0) {
    $_SESSION['error_message'] = "ID Layanan tidak valid untuk penghapusan.";
    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // 1. Hapus referensi dari tabel 'terikat'
    // Ini akan terhapus otomatis jika ada ON DELETE CASCADE dari layanan ke terikat,
    // namun untuk lebih eksplisit dan jika tidak ada CASCADE:
    $query_delete_terikat = "DELETE FROM terikat WHERE Id_Layanan = $id_layanan";
    if (!mysqli_query($koneksi, $query_delete_terikat)) {
        // Tidak perlu throw error jika tidak ada, mungkin memang tidak terikat
    }

    // 2. Periksa FK di tabel 'pesanan' (pesanan_ibfk_3)
    $check_pesanan_query = "SELECT COUNT(*) as count FROM pesanan WHERE Id_Layanan = $id_layanan";
    $pesanan_result = mysqli_query($koneksi, $check_pesanan_query);
    $pesanan_count = ($pesanan_result) ? mysqli_fetch_assoc($pesanan_result)['count'] : 0;

    if ($pesanan_count > 0) {
        throw new Exception("Tidak dapat menghapus layanan ini karena masih direferensikan oleh $pesanan_count data pesanan. Atur ulang referensi atau hapus data pesanan terkait terlebih dahulu.");
    }

    // 3. Hapus data dari tabel spesialisasi (anak)
    // Ini akan terhapus otomatis jika ON DELETE CASCADE dari layanan_anak ke layanan induk sudah benar.
    // Jika tidak, lakukan secara manual:
    // $jenis_layanan_query = mysqli_query($koneksi, "SELECT Jenis_Layanan FROM layanan WHERE Id_Layanan = $id_layanan");
    // if ($jenis_layanan_query && mysqli_num_rows($jenis_layanan_query) > 0) {
    //     $jenis = mysqli_fetch_assoc($jenis_layanan_query)['Jenis_Layanan'];
    //     $table_spec_name = '';
    //     if ($jenis == 'Makanan') $table_spec_name = 'layanan_makanan';
    //     elseif ($jenis == 'Kesehatan') $table_spec_name = 'layanan_kesehatan';
    //     elseif ($jenis == 'Layanan Rumah') $table_spec_name = 'layanan_pelayanan_rumah';
        
    //     if (!empty($table_spec_name)) {
    //         $query_delete_spec = "DELETE FROM $table_spec_name WHERE Id_Layanan = $id_layanan";
    //         if (!mysqli_query($koneksi, $query_delete_spec)) {
    //             // Abaikan jika tidak ada, karena bisa jadi layanan 'Lainnya' atau sudah terhapus
    //         }
    //     }
    // }


    // 4. Hapus data layanan utama
    // Jika ON DELETE CASCADE di FK tabel anak sudah benar, langkah 3 tidak perlu eksplisit.
    $query_delete_layanan = "DELETE FROM layanan WHERE Id_Layanan = $id_layanan";
    if (mysqli_query($koneksi, $query_delete_layanan)) {
        if (mysqli_affected_rows($koneksi) > 0) {
            mysqli_commit($koneksi);
            $_SESSION['success_message'] = "Data layanan utama dan semua data spesifik terkait (jika ON DELETE CASCADE aktif) serta relasi mitra berhasil dihapus.";
        } else {
            mysqli_commit($koneksi); 
            $_SESSION['error_message'] = "Data layanan utama tidak ditemukan (mungkin sudah dihapus). Relasi lain telah diperiksa.";
        }
    } else {
        throw new Exception("Gagal menghapus data layanan utama: " . mysqli_error($koneksi));
    }

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: index.php");
exit;
?>