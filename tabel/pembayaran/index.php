// --- FILE: tabel/bayaran/index.php ---
<?php
session_start();
require '../../config/database.php';

$data = mysqli_query($koneksi, "
  SELECT b.*, 
         p.Nama_Depan, p.Nama_Tengah, p.Nama_Belakang, p.Email, p.Alamat
  FROM bayaran b
  LEFT JOIN pengguna p ON b.Id_Pengguna = p.Id_pengguna
");
?>
<h2>Data Pembayaran</h2>
<a href="tambah.php">Tambah</a> | <a href="../../dashboard.php">Kembali</a>
<table border="1">
<tr>
  <th>ID</th>
  <th>ID Pengguna</th>
  <th>Tanggal</th>
  <th>Jumlah</th>
  <th>Aksi</th>
</tr>
<?php while ($d = mysqli_fetch_assoc($data)) : ?>
<tr>
  <td><?= $d['Id_Pembayaran'] ?></td>
  <td>
    <?= $d['Id_Pengguna'] ?>
    <button onclick="toggleDetail(<?= $d['Id_Pembayaran'] ?>)">Lihat</button>
    <div id="detail<?= $d['Id_Pembayaran'] ?>" style="display:none; font-size: 90%; margin-top: 5px; border: 1px solid #ccc; padding: 5px;">
      <strong>Nama:</strong> <?= $d['Nama_Depan'] . ' ' . $d['Nama_Tengah'] . ' ' . $d['Nama_Belakang'] ?><br>
      <strong>Email:</strong> <?= $d['Email'] ?><br>
      <strong>Alamat:</strong> <?= $d['Alamat'] ?>
    </div>
  </td>
  <td><?= $d['Tanggal'] ?></td>
  <td><?= $d['Jumlah'] ?></td>
  <td>
    <a href="edit.php?id=<?= $d['Id_Pembayaran'] ?>">Edit</a>
    <a href="hapus.php?id=<?= $d['Id_Pembayaran'] ?>" onclick="return confirm('Yakin?')">Hapus</a>
  </td>
</tr>
<?php endwhile; ?>
</table>

<script>
function toggleDetail(id) {
  const el = document.getElementById('detail' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
