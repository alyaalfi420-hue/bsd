PERBAIKAN LAUNDRYKU - RIWAYAT DAN NOTIFIKASI

Perbaikan:
1. riwayat.php tidak lagi langsung memanggil bind_param() jika prepare() gagal.
   Query memeriksa kolom tabel yang tersedia dan menampilkan pesan diagnostik jika
   struktur inti database tidak cocok.
2. Rincian pesanan dimuat sekaligus, dan riwayat menampilkan status, layanan, berat,
   total, serta tautan tracking. Halaman diperbarui otomatis setiap 30 detik.
3. API notifikasi mendukung pelanggan yang login maupun pelanggan yang sedang melihat
   riwayat menggunakan nomor HP. Kolom waktu/status baca dipetakan secara dinamis.
4. Widget notifikasi memeriksa perubahan setiap 5 detik dan mendukung suara/browser.
5. Menghindari trigger SQL tambahan yang dapat membuat notifikasi status ganda karena
   admin/pesanan.php dan kurir/index.php sudah membuat notifikasi pada alur perubahan.

CARA PEMASANGAN:
1. Matikan perubahan sementara dan backup folder C:\xampp\htdocs\laundry serta database
   db_laundry melalui phpMyAdmin.
2. Ekstrak ZIP ini. Salin isi folder laundry ke C:\xampp\htdocs\laundry dan izinkan
   overwrite file. Jangan menghapus file lain milik Anda yang tidak ada di ZIP.
3. Pastikan config/koneksi.php menunjuk database yang benar (default db_laundry).
4. Aktifkan Apache dan MySQL di XAMPP, lalu buka:
   http://localhost/laundry/riwayat.php
5. Cari riwayat memakai nomor HP pelanggan. Ubah status dari panel Admin > Pesanan.
   Status pada riwayat akan diperbarui maksimal sekitar 30 detik; notifikasi diperiksa
   setiap 5 detik.
6. Untuk notifikasi browser, klik tombol Aktifkan suara notifikasi dan izinkan notifikasi
   browser jika diminta. Suara browser biasanya memerlukan HTTPS atau localhost.

CATATAN:
- Sintaks semua file PHP telah diperiksa dengan php -l.
- Pengujian koneksi terhadap database lokal Anda tidak dapat dilakukan dari paket ini.
- Jika muncul pesan bahwa kolom inti tidak ditemukan, cocokkan struktur tabel dengan
  database yang sedang dipakai. Jangan menghapus database lama untuk mengatasi error.


PERBAIKAN LANJUTAN NOTIFIKASI (23 Oktober 2026)
- Endpoint membedakan pemuatan awal dari polling berkala, termasuk saat belum ada notifikasi (ID terakhir masih 0).
- Penyimpanan notifikasi pada panel Admin kini menampilkan error jika tabel/insert gagal, bukan diam-diam mengabaikannya.
- Handler mendukung kolom sudah_dibaca maupun dibaca dan kolom waktu created_at maupun waktu.
- SQL memastikan tabel notifikasi dibuat jika belum ada; tabel yang sudah ada tidak dihapus.
- Cache-buster JavaScript diperbarui agar browser mengambil file terbaru.

PENTING: buka phpMyAdmin, pilih db_laundry, lalu jalankan perbaikan_db_laundry.sql. Backup database sebelum menjalankan. Setelah itu hard refresh browser (Ctrl+F5), ubah status pesanan dari admin/pesanan.php, dan cek apakah notifikasi tampil. Jika admin menampilkan pesan gagal menyimpan notifikasi, kirimkan teks error tersebut.
