<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/ensure_catalog.php';
$layanan = $conn->query("SELECT * FROM layanan WHERE status='aktif' ORDER BY id_layanan");
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LaundryKu — Laundry Anti Ribet, Auto Wangi</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme-pro.css?v=3">
</head>
<body>

<!-- Navigation Bar -->
<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="index.php">LaundryKu</a>
    <div class="nav-links">
      <a href="#layanan">Layanan</a>
      <a href="pesan.php">Order</a>
      <a href="tracking.php">Lacak</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="saldo.php">Saldo</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<div class="hero-wrapper">
  <section class="hero">
    <div>
      <span class="pill">Jemput di rumah, lacak live, bayar QRIS</span>
      <h1>Cucian numpuk? Serahin ke kami, <span>beres</span> tanpa drama.</h1>
      <p>Order laundry dari rumah aja, kurir yang jemput. Status cucian bisa kamu pantau live, bayarnya bisa QRIS atau saldo, dan ada voucher biar dompet tetap aman.</p>
      <div class="hero-actions">
        <a class="btn" href="pesan.php">Order Sekarang</a>
        <a class="btn secondary" href="tracking.php">Cek Cucianku</a>
      </div>
    </div>
    <div>
      <div class="hero-box">
        <div class="brand-badge" style="width:48px;height:48px;font-size:16px;margin-bottom:16px">LK</div>
        <h2>LaundryKu Express</h2>
        <p>Baju bersih, wangi, dan rapi. Kamu tinggal santai.</p>
        <div class="features">
          <span>Kiloan & Satuan</span>
          <span>Jemput Cepat</span>
          <span>Voucher Hemat</span>
          <span>Chatbot 24 Jam</span>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Layanan Kami -->
<section class="section" id="layanan">
  <div class="section-title">
    <h2>Pilih paket yang sesuai vibes cucianmu</h2>
    <p>Harganya jelas, gak ada biaya siluman.</p>
  </div>
  <div class="service-grid">
    <?php if($layanan && $layanan->num_rows > 0): ?>
      <?php while($x = $layanan->fetch_assoc()): 
        $initial = strtoupper(mb_substr($x['nama_layanan'], 0, 1));
      ?>
        <div class="service-card">
          <div class="service-icon"><?=$initial?></div>
          <h3><?=htmlspecialchars($x['nama_layanan'])?></h3>
          <div class="service-price"><?=rupiah($x['harga'])?><span style="font-size:14px;color:var(--muted);font-weight:400"> / <?=htmlspecialchars($x['satuan'])?></span></div>
          <p><?=htmlspecialchars($x['deskripsi'] ?: 'Bersih, higienis, wangi premium, dan rapi.')?></p>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto">
            <small style="color:var(--muted)">Selesai sekitar <?=((int)$x['estimasi_jam'])?> jam</small>
            <a href="pesan.php" class="small-btn primary">Gas order</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="muted" style="text-align:center;grid-column:1/-1">Belum ada paket layanan aktif saat ini.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Alur Pemesanan -->
<section class="section">
  <div class="section-title">
    <h2>Cucian beres dalam 4 langkah doang</h2>
    <p>Dari order sampai baju balik ke rumahmu.</p>
  </div>
  <ol class="steps" style="padding:0;margin:0">
    <li><b>Order online</b><span>Pilih paket, isi alamat jemput.</span></li>
    <li><b>Kurir meluncur</b><span>Cucian ditimbang, harga dikonfirmasi.</span></li>
    <li><b>Dicuci dan disetrika</b><span>Pantau terus pakai kode pesanan.</span></li>
    <li><b>Diantar balik</b><span>Bayar tunai, QRIS, atau saldo.</span></li>
  </ol>
</section>

<!-- Keunggulan Layanan -->
<section class="section">
  <div class="section-title">
    <h2>Kenapa harus LaundryKu?</h2>
    <p>Hasilnya konsisten, prosesnya transparan, kamu tenang.</p>
  </div>
  <div class="why-grid">
    <div class="feature-card"><div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg></div><b>Jemput antar, gak usah keluar</b><span class="muted">Kurir ambil baju kotor di depan pintu, lalu antar balik setelah rapi.</span></div>
    <div class="feature-card"><div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><b>Lacak real-time</b><span class="muted">Dari diterima, ditimbang, dicuci, disetrika, sampai selesai, semua kelihatan.</span></div>
    <div class="feature-card"><div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></div><b>Bayar sesukamu</b><span class="muted">Tunai pas serah terima, scan QRIS, atau pakai saldo LaundryKu.</span></div>
    <div class="feature-card"><div class="why-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg></div><b>Chatbot siaga</b><span class="muted">Mau tanya tarif, jangkauan jemput, atau promo? Tanya aja kapan pun.</span></div>
  </div>
</section>

<!-- Promo & Metode Pembayaran -->
<section class="section">
  <div class="section-title">
    <h2>Promo & cara bayar</h2>
    <p>Ada diskon, ada cara bayar yang gampang. Win-win.</p>
  </div>
  <div class="feature-grid">
    <a class="feature-card link-card" href="pesan.php#voucher">
      <b>Voucher Promo</b>
      <span>Masukin kode promo pas checkout, harga langsung turun.</span>
    </a>
    <a class="feature-card link-card" href="pesan.php#pembayaran">
      <b>Pembayaran Cash</b>
      <span>Bayar cash di outlet atau langsung ke kurir.</span>
    </a>
    <a class="feature-card link-card" href="pesan.php#pembayaran">
      <b>Scan QRIS Instan</b>
      <span>Bisa lewat GoPay, OVO, Dana, ShopeePay, dan semua m-banking.</span>
    </a>
    <a class="feature-card link-card" href="saldo.php">
      <b>Saldo LaundryKu</b>
      <span>Gak perlu receh. Isi saldo dulu, bayar jadi sat set.</span>
    </a>
  </div>
</section>

<!-- Menu Cepat Aplikasi -->
<section class="section">
  <div class="section-title">
    <h2>Mau ngapain hari ini?</h2>
    <p>Pilih menu yang kamu butuhin.</p>
  </div>
  <div class="feature-grid">
    <a class="feature-card link-card" href="pesan.php">
      <b>Order Baru</b>
      <span>Pilih paket, tentuin jumlahnya, kirim baju kotormu.</span>
    </a>
    <a class="feature-card link-card" href="tracking.php">
      <b>Lacak Cucian</b>
      <span>Ketik kode pesanan (LDR-) buat lihat cucianmu lagi di mana.</span>
    </a>
    <a class="feature-card link-card" href="riwayat.php">
      <b>Riwayat Pesanan</b>
      <span>Cukup masukin nomor HP, semua riwayat langsung muncul.</span>
    </a>
    <a class="feature-card link-card" href="chatbot.php">
      <b>Chatbot Bantuan</b>
      <span>Tanya tarif, radius jemput, atau apa pun soal laundry.</span>
    </a>
    <a class="feature-card link-card" href="topup.php">
      <b>Isi Saldo Dompet</b>
      <span>Isi saldo biar checkout makin ngebut.</span>
    </a>
    <a class="feature-card link-card" href="saldo.php">
      <b>Cek Saldo Anda</b>
      <span>Intip sisa saldomu kapan aja.</span>
    </a>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div>
      <b>LaundryKu</b>
      <span>Layanan Laundry Modern • Antar-Jemput • QRIS & Saldo • Tracking Cepat</span>
    </div>
    <div>
      <span>&copy; <?=date('Y')?> LaundryKu. All rights reserved.</span>
    </div>
  </div>
</footer>

</body>
</html>
