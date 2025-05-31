-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2025 at 08:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aplikasisuruh`
--

-- --------------------------------------------------------

--
-- Table structure for table `bayaran`
--

CREATE TABLE `bayaran` (
  `Id_Pembayaran` int(11) NOT NULL,
  `Id_Pengguna` int(11) DEFAULT NULL,
  `Tanggal` date NOT NULL,
  `Jumlah` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bayaran`
--

INSERT INTO `bayaran` (`Id_Pembayaran`, `Id_Pengguna`, `Tanggal`, `Jumlah`) VALUES
(1, 1, '2024-12-18', 'RP. 700.000'),
(2, 2, '2022-08-07', 'RP. 289.000'),
(3, 3, '2023-11-29', 'RP. 91.000'),
(4, 4, '2024-06-04', 'RP. 1.450.000'),
(5, 5, '2025-02-25', 'RP. 14.000');

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `Id_DetailPesanan` int(11) NOT NULL,
  `Id_Pesanan` int(11) NOT NULL,
  `Harga` decimal(10,2) NOT NULL,
  `Jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`Id_DetailPesanan`, `Id_Pesanan`, `Harga`, `Jumlah`) VALUES
(1, 1, 80000.00, 2),
(2, 2, 52000.00, 5),
(3, 3, 91000.00, 1),
(4, 4, 512000.00, 2),
(5, 5, 12000.00, 21);

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `Id_Layanan` int(11) NOT NULL,
  `Nama_Layanan` varchar(100) NOT NULL,
  `Jenis_Layanan` enum('Makanan','Kesehatan','Layanan Rumah','Lainnya') NOT NULL,
  `Deskripsi_Umum` text DEFAULT NULL,
  `Status_Aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`Id_Layanan`, `Nama_Layanan`, `Jenis_Layanan`, `Deskripsi_Umum`, `Status_Aktif`) VALUES
(1, 'Makanan Rumahan Enak', 'Makanan', NULL, 1),
(2, 'Bersihkan WC MU', 'Layanan Rumah', NULL, 1),
(3, 'Crispy Chiken on the go', 'Makanan', NULL, 1),
(4, 'Check Gula Darah', 'Kesehatan', NULL, 1),
(5, 'Check Kolestrol', 'Kesehatan', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `layanan_kesehatan`
--

CREATE TABLE `layanan_kesehatan` (
  `Id_Layanan` int(11) NOT NULL,
  `Tenaga_Medis_Kualifikasi` varchar(50) DEFAULT NULL,
  `Tarif_Layanan_Kesehatan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layanan_makanan`
--

CREATE TABLE `layanan_makanan` (
  `Id_Layanan` int(11) NOT NULL,
  `Restoran` varchar(50) DEFAULT NULL,
  `Nama_Kurir_Default` varchar(50) DEFAULT NULL,
  `Kendaraan_Kurir_Default` varchar(50) DEFAULT NULL,
  `Tarif_Pengiriman_Makanan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layanan_makanan`
--

INSERT INTO `layanan_makanan` (`Id_Layanan`, `Restoran`, `Nama_Kurir_Default`, `Kendaraan_Kurir_Default`, `Tarif_Pengiriman_Makanan`) VALUES
(1, 'Warung Marinah', 'Saep Muraep', 'Vario techno 2018', 'RP. 4000/KM');

-- --------------------------------------------------------

--
-- Table structure for table `layanan_pelayanan_rumah`
--

CREATE TABLE `layanan_pelayanan_rumah` (
  `Id_Layanan` int(11) NOT NULL,
  `Deskripsi_Detail_Pekerjaan` text DEFAULT NULL,
  `Perkiraan_Durasi` varchar(50) DEFAULT NULL,
  `Satuan_Tarif_Rumah` varchar(50) DEFAULT NULL,
  `Tarif_Pelayanan_Rumah` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mitra`
--

CREATE TABLE `mitra` (
  `Id_Mitra` int(11) NOT NULL,
  `Nama_Mitra` varchar(50) NOT NULL,
  `No_Telp` varchar(50) NOT NULL,
  `Spesialis_Mitra` varchar(50) NOT NULL,
  `Id_Perusahaan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mitra`
--

INSERT INTO `mitra` (`Id_Mitra`, `Nama_Mitra`, `No_Telp`, `Spesialis_Mitra`, `Id_Perusahaan`) VALUES
(1, 'KFC', '+628258935863', 'Makanan Cepat Saji', 1),
(2, 'Sedot Wc Rangga', '+628258346863', 'Membersihkan Septic Tank', 1),
(3, 'House Design Inc', '+6283274628734', 'Design Your Dream Home', 1),
(4, 'Bu Marinah', '+62387472463435', 'Makanan Rumahan Murmer', 1),
(5, 'Rumah Sakit Hasan Sadikin', '+6233482746534', 'Hospital', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pekerja`
--

CREATE TABLE `pekerja` (
  `Id_Pekerja` int(11) NOT NULL,
  `Id_Perusahaan` int(11) NOT NULL,
  `Nama_Depan` varchar(50) NOT NULL,
  `Nama_Tengah` varchar(50) NOT NULL,
  `Nama_Belakang` varchar(50) NOT NULL,
  `Tanggal_lahir` date NOT NULL,
  `NO_Telp` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pekerja`
--

INSERT INTO `pekerja` (`Id_Pekerja`, `Id_Perusahaan`, `Nama_Depan`, `Nama_Tengah`, `Nama_Belakang`, `Tanggal_lahir`, `NO_Telp`) VALUES
(1, 1, 'sugeng', 'mulyono', 'karbit', '1999-12-24', '0824839048932'),
(2, 1, 'erik', 'erikson', 'alir', '2002-07-19', '08239742563'),
(3, 1, 'Sutomo', 'lailas', 'hartono', '1978-01-12', '082393920'),
(4, 1, 'Maman', 'Suherman', 'Laliga', '1989-04-29', '085592039203'),
(5, 1, 'Mirza', 'Fayzul', 'Haq', '2006-12-17', '0859110224715');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `Id_pengguna` int(11) NOT NULL,
  `Nama_Depan` varchar(50) NOT NULL,
  `Nama_Tengah` varchar(50) NOT NULL,
  `Nama_Belakang` varchar(50) NOT NULL,
  `Tanggal_Lahir` date NOT NULL,
  `Alamat` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `No_Telp` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`Id_pengguna`, `Nama_Depan`, `Nama_Tengah`, `Nama_Belakang`, `Tanggal_Lahir`, `Alamat`, `Email`, `No_Telp`) VALUES
(1, 'Randi', 'Aditiya', 'Suharisman', '2006-06-17', 'Jalan Cirebon', 'randias@Gmail.com', '082392572832'),
(2, 'Rizki', 'Cahyadi', 'Putra', '2003-12-26', 'Jalan Serang', 'sykes@Gmail.com', '08232938274'),
(3, 'Adi', 'Saputra', 'Koswara', '1993-03-29', 'Jalan Garut', 'Adisk@Gmail.com', '085924901495'),
(4, 'Kayla', 'Emira', 'Qonitah', '2007-06-07', 'Jalan Cibubur', 'kayethekid@Gmail.com', '082938842325'),
(5, 'Tsalis', 'Sholeh', 'Akbar', '2012-10-17', 'Jalan Jambi', 'Tsalisirv@Gmail.com', '088349532536');

-- --------------------------------------------------------

--
-- Table structure for table `perusahaan`
--

CREATE TABLE `perusahaan` (
  `Id_Perusahaan` int(11) NOT NULL,
  `Id_Profit` int(11) DEFAULT NULL,
  `Nama` varchar(50) NOT NULL,
  `CEO` varchar(50) NOT NULL,
  `Kota` varchar(50) NOT NULL,
  `Jalan` varchar(50) NOT NULL,
  `Kode_Pos` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perusahaan`
--

INSERT INTO `perusahaan` (`Id_Perusahaan`, `Id_Profit`, `Nama`, `CEO`, `Kota`, `Jalan`, `Kode_Pos`) VALUES
(1, NULL, 'SuruhSuruh.com', 'Khong cie', 'Bandung', 'Asia Afrika', '40233');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `Id_Pesanan` int(11) NOT NULL,
  `Id_Pengguna` int(11) DEFAULT NULL,
  `Id_Pembayaran` int(11) DEFAULT NULL,
  `Id_Layanan` int(11) DEFAULT NULL,
  `Tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`Id_Pesanan`, `Id_Pengguna`, `Id_Pembayaran`, `Id_Layanan`, `Tanggal`) VALUES
(1, 1, 1, 3, '2024-12-18'),
(2, 1, 1, 1, '2024-12-18'),
(3, 3, 3, 2, '2023-11-29'),
(4, 4, 4, 2, '2024-06-04'),
(5, 2, 2, 1, '2022-08-07');

-- --------------------------------------------------------

--
-- Table structure for table `profit`
--

CREATE TABLE `profit` (
  `Id_Profit` int(11) NOT NULL,
  `Id_DetailPesanan` int(11) DEFAULT NULL,
  `Tanggal_Profit` date DEFAULT NULL,
  `total_Profit` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profit`
--

INSERT INTO `profit` (`Id_Profit`, `Id_DetailPesanan`, `Tanggal_Profit`, `total_Profit`) VALUES
(1, 1, '2024-12-18', 18520000.00),
(2, 2, '2024-12-18', 18520000.00),
(3, 3, '2023-11-29', 29823000.00),
(4, 4, '2024-06-04', 8234000.00),
(5, 5, '2022-04-07', 1522000.00);

-- --------------------------------------------------------

--
-- Table structure for table `terikat`
--

CREATE TABLE `terikat` (
  `Id_Mitra` int(11) NOT NULL,
  `Id_Layanan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terikat`
--

INSERT INTO `terikat` (`Id_Mitra`, `Id_Layanan`) VALUES
(1, 3),
(2, 2),
(3, 2),
(4, 1),
(5, 4),
(5, 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bayaran`
--
ALTER TABLE `bayaran`
  ADD PRIMARY KEY (`Id_Pembayaran`),
  ADD KEY `Id_Pengguna` (`Id_Pengguna`);

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`Id_DetailPesanan`),
  ADD KEY `Id_Pesanan` (`Id_Pesanan`);

--
-- Indexes for table `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`Id_Layanan`);

--
-- Indexes for table `layanan_kesehatan`
--
ALTER TABLE `layanan_kesehatan`
  ADD PRIMARY KEY (`Id_Layanan`);

--
-- Indexes for table `layanan_makanan`
--
ALTER TABLE `layanan_makanan`
  ADD PRIMARY KEY (`Id_Layanan`);

--
-- Indexes for table `layanan_pelayanan_rumah`
--
ALTER TABLE `layanan_pelayanan_rumah`
  ADD PRIMARY KEY (`Id_Layanan`);

--
-- Indexes for table `mitra`
--
ALTER TABLE `mitra`
  ADD PRIMARY KEY (`Id_Mitra`),
  ADD KEY `fk_Id_Perusahaan` (`Id_Perusahaan`);

--
-- Indexes for table `pekerja`
--
ALTER TABLE `pekerja`
  ADD PRIMARY KEY (`Id_Pekerja`,`Id_Perusahaan`),
  ADD KEY `Id_Perusahaan` (`Id_Perusahaan`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`Id_pengguna`);

--
-- Indexes for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD PRIMARY KEY (`Id_Perusahaan`),
  ADD KEY `Id_Profit` (`Id_Profit`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`Id_Pesanan`),
  ADD KEY `Id_Pengguna` (`Id_Pengguna`),
  ADD KEY `Id_Pembayaran` (`Id_Pembayaran`),
  ADD KEY `Id_Layanan` (`Id_Layanan`);

--
-- Indexes for table `profit`
--
ALTER TABLE `profit`
  ADD PRIMARY KEY (`Id_Profit`),
  ADD KEY `fk_profit_to_detail_pesanan` (`Id_DetailPesanan`);

--
-- Indexes for table `terikat`
--
ALTER TABLE `terikat`
  ADD PRIMARY KEY (`Id_Mitra`,`Id_Layanan`),
  ADD KEY `Id_Layanan` (`Id_Layanan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bayaran`
--
ALTER TABLE `bayaran`
  MODIFY `Id_Pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `Id_DetailPesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `Id_Layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mitra`
--
ALTER TABLE `mitra`
  MODIFY `Id_Mitra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pekerja`
--
ALTER TABLE `pekerja`
  MODIFY `Id_Pekerja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `Id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `perusahaan`
--
ALTER TABLE `perusahaan`
  MODIFY `Id_Perusahaan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `Id_Pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `profit`
--
ALTER TABLE `profit`
  MODIFY `Id_Profit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bayaran`
--
ALTER TABLE `bayaran`
  ADD CONSTRAINT `bayaran_ibfk_1` FOREIGN KEY (`Id_Pengguna`) REFERENCES `pengguna` (`Id_pengguna`);

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`Id_Pesanan`) REFERENCES `pesanan` (`Id_Pesanan`);

--
-- Constraints for table `layanan_kesehatan`
--
ALTER TABLE `layanan_kesehatan`
  ADD CONSTRAINT `fk_kesehatan_spec_layanan` FOREIGN KEY (`Id_Layanan`) REFERENCES `layanan` (`Id_Layanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `layanan_makanan`
--
ALTER TABLE `layanan_makanan`
  ADD CONSTRAINT `fk_makanan_spec_layanan` FOREIGN KEY (`Id_Layanan`) REFERENCES `layanan` (`Id_Layanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `layanan_pelayanan_rumah`
--
ALTER TABLE `layanan_pelayanan_rumah`
  ADD CONSTRAINT `fk_pelayananrumah_spec_layanan` FOREIGN KEY (`Id_Layanan`) REFERENCES `layanan` (`Id_Layanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mitra`
--
ALTER TABLE `mitra`
  ADD CONSTRAINT `fk_Id_Perusahaan` FOREIGN KEY (`Id_Perusahaan`) REFERENCES `perusahaan` (`Id_Perusahaan`);

--
-- Constraints for table `pekerja`
--
ALTER TABLE `pekerja`
  ADD CONSTRAINT `pekerja_ibfk_1` FOREIGN KEY (`Id_Perusahaan`) REFERENCES `perusahaan` (`Id_Perusahaan`);

--
-- Constraints for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD CONSTRAINT `perusahaan_ibfk_1` FOREIGN KEY (`Id_Profit`) REFERENCES `profit` (`Id_Profit`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`Id_Pengguna`) REFERENCES `pengguna` (`Id_pengguna`),
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`Id_Pembayaran`) REFERENCES `bayaran` (`Id_Pembayaran`),
  ADD CONSTRAINT `pesanan_ibfk_3` FOREIGN KEY (`Id_Layanan`) REFERENCES `layanan` (`Id_Layanan`);

--
-- Constraints for table `profit`
--
ALTER TABLE `profit`
  ADD CONSTRAINT `fk_profit_to_detail_pesanan` FOREIGN KEY (`Id_DetailPesanan`) REFERENCES `detail_pesanan` (`Id_DetailPesanan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `terikat`
--
ALTER TABLE `terikat`
  ADD CONSTRAINT `terikat_ibfk_1` FOREIGN KEY (`Id_Mitra`) REFERENCES `mitra` (`Id_Mitra`),
  ADD CONSTRAINT `terikat_ibfk_2` FOREIGN KEY (`Id_Layanan`) REFERENCES `layanan` (`Id_Layanan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
