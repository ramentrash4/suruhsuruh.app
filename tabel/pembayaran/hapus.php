<?php
require '../../config/database.php';
$id = $_GET['id'];

if (!is_numeric($id)) {
    die("ID tidak valid");
}

mysqli_query($koneksi, "DELETE FROM bayaran WHERE Id_Pembayaran=$id");
header("Location: index.php");
exit;
?>