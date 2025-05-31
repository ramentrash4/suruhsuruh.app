<?php
require '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // proses simpan
    header("Location: index.php");
    exit;
}
?>
<h2>Tambah Data Layanan Makanan</h2>
<form method="post">
    <label>Kolom Lain:</label> <input type="text" name="kolom" required><br>

    <button type="submit">Simpan</button>
</form>
<a href="index.php">Kembali</a>
<script>
</script>
