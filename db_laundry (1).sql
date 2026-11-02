-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Nov 2026 pada 11.45
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_laundry`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `alamat_pelanggan`
--

CREATE TABLE `alamat_pelanggan` (
  `id_alamat` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `label_alamat` varchar(100) DEFAULT NULL,
  `alamat_lengkap` text NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_utama` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `antar_jemput`
--

CREATE TABLE `antar_jemput` (
  `id_antar_jemput` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_alamat` int(11) NOT NULL,
  `jenis` varchar(30) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `jarak_km` decimal(10,2) DEFAULT 0.00,
  `berat_kg` decimal(10,2) DEFAULT 0.00,
  `ongkos` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chatbot_faq`
--

CREATE TABLE `chatbot_faq` (
  `id_faq` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text NOT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `jumlah` decimal(10,2) DEFAULT 0.00,
  `harga` decimal(12,2) DEFAULT 0.00,
  `subtotal` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_layanan`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 1, 8.00, 7000.00, 56000.00),
(2, 2, 3, 9.00, 6000.00, 54000.00),
(3, 3, 2, 1.00, 10000.00, 10000.00),
(4, 4, 2, 1.00, 10000.00, 10000.00),
(5, 5, 3, 1.00, 6000.00, 6000.00),
(6, 6, 1, 1.00, 7000.00, 7000.00),
(7, 7, 2, 1.00, 10000.00, 10000.00),
(8, 8, 2, 1.00, 10000.00, 10000.00),
(9, 9, 2, 1.00, 10000.00, 10000.00),
(10, 10, 2, 1.00, 10000.00, 10000.00),
(11, 11, 2, 1.00, 10000.00, 10000.00),
(12, 12, 2, 1.00, 10000.00, 10000.00),
(16, 16, 2, 1.00, 10000.00, 10000.00),
(17, 17, 1, 1.00, 7000.00, 7000.00),
(18, 18, 1, 1.00, 7000.00, 7000.00),
(19, 19, 3, 1.00, 6000.00, 6000.00),
(20, 20, 1, 1.00, 7000.00, 7000.00),
(21, 21, 2, 1.00, 10000.00, 10000.00),
(22, 22, 1, 1.00, 7000.00, 7000.00),
(23, 23, 1, 1.00, 7000.00, 7000.00),
(24, 24, 1, 3.00, 7000.00, 21000.00),
(25, 25, 2, 1.00, 10000.00, 10000.00),
(26, 26, 1, 1.00, 7000.00, 7000.00),
(27, 27, 1, 1.00, 7000.00, 7000.00),
(28, 28, 1, 1.00, 7000.00, 7000.00),
(29, 29, 2, 1.00, 10000.00, 10000.00),
(30, 30, 2, 15.00, 10000.00, 150000.00),
(31, 31, 1, 9.00, 7000.00, 63000.00),
(32, 32, 2, 9.00, 10000.00, 90000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `dompet`
--

CREATE TABLE `dompet` (
  `id_dompet` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `saldo` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dompet`
--

INSERT INTO `dompet` (`id_dompet`, `id_user`, `saldo`, `created_at`) VALUES
(1, 3, 0.00, '2026-10-18 15:31:10'),
(2, 4, 0.00, '2026-10-18 15:59:21'),
(3, 5, 0.00, '2026-10-19 04:25:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laundryku_setup_flags`
--

CREATE TABLE `laundryku_setup_flags` (
  `flag` varchar(100) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laundryku_setup_flags`
--

INSERT INTO `laundryku_setup_flags` (`flag`, `completed_at`) VALUES
('services_voucher_seed_v1', '2026-10-18 15:58:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) DEFAULT 'kg',
  `estimasi_jam` int(11) DEFAULT 24,
  `deskripsi` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `harga`, `satuan`, `estimasi_jam`, `deskripsi`, `status`, `created_at`) VALUES
(1, 'Cuci Kering', 7000.00, 'kg', 24, 'Layanan cuci kering tanpa setrika.', 'aktif', '2026-10-18 15:58:12'),
(2, 'Cuci Setrika', 10000.00, 'kg', 48, 'Layanan mencuci dan menyetrika pakaian.', 'aktif', '2026-10-18 15:58:12'),
(3, 'Setrika Aja', 6000.00, 'kg', 24, 'Layanan menyetrika pakaian saja.', 'aktif', '2026-10-18 15:58:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `judul` varchar(150) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `sudah_dibaca` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_user`, `id_pesanan`, `judul`, `pesan`, `jenis`, `sudah_dibaca`, `created_at`) VALUES
(1, 4, 1, 'Pesanan baru dibuat', 'Kode LDR-20261018-175921-754. Estimasi total Rp 50.400.', 'pesanan', 0, '2026-10-18 15:59:21'),
(2, 4, 2, 'Pesanan baru dibuat', 'Kode LDR-20261018-180136-390. Estimasi total Rp 48.600.', 'pesanan', 0, '2026-10-18 16:01:36'),
(3, 4, 3, 'Pesanan baru dibuat', 'Kode LDR-20261018-180748-654. Estimasi total Rp 9.000.', 'pesanan', 0, '2026-10-18 16:07:48'),
(4, 4, 4, 'Pesanan baru dibuat', 'Kode LDR-20261018-181105-990. Estimasi total Rp 9.000.', 'pesanan', 0, '2026-10-18 16:11:05'),
(5, 4, 5, 'Pesanan baru dibuat', 'Kode LDR-20261019-052213-507. Estimasi total Rp 3.600.', 'pesanan', 0, '2026-10-19 03:22:13'),
(6, 4, 6, 'Pesanan baru dibuat', 'Kode LDR-20261019-052846-990. Estimasi total Rp 4.200.', 'pesanan', 0, '2026-10-19 03:28:46'),
(7, 4, 7, 'Pesanan baru dibuat', 'Kode LDR-20261019-053957-236. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 03:39:57'),
(8, 4, 7, 'Status laundry diperbarui', 'Pesanan LDR-20261019-053957-236 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 03:55:48'),
(9, 4, 7, 'Status laundry diperbarui', 'Pesanan LDR-20261019-053957-236 berubah dari diterima menjadi dikeringkan.', 'status_laundry', 0, '2026-10-19 03:55:57'),
(10, 4, 5, 'Status laundry diperbarui', 'Pesanan LDR-20261019-052213-507 berubah dari diterima menjadi diantar.', 'status_laundry', 0, '2026-10-19 03:56:13'),
(11, 4, 1, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261018-175921-754 sebesar Rp 50.400 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 03:56:37'),
(12, 4, 2, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261018-180136-390 sebesar Rp 48.600 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 03:56:41'),
(13, 4, 3, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261018-180748-654 sebesar Rp 9.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 03:56:45'),
(14, 4, 7, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-053957-236 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 03:56:51'),
(15, 4, 6, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-052846-990 sebesar Rp 4.200 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 03:57:04'),
(16, 4, 4, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261018-181105-990 sebesar Rp 9.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 04:04:58'),
(17, 4, 5, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-052213-507 sebesar Rp 3.600 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 04:11:18'),
(18, 4, 8, 'Pesanan baru dibuat', 'Kode LDR-20261019-061229-166. Estimasi total Rp 10.000.', 'pesanan', 0, '2026-10-19 04:12:29'),
(19, 4, 8, 'Status laundry diperbarui', 'Pesanan LDR-20261019-061229-166 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 04:13:11'),
(20, 4, 8, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-061229-166 sebesar Rp 10.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 04:13:33'),
(21, 4, 9, 'Pesanan baru dibuat', 'Kode LDR-20261019-061858-693. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 04:18:58'),
(22, 4, 9, 'Status laundry diperbarui', 'Pesanan LDR-20261019-061858-693 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 04:19:44'),
(23, 4, 9, 'Pesanan diterima', 'Pesanan LDR-20261019-061858-693 sudah diterima dan masuk proses.', 'pesanan', 0, '2026-10-19 04:19:44'),
(24, 4, 9, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-061858-693 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-19 04:23:06'),
(25, 5, 10, 'Pesanan baru dibuat', 'Kode LDR-20261019-062552-320. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 04:25:52'),
(26, 5, 10, 'Status laundry diperbarui', 'Pesanan LDR-20261019-062552-320 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 04:26:36'),
(27, 5, 10, 'Pesanan diterima', 'Pesanan LDR-20261019-062552-320 sudah diterima dan masuk proses.', 'pesanan', 0, '2026-10-19 04:26:36'),
(28, 5, 11, 'Pesanan baru dibuat', 'Kode LDR-20261019-063515-410. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 04:35:15'),
(29, 5, 11, 'Status laundry diperbarui', 'Pesanan LDR-20261019-063515-410 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 04:35:44'),
(30, 5, 11, 'Pesanan diterima', 'Pesanan LDR-20261019-063515-410 sudah diterima dan masuk proses.', 'pesanan', 0, '2026-10-19 04:35:44'),
(31, 5, 12, 'Pesanan baru dibuat', 'Kode LDR-20261019-063833-790. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 04:38:33'),
(32, 5, 12, 'Status laundry diperbarui', 'Pesanan LDR-20261019-063833-790 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-19 04:38:54'),
(33, 5, 12, 'Pesanan diterima', 'Pesanan LDR-20261019-063833-790 sudah diterima dan masuk proses.', 'pesanan', 0, '2026-10-19 04:38:54'),
(37, 5, 16, 'Pesanan baru dibuat', 'Kode LDR-20261019-070200-308. Estimasi total Rp 6.000.', 'pesanan', 0, '2026-10-19 05:02:00'),
(38, 5, 17, 'Pesanan baru dibuat', 'Kode LDR-20261019-070840-765. Estimasi total Rp 4.200.', 'pesanan', 0, '2026-10-19 05:08:40'),
(39, 5, 18, 'Pesanan baru dibuat', 'Kode LDR-20261023-054334-575. Estimasi total Rp 4.200.', 'pesanan', 0, '2026-10-23 03:43:35'),
(40, 5, 19, 'Pesanan baru dibuat', 'Kode LDR-20261023-055722-312. Estimasi total Rp 3.600.', 'pesanan', 0, '2026-10-23 03:57:22'),
(41, 5, 19, 'Status laundry diperbarui', 'Pesanan LDR-20261023-055722-312 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 03:58:06'),
(42, 5, 18, 'Status laundry diperbarui', 'Pesanan LDR-20261023-054334-575 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 03:58:15'),
(43, 5, 17, 'Status laundry diperbarui', 'Pesanan LDR-20261019-070840-765 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 03:58:18'),
(44, 5, 16, 'Status laundry diperbarui', 'Pesanan LDR-20261019-070200-308 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 03:58:20'),
(45, 5, 10, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-062552-320 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 03:58:39'),
(46, 5, 11, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-063515-410 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 03:58:43'),
(47, 5, 12, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-063833-790 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 03:58:48'),
(48, 5, 20, 'Pesanan baru dibuat', 'Kode LDR-20261023-060402-489. Estimasi total Rp 4.200.', 'pesanan', 0, '2026-10-23 04:04:02'),
(49, 5, 21, 'Pesanan baru dibuat', 'Kode LDR-20261023-060416-572. Estimasi total Rp 10.000.', 'pesanan', 0, '2026-10-23 04:04:16'),
(50, 5, 21, 'Status laundry diperbarui', 'Pesanan LDR-20261023-060416-572 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:04:53'),
(51, 5, 20, 'Status laundry diperbarui', 'Pesanan LDR-20261023-060402-489 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:04:58'),
(52, 5, 22, 'Pesanan baru dibuat', 'Kode LDR-20261023-061046-570. Estimasi total Rp 6.300.', 'pesanan', 0, '2026-10-23 04:10:46'),
(53, 5, 22, 'Status laundry diperbarui', 'Pesanan LDR-20261023-061046-570 berubah dari menunggu menjadi selesai.', 'status_laundry', 0, '2026-10-23 04:12:42'),
(54, 5, 22, 'Status laundry diperbarui', 'Pesanan LDR-20261023-061046-570 berubah dari selesai menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:12:54'),
(55, 5, 16, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-070200-308 sebesar Rp 6.000 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 04:13:11'),
(56, 5, 17, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261019-070840-765 sebesar Rp 4.200 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 04:13:15'),
(57, 5, 18, 'Pembayaran dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261023-054334-575 sebesar Rp 4.200 telah dikonfirmasi oleh kasir.', 'pembayaran', 0, '2026-10-23 04:13:21'),
(58, 5, 23, 'Pesanan baru dibuat', 'Kode LDR-20261023-061608-219. Estimasi total Rp 7.000.', 'pesanan', 0, '2026-10-23 04:16:08'),
(59, 5, 24, 'Pesanan baru dibuat', 'Kode LDR-20261023-061702-492. Estimasi total Rp 21.000.', 'pesanan', 0, '2026-10-23 04:17:02'),
(60, 5, 24, 'Status laundry diperbarui', 'Pesanan LDR-20261023-061702-492 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:25:52'),
(61, 5, 23, 'Status laundry diperbarui', 'Pesanan LDR-20261023-061608-219 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:25:55'),
(62, 5, 24, 'Status laundry diperbarui', 'Pesanan LDR-20261023-061702-492 berubah dari diterima menjadi dikeringkan.', 'status_laundry', 0, '2026-10-23 04:26:06'),
(63, 3, 25, 'Pesanan Masuk', 'Pesanan LDR-20261023-063332-371 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-10-22 23:33:32'),
(64, 3, 26, 'Pesanan Masuk', 'Pesanan LDR-20261023-063422-744 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-10-22 23:34:22'),
(65, 3, 26, 'Status laundry diperbarui', 'Pesanan LDR-20261023-063422-744 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:35:55'),
(66, 3, 26, 'Status laundry diperbarui', 'Pesanan LDR-20261023-063422-744 berubah dari diterima menjadi disetrika.', 'status_laundry', 0, '2026-10-23 04:36:03'),
(67, 3, 25, 'Status laundry diperbarui', 'Pesanan LDR-20261023-063332-371 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-23 04:36:06'),
(68, 3, 25, 'Status laundry diperbarui', 'Pesanan LDR-20261023-063332-371 berubah dari diterima menjadi diantar.', 'status_laundry', 0, '2026-10-23 04:36:15'),
(69, 3, 27, 'Pesanan Masuk', 'Pesanan LDR-20261023-064302-998 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-10-22 23:43:02'),
(70, 3, 28, 'Pesanan Masuk', 'Pesanan LDR-20261023-065552-889 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-10-22 23:55:52'),
(71, 3, 28, 'Status laundry diperbarui', 'Pesanan LDR-20261023-065552-889 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-24 17:27:11'),
(72, 3, 27, 'Status laundry diperbarui', 'Pesanan LDR-20261023-064302-998 berubah dari menunggu menjadi diterima.', 'status_laundry', 0, '2026-10-24 17:27:14'),
(73, 3, 29, 'Pesanan Masuk', 'Pesanan LDR-20261102-090422-515 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-11-02 02:04:22'),
(74, 3, 30, 'Pesanan Masuk', 'Pesanan LDR-20261102-092627-196 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-11-02 02:26:27'),
(75, 3, 31, 'Pesanan Masuk', 'Pesanan LDR-20261102-093556-693 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-11-02 02:35:56'),
(76, 5, 19, 'Pembayaran Dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261023-055722-312 sebesar Rp 3.600 telah berhasil diverifikasi oleh kasir.', 'pembayaran', 0, '2026-11-02 08:39:08'),
(77, 5, 20, 'Pembayaran Dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261023-060402-489 sebesar Rp 4.200 telah berhasil diverifikasi oleh kasir.', 'pembayaran', 0, '2026-11-02 08:39:24'),
(78, 5, 21, 'Pembayaran Dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261023-060416-572 sebesar Rp 10.000 telah berhasil diverifikasi oleh kasir.', 'pembayaran', 0, '2026-11-02 08:39:27'),
(79, 3, 30, 'Pembayaran Dikonfirmasi', 'Pembayaran untuk pesanan LDR-20261102-092627-196 sebesar Rp 140.000 telah berhasil diverifikasi oleh kasir.', 'pembayaran', 0, '2026-11-02 08:39:34'),
(80, 3, 32, 'Pesanan Masuk', 'Pesanan LDR-20261102-100942-817 berhasil dibuat dan sedang menunggu konfirmasi.', 'status_laundry', 0, '2026-11-02 03:09:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `metode` varchar(30) DEFAULT NULL,
  `jumlah` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'menunggu',
  `reference_no` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pesanan`, `metode`, `jumlah`, `status`, `reference_no`, `paid_at`) VALUES
(1, 1, 'cash', 50400.00, 'lunas', 'PAY-20261018175921-514', '2026-10-19 05:56:37'),
(2, 2, 'cash', 48600.00, 'lunas', 'PAY-20261018180136-370', '2026-10-19 05:56:41'),
(3, 3, 'cash', 9000.00, 'lunas', 'PAY-20261018180748-487', '2026-10-19 05:56:45'),
(4, 4, 'cash', 9000.00, 'lunas', 'PAY-20261018181105-974', '2026-10-19 06:04:58'),
(5, 5, 'cash', 3600.00, 'lunas', 'PAY-20261019052213-788', '2026-10-19 06:11:18'),
(6, 6, 'cash', 4200.00, 'lunas', 'PAY-20261019052846-117', '2026-10-19 05:57:04'),
(7, 7, 'cash', 6000.00, 'lunas', 'PAY-20261019053957-336', '2026-10-19 05:56:51'),
(8, 8, 'cash', 10000.00, 'lunas', 'PAY-20261019061229-822', '2026-10-19 06:13:33'),
(9, 9, 'cash', 6000.00, 'lunas', 'PAY-20261019061858-664', '2026-10-19 06:23:06'),
(10, 10, 'cash', 6000.00, 'lunas', 'PAY-20261019062552-859', '2026-10-23 05:58:39'),
(11, 11, 'cash', 6000.00, 'lunas', 'PAY-20261019063515-826', '2026-10-23 05:58:43'),
(12, 12, 'cash', 6000.00, 'lunas', 'PAY-20261019063833-779', '2026-10-23 05:58:48'),
(16, 16, 'cash', 6000.00, 'lunas', 'PAY-20261019070200-122', '2026-10-23 06:13:11'),
(17, 17, 'cash', 4200.00, 'lunas', 'PAY-20261019070840-184', '2026-10-23 06:13:15'),
(18, 18, 'cash', 4200.00, 'lunas', 'PAY-20261023054335-947', '2026-10-23 06:13:21'),
(19, 19, 'cash', 3600.00, 'lunas', 'PAY-20261023055722-726', '2026-11-02 09:39:08'),
(20, 20, 'cash', 4200.00, 'lunas', 'PAY-20261023060402-470', '2026-11-02 09:39:24'),
(21, 21, 'cash', 10000.00, 'lunas', 'PAY-20261023060416-976', '2026-11-02 09:39:27'),
(22, 22, 'cash', 6300.00, 'menunggu', 'PAY-20261023061046-336', NULL),
(23, 23, 'cash', 7000.00, 'menunggu', 'PAY-20261023061608-485', NULL),
(24, 24, 'cash', 21000.00, 'menunggu', 'PAY-20261023061702-980', NULL),
(25, 25, 'cash', 10000.00, 'menunggu', 'PAY-20261023063332-522', NULL),
(26, 26, 'cash', 7000.00, 'menunggu', 'PAY-20261023063422-685', NULL),
(27, 27, 'cash', 7000.00, 'menunggu', 'PAY-20261023064302-218', NULL),
(28, 28, 'cash', 7000.00, 'menunggu', 'PAY-20261023065552-669', NULL),
(29, 29, 'cash', 10000.00, 'menunggu', 'PAY-20261102090422-241', NULL),
(30, 30, 'qris', 140000.00, 'lunas', 'PAY-20261102092627-484', '2026-11-02 09:39:34'),
(31, 31, 'cash', 63000.00, 'menunggu', 'PAY-20261102093556-805', NULL),
(32, 32, 'qris', 81000.00, 'menunggu', 'PAY-20261102100942-397', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penugasan_kurir`
--

CREATE TABLE `penugasan_kurir` (
  `id_penugasan` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_kurir` int(11) NOT NULL,
  `status` varchar(30) DEFAULT 'ditugaskan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `kode_pesanan` varchar(50) NOT NULL,
  `id_user` int(11) NOT NULL,
  `berat_estimasi` decimal(10,2) DEFAULT 0.00,
  `metode_pengiriman` enum('datang_sendiri','antar_jemput') DEFAULT 'datang_sendiri',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `ongkir` decimal(12,2) DEFAULT 0.00,
  `diskon` decimal(12,2) DEFAULT 0.00,
  `total_estimasi` decimal(12,2) DEFAULT 0.00,
  `estimasi_selesai` datetime DEFAULT NULL,
  `status_pesanan` varchar(30) DEFAULT 'menunggu',
  `catatan` text DEFAULT NULL,
  `tanggal_pesanan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `kode_pesanan`, `id_user`, `berat_estimasi`, `metode_pengiriman`, `subtotal`, `ongkir`, `diskon`, `total_estimasi`, `estimasi_selesai`, `status_pesanan`, `catatan`, `tanggal_pesanan`) VALUES
(1, 'LDR-20261018-175921-754', 4, 8.00, 'datang_sendiri', 56000.00, 0.00, 5600.00, 50400.00, '2026-10-19 17:59:21', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-18 15:59:21'),
(2, 'LDR-20261018-180136-390', 4, 9.00, 'datang_sendiri', 54000.00, 0.00, 5400.00, 48600.00, '2026-10-19 18:01:36', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-18 16:01:36'),
(3, 'LDR-20261018-180748-654', 4, 9.00, 'datang_sendiri', 10000.00, 0.00, 1000.00, 9000.00, '2026-10-20 18:07:48', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-18 16:07:48'),
(4, 'LDR-20261018-181105-990', 4, 9.00, 'datang_sendiri', 10000.00, 0.00, 1000.00, 9000.00, '2026-10-20 18:11:05', 'selesai', 'Pesanan publik. Pembayaran: cash.', '2026-10-18 16:11:05'),
(5, 'LDR-20261019-052213-507', 4, 1.00, 'datang_sendiri', 6000.00, 0.00, 2400.00, 3600.00, '2026-10-20 05:22:13', 'diantar', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 03:22:13'),
(6, 'LDR-20261019-052846-990', 4, 1.00, 'datang_sendiri', 7000.00, 0.00, 2800.00, 4200.00, '2026-10-20 05:28:46', 'selesai', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 03:28:46'),
(7, 'LDR-20261019-053957-236', 4, 1.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 05:39:57', 'dikeringkan', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 03:39:57'),
(8, 'LDR-20261019-061229-166', 4, 10.00, 'datang_sendiri', 10000.00, 0.00, 0.00, 10000.00, '2026-10-21 06:12:29', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 04:12:29'),
(9, 'LDR-20261019-061858-693', 4, 10.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 06:18:58', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 04:18:58'),
(10, 'LDR-20261019-062552-320', 5, 10.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 06:25:52', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 04:25:52'),
(11, 'LDR-20261019-063515-410', 5, 10.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 06:35:15', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 04:35:15'),
(12, 'LDR-20261019-063833-790', 5, 10.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 06:38:33', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 04:38:33'),
(16, 'LDR-20261019-070200-308', 5, 8.00, 'datang_sendiri', 10000.00, 0.00, 4000.00, 6000.00, '2026-10-21 07:02:00', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 05:02:00'),
(17, 'LDR-20261019-070840-765', 5, 5.90, 'datang_sendiri', 7000.00, 0.00, 2800.00, 4200.00, '2026-10-20 07:08:40', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-19 05:08:40'),
(18, 'LDR-20261023-054334-575', 5, 5.90, 'datang_sendiri', 7000.00, 0.00, 2800.00, 4200.00, '2026-10-24 05:43:34', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 03:43:34'),
(19, 'LDR-20261023-055722-312', 5, 5.90, 'datang_sendiri', 6000.00, 0.00, 2400.00, 3600.00, '2026-10-24 05:57:22', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 03:57:22'),
(20, 'LDR-20261023-060402-489', 5, 5.90, 'datang_sendiri', 7000.00, 0.00, 2800.00, 4200.00, '2026-10-24 06:04:02', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:04:02'),
(21, 'LDR-20261023-060416-572', 5, 5.90, 'datang_sendiri', 10000.00, 0.00, 0.00, 10000.00, '2026-10-25 05:04:16', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:04:16'),
(22, 'LDR-20261023-061046-570', 5, 20.00, 'datang_sendiri', 7000.00, 0.00, 700.00, 6300.00, '2026-10-24 06:10:46', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:10:46'),
(23, 'LDR-20261023-061608-219', 5, 20.00, 'datang_sendiri', 7000.00, 0.00, 0.00, 7000.00, '2026-10-24 06:16:08', 'diterima', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:16:08'),
(24, 'LDR-20261023-061702-492', 5, 20.00, 'datang_sendiri', 21000.00, 0.00, 0.00, 21000.00, '2026-10-24 06:17:02', 'dikeringkan', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:17:02'),
(25, 'LDR-20261023-063332-371', 3, 6.40, 'datang_sendiri', 10000.00, 0.00, 0.00, 10000.00, '2026-10-25 05:33:32', 'diantar', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:33:32'),
(26, 'LDR-20261023-063422-744', 3, 90.00, 'datang_sendiri', 7000.00, 0.00, 0.00, 7000.00, '2026-10-24 06:34:22', 'disetrika', 'Pesanan publik. Pembayaran: cash.', '2026-10-23 04:34:22'),
(27, 'LDR-20261023-064302-998', 3, 90.00, 'datang_sendiri', 7000.00, 0.00, 0.00, 7000.00, '2026-10-24 06:43:02', 'diterima', 'Atas nama: alpi | HP: 088971174883 | Pembayaran: cash.', '2026-10-23 04:43:02'),
(28, 'LDR-20261023-065552-889', 3, 90.00, 'datang_sendiri', 7000.00, 0.00, 0.00, 7000.00, '2026-10-24 06:55:52', 'diterima', 'Atas nama: febi | HP: 081234567892 | Pembayaran: cash.', '2026-10-23 04:55:52'),
(29, 'LDR-20261102-090422-515', 3, 90.00, 'datang_sendiri', 10000.00, 0.00, 0.00, 10000.00, '2026-11-04 09:04:22', 'menunggu', 'Atas nama: JESLYNN | HP: 088971174883 | Pembayaran: cash.', '2026-11-02 08:04:22'),
(30, 'LDR-20261102-092627-196', 3, 8.00, 'datang_sendiri', 150000.00, 0.00, 10000.00, 140000.00, '2026-11-04 09:26:27', 'menunggu', 'Atas nama: JEJE | HP: 081234567892 | Pembayaran: qris.', '2026-11-02 08:26:27'),
(31, 'LDR-20261102-093556-693', 3, 9.00, 'datang_sendiri', 63000.00, 0.00, 0.00, 63000.00, '2026-11-03 09:35:56', 'menunggu', 'Atas nama: Pelanggan | HP: 081234567892 | Pembayaran: cash.', '2026-11-02 08:35:56'),
(32, 'LDR-20261102-100942-817', 3, 90.00, 'datang_sendiri', 90000.00, 0.00, 9000.00, 81000.00, '2026-11-04 10:09:42', 'menunggu', 'Atas nama: JEJE | HP: 081234567892 | Pembayaran: qris.', '2026-11-02 09:09:42');

--
-- Trigger `pesanan`
--
DELIMITER $$
CREATE TRIGGER `trg_laundry_status_pesanan_after_update` AFTER UPDATE ON `pesanan` FOR EACH ROW BEGIN
  IF NOT (OLD.`status_pesanan` <=> NEW.`status_pesanan`) THEN
    INSERT INTO `tracking_pesanan` (`id_pesanan`, `status`, `keterangan`, `waktu`)
    VALUES (
      NEW.`id_pesanan`, NEW.`status_pesanan`,
      CONCAT('Status laundry berubah dari ', COALESCE(OLD.`status_pesanan`, 'belum ada'),
             ' menjadi ', COALESCE(NEW.`status_pesanan`, 'belum ada'), '.'),
      CURRENT_TIMESTAMP
    );
    INSERT INTO `notifikasi` (`id_user`, `id_pesanan`, `judul`, `pesan`, `jenis`, `sudah_dibaca`, `created_at`)
    VALUES (
      NEW.`id_user`, NEW.`id_pesanan`, 'Status laundry diperbarui',
      CONCAT('Pesanan ', NEW.`kode_pesanan`, ' berubah dari ',
             COALESCE(OLD.`status_pesanan`, 'belum ada'), ' menjadi ',
             COALESCE(NEW.`status_pesanan`, 'belum ada'), '.'),
      'status_laundry', 0, CURRENT_TIMESTAMP
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tarif_antar_jemput`
--

CREATE TABLE `tarif_antar_jemput` (
  `id_tarif` int(11) NOT NULL,
  `jarak_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `jarak_max` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tracking_pesanan`
--

CREATE TABLE `tracking_pesanan` (
  `id_tracking` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tracking_pesanan`
--

INSERT INTO `tracking_pesanan` (`id_tracking`, `id_pesanan`, `status`, `keterangan`, `waktu`) VALUES
(1, 1, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 50.400.', '2026-10-18 15:59:21'),
(2, 2, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 48.600.', '2026-10-18 16:01:36'),
(3, 3, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 9.000.', '2026-10-18 16:07:48'),
(4, 4, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 9.000.', '2026-10-18 16:11:05'),
(5, 5, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 3.600.', '2026-10-19 03:22:13'),
(6, 6, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 4.200.', '2026-10-19 03:28:46'),
(7, 7, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 03:39:57'),
(8, 7, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 03:55:48'),
(9, 7, 'dikeringkan', 'Status laundry berubah dari diterima menjadi dikeringkan.', '2026-10-19 03:55:57'),
(10, 5, 'diantar', 'Status laundry berubah dari diterima menjadi diantar.', '2026-10-19 03:56:13'),
(11, 8, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 10.000.', '2026-10-19 04:12:29'),
(12, 8, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 04:13:11'),
(13, 9, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 04:18:58'),
(14, 9, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 04:19:44'),
(15, 9, 'diterima', 'Pesanan disetujui dan diterima oleh Admin.', '2026-10-19 04:19:44'),
(16, 10, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 04:25:52'),
(17, 10, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 04:26:36'),
(18, 10, 'diterima', 'Pesanan disetujui dan diterima oleh Admin.', '2026-10-19 04:26:36'),
(19, 11, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 04:35:15'),
(20, 11, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 04:35:44'),
(21, 11, 'diterima', 'Pesanan disetujui dan diterima oleh Admin.', '2026-10-19 04:35:44'),
(22, 12, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 04:38:33'),
(23, 12, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-19 04:38:54'),
(24, 12, 'diterima', 'Pesanan disetujui dan diterima oleh Admin.', '2026-10-19 04:38:54'),
(28, 16, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.000.', '2026-10-19 05:02:00'),
(29, 17, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 4.200.', '2026-10-19 05:08:40'),
(30, 18, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 4.200.', '2026-10-23 03:43:34'),
(31, 19, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 3.600.', '2026-10-23 03:57:22'),
(32, 19, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 03:58:06'),
(33, 18, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 03:58:15'),
(34, 17, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 03:58:18'),
(35, 16, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 03:58:20'),
(36, 20, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 4.200.', '2026-10-23 04:04:02'),
(37, 21, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 10.000.', '2026-10-23 04:04:16'),
(38, 21, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:04:53'),
(39, 20, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:04:58'),
(40, 22, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 6.300.', '2026-10-23 04:10:46'),
(41, 22, 'selesai', 'Status laundry berubah dari menunggu menjadi selesai.', '2026-10-23 04:12:42'),
(42, 22, 'diterima', 'Status laundry berubah dari selesai menjadi diterima.', '2026-10-23 04:12:54'),
(43, 23, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 7.000.', '2026-10-23 04:16:08'),
(44, 24, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 21.000.', '2026-10-23 04:17:02'),
(45, 24, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:25:52'),
(46, 23, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:25:55'),
(47, 24, 'dikeringkan', 'Status laundry berubah dari diterima menjadi dikeringkan.', '2026-10-23 04:26:06'),
(48, 25, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 10.000.', '2026-10-23 04:33:32'),
(49, 26, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 7.000.', '2026-10-23 04:34:22'),
(50, 26, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:35:55'),
(51, 26, 'disetrika', 'Status laundry berubah dari diterima menjadi disetrika.', '2026-10-23 04:36:03'),
(52, 25, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-23 04:36:06'),
(53, 25, 'diantar', 'Status laundry berubah dari diterima menjadi diantar.', '2026-10-23 04:36:15'),
(54, 27, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 7.000.', '2026-10-23 04:43:02'),
(55, 28, 'menunggu', 'Pesanan dibuat melalui website. Estimasi total Rp 7.000.', '2026-10-23 04:55:52'),
(56, 28, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-24 17:27:11'),
(57, 27, 'diterima', 'Status laundry berubah dari menunggu menjadi diterima.', '2026-10-24 17:27:14'),
(58, 29, 'menunggu', 'Pesanan berhasil dibuat melalui website. Estimasi total Rp 10.000.', '2026-11-02 08:04:22'),
(59, 30, 'menunggu', 'Pesanan berhasil dibuat melalui website. Estimasi total Rp 140.000.', '2026-11-02 08:26:27'),
(60, 31, 'menunggu', 'Pesanan berhasil dibuat melalui website. Estimasi total Rp 63.000.', '2026-11-02 08:35:56'),
(61, 32, 'menunggu', 'Pesanan berhasil dibuat melalui website. Estimasi total Rp 81.000.', '2026-11-02 09:09:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_dompet`
--

CREATE TABLE `transaksi_dompet` (
  `id_transaksi` int(11) NOT NULL,
  `id_dompet` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `jenis` varchar(30) DEFAULT NULL,
  `nominal` decimal(12,2) DEFAULT 0.00,
  `saldo_sebelum` decimal(12,2) DEFAULT 0.00,
  `saldo_sesudah` decimal(12,2) DEFAULT 0.00,
  `metode` varchar(30) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_dompet`
--

INSERT INTO `transaksi_dompet` (`id_transaksi`, `id_dompet`, `id_pesanan`, `jenis`, `nominal`, `saldo_sebelum`, `saldo_sesudah`, `metode`, `status`, `reference_no`, `tanggal`) VALUES
(1, 2, NULL, 'topup', 20000.00, 0.00, 0.00, 'cash', 'menunggu', 'TOP-20261024192349-127', '2026-10-24 17:23:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','pelanggan','kurir') DEFAULT 'pelanggan',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `no_hp`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Kasir', 'kasir@laundry.local', '081234567891', '$2y$10$b9qcKgLddNYAoEb.DDBsceR9zeuDPbsj4J4yu4SweVHxbx6fcjfV6', 'kasir', 'aktif', '2026-10-18 13:21:56'),
(2, 'Admin', 'admin@laundry.local', '081234567890', '$2y$10$nGMFdGlXaEInbM2q.iDv4uUM5pfLSgN.EqrL/ihxkfGIMh6M79hIK', 'admin', 'aktif', '2026-10-18 15:31:10'),
(3, 'Pelanggan', 'pelanggan@laundry.local', '081234567892', '$2y$10$j2qNxjw3tVtOnXNsJDKRtOZreoBjxj9ChkyDu/2SUENw5qymyZeXG', 'pelanggan', 'aktif', '2026-10-18 15:31:10'),
(4, 'ALYA  ALFI LUTFIAH', 'alyaalfi420@gmail.com', '085279062460', '$2y$10$9QUCvbSSSi97PHEKcJerLu6AYEHhnaEKow6RzyocVVQMR.gB3upMy', 'pelanggan', 'aktif', '2026-10-18 15:59:21'),
(5, 'ayya', 'alpiapi476@gmail.com', '088971174883', '$2y$10$FYcwQWZ51dSRuzRygSltbORMaLqhjZ55Lo2MFvxHPO1iGo8aI8Ify', 'pelanggan', 'aktif', '2026-10-19 04:25:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `voucher`
--

CREATE TABLE `voucher` (
  `id_voucher` int(11) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `jenis_diskon` enum('persen','nominal') DEFAULT 'nominal',
  `nilai_diskon` decimal(12,2) DEFAULT 0.00,
  `minimal_transaksi` decimal(12,2) DEFAULT 0.00,
  `maksimal_diskon` decimal(12,2) DEFAULT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_berakhir` datetime NOT NULL,
  `syarat_topup` decimal(12,2) DEFAULT 0.00,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `voucher`
--

INSERT INTO `voucher` (`id_voucher`, `kode`, `nama`, `deskripsi`, `jenis_diskon`, `nilai_diskon`, `minimal_transaksi`, `maksimal_diskon`, `tanggal_mulai`, `tanggal_berakhir`, `syarat_topup`, `status`) VALUES
(1, 'HEMAT10', 'Diskon 10%', 'Voucher contoh untuk pengujian checkout.', 'persen', 10.00, 0.00, 10000.00, '2026-10-17 17:58:12', '2027-10-18 17:58:12', 0.00, 'aktif'),
(2, 'HEMAT40', 'PELANGGAN', '', 'persen', 40.00, 0.00, NULL, '2026-10-18 18:18:00', '2026-11-17 18:18:00', 0.00, 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `voucher_pelanggan`
--

CREATE TABLE `voucher_pelanggan` (
  `id_voucher_user` int(11) NOT NULL,
  `id_voucher` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `status` enum('tersedia','digunakan','kadaluarsa') DEFAULT 'tersedia',
  `id_pesanan_penggunaan` int(11) DEFAULT NULL,
  `digunakan_pada` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `alamat_pelanggan`
--
ALTER TABLE `alamat_pelanggan`
  ADD PRIMARY KEY (`id_alamat`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `antar_jemput`
--
ALTER TABLE `antar_jemput`
  ADD PRIMARY KEY (`id_antar_jemput`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_alamat` (`id_alamat`);

--
-- Indeks untuk tabel `chatbot_faq`
--
ALTER TABLE `chatbot_faq`
  ADD PRIMARY KEY (`id_faq`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_layanan` (`id_layanan`);

--
-- Indeks untuk tabel `dompet`
--
ALTER TABLE `dompet`
  ADD PRIMARY KEY (`id_dompet`),
  ADD UNIQUE KEY `unique_dompet_user` (`id_user`);

--
-- Indeks untuk tabel `laundryku_setup_flags`
--
ALTER TABLE `laundryku_setup_flags`
  ADD PRIMARY KEY (`flag`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id_layanan`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `penugasan_kurir`
--
ALTER TABLE `penugasan_kurir`
  ADD PRIMARY KEY (`id_penugasan`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_kurir` (`id_kurir`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `tarif_antar_jemput`
--
ALTER TABLE `tarif_antar_jemput`
  ADD PRIMARY KEY (`id_tarif`);

--
-- Indeks untuk tabel `tracking_pesanan`
--
ALTER TABLE `tracking_pesanan`
  ADD PRIMARY KEY (`id_tracking`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `transaksi_dompet`
--
ALTER TABLE `transaksi_dompet`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_dompet` (`id_dompet`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id_voucher`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `voucher_pelanggan`
--
ALTER TABLE `voucher_pelanggan`
  ADD PRIMARY KEY (`id_voucher_user`),
  ADD KEY `id_voucher` (`id_voucher`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_pesanan_penggunaan` (`id_pesanan_penggunaan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `alamat_pelanggan`
--
ALTER TABLE `alamat_pelanggan`
  MODIFY `id_alamat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `antar_jemput`
--
ALTER TABLE `antar_jemput`
  MODIFY `id_antar_jemput` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `chatbot_faq`
--
ALTER TABLE `chatbot_faq`
  MODIFY `id_faq` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `dompet`
--
ALTER TABLE `dompet`
  MODIFY `id_dompet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `penugasan_kurir`
--
ALTER TABLE `penugasan_kurir`
  MODIFY `id_penugasan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `tarif_antar_jemput`
--
ALTER TABLE `tarif_antar_jemput`
  MODIFY `id_tarif` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tracking_pesanan`
--
ALTER TABLE `tracking_pesanan`
  MODIFY `id_tracking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT untuk tabel `transaksi_dompet`
--
ALTER TABLE `transaksi_dompet`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id_voucher` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `voucher_pelanggan`
--
ALTER TABLE `voucher_pelanggan`
  MODIFY `id_voucher_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `alamat_pelanggan`
--
ALTER TABLE `alamat_pelanggan`
  ADD CONSTRAINT `alamat_pelanggan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `antar_jemput`
--
ALTER TABLE `antar_jemput`
  ADD CONSTRAINT `antar_jemput_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `antar_jemput_ibfk_2` FOREIGN KEY (`id_alamat`) REFERENCES `alamat_pelanggan` (`id_alamat`);

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`);

--
-- Ketidakleluasaan untuk tabel `dompet`
--
ALTER TABLE `dompet`
  ADD CONSTRAINT `dompet_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penugasan_kurir`
--
ALTER TABLE `penugasan_kurir`
  ADD CONSTRAINT `penugasan_kurir_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `penugasan_kurir_ibfk_2` FOREIGN KEY (`id_kurir`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `tracking_pesanan`
--
ALTER TABLE `tracking_pesanan`
  ADD CONSTRAINT `tracking_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi_dompet`
--
ALTER TABLE `transaksi_dompet`
  ADD CONSTRAINT `transaksi_dompet_ibfk_1` FOREIGN KEY (`id_dompet`) REFERENCES `dompet` (`id_dompet`),
  ADD CONSTRAINT `transaksi_dompet_ibfk_2` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Ketidakleluasaan untuk tabel `voucher_pelanggan`
--
ALTER TABLE `voucher_pelanggan`
  ADD CONSTRAINT `voucher_pelanggan_ibfk_1` FOREIGN KEY (`id_voucher`) REFERENCES `voucher` (`id_voucher`),
  ADD CONSTRAINT `voucher_pelanggan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `voucher_pelanggan_ibfk_3` FOREIGN KEY (`id_pesanan_penggunaan`) REFERENCES `pesanan` (`id_pesanan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
