LaundryKu tetap menggunakan localhost/laundry/. Pelanggan tidak login. Admin dan Kasir login. Kurir memakai kode akses tugas tanpa login. Pengelolaan pesanan: admin/pesanan.php.


V12 FIX: migrasi token_akses dibuat idempoten sehingga tidak mengulang ALTER TABLE jika kolom sudah ada.


VERSI LENGKAP TERBARU
- Tetap gunakan C:\xampp\htdocs\laundry
- Database tetap db_laundry
- Chatbot sudah memiliki tombol Kirim lokasi saya.
- Jangan import ulang database.


PENYEMPURNAAN UI/UX
- Tampilan diperbarui dengan gaya visual yang konsisten, tipografi lebih rapi, kartu layanan, navigasi, formulir, tabel, dan panel admin yang lebih bersih.
- Responsif untuk desktop, tablet, dan ponsel; ditambahkan indikator fokus keyboard dan dukungan reduced motion.
- Fungsi PHP dan struktur database tidak diubah. Seluruh file PHP telah lolos pemeriksaan sintaks.

CARA MENJALANKAN
1. Ekstrak folder laundry ke C:\\xampp\\htdocs\\laundry.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Pastikan database db_laundry yang sudah ada tetap tersedia; tidak perlu mengimpor ulang database.
4. Buka http://localhost/laundry/.

AKUN UJI LOKAL
Buka: http://localhost/laundry/auth/login.php

FITUR LAYANAN & VOUCHER (pembaruan 18 Oktober 2026)
- Halaman Admin > Kelola Layanan & Voucher: admin dapat mengubah tarif dan status aktif/nonaktif untuk setiap layanan.
- Tiga layanan yang disiapkan: Cuci Kering, Cuci Setrika, Setrika Aja.
- Tarif awal merupakan CONTOH dan bisa diubah oleh admin: Rp7.000/kg, Rp10.000/kg, Rp6.000/kg.
- Voucher contoh HEMAT10: diskon 10%, maksimum Rp10.000, minimum transaksi Rp0. Kode dapat dimasukkan saat checkout.
- Jika layanan atau voucher belum ada, sistem menyiapkan data awal sekali ketika halaman publik dibuka. Data yang sudah ada tidak dihapus.
- Setelah menyalin folder pembaruan ke htdocs/laundry, buka http://localhost/laundry/ lalu login admin dan buka http://localhost/laundry/admin/layanan_voucher.php.
- Penggunaan voucher dihitung di sisi server saat pesanan dikirim. Diskon akan terlihat pada pesan konfirmasi pesanan.
