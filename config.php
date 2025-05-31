<?php
// Mulai session di sini agar bisa dipanggil sekali di semua halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menentukan BASE_URL secara dinamis
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);

// Jika kita berada di dalam subdirektori seperti /tabel/pengguna, kita perlu naik ke root
// Cari posisi '/tabel', '/auth', dll. dan potong path-nya.
// Cara yang lebih sederhana untuk proyek ini adalah mendefinisikannya secara manual jika dinamis terlalu rumit.

// CARA SEDERHANA DAN ANDAL UNTUK PROYEK LOKAL:
define('BASE_URL', '/projekbasdat/'); // Sesuaikan 'projekbasdat' dengan nama folder proyek Anda di htdocs

// Sertakan koneksi database juga di sini agar terpusat
require_once __DIR__ . '/config/database.php';
?>