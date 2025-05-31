<?php
require '../../config/database.php';
$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM bayaran WHERE Id_Pembayaran = $id"));
$pengguna = mysqli_query($koneksi, "SELECT * FROM pengguna");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = $_POST['id_pengguna'];
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];

    mysqli_query($koneksi, "UPDATE bayaran SET Id_Pengguna='$id_pengguna', Tanggal='$tanggal', Jumlah='$jumlah' WHERE Id_Pembayaran=$id");
    header("Location: index.php");
    exit;
}
?>
<h2>Edit Pembayaran</h2>
<form method="post">
    <label>Pengguna:</label>
    <select name="id_pengguna" id="id_pengguna" onchange="tampilkanDetail()" required>
        <option value="">-- Pilih Pengguna --</option>
        <?php while ($p = mysqli_fetch_assoc($pengguna)) : ?>
        <option 
            value="<?= $p['Id_pengguna'] ?>" 
            data-nama="<?= $p['Nama_Depan'] . ' ' . $p['Nama_Tengah'] . ' ' . $p['Nama_Belakang'] ?>"
            data-email="<?= $p['Email'] ?>"
            data-alamat="<?= $p['Alamat'] ?>"
            <?= ($p['Id_pengguna'] == $data['Id_Pengguna']) ? 'selected' : '' ?>
        >
            <?= $p['Id_pengguna'] ?> - <?= $p['Nama_Depan'] ?>
        </option>
        <?php endwhile; ?>
    </select><br><br>

    <div id="detail_pengguna" style="margin-top:10px; border:1px solid #ccc; padding:10px;"></div>

    <label>Tanggal:</label>
    <input type="date" name="tanggal" value="<?= $data['Tanggal'] ?>" required><br><br>

    <label>Jumlah:</label>
    <input type="text" name="jumlah" value="<?= $data['Jumlah'] ?>" required><br><br>

    <button type="submit">Simpan Perubahan</button>
</form>
<a href="index.php">Kembali</a>

<script>
tampilkanDetail();
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