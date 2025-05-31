<?php
session_start();
require '../../config/database.php';

$data = mysqli_query($koneksi, "SELECT * FROM layanan_makanan");
?>
<h2>Data Layanan Makanan</h2>
<a href="tambah.php">Tambah</a> | <a href="../../dashboard.php">Kembali</a>
<table border="1">
<tr>
  <th>ID</th>
  <th>Kolom Lain</th>
  <th>Aksi</th>
</tr>
<?php while ($d = mysqli_fetch_assoc($data)) : ?>
<tr>
  <td><?= $d['Id_Layanan_makanan'] ?></td>
  <td>...</td>
  
  <td>
    <a href="edit.php?id=<?= $d['Id_Layanan_makanan'] ?>">Edit</a>
    <a href="hapus.php?id=<?= $d['Id_Layanan_makanan'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
  </td>
</tr>
<tr><td colspan="100%"></td></tr>
<?php endwhile; ?>
</table>

<script>
function toggleDetail(id) {
  const el = document.getElementById('detail' + id);
  if (el) {
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
  }
}
</script>
