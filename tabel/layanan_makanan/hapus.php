<?php
require '../../config/database.php';
$id = $_GET['id'];
mysqli_query($koneksi, "DELETE FROM layanan_makanan WHERE Id_Layanan_makanan=$id");
header("Location: index.php");
exit;
?>
