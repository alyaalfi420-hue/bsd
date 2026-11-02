<?php
require_once __DIR__.'/config/koneksi.php';
$saldo=null;$err='';$hp='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $hp=trim($_POST['no_hp']??'');
  $s=$conn->prepare("SELECT d.saldo,u.nama FROM dompet d JOIN users u ON u.id_user=d.id_user WHERE u.no_hp=? LIMIT 1");
  $s->bind_param('s',$hp);
  $s->execute();
  $x=$s->get_result()->fetch_assoc();
  if($x) $saldo=$x;
  else $err='Nomor HP belum memiliki dompet saldo atau belum pernah membuat pesanan.';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cek Saldo Laundry — LaundryKu</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme-pro.css?v=3">
</head>
<body>

<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="index.php">LaundryKu</a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="pesan.php">Order</a>
      <a href="tracking.php">Lacak</a>
      <a href="topup.php">Isi Saldo</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<main class="container narrow">
  <div class="panel">
    <span class="pill">Dompet Digital</span>
    <h1 style="margin:12px 0 6px">Intip Saldo Kamu</h1>
    <p class="muted">Masukin nomor WhatsApp yang kamu pakai pas order buat lihat sisa saldomu.</p>

    <form method="post">
      <label for="no_hp">Nomor WhatsApp</label>
      <input id="no_hp" name="no_hp" value="<?=htmlspecialchars($hp)?>" placeholder="Contoh: 081234567890" inputmode="tel" required>
      <button class="btn full" type="submit">Cek Saldo</button>
    </form>

    <?php if($err): ?>
      <div class="alert" style="margin-top:18px"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <?php if($saldo): ?>
      <div class="wallet large">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:14px;color:#d1d5db;font-weight:600">DOMPET PELANGGAN</span>
          <span class="badge red">LK Wallet</span>
        </div>
        <div style="margin-top:14px;font-size:16px;color:#ffffff">
          Halo, <b><?=htmlspecialchars($saldo['nama'])?></b>
        </div>
        <small style="color:#9ca3af">Saldo kamu sekarang</small>
        <strong>Rp <?=number_format($saldo['saldo'],0,',','.')?></strong>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
          <a class="btn" href="topup.php" style="padding:10px 18px;font-size:13px">
            Tambah Saldo (Top Up)
          </a>
          <a class="btn secondary" href="pesan.php" style="padding:10px 18px;font-size:13px">
            Pakai untuk Pesan Cucian
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
</body>
</html>
