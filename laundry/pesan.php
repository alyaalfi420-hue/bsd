<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/ensure_catalog.php';
require_once __DIR__ . '/config/notifikasi_helper.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
laundry_ensure_schema($conn);
$conn->query("CREATE TABLE IF NOT EXISTS tarif_antar_jemput (id_tarif INT AUTO_INCREMENT PRIMARY KEY, jarak_min DECIMAL(6,2) NOT NULL DEFAULT 0, jarak_max DECIMAL(6,2) NOT NULL DEFAULT 0, minimal_kg DECIMAL(6,2) NOT NULL DEFAULT 0, tarif DECIMAL(12,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'aktif') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cntTarif=$conn->query("SELECT COUNT(*) c FROM tarif_antar_jemput");
if($cntTarif&&(int)$cntTarif->fetch_assoc()['c']===0){
  $conn->query("INSERT INTO tarif_antar_jemput(jarak_min,jarak_max,minimal_kg,tarif,status) VALUES (0,3,2,5000,'aktif'),(3,6,3,8000,'aktif'),(6,10,5,12000,'aktif')");
}
$sessUser=null;
if(isset($_SESSION['id_user'])&&($_SESSION['role']??'')==='pelanggan'){
  $q=$conn->prepare('SELECT id_user,nama,email,no_hp FROM users WHERE id_user=? LIMIT 1');
  $sid=(int)$_SESSION['id_user'];
  $q->bind_param('i',$sid);
  $q->execute();
  $sessUser=$q->get_result()->fetch_assoc();
  $q->close();
}
function rupiah($n){return 'Rp '.number_format((float)$n,0,',','.');}
$msg='';$err='';$kodeHasil='';
if(isset($_SESSION['flash'])){
  $msg=(string)$_SESSION['flash']['msg'];
  $kodeHasil=(string)$_SESSION['flash']['kode'];
  unset($_SESSION['flash']);
}
$layananRows=$conn->query("SELECT * FROM layanan WHERE LOWER(TRIM(status))='aktif' ORDER BY id_layanan")->fetch_all(MYSQLI_ASSOC);
$rt=$conn->query("SELECT * FROM tarif_antar_jemput WHERE status='aktif' ORDER BY jarak_min");
$tarif=$rt?$rt->fetch_all(MYSQLI_ASSOC):[];
$vouchers=$conn->query("SELECT * FROM voucher WHERE status='aktif' AND tanggal_mulai<=NOW() AND tanggal_berakhir>=NOW() ORDER BY id_voucher")->fetch_all(MYSQLI_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  $nama=trim($_POST['nama']??'');
  $email=trim($_POST['email']??'');
  $hp=trim($_POST['no_hp']??'');
  $serviceIds=$_POST['service_ids']??[];
  $quantities=$_POST['quantities']??[];
  $berat=(float)($_POST['berat_estimasi']??0);
  $metodePengirimanInput=$_POST['metode_pengiriman']??'datang_sendiri';
  $alamat=trim($_POST['alamat']??'');
  $lat=(float)($_POST['latitude']??0);
  $lng=(float)($_POST['longitude']??0);
  $metodeBayar=$_POST['metode_bayar']??'cash';
  $kodeVoucher=strtoupper(trim($_POST['kode_voucher']??''));

  if(!in_array($metodeBayar,['cash','qris','saldo'],true))$metodeBayar='cash';
  if($nama===''||$hp===''||$berat<=0||!is_array($serviceIds)||count($serviceIds)===0){
    $err='Lengkapi data pelanggan, perkiraan berat, dan minimal satu layanan.';
  }
  $items=[];$subtotal=0;$maxJam=0;
  if($err===''){
    foreach($serviceIds as $i=>$sid){
      $sid=(int)$sid;
      $qty=(float)($quantities[$i]??0);
      if($sid<=0||$qty<=0)continue;
      $st=$conn->prepare("SELECT * FROM layanan WHERE id_layanan=? AND status='aktif'");
      $st->bind_param('i',$sid);
      $st->execute();
      $sv=$st->get_result()->fetch_assoc();
      if(!$sv){$err='Ada layanan yang tidak tersedia.';break;}
      $sub=$sv['harga']*$qty;
      $subtotal+=$sub;
      $maxJam=max($maxJam,(int)$sv['estimasi_jam']);
      $items[]=['id'=>$sid,'nama'=>$sv['nama_layanan'],'qty'=>$qty,'harga'=>(float)$sv['harga'],'sub'=>$sub,'satuan'=>$sv['satuan']];
    }
    if(!$items && $err==='')$err='Keranjang masih kosong. Tambahkan layanan ke keranjang.';
  }
  $ongkir=0;$jarak=0;
  if($err===''&&$metodePengirimanInput==='antar_jemput'){
    if($alamat==='')$err='Alamat lengkap wajib diisi untuk layanan antar-jemput.';
    elseif($lat==0||$lng==0)$err='Tekan tombol "Pakai Lokasiku" (atau isi koordinat manual) agar jarak penjemputan bisa dihitung.';
    else{
      require __DIR__.'/config/outlet.php';$outLat=(float)$OUTLET_LAT;$outLng=(float)$OUTLET_LNG;$rad=pi()/180;
      $dlat=($lat-$outLat)*$rad;$dlng=($lng-$outLng)*$rad;
      $a=sin($dlat/2)**2+cos($outLat*$rad)*cos($lat*$rad)*sin($dlng/2)**2;
      $jarak=6371*2*atan2(sqrt($a),sqrt(1-$a));
      $found=null;
      foreach($tarif as $t){
        if($jarak>=$t['jarak_min']&&$jarak<=$t['jarak_max']){$found=$t;break;}
      }
      if(!$found){
        $maxJ=0;foreach($tarif as $t)$maxJ=max($maxJ,(float)$t['jarak_max']);
        $err='Lokasi penjemputan berada di luar jangkauan area antar-jemput kami. Jarak terhitung dari outlet: '.number_format($jarak,1,',','.').' km, jangkauan maksimal: '.number_format($maxJ,1,',','.').' km. Jika jarak ini terlalu jauh dari kenyataan, koordinat outlet di config/outlet.php belum sesuai.';
      }
      elseif($berat<$found['minimal_kg'])$err='Berat minimal untuk antar-jemput area ini adalah '.$found['minimal_kg'].' kg.';
      else $ongkir=$found['tarif'];
    }
  }
  $diskon=0;$voucherRow=null;
  if($err===''&&$kodeVoucher!==''){
    $st=$conn->prepare("SELECT * FROM voucher WHERE kode=? AND status='aktif' AND tanggal_mulai<=NOW() AND tanggal_berakhir>=NOW() LIMIT 1");
    $st->bind_param('s',$kodeVoucher);
    $st->execute();
    $voucherRow=$st->get_result()->fetch_assoc();
    if(!$voucherRow)$err='Kode voucher tidak ditemukan atau sudah kedaluwarsa.';
    elseif($subtotal+$ongkir<(float)$voucherRow['minimal_transaksi'])$err='Minimal transaksi untuk voucher ini adalah '.rupiah($voucherRow['minimal_transaksi']).'.';
    else{
      $diskon=$voucherRow['jenis_diskon']==='nominal'?(float)$voucherRow['nilai_diskon']:(($subtotal+$ongkir)*(float)$voucherRow['nilai_diskon']/100);
      if($voucherRow['maksimal_diskon']!==null)$diskon=min($diskon,(float)$voucherRow['maksimal_diskon']);
      $diskon=min($diskon,$subtotal+$ongkir);
    }
  }
  if($err===''){
    $total=max(0,$subtotal+$ongkir-$diskon);
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    try{
      $uid=0;
      if($sessUser){$uid=(int)$sessUser['id_user'];}
      else{
        $ids=laundry_user_ids_by_phone($conn,$hp);
        if($ids)$uid=$ids[0];
      }
      if(!$uid){
        $email=$email!==''?$email:'guest'.time().rand(10,99).'@laundry.local';
        $password=password_hash('guest123',PASSWORD_DEFAULT);
        $st=$conn->prepare("INSERT INTO users(nama,email,no_hp,password,role) VALUES(?,?,?,?, 'pelanggan')");
        $st->bind_param('ssss',$nama,$email,$hp,$password);
        $st->execute();
        $uid=$conn->insert_id;
        $st=$conn->prepare("INSERT INTO dompet(id_user,saldo) VALUES(?,0)");
        $st->bind_param('i',$uid);
        $st->execute();
      }
      $st=$conn->prepare("SELECT id_dompet,saldo FROM dompet WHERE id_user=? LIMIT 1 FOR UPDATE");
      $st->bind_param('i',$uid);
      $st->execute();
      $wallet=$st->get_result()->fetch_assoc();
      if(!$wallet){
        $st=$conn->prepare("INSERT INTO dompet(id_user,saldo) VALUES(?,0)");
        $st->bind_param('i',$uid);
        $st->execute();
        $wallet=['id_dompet'=>$conn->insert_id,'saldo'=>0];
      }
      if($metodeBayar==='saldo'&&(float)$wallet['saldo']<$total){
        throw new Exception('Saldo tidak mencukupi. Saldo Anda saat ini '.rupiah($wallet['saldo']).'. Silakan isi saldo terlebih dahulu.');
      }
      $kode='LDR-'.date('Ymd-His').'-'.random_int(100,999);
      $estimasi=date('Y-m-d H:i:s',time()+($maxJam*3600));
      $status='menunggu';
      $cat='Atas nama: '.$nama.' | HP: '.$hp.' | Pembayaran: '.$metodeBayar.'.';
      
      $st=$conn->prepare("INSERT INTO pesanan(kode_pesanan,id_user,berat_estimasi,metode_pengiriman,subtotal,ongkir,diskon,total_estimasi,estimasi_selesai,status_pesanan,catatan) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
      $st->bind_param('sidsddddsss',$kode,$uid,$berat,$metodePengirimanInput,$subtotal,$ongkir,$diskon,$total,$estimasi,$status,$cat);
      $st->execute();
      $pid=$conn->insert_id;

      $st=$conn->prepare("INSERT INTO detail_pesanan(id_pesanan,id_layanan,jumlah,harga,subtotal) VALUES(?,?,?,?,?)");
      foreach($items as $it){
        $st->bind_param('iiddd',$pid,$it['id'],$it['qty'],$it['harga'],$it['sub']);
        $st->execute();
      }

      $trk='menunggu';
      $ket='Pesanan berhasil dibuat melalui website. Estimasi total '.rupiah($total).'.';
      $st=$conn->prepare("INSERT INTO tracking_pesanan(id_pesanan,status,keterangan) VALUES(?,?,?)");
      $st->bind_param('iss',$pid,$trk,$ket);
      $st->execute();

      if($voucherRow){
        $voucherUsageTable=$conn->query("SHOW TABLES LIKE 'voucher_pelanggan'");
        if($voucherUsageTable && $voucherUsageTable->num_rows>0){
          $vid=(int)$voucherRow['id_voucher'];
          $st=$conn->prepare("SELECT id_voucher_user FROM voucher_pelanggan WHERE id_voucher=? AND id_user=? AND status='tersedia' LIMIT 1");
          if($st){
            $st->bind_param('ii',$vid,$uid);
            $st->execute();
            $vu=$st->get_result()->fetch_assoc();
            $st->close();
            if($vu){
              $vuid=(int)$vu['id_voucher_user'];
              $st=$conn->prepare("UPDATE voucher_pelanggan SET status='digunakan',id_pesanan_penggunaan=?,digunakan_pada=NOW() WHERE id_voucher_user=?");
              if($st){$st->bind_param('ii',$pid,$vuid);$st->execute();$st->close();}
            }
          }
        }
      }

      if($metodePengirimanInput==='antar_jemput'){
        $st=$conn->prepare("INSERT INTO alamat_pelanggan(id_user,label_alamat,alamat_lengkap,latitude,longitude,is_utama) VALUES(?,?,?,?,?,1)");
        $label='Alamat Pesanan';
        $st->bind_param('issdd',$uid,$label,$alamat,$lat,$lng);
        $st->execute();
        $aid=$conn->insert_id;
        $jenis='jemput';
        $statusAJ='menunggu';
        $st=$conn->prepare("INSERT INTO antar_jemput(id_pesanan,id_alamat,jenis,latitude,longitude,jarak_km,berat_kg,ongkos,status) VALUES(?,?,?,?,?,?,?,?,?)");
        $st->bind_param('iisddddds',$pid,$aid,$jenis,$lat,$lng,$jarak,$berat,$ongkir,$statusAJ);
        $st->execute();
      }

      $payStatus=$metodeBayar==='saldo'?'lunas':'menunggu';
      $ref='PAY-'.date('YmdHis').'-'.random_int(100,999);
      $paidAt=$metodeBayar==='saldo'?date('Y-m-d H:i:s'):null;
      $st=$conn->prepare("INSERT INTO pembayaran(id_pesanan,metode,jumlah,status,reference_no,paid_at) VALUES(?,?,?,?,?,?)");
      $st->bind_param('isdsss',$pid,$metodeBayar,$total,$payStatus,$ref,$paidAt);
      $st->execute();

      if($metodeBayar==='saldo'){
        $before=(float)$wallet['saldo'];
        $after=$before-$total;
        $did=(int)$wallet['id_dompet'];
        $st=$conn->prepare("UPDATE dompet SET saldo=? WHERE id_dompet=?");
        $st->bind_param('di',$after,$did);
        $st->execute();
        $jenis='pembayaran';
        $ok='berhasil';
        $st=$conn->prepare("INSERT INTO transaksi_dompet(id_dompet,id_pesanan,jenis,nominal,saldo_sebelum,saldo_sesudah,metode,status,reference_no) VALUES(?,?,?,?,?,?,?,?,?)");
        $st->bind_param('iisdddsss',$did,$pid,$jenis,$total,$before,$after,$metodeBayar,$ok,$ref);
        $st->execute();
      }

      $conn->commit();
      mysqli_report(MYSQLI_REPORT_OFF);
      try{laundry_notif_status($conn,$uid,$pid,'menunggu',$kode);}catch(Throwable $ne){}
      
      $kodeHasil=$kode;
      $_SESSION['pesanan_saya']=array_slice(array_merge([(int)$pid],$_SESSION['pesanan_saya']??[]),0,30);
      if(!$sessUser)$_SESSION['guest_hp']=$hp;
      $langkah=['cash'=>'Siapkan uang tunai '.rupiah($total).' dan bayar ke kurir atau kasir. Status berubah Lunas setelah kasir mengonfirmasi.','qris'=>'Scan QRIS sebesar '.rupiah($total).', lalu tunjukkan bukti bayar ke kasir. Status berubah Lunas setelah kasir mengonfirmasi.','saldo'=>'Pembayaran sudah Lunas dan saldo otomatis terpotong.'][$metodeBayar];
      $msg='Pesanan berhasil dibuat dengan kode '.$kode.'. '.($diskon>0?'Hemat voucher: -'.rupiah($diskon).'. ':'').'Estimasi total '.rupiah($total).'. '.$langkah;
      $_SESSION['flash']=['msg'=>$msg,'kode'=>$kodeHasil];
      header('Location: pesan.php#pesanan-saya');
      exit;
    }catch(Throwable $e){
      mysqli_report(MYSQLI_REPORT_OFF);
      $conn->rollback();
      $err=($e instanceof mysqli_sql_exception?'Pesanan gagal disimpan (data tidak dibuat). Detail: ':'').($e->getMessage()?:'Pesanan gagal diproses.');
    }
  }
}

$myOrders=[];
$ids=[];
if($sessUser){
  $ids=[(int)$sessUser['id_user']];
  if(trim((string)$sessUser['no_hp'])!=='')$ids=array_values(array_unique(array_merge($ids,laundry_user_ids_by_phone($conn,(string)$sessUser['no_hp']))));
} elseif(!empty($_SESSION['guest_hp'])){
  $ids=laundry_user_ids_by_phone($conn,(string)$_SESSION['guest_hp']);
}
$own=array_map('intval',$_SESSION['pesanan_saya']??[]);
$w=[];
if($ids)$w[]='p.id_user IN ('.implode(',',array_map('intval',$ids)).')';
if($own)$w[]='p.id_pesanan IN ('.implode(',',$own).')';
if($w){
  $rs=$conn->query("SELECT p.id_pesanan,p.kode_pesanan,p.status_pesanan,p.tanggal_pesanan,COALESCE(p.total_final,p.total_estimasi) total FROM pesanan p WHERE ".implode(' OR ',$w)." ORDER BY p.id_pesanan DESC LIMIT 8");
  if($rs)$myOrders=$rs->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pesan Laundry — LaundryKu</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme-pro.css?v=3">
</head>
<body>

<!-- Navigation Bar -->
<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="index.php">LaundryKu</a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="tracking.php">Lacak</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="saldo.php">Saldo</a>
      <a href="topup.php">Isi Saldo</a>
      <a href="riwayat.php">Riwayat</a>
      <?php if($sessUser): ?>
        <a href="pelanggan/index.php">Dashboard</a>
        <a href="auth/logout.php" class="admin-link">Keluar</a>
      <?php else: ?>
        <a href="auth/login.php" class="admin-link">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container">
  <div class="order-layout">
    
    <!-- Left Column: Form & Services -->
    <div class="panel">
      <span class="pill">Formulir Pemesanan</span>
      <h1 style="margin:12px 0 6px">Order Laundry</h1>
      <p class="muted">Pilih paket, masukin keranjang, lalu atur pengiriman dan pembayaran. Gampang kok.</p>

      <?php if($msg): ?>
        <div class="success">
          <?=htmlspecialchars($msg)?>
          <div style="margin-top:8px">
            <a href="tracking.php?kode=<?=urlencode($kodeHasil)?>" class="btn" style="padding:8px 16px;font-size:13px">
              Lacak Status Pesanan Ini
            </a>
          </div>
        </div>
      <?php endif; ?>

      <?php if($err): ?>
        <div class="alert"><?=htmlspecialchars($err)?></div>
      <?php endif; ?>

      <form method="post" id="orderForm">
        <label for="nama">Nama kamu</label>
        <input id="nama" name="nama" required value="<?=htmlspecialchars($_POST['nama']??($sessUser['nama']??''))?>" placeholder="Nama pemesan">

        <label for="no_hp">Nomor WhatsApp</label>
        <input id="no_hp" name="no_hp" required inputmode="tel" value="<?=htmlspecialchars($_POST['no_hp']??($sessUser['no_hp']??''))?>" placeholder="08xxxxxxxxxx">

        <label for="email">Email (boleh dikosongin)</label>
        <input id="email" type="email" name="email" value="<?=htmlspecialchars($_POST['email']??($sessUser['email']??''))?>" placeholder="nama@email.com">

        <!-- Service Picker & Cart Builder -->
        <div class="cart-builder service-picker-panel">
          <div class="service-picker-heading">
            <div>
              <span class="section-kicker">LANGKAH 1 DARI 3</span>
              <h2>Pilih paketnya dulu</h2>
              <p class="muted">Klik paket di bawah, isi jumlahnya, lalu tekan <b>Masukin Keranjang</b>.</p>
            </div>
            <span class="service-count"><?=count($layananRows)?> Layanan Aktif</span>
          </div>

          <?php if(count($layananRows) > 0): ?>
            <div class="service-choice-grid" id="serviceChoiceGrid">
              <?php foreach($layananRows as $x): ?>
                <button type="button" class="service-choice" data-service-id="<?=$x['id_layanan']?>" aria-pressed="false" onclick="chooseService('<?=$x['id_layanan']?>')">
                  <span class="service-choice-icon" aria-hidden="true"><?=htmlspecialchars(mb_strtoupper(mb_substr($x['nama_layanan'], 0, 1)))?></span>
                  <span class="service-choice-name"><?=htmlspecialchars($x['nama_layanan'])?></span>
                  <strong class="service-choice-price"><?=rupiah($x['harga'])?><small>/<?=htmlspecialchars($x['satuan'])?></small></strong>
                  <?php if(isset($x['estimasi_jam'])): ?>
                    <span class="service-choice-time"><?=$x['estimasi_jam']?> jam</span>
                  <?php endif; ?>
                  <span class="service-choice-check">Pilih Layanan</span>
                </button>
              <?php endforeach; ?>
            </div>

            <div class="service-select-row">
              <label for="servicePicker">Paket pilihanmu & jumlahnya</label>
              <div class="add-row">
                <select id="servicePicker" required>
                  <option value="">-- Pilih layanan dari daftar --</option>
                  <?php foreach($layananRows as $x): ?>
                    <option value="<?=$x['id_layanan']?>" data-name="<?=htmlspecialchars($x['nama_layanan'],ENT_QUOTES)?>" data-price="<?=$x['harga']?>" data-unit="<?=htmlspecialchars($x['satuan'],ENT_QUOTES)?>">
                      <?=htmlspecialchars($x['nama_layanan'])?> — <?=rupiah($x['harga'])?> / <?=htmlspecialchars($x['satuan'])?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input type="number" id="qtyPicker" min="0.1" step="0.1" value="1" aria-label="Jumlah layanan" placeholder="Qty">
                <button type="button" class="btn" onclick="addToCart()">Masukin Keranjang</button>
              </div>
            </div>
          <?php else: ?>
            <div class="cart-empty">Belum ada layanan aktif yang dapat dipilih saat ini.</div>
          <?php endif; ?>

          <div class="cart-heading">
            <h3>Keranjang Kamu</h3>
            <span id="cartCount">0 item</span>
          </div>

          <div id="cartEmpty" class="cart-empty muted">
            Keranjang masih kosong. Pilih layanan di atas lalu tekan Tambah ke Keranjang.
          </div>

          <div class="table-wrap">
            <table id="cartTable" style="display:none">
              <thead>
                <tr>
                  <th>Layanan</th>
                  <th>Jumlah</th>
                  <th>Harga Satuan</th>
                  <th>Subtotal</th>
                  <th style="text-align:center">Aksi</th>
                </tr>
              </thead>
              <tbody id="cartBody"></tbody>
            </table>
          </div>
          <div id="hiddenItems"></div>
        </div>

        <!-- Weight & Delivery -->
        <label for="berat">Perkiraan berat total (kg)</label>
        <input type="number" step="0.1" min="0.1" name="berat_estimasi" id="berat" required value="<?=htmlspecialchars($_POST['berat_estimasi']??'')?>" placeholder="Contoh: 3.5">
        <small class="muted" style="display:block;margin-top:-4px;margin-bottom:12px">Berat asli bakal ditimbang ulang pas cucian sampai di outlet.</small>

        <label for="metode">Mau dijemput atau antar sendiri?</label>
        <select name="metode_pengiriman" id="metode">
          <option value="datang_sendiri" <?=(($_POST['metode_pengiriman']??'')==='datang_sendiri'?'selected':'')?>>Datang Sendiri ke Outlet</option>
          <option value="antar_jemput" <?=(($_POST['metode_pengiriman']??'')==='antar_jemput'?'selected':'')?>>Layanan Antar-Jemput Kurir</option>
        </select>

        <div id="pickupFields" style="display:none;margin-top:16px;padding:18px;border:1.5px solid var(--brand-border);background:#fff9fa;border-radius:var(--radius-lg)">
          <label for="alamat" style="margin-top:0">Alamat jemput</label>
          <textarea name="alamat" id="alamat" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, patokan biar kurir gak nyasar"><?=htmlspecialchars($_POST['alamat']??'')?></textarea>

          <label>Titik lokasi GPS (buat hitung jarak & ongkir)</label>
          <div class="row">
            <input name="latitude" id="lat" placeholder="Latitude" readonly value="<?=htmlspecialchars($_POST['latitude']??'')?>">
            <input name="longitude" id="lng" placeholder="Longitude" readonly value="<?=htmlspecialchars($_POST['longitude']??'')?>">
            <button type="button" class="btn secondary" onclick="getLocation()">Pakai Lokasiku</button>
          </div>
          <small class="muted" style="display:block;margin-top:6px">Pastikan izin lokasi di browsermu udah diizinin ya.</small>
        </div>

        <!-- Voucher Promo -->
        <section id="voucher" class="checkout-box">
          <h2>Voucher & Diskon</h2>
          <p class="muted">Pakai kode voucher biar dapat potongan harga.</p>
          <?php if(count($vouchers) > 0): ?>
            <div class="voucher-list">
              <?php foreach($vouchers as $v): ?>
                <button type="button" class="voucher-card" onclick="useVoucher('<?=htmlspecialchars($v['kode'],ENT_QUOTES)?>')">
                  <b><?=htmlspecialchars($v['kode'])?></b>
                  <strong><?=htmlspecialchars($v['nama'])?></strong>
                  <span><?=htmlspecialchars($v['deskripsi'])?></span>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <input name="kode_voucher" id="kodeVoucher" placeholder="Ketik kode voucher (contoh: HEMAT10)" value="<?=htmlspecialchars($_POST['kode_voucher']??'')?>">
        </section>

        <!-- Payment Method -->
        <section id="pembayaran" class="checkout-box">
          <h2>Metode Pembayaran</h2>
          <p class="muted">Pilih cara bayar yang paling nyaman buat kamu.</p>
          <div class="payment-grid">
            <label class="pay-option">
              <input type="radio" name="metode_bayar" value="cash" <?=(($_POST['metode_bayar']??'cash')==='cash'?'checked':'')?>>
              <span>
                <b>Cash (Tunai)</b>
                <small>Bayar pas serah terima di outlet atau ke kurir.</small>
              </span>
            </label>
            <label class="pay-option">
              <input type="radio" name="metode_bayar" value="qris" <?=(($_POST['metode_bayar']??'')==='qris'?'checked':'')?>>
              <span>
                <b>QRIS Instan</b>
                <small>Scan QRIS, nanti petugas yang konfirmasi.</small>
              </span>
            </label>
            <label class="pay-option">
              <input type="radio" name="metode_bayar" value="saldo" <?=(($_POST['metode_bayar']??'')==='saldo'?'checked':'')?>>
              <span>
                <b>Saldo LaundryKu</b>
                <small>Langsung kepotong dari saldomu.</small>
              </span>
            </label>
          </div>

          <div id="qris-box" class="qris-box" style="display:none">
            <div class="qris-title">Scan QRIS LaundryKu</div>
            <p class="muted">Scan QR di bawah pakai BCA, Mandiri, BRI, GoPay, OVO, Dana, atau ShopeePay.</p>
            <img src="assets/images/qris.png" alt="Kode QR pembayaran LaundryKu" class="qris-img">
            <small class="muted">Simpan bukti bayarmu buat jaga-jaga ya.</small>
          </div>
        </section>

        <!-- Summary & Submit -->
        <div class="summary">
          <b>Estimasi Subtotal Keranjang</b>
          <div id="totalPreview">Rp 0</div>
          <p>Catatan: total akhir bisa berubah setelah cucian ditimbang di outlet.</p>
        </div>

        <button class="btn full" type="submit" style="font-size:16px;padding:15px">
          Buat & Konfirmasi Pesanan
        </button>
      </form>
    </div>

    <!-- Right Column: Sidebar Cart & History -->
    <aside>
      <div class="panel">
        <h2 style="font-size:18px;margin-bottom:12px">Rangkuman Keranjang</h2>
        <div id="sideCart" class="side-cart">Belum ada layanan yang ditambahkan.</div>
      </div>

      <div class="panel" id="pesanan-saya" style="margin-top:20px">
        <h2 style="font-size:18px;margin-bottom:12px">Pesanan Terakhir Anda</h2>
        <?php if(!$myOrders): ?>
          <p class="muted" style="margin:0;font-size:13px">Belum ada pesanan aktif. Pesanan yang Anda buat akan langsung muncul di sini.</p>
        <?php else: ?>
          <?php foreach($myOrders as $mo): ?>
            <a class="side-cart-item" href="tracking.php?kode=<?=urlencode($mo['kode_pesanan'])?>" style="text-decoration:none;display:block">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <b style="color:var(--brand)"><?=htmlspecialchars($mo['kode_pesanan'])?></b>
                <span class="status-pill <?=htmlspecialchars($mo['status_pesanan'])?>" style="font-size:11px;padding:3px 8px">
                  <?=htmlspecialchars(ucwords(str_replace('_',' ',$mo['status_pesanan'])))?>
                </span>
              </div>
              <span style="font-size:13px"><?=rupiah($mo['total'])?></span>
              <small class="muted"><?=date('d/m/Y H:i',strtotime($mo['tanggal_pesanan']))?></small>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="panel" style="margin-top:20px">
        <h2 style="font-size:18px;margin-bottom:12px">Info Pembayaran</h2>
        <div class="method-line">
          <b>Cash</b>
          <span>Bayar di tempat</span>
        </div>
        <div class="method-line">
          <b>QRIS</b>
          <span>Verifikasi cepat</span>
        </div>
        <div class="method-line">
          <b>Saldo</b>
          <span>Otomatis terpotong</span>
        </div>
      </div>
    </aside>

  </div>
</main>

<script>
<?php
$initCart=[];
if($_SERVER['REQUEST_METHOD']==='POST'&&$err!==''&&is_array($_POST['service_ids']??null)){
  $byId=[];foreach($layananRows as $lr)$byId[(string)$lr['id_layanan']]=$lr;
  foreach($_POST['service_ids'] as $i=>$sid){
    $q=(float)($_POST['quantities'][$i]??0);
    if(isset($byId[(string)$sid])&&$q>0)$initCart[]=['id'=>(string)$sid,'name'=>$byId[(string)$sid]['nama_layanan'],'price'=>(float)$byId[(string)$sid]['harga'],'unit'=>$byId[(string)$sid]['satuan'],'qty'=>$q];
  }
}
?>
let cart = <?=json_encode($initCart,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
const picker = document.getElementById('servicePicker');
const qty = document.getElementById('qtyPicker');
const body = document.getElementById('cartBody');
const table = document.getElementById('cartTable');
const empty = document.getElementById('cartEmpty');
const hidden = document.getElementById('hiddenItems');
const preview = document.getElementById('totalPreview');
const side = document.getElementById('sideCart');

function chooseService(id) {
  if (!picker) return;
  picker.value = String(id);
  document.querySelectorAll('.service-choice').forEach(b => {
    const active = b.dataset.serviceId === String(id);
    b.classList.toggle('selected', active);
    b.setAttribute('aria-pressed', active ? 'true' : 'false');
    const label = b.querySelector('.service-choice-check');
    if (label) label.textContent = active ? 'Layanan Dipilih' : 'Pilih Layanan';
  });
  const target = document.querySelector('.service-select-row');
  if (target) target.classList.add('service-select-active');
}

function addToCart() {
  if (!picker || picker.selectedIndex < 0) return;
  const o = picker.options[picker.selectedIndex];
  const id = o.value;
  const q = parseFloat(qty.value || 0);

  if (!id || q <= 0) {
    alert('Pilih salah satu layanan dan masukkan jumlah yang valid (minimal 0.1).');
    return;
  }
  const found = cart.find(x => x.id === id);
  if (found) {
    found.qty += q;
  } else {
    cart.push({
      id: id,
      name: o.dataset.name,
      price: parseFloat(o.dataset.price),
      unit: o.dataset.unit,
      qty: q
    });
  }
  renderCart();
}

function removeCart(i) {
  cart.splice(i, 1);
  renderCart();
}

function renderCart() {
  body.innerHTML = '';
  hidden.innerHTML = '';
  let total = 0;

  cart.forEach((x, i) => {
    const sub = x.price * x.qty;
    total += sub;
    const tr = document.createElement('tr');
    tr.innerHTML = '<td><b>' + esc(x.name) + '</b></td>' +
      '<td>' + x.qty + ' ' + esc(x.unit) + '</td>' +
      '<td>Rp ' + x.price.toLocaleString('id-ID') + '</td>' +
      '<td><b>Rp ' + sub.toLocaleString('id-ID') + '</b></td>' +
      '<td style="text-align:center"><button type="button" class="danger-btn" onclick="removeCart(' + i + ')">Hapus</button></td>';
    body.appendChild(tr);
    hidden.innerHTML += '<input type="hidden" name="service_ids[]" value="' + x.id + '">' +
      '<input type="hidden" name="quantities[]" value="' + x.qty + '">';
  });

  table.style.display = cart.length ? 'table' : 'none';
  empty.style.display = cart.length ? 'none' : 'block';
  document.getElementById('cartCount').textContent = cart.length + ' item';
  preview.textContent = 'Rp ' + total.toLocaleString('id-ID');
  side.innerHTML = cart.length ? cart.map(x => '<div class="side-cart-item"><b>' + esc(x.name) + '</b><span>' + x.qty + ' ' + esc(x.unit) + ' × Rp ' + x.price.toLocaleString('id-ID') + ' = <b>Rp ' + (x.qty * x.price).toLocaleString('id-ID') + '</b></span></div>').join('') : 'Belum ada layanan yang ditambahkan.';
}

function esc(s) {
  return String(s).replace(/[&<>'"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[m]));
}

function useVoucher(v) {
  document.getElementById('kodeVoucher').value = v;
  document.getElementById('pembayaran').scrollIntoView({ behavior: 'smooth' });
}

/* FIX BUG: Variable renamed to metodePengiriman to prevent variable conflict */
const metodePengiriman = document.getElementById('metode');
const fields = document.getElementById('pickupFields');
if (metodePengiriman && fields) {
  metodePengiriman.onchange = () => {
    fields.style.display = metodePengiriman.value === 'antar_jemput' ? 'block' : 'none';
  };
  if (metodePengiriman.value === 'antar_jemput') {
    fields.style.display = 'block';
  }
}

function getLocation() {
  if (!navigator.geolocation) {
    alert('Browser Anda tidak mendukung deteksi lokasi.');
    return;
  }
  navigator.geolocation.getCurrentPosition(p => {
    document.getElementById('lat').value = p.coords.latitude.toFixed(8);
    document.getElementById('lng').value = p.coords.longitude.toFixed(8);
  }, () => {
    ['lat','lng'].forEach(id => { document.getElementById(id).readOnly = false; });
    alert('Lokasi otomatis gagal (izin ditolak, atau situs belum memakai HTTPS). Isi Latitude dan Longitude manual: buka Google Maps, klik kanan lokasi jemput, lalu salin angka koordinatnya.');
  });
}

function toggleQris() {
  const r = document.querySelector('input[name="metode_bayar"]:checked');
  const box = document.getElementById('qris-box');
  if (box) box.style.display = (r && r.value === 'qris') ? 'block' : 'none';
}
document.querySelectorAll('input[name="metode_bayar"]').forEach(x => x.addEventListener('change', toggleQris));
toggleQris();
if (cart.length) renderCart();
</script>

<?php if(!$sessUser&&!empty($_SESSION['guest_hp'])): ?>
  <script>window.LaundryKuPhone=<?=json_encode((string)$_SESSION['guest_hp'],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;</script>
<?php endif; ?>
<script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>

</body>
</html>
