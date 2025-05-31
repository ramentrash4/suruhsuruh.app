<?php
// Mulai session di sini agar bisa dipanggil sekali di semua halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definisikan BASE_URL (sesuaikan '/projekbasdat/' jika nama folder Anda berbeda)
if (!defined('BASE_URL')) {
    // Cara sederhana dan andal untuk proyek lokal:
    define('BASE_URL', '/projekbasdat/'); 
}

// Sertakan koneksi database juga di sini agar terpusat
// Pastikan path ke database.php ini benar relatif terhadap config.php
require_once __DIR__ . '/config/database.php'; 

// Sertakan kredensial admin 
// Ganti path ini jika Anda meletakkan admin_credentials.php di tempat lain
if (file_exists(__DIR__ . '/config/admin_credentials.php')) { 
    require_once __DIR__ . '/config/admin_credentials.php';
}
// Contoh jika ada di root projekbasdat (satu level di atas folder config ini):
// elseif (file_exists(dirname(__DIR__) . '/admin_credentials.php')) {
//     require_once dirname(__DIR__) . '/admin_credentials.php';
// }
?>