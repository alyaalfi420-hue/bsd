<?php
require_once __DIR__.'/config/koneksi.php';
$data=null;$logs=[];$details=[];$pay=[];$pickup=null;$err='';
function rupiah($n){return 'Rp '.number_format((float)$n,0,',','.');}
function dbColumns($conn,$table){
    $out=[];
    if(!preg_match('/^[a-zA-Z0-9_]+$/',$table)) return $out;
    $r=$conn->query("SHOW COLUMNS FROM `".$table."`");
    if($r){while($row=$r->fetch_assoc())$out[$row['Field']]=true;$r->free();}
    return $out;
}
function safeRows($conn,$sql,$type=null,$value=null,$one=false){
    $st=$conn->prepare($sql);
    if(!$st) return $one?null:[];
    if($type!==null){$st->bind_param($type,$value);}
    if(!$st->execute()){$st->close();return $one?null:[];}
    $res=$st->get_result();
    $result=$one?($res?$res->fetch_assoc():null):($res?$res->fetch_all(MYSQLI_ASSOC):[]);
    $st->close();return $result;
}
$labels=[
    'menunggu'=>'Menunggu Konfirmasi',
    'dikonfirmasi'=>'Dikonfirmasi',
    'dijemput'=>'Sedang Dijemput',
    'diterima'=>'Diterima Outlet',
    'ditimbang'=>'Ditimbang',
    'diproses'=>'Sedang Diproses',
    'dicuci'=>'Sedang Dicuci',
    'dikeringkan'=>'Sedang Dikeringkan',
    'disetrika'=>'Sedang Disetrika',
    'selesai'=>'Selesai Dikerjakan',
    'diantar'=>'Sedang Diantar Kurir',
    'terkirim'=>'Sudah Diterima',
    'diambil'=>'Sudah Diambil Pelanggan',
    'dibatalkan'=>'Pesanan Dibatalkan'
];
$flow=['menunggu','dikonfirmasi','dijemput','diterima','ditimbang','dicuci','dikeringkan','disetrika','selesai','diantar','terkirim'];
$explain=[
    'menunggu'=>'Pesanan sudah masuk ke antrean dan menunggu konfirmasi dari kasir/admin.',
    'dikonfirmasi'=>'Pesanan telah dikonfirmasi dan dijadwalkan untuk penanganan.',
    'dijemput'=>'Kurir sedang menuju lokasi Anda untuk menjemput pakaian kotor.',
    'diterima'=>'Pakaian kotor telah tiba di outlet LaundryKu.',
    'ditimbang'=>'Pakaian sedang ditimbang untuk perhitungan berat aktual dan total final.',
    'diproses'=>'Cucian sedang masuk ke proses pembersihan.',
    'dicuci'=>'Pakaian Anda sedang dicuci dengan deterjen khusus dan higienis.',
    'dikeringkan'=>'Pakaian sedang dalam proses pengeringan mesin bersuhu optimal.',
    'disetrika'=>'Pakaian sedang disetrika dan dilipat rapi dengan pewangi pakaian.',
    'selesai'=>'Pakaian sudah rapi dan siap diambil di outlet atau siap diantar kurir.',
    'diantar'=>'Kurir sedang dalam perjalanan mengantarkan pakaian bersih ke lokasi Anda.',
    'terkirim'=>'Cucian bersih telah sukses diterima oleh pelanggan. Terima kasih!',
    'diambil'=>'Cucian bersih telah diambil langsung di outlet. Terima kasih!',
    'dibatalkan'=>'Pesanan ini telah dibatalkan.'
];

if($_SERVER['REQUEST_METHOD']==='POST'||isset($_GET['kode'])){
    $kode=trim($_POST['kode']??$_GET['kode']??'');
    if($kode==='') {
        $err='Silakan masukkan kode pesanan yang valid.';
    } else {
        $pc=dbColumns($conn,'pesanan');
        $uc=dbColumns($conn,'users');
        if(!$pc || !$uc || !isset($pc['kode_pesanan'],$pc['id_user'],$pc['id_pesanan'],$uc['id_user'])){
            $err='Struktur database belum sesuai.';
        } else {
            $nama=isset($uc['nama'])?'u.nama':"'Pelanggan'";
            $hp=isset($uc['no_hp'])?'u.no_hp':"''";
            $sql="SELECT p.*, $nama AS nama, $hp AS no_hp FROM pesanan p JOIN users u ON u.id_user=p.id_user WHERE p.kode_pesanan=? LIMIT 1";
            $data=safeRows($conn,$sql,'s',$kode,true);
            if(!$data)$err='Pesanan dengan kode "'.htmlspecialchars($kode).'" tidak ditemukan. Periksa kembali kodenya.';
        }
    }
}

if($data){
    $pid=(int)$data['id_pesanan'];
    $tc=dbColumns($conn,'tracking_pesanan');
    if($tc && isset($tc['id_pesanan'],$tc['status'])){
        $select='t.*';$join='';
        if(isset($tc['updated_by']) && isset($uc['id_user']) && isset($uc['nama'])){
            $select.=',u.nama AS updater';
            $join=' LEFT JOIN users u ON u.id_user=t.updated_by';
        } else {
            $select.=",'' AS updater";
        }
        $order=isset($tc['id_tracking'])?'t.id_tracking ASC':(isset($tc['created_at'])?'t.created_at ASC':'t.id_pesanan ASC');
        $logs=safeRows($conn,"SELECT $select FROM tracking_pesanan t$join WHERE t.id_pesanan=? ORDER BY $order",'i',$pid);
    }
    $dc=dbColumns($conn,'detail_pesanan');
    $lc=dbColumns($conn,'layanan');
    if($dc && $lc && isset($dc['id_pesanan'],$dc['id_layanan'],$lc['id_layanan'])){
        $fields=[];
        foreach(['jumlah','harga','subtotal'] as $f){
            if(isset($dc[$f]))$fields[]='d.'.$f;
            else $fields[]='0 AS '.$f;
        }
        $fields[] = isset($lc['nama_layanan'])?'l.nama_layanan':"'Layanan' AS nama_layanan";
        $fields[] = isset($lc['satuan'])?'l.satuan':"'unit' AS satuan";
        $order=isset($dc['id_detail'])?'d.id_detail':'d.id_pesanan';
        $details=safeRows($conn,'SELECT '.implode(',',$fields)." FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan=? ORDER BY $order",'i',$pid);
    }
    $payCols=dbColumns($conn,'pembayaran');
    if($payCols && isset($payCols['id_pesanan'])){
        $fields=[];
        foreach(['metode','jumlah','status','paid_at'] as $f){
            $fields[]=isset($payCols[$f])?'`'.$f.'`':"'' AS `$f`";
        }
        $order=isset($payCols['id_pembayaran'])?'id_pembayaran DESC':'id_pesanan DESC';
        $pay=safeRows($conn,'SELECT '.implode(',',$fields)." FROM pembayaran WHERE id_pesanan=? ORDER BY $order",'i',$pid);
    }
    $ac=dbColumns($conn,'antar_jemput');
    $addr=dbColumns($conn,'alamat_pelanggan');
    if($ac && $addr && isset($ac['id_pesanan'],$ac['id_alamat'],$addr['id_alamat'])){
        $fields='aj.*'.(isset($addr['alamat_lengkap'])?',a.alamat_lengkap':",'' AS alamat_lengkap");
        $order=isset($ac['id_antar_jemput'])?'aj.id_antar_jemput DESC':'aj.id_pesanan DESC';
        $pickup=safeRows($conn,"SELECT $fields FROM antar_jemput aj JOIN alamat_pelanggan a ON a.id_alamat=aj.id_alamat WHERE aj.id_pesanan=? ORDER BY $order LIMIT 1",'i',$pid,true);
    }
}

$progress=0;
if($data){
    $stNow = $data['status_pesanan'] ?? '';
    $idx = array_search($stNow, $flow, true);
    if($idx === false) {
        $progress = ($stNow === 'dibatalkan') ? 100 : 100;
    } else {
        $progress = max(10, round(($idx / (count($flow) - 1)) * 100));
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tracking Status Pesanan — LaundryKu</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme-pro.css?v=3">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="index.php">LaundryKu</a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="pesan.php">Order</a>
      <a href="riwayat.php">Riwayat</a>
      <a href="chatbot.php">Chatbot</a>
      <a href="saldo.php">Saldo</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<main class="container">
  <div class="panel">
    <span class="pill">Pelacakan Real-Time</span>
    <h1 style="margin:12px 0 6px">Cucianku Lagi Di Mana?</h1>
    <p class="muted">Masukin kode pesanan (contoh: LDR-20261024-...) buat pantau cucianmu.</p>

    <form method="post" class="tracking-form">
      <input name="kode" placeholder="Masukin kode pesanan, contoh: LDR-20261007-123" value="<?=htmlspecialchars($_POST['kode']??$_GET['kode']??'')?>" required>
      <button class="btn" type="submit">Lacak Sekarang</button>
    </form>

    <?php if($err): ?>
      <div class="alert" style="margin-top:16px"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>
  </div>

  <?php if($data): ?>
    <?php $currStatus = (string)($data['status_pesanan'] ?? 'menunggu'); ?>
    
    <?php if($currStatus === 'selesai' || $currStatus === 'terkirim' || $currStatus === 'diambil'): ?>
      <div class="success" style="margin-top:20px;font-size:15px">
        <b>Laundry Selesai Dikerjakan!</b>
        <p style="margin:4px 0 0">Pesanan <b><?=htmlspecialchars($data['kode_pesanan'])?></b> udah selesai, bersih dan rapi. Makasih udah percayain cucianmu ke LaundryKu!</p>
      </div>
    <?php endif; ?>

    <!-- Main Order Status Card -->
    <section class="track-hero">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
        <div>
          <span class="pill" style="font-size:12px">KODE PESANAN</span>
          <div class="track-code" style="margin-top:4px"><?=htmlspecialchars($data['kode_pesanan'])?></div>
          <div class="muted">Pelanggan: <b><?=htmlspecialchars($data['nama'])?></b> • <?=htmlspecialchars($data['no_hp'])?></div>
        </div>
        <div>
          <span class="status-pill <?=$currStatus?>" style="font-size:14px;padding:8px 16px">
            <?=htmlspecialchars($labels[$currStatus] ?? ucwords(str_replace('_',' ',$currStatus)))?>
          </span>
        </div>
      </div>

      <!-- Attractive Animated Progress Bar -->
      <div style="margin-top:24px">
        <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;color:var(--ink);margin-bottom:6px">
          <span>Progres Pengerjaan</span>
          <span style="color:var(--brand)"><?=$progress?>%</span>
        </div>
        <div class="progress">
          <span style="width:<?=$progress?>%"></span>
        </div>
        <div class="status-desc" style="font-size:14.5px">
          <?=htmlspecialchars($explain[$currStatus] ?? 'Status pesanan sedang dalam penanganan petugas.')?>
        </div>
      </div>

      <div style="margin-top:24px;border-top:1px solid var(--line);padding-top:16px">
        <div class="detail-row">
          <span>Estimasi Selesai</span>
          <b><?=(!empty($data['estimasi_selesai']) ? date('d M Y, H:i', strtotime($data['estimasi_selesai'])) : 'Belum ditentukan')?></b>
        </div>
        <div class="detail-row">
          <span>Berat Cucian</span>
          <b><?=htmlspecialchars((!empty($data['berat_aktual']) ? $data['berat_aktual'].' kg (aktual)' : ($data['berat_estimasi']??0).' kg (estimasi)'))?></b>
        </div>
        <div class="detail-row">
          <span>Metode Layanan</span>
          <b><?=htmlspecialchars(ucwords(str_replace('_', ' ', $data['metode_pengiriman'] ?? 'datang_sendiri')))?></b>
        </div>
        <div class="detail-row">
          <span>Total Biaya</span>
          <b style="font-size:18px;color:var(--brand)">
            <?=rupiah(((($data['total_final']??null)!==null) ? $data['total_final'] : ($data['total_estimasi']??0)))?>
          </b>
        </div>
      </div>
    </section>

    <!-- Details Grid -->
    <div class="track-grid">
      <!-- Service Items -->
      <div class="track-box">
        <h3>Isi Pesanan</h3>
        <div>
          <?php foreach($details as $d): ?>
            <div class="detail-row">
              <div>
                <b><?=htmlspecialchars($d['nama_layanan'])?></b><br>
                <small class="muted"><?=rtrim(rtrim(number_format($d['jumlah'],2,',','.'),'0'),',')?> <?=$d['satuan']?> × <?=rupiah($d['harga'])?></small>
              </div>
              <b><?=rupiah($d['subtotal'])?></b>
            </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:16px;border-top:1.5px dashed var(--line);padding-top:12px">
          <div class="detail-row">
            <span>Subtotal Layanan</span>
            <b><?=rupiah(($data['subtotal']??0))?></b>
          </div>
          <div class="detail-row">
            <span>Ongkir Antar-Jemput</span>
            <b><?=rupiah(($data['ongkir']??0))?></b>
          </div>
          <?php if(($data['diskon']??0) > 0): ?>
            <div class="detail-row" style="color:var(--success)">
              <span>Potongan Voucher Promo</span>
              <b>- <?=rupiah($data['diskon'])?></b>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Payment & Delivery Info -->
      <div class="track-box">
        <h3>Info Pembayaran</h3>
        <?php if($pay): ?>
          <?php foreach($pay as $p): ?>
            <div class="detail-row">
              <div>
                <b><?=ucwords(str_replace('_',' ',$p['metode']))?></b><br>
                <small class="status-pill <?=htmlspecialchars($p['status'])?>" style="font-size:11px;padding:2px 8px;margin-top:4px">
                  <?=htmlspecialchars(ucwords($p['status']))?>
                </small>
              </div>
              <div>
                <b><?=rupiah($p['jumlah'])?></b>
                <?php if($p['paid_at']): ?>
                  <small class="muted" style="display:block"><?=date('d/m/Y H:i', strtotime($p['paid_at']))?></small>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">Belum ada pembayaran yang tercatat.</div>
        <?php endif; ?>

        <?php if($pickup): ?>
          <h3 style="margin-top:24px">Antar-Jemput Kurir</h3>
          <div class="detail-row">
            <span>Jenis Antar/Jemput</span>
            <b><?=ucwords($pickup['jenis']??'-')?></b>
          </div>
          <div class="detail-row">
            <span>Jarak Tempuh</span>
            <b><?=number_format(($pickup['jarak_km']??0),2,',','.')?> km</b>
          </div>
          <div class="detail-row">
            <span>Status Kurir</span>
            <span class="status-pill <?=htmlspecialchars($pickup['status']??'menunggu')?>">
              <?=ucwords(str_replace('_',' ',($pickup['status']??'menunggu')))?>
            </span>
          </div>
          <div style="margin-top:12px;padding:12px;background:#f8f9fc;border-radius:var(--radius-sm);font-size:13px">
            <b>Alamat:</b> <?=htmlspecialchars($pickup['alamat_lengkap']?:'-')?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Live Courier Map if Antar-Jemput -->
    <?php if($pickup): ?>
      <section class="track-box" style="margin-top:20px">
        <h3>Posisi Kurir Live</h3>
        <p class="muted">Posisi kurir update otomatis selama kurir membagikan lokasinya.</p>
        <div id="liveCourierMap" style="height:340px;border-radius:16px;border:1px solid var(--line);background:#171717;overflow:hidden"></div>
        <p id="liveCourierStatus" class="muted" style="margin-top:10px;font-size:13px">Lagi nyari posisi kurir…</p>
      </section>

      <script>
      (function(){
        const kode = <?=json_encode($data['kode_pesanan'])?>;
        const status = document.getElementById('liveCourierStatus');
        const map = L.map('liveCourierMap').setView([-5.3971, 105.2668], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker = null;
        async function refresh(){
          try {
            const r = await fetch('api/lokasi_kurir.php?kode=' + encodeURIComponent(kode) + '&t=' + Date.now(), {cache:'no-store'});
            const j = await r.json();
            if(j.ok && j.location){
              const l = j.location, ll = [Number(l.latitude), Number(l.longitude)];
              if(!marker) {
                marker = L.marker(ll).addTo(map).bindPopup('<b>Posisi Kurir Saat Ini</b>');
              } else {
                marker.setLatLng(ll);
              }
              map.setView(ll, 15);
              status.textContent = 'Lokasi diperbarui: ' + new Date(l.updated_at.replace(' ','T')).toLocaleString('id-ID') + (l.accuracy_m ? ' (akurasi ±' + Math.round(Number(l.accuracy_m)) + ' m)' : '');
            } else {
              status.textContent = 'Kurir belum mengaktifkan pelacakan GPS langsung. Silakan cek kembali beberapa saat lagi.';
            }
          } catch(e) {
            status.textContent = 'Peta belum dapat memperbarui lokasi saat ini.';
          }
        }
        refresh();
        setInterval(refresh, 10000);
      })();
      </script>
    <?php endif; ?>

    <!-- Timeline of status updates -->
    <section class="track-box" style="margin-top:20px">
      <h3>Perjalanan Cucianmu</h3>
      <div class="timeline-clean">
        <?php if($logs): ?>
          <?php foreach($logs as $l): ?>
            <div class="tl">
              <div style="display:flex;align-items:center;gap:8px">
                <b><?=htmlspecialchars($labels[$l['status']] ?? ucwords(str_replace('_',' ',$l['status'])))?></b>
                <span class="status-pill <?=htmlspecialchars($l['status'])?>" style="font-size:11px;padding:2px 8px">
                  <?=htmlspecialchars(ucwords(str_replace('_',' ',$l['status'])))?>
                </span>
              </div>
              <span><?=htmlspecialchars(($l['keterangan']??'') ?: 'Status diperbarui.')?></span>
              <small>
                <?=!empty($l['created_at']) ? date('d M Y, H:i', strtotime($l['created_at'])) : (!empty($l['updated_at']) ? date('d M Y, H:i', strtotime($l['updated_at'])) : '-')?>
                <?php if(!empty($l['updater'])): ?>
                  • oleh <b><?=htmlspecialchars($l['updater'])?></b>
                <?php endif; ?>
              </small>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">Belum ada update status buat pesanan ini.</div>
        <?php endif; ?>
      </div>
    </section>

  <?php endif; ?>
</main>

<script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
</body>
</html>
