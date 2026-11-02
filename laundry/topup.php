<?php
require_once __DIR__.'/config/koneksi.php';
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
$msg='';$err='';$hp='';$amount='';$method='cash';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $hp=trim($_POST['no_hp']??'');
  $amount=(float)($_POST['nominal']??0);
  $method=$_POST['metode']??'cash';
  
  if($hp==='' || $amount<10000){
    $err='Nomor HP dan nominal minimal Rp 10.000 wajib diisi.';
  } elseif(!in_array($method,['cash','qris'],true)){
    $err='Metode top up tidak valid.';
  } else {
    $s=$conn->prepare("SELECT id_user,nama FROM users WHERE no_hp=? AND role='pelanggan' LIMIT 1");
    $s->bind_param('s',$hp);
    $s->execute();
    $u=$s->get_result()->fetch_assoc();
    if(!$u){
      $err='Nomor HP belum terdaftar sebagai pelanggan. Buat pesanan terlebih dahulu agar dompet akun terbentuk.';
    } else {
      $s=$conn->prepare("SELECT id_dompet,saldo FROM dompet WHERE id_user=? LIMIT 1");
      $s->bind_param('i',$u['id_user']);
      $s->execute();
      $wallet=$s->get_result()->fetch_assoc();
      if(!$wallet){
        $s=$conn->prepare("INSERT INTO dompet(id_user,saldo) VALUES(?,0)");
        $s->bind_param('i',$u['id_user']);
        $s->execute();
        $wallet=['id_dompet'=>$conn->insert_id,'saldo'=>0];
      }
      $before=(float)$wallet['saldo'];
      $ref='TOP-'.date('YmdHis').'-'.random_int(100,999);
      $status='menunggu';
      $jenis='topup';
      $s=$conn->prepare("INSERT INTO transaksi_dompet(id_dompet,jenis,nominal,saldo_sebelum,saldo_sesudah,metode,status,reference_no) VALUES(?,?,?,?,?,?,?,?)");
      $s->bind_param('isdddsss',$wallet['id_dompet'],$jenis,$amount,$before,$before,$method,$status,$ref);
      $s->execute();
      $msg='Pengajuan top up sebesar '.rp($amount).' berhasil dibuat dengan kode transaksi '.$ref.'. Saldo akan bertambah setelah diverifikasi oleh Admin atau Kasir.';
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengajuan Isi Saldo — LaundryKu</title>
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
      <a href="saldo.php">Cek Saldo</a>
      <a href="tracking.php">Lacak</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<main class="container narrow">
  <div class="panel">
    <span class="pill">Isi Saldo Dompet</span>
    <h1 style="margin:12px 0 6px">Top Up Saldo</h1>
    <p class="muted">Isi saldo dulu biar bayar cucian gak ribet, gak perlu cash.</p>

    <?php if($msg): ?>
      <div class="success">
        <?=htmlspecialchars($msg)?>
        <div style="margin-top:10px">
          <a href="saldo.php" class="btn" style="padding:8px 16px;font-size:13px">Periksa Saldo Saya</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if($err): ?>
      <div class="alert"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <form method="post">
      <label for="no_hp">Nomor WhatsApp</label>
      <input id="no_hp" name="no_hp" value="<?=htmlspecialchars($hp)?>" placeholder="08xxxxxxxxxx" inputmode="tel" required>

      <label for="nominal">Mau isi berapa? (min. Rp 10.000)</label>
      <input type="number" id="nominal" name="nominal" min="10000" step="1000" value="<?=htmlspecialchars($amount)?>" placeholder="Contoh: 50000" required>
      
      <!-- Preset buttons -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
        <button type="button" class="small-btn" onclick="document.getElementById('nominal').value=20000">+ Rp 20.000</button>
        <button type="button" class="small-btn" onclick="document.getElementById('nominal').value=50000">+ Rp 50.000</button>
        <button type="button" class="small-btn" onclick="document.getElementById('nominal').value=100000">+ Rp 100.000</button>
      </div>

      <label for="metodeTopup">Bayar top up pakai apa?</label>
      <select name="metode" id="metodeTopup">
        <option value="cash" <?=$method==='cash'?'selected':''?>>Cash / Tunai (Bayar langsung ke Kasir Outlet)</option>
        <option value="qris" <?=$method==='qris'?'selected':''?>>QRIS (Scan instan e-wallet / m-banking)</option>
      </select>

      <div id="qrisTopup" class="qris-box" style="display:none">
        <div class="qris-title">Scan QRIS LaundryKu</div>
        <p class="muted">Transfer sesuai nominal ke QRIS ini ya. Admin atau Kasir bakal verifikasi top upmu.</p>
        <img src="assets/images/qris.png" alt="Kode QR pembayaran QRIS" class="qris-img">
      </div>

      <button class="btn full" type="submit" style="margin-top:24px">
        Ajukan Pengisian Saldo
      </button>
    </form>
  </div>
</main>

<script>
const m = document.getElementById('metodeTopup');
const q = document.getElementById('qrisTopup');
function updateQris(){
  if (m && q) q.style.display = (m.value === 'qris') ? 'block' : 'none';
}
if (m) m.onchange = updateQris;
updateQris();
</script>

<script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
</body>
</html>
