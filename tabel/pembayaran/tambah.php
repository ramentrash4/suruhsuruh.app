// --- FILE: tabel/bayaran/tambah.php ---
<?php
require '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = $_POST['id_pengguna'];
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];

    $query = "INSERT INTO bayaran (Id_Pengguna, Tanggal, Jumlah) 
              VALUES ('$id_pengguna', '$tanggal', '$jumlah')";
    mysqli_query($koneksi, $query);
    header("Location: index.php");
    exit;
}

$pengguna = mysqli_query($koneksi, "SELECT * FROM pengguna");
?>
<h2>Tambah Pembayaran</h2>
<form method="post">
    <label>Pengguna:</label>
    <select name="id_pengguna" id="id_pengguna" onchange="tampilkanDetail()" required>
        <option value="">-- Pilih Pengguna --</option>
        <?php while ($p = mysqli_fetch_assoc($pengguna)) : ?>
        <option 
            value="<?= $p['Id_pengguna'] ?>" 
            data-nama="<?= $p['Nama_Depan'] . ' ' . $p['Nama_Tengah'] . ' ' . $p['Nama_Belakang'] ?>"
            data-email="<?= $p['Email'] ?>"
            data-alamat="<?= $p['Alamat'] ?>">
            <?= $p['Id_pengguna'] ?> - <?= $p['Nama_Depan'] ?>
        </option>
        <?php endwhile; ?>
    </select><br><br>

    <div id="detail_pengguna" style="margin-top:10px; border:1px solid #ccc; padding:10px;"></div>

    <label>Tanggal:</label>
    <input type="date" name="tanggal" required><br><br>

    <label>Jumlah:</label>
    <input type="text" name="jumlah" required><br><br>

    <button type="submit">Simpan</button>
</form>
<a href="index.php">Kembali</a>

<script>
function tampilkanDetail() {
    const select = document.getElementById('id_pengguna');
    const selected = select.options[select.selectedIndex];
    const nama = selected.getAttribute('data-nama');
    const email = selected.getAttribute('data-email');
    const alamat = selected.getAttribute('data-alamat');

    if (selected.value) {
        document.getElementById('detail_pengguna').innerHTML = `
            <strong>Nama:</strong> ${nama}<br>
            <strong>Email:</strong> ${email}<br>
            <strong>Alamat:</strong> ${alamat}
        `;
    } else {
        document.getElementById('detail_pengguna').innerHTML = '';
    }
}
</script>
