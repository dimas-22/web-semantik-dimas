-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql206.infinityfree.com
-- Waktu pembuatan: 28 Sep 2026 pada 12.54
-- Versi server: 11.4.13-MariaDB
-- Versi PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_43034143_universitassemantik`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `fakultas`
--

CREATE TABLE `fakultas` (
  `kode_fakultas` varchar(10) NOT NULL,
  `nama_fakultas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `fakultas`
--

INSERT INTO `fakultas` (`kode_fakultas`, `nama_fakultas`) VALUES
('FT', 'Teknik');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `npm` varchar(20) NOT NULL,
  `nama_mahasiswa` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `alamat` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `kode_prodi` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`npm`, `nama_mahasiswa`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `tanggal_masuk`, `alamat`, `password`, `kode_prodi`) VALUES
('2024001', 'Ahmad', 'L', 'Bengkulu', '2006-01-10', '2024-09-01', 'Bengkulu', 'password123', 'TI'),
('2024002', 'Budi', 'L', 'Curup', '2006-02-15', '2024-09-01', 'Curup', 'password123', 'TI');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('administrator','prodi','mahasiswa') NOT NULL,
  `kode_prodi` varchar(10) DEFAULT NULL,
  `npm` varchar(20) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `username`, `password`, `nama_lengkap`, `role`, `kode_prodi`, `npm`, `status`, `created_at`, `updated_at`) VALUES
(1, 'administrator', 'admin123', 'Administrator Universitas', 'administrator', NULL, NULL, 'aktif', '2026-09-28 15:24:43', '2026-09-28 15:24:43'),
(2, 'prodi_ti', 'prodi123', 'Admin Teknik Informatika', 'prodi', 'TI', NULL, 'aktif', '2026-09-28 15:24:58', '2026-09-28 15:24:58'),
(3, 'prodi_sistem_informasi', 'prodi123', 'Admin Sistem Informasi', 'prodi', 'SI', NULL, 'aktif', '2026-09-28 15:25:14', '2026-09-28 15:25:14'),
(4, 'Ahmad', '$2y$12$7rd.tQsnNYGZUNxWOAxY.uZ5QlfOFI4vXKj/kK/mmt6g4NWPefeMO', 'Ahmad', 'mahasiswa', NULL, '2024001', 'aktif', '2026-09-28 16:08:02', '2026-09-28 16:08:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_studi`
--

CREATE TABLE `program_studi` (
  `kode_prodi` varchar(10) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL,
  `kode_fakultas` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `program_studi`
--

INSERT INTO `program_studi` (`kode_prodi`, `nama_prodi`, `kode_fakultas`) VALUES
('SI', 'Sistem Informasi', 'FT'),
('TI', 'Teknik Informatika', 'FT');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`kode_fakultas`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`npm`),
  ADD KEY `fk_mahasiswa_prodi` (`kode_prodi`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_pengguna_prodi` (`kode_prodi`),
  ADD KEY `fk_pengguna_mahasiswa` (`npm`);

--
-- Indeks untuk tabel `program_studi`
--
ALTER TABLE `program_studi`
  ADD PRIMARY KEY (`kode_prodi`),
  ADD KEY `fk_prodi_fakultas` (`kode_fakultas`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mahasiswa_prodi` FOREIGN KEY (`kode_prodi`) REFERENCES `program_studi` (`kode_prodi`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `program_studi`
--
ALTER TABLE `program_studi`
  ADD CONSTRAINT `fk_prodi_fakultas` FOREIGN KEY (`kode_fakultas`) REFERENCES `fakultas` (`kode_fakultas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
