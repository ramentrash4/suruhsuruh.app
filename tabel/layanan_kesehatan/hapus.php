<?php
require '../../config/database.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM layanan_kesehatan WHERE Id_Layanan_kesehatan=$id");
header("Location: index.php");
exit;
?>
