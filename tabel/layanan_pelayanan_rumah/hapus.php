<?php
require '../../config/database.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM layanan_pelayanan_rumah WHERE Id_Layanan_pelayanan_rumah=$id");
header("Location: index.php");
exit;
?>
