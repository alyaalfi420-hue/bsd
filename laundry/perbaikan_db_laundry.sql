-- Perbaikan database LaundryKu berdasarkan struktur db_laundry.sql
-- Jalankan setelah memilih database db_laundry di phpMyAdmin.

-- 1. Izinkan role kasir pada tabel users (sebelumnya enum tidak memiliki 'kasir').
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','kasir','pelanggan','kurir') DEFAULT 'pelanggan';

-- 2. Perbaiki akun Kasir yang saat ini role-nya kosong.
UPDATE `users`
SET `role` = 'kasir', `status` = 'aktif'
WHERE `email` = 'kasir@laundry.local';

-- Notifikasi perubahan status dibuat oleh handler aplikasi pada admin/pesanan.php
-- dan kurir/index.php agar tidak menghasilkan notifikasi ganda.


-- 3. Pastikan tabel notifikasi tersedia. Tidak menghapus data tabel yang sudah ada.
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id_notifikasi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `id_pesanan` INT NULL,
  `judul` VARCHAR(180) NOT NULL,
  `pesan` TEXT NOT NULL,
  `jenis` VARCHAR(40) NOT NULL DEFAULT 'status_laundry',
  `sudah_dibaca` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notifikasi`),
  KEY `idx_notifikasi_user_id` (`id_user`, `id_notifikasi`),
  KEY `idx_notifikasi_pesanan` (`id_pesanan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catatan: kolom status pesanan otomatis disesuaikan oleh aplikasi (config/notifikasi_helper.php).
