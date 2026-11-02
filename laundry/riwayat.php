<?php
require_once __DIR__.'/config/koneksi.php';
function h($v){return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');}
function rupiah($n){return 'Rp '.number_format((float)$n,0,',','.');}
function tableColumns(mysqli $conn, string $table): array {
    $out=[];
    $res=$conn->query("SHOW COLUMNS FROM `".$conn->real_escape_string($table)."`");
    if($res) while($row=$res->fetch_assoc()) $out[$row['Field']]=true;
    return $out;
}
$hp=trim((string)($_GET['no_hp']??''));
$statusFilter=trim((string)($_GET['status']??''));
$pelanggan=null; $orders=[]; $err=''; $detailsByOrder=[];
$statusLabels=[
    'menunggu'=>'Menunggu Konfirmasi',
    'diterima'=>'Diterima Outlet',
    'dicuci'=>'Sedang Dicuci',
    'dikeringkan'=>'Sedang Dikeringkan',
    'disetrika'=>'Sedang Disetrika',
    'selesai'=>'Selesai',
    'diantar'=>'Sedang Diantar',
    'terkirim'=>'Sudah Diterima',
    'diambil'=>'Sudah Diambil',
    'dibatalkan'=>'Dibatalkan'
];

try {
  $userCols=tableColumns($conn,'users');
  if($hp!=='') {
    if(!isset($userCols['no_hp'],$userCols['id_user'],$userCols['role'])) throw new RuntimeException('Struktur tabel users belum sesuai.');
    $s=$conn->prepare("SELECT id_user,nama,no_hp FROM users WHERE no_hp=? AND role='pelanggan' LIMIT 1");
    if(!$s) throw new RuntimeException('Query pelanggan gagal: '.$conn->error);
    $s->bind_param('s',$hp); $s->execute(); $pelanggan=$s->get_result()->fetch_assoc(); $s->close();
    if(!$pelanggan) {
      $err='Nomor HP belum memiliki akun pelanggan atau belum pernah membuat pesanan.';
    } else {
      $pc=tableColumns($conn,'pesanan');
      foreach(['id_pesanan','id_user','status_pesanan'] as $needed) {
        if(!isset($pc[$needed])) throw new RuntimeException('Kolom '.$needed.' tidak ditemukan pada tabel pesanan.');
      }
      $fields=['id_pesanan','kode_pesanan','berat_estimasi','berat_aktual','metode_pengiriman','subtotal','ongkir','diskon','total_estimasi','total_final','estimasi_selesai','status_pesanan','tanggal_pesanan'];
      $select=[];
      foreach($fields as $f) $select[] = isset($pc[$f]) ? "`$f`" : "NULL AS `$f`";
      $sql='SELECT '.implode(',',$select).' FROM pesanan WHERE id_user=?'; 
      $types='i'; 
      $args=[(int)$pelanggan['id_user']];
      if($statusFilter!=='' && isset($statusLabels[$statusFilter])) { 
        $sql.=' AND status_pesanan=?'; 
        $types.='s'; 
        $args[]=$statusFilter; 
      }
      $orderCol=isset($pc['tanggal_pesanan'])?'tanggal_pesanan':'id_pesanan';
      $sql.=" ORDER BY `$orderCol` DESC, id_pesanan DESC";
      $s=$conn->prepare($sql);
      if(!$s) throw new RuntimeException('Query riwayat gagal: '.$conn->error);
      $s->bind_param($types,...$args); 
      $s->execute(); 
      $orders=$s->get_result()->fetch_all(MYSQLI_ASSOC); 
      $s->close();

      $dc=tableColumns($conn,'detail_pesanan'); 
      $lc=tableColumns($conn,'layanan');
      if(isset($dc['id_pesanan'],$dc['id_layanan']) && isset($lc['id_layanan'],$lc['nama_layanan'])) {
        $detailFields=[];
        $detailFields[]='d.id_pesanan';
        $detailFields[]=isset($dc['jumlah'])?'d.jumlah':'1 AS jumlah';
        $detailFields[]=isset($dc['harga'])?'d.harga':'0 AS harga';
        $detailFields[]=isset($dc['subtotal'])?'d.subtotal':'0 AS subtotal';
        $detailFields[]='l.nama_layanan';
        $sort=isset($dc['id_detail'])?'d.id_detail':(isset($dc['id_pesanan'])?'d.id_pesanan':'d.id_layanan');
        $ids=array_map(fn($o)=>(int)$o['id_pesanan'],$orders);
        if($ids) {
          $in=implode(',',array_fill(0,count($ids),'?')); 
          $t=str_repeat('i',count($ids));
          $q=$conn->prepare('SELECT '.implode(',',$detailFields)." FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan IN ($in) ORDER BY $sort");
          if($q){
            $q->bind_param($t,...$ids);
            $q->execute();
            $rs=$q->get_result();
            while($d=$rs->fetch_assoc())$detailsByOrder[(int)$d['id_pesanan']][]=$d;
            $q->close();
          }
        }
      }
    }
  }
} catch(Throwable $e) { 
  $err='Riwayat belum dapat dimuat: '.$e->getMessage(); 
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Pesanan — LaundryKu</title>
  <?php if($pelanggan): ?>
    <meta http-equiv="refresh" content="30">
  <?php endif; ?>
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
      <a href="chatbot.php">Chatbot</a>
      <a href="saldo.php">Saldo</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<main class="container">
  <div class="panel">
    <span class="pill">Riwayat Pesanan</span>
    <h1 style="margin:12px 0 6px">Riwayat Order Kamu</h1>
    <p class="muted">Masukin nomor WhatsApp yang kamu pakai pas order, riwayat cucianmu langsung muncul.</p>

    <form method="get" class="tracking-form">
      <input name="no_hp" placeholder="Contoh: 081234567890" value="<?=h($hp)?>" required inputmode="tel">
      <button class="btn" type="submit">Cari Order</button>
    </form>

    <?php if($err): ?>
      <div class="alert" style="margin-top:16px"><?=h($err)?></div>
    <?php endif; ?>

    <?php if($pelanggan): ?>
      <!-- Filter and Info Header -->
      <div class="result" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px">
        <div>
          <b style="font-size:16px;color:var(--ink)"><?=h($pelanggan['nama']??'Pelanggan')?></b>
          <span class="muted" style="display:block;font-size:13px">Nomor HP: <?=h($pelanggan['no_hp']??$hp)?></span>
        </div>
        <div>
          <span class="pill2">Total <?=count($orders)?> Pesanan Ditemukan</span>
        </div>
      </div>

      <form method="get" class="history-toolbar">
        <input type="hidden" name="no_hp" value="<?=h($hp)?>">
        <select name="status" aria-label="Filter status pesanan">
          <option value="">-- Semua Status Pesanan --</option>
          <?php foreach($statusLabels as $key=>$label): ?>
            <option value="<?=h($key)?>" <?=$statusFilter===$key?'selected':''?>><?=h($label)?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn" type="submit" style="padding:9px 18px;font-size:13px">Filter</button>
        <?php if($statusFilter!==''): ?>
          <a class="btn secondary" href="riwayat.php?no_hp=<?=urlencode($hp)?>" style="padding:9px 16px;font-size:13px">Reset Filter</a>
        <?php endif; ?>
      </form>
      <small class="muted" style="display:block">Halaman ini auto-refresh tiap 30 detik, gak perlu reload.</small>

      <!-- Orders List -->
      <?php if(!$orders): ?>
        <div class="empty-state" style="margin-top:20px">
          <?= $statusFilter!=='' ? 'Tidak ditemukan pesanan dengan status "'.h($statusLabels[$statusFilter]??$statusFilter).'". Coba pilih "Semua Status".' : 'Belum ada transaksi pesanan laundry pada nomor ini.' ?>
        </div>
      <?php else: ?>
        <div class="history-list">
          <?php foreach($orders as $o): 
            $st = (string)($o['status_pesanan'] ?? 'menunggu');
            $statusText = $statusLabels[$st] ?? ucwords(str_replace('_',' ',$st));
            $total = ($o['total_final'] !== null) ? (float)$o['total_final'] : (float)($o['total_estimasi'] ?? 0);
            $details = $detailsByOrder[(int)$o['id_pesanan']] ?? [];
          ?>
            <article class="history-card">
              <div class="history-head">
                <div>
                  <a href="tracking.php?kode=<?=urlencode((string)($o['kode_pesanan']??''))?>" class="history-code">
                    <?=h($o['kode_pesanan'] ?? ('Pesanan #'.$o['id_pesanan']))?>
                  </a>
                  <small>
                    <?=!empty($o['tanggal_pesanan']) ? date('d M Y, H:i', strtotime($o['tanggal_pesanan'])) : 'Tanggal tidak tersedia'?>
                  </small>
                </div>
                <span class="status-pill <?=$st?>">
                  <?=h($statusText)?>
                </span>
              </div>

              <div class="history-services">
                <?php if($details): ?>
                  <?php foreach($details as $d): ?>
                    <div>
                      <span><?=h($d['nama_layanan'])?> × <?=h(rtrim(rtrim(number_format((float)$d['jumlah'],2,'.',''),'0'),'.'))?></span>
                      <b><?=rupiah($d['subtotal'])?></b>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="muted">Rincian paket belum tersedia.</div>
                <?php endif; ?>
              </div>

              <div class="history-foot">
                <span>
                  Pengiriman: <?=h(ucwords(str_replace('_',' ',$o['metode_pengiriman']??'-')))?><br>
                  Berat: <?php if($o['berat_aktual']!==null): ?>
                    <b><?=number_format((float)$o['berat_aktual'],1,',','.')?> kg (aktual)</b>
                  <?php else: ?>
                    <?=number_format((float)($o['berat_estimasi']??0),1,',','.')?> kg (estimasi)
                  <?php endif; ?>
                </span>
                <div style="text-align:right">
                  <small class="muted" style="display:block">Total Pembayaran</small>
                  <strong style="color:var(--brand);font-size:20px"><?=rupiah($total)?></strong>
                </div>
              </div>

              <?php if(!empty($o['estimasi_selesai'])): ?>
                <div class="history-note" style="margin-top:10px">
                  Estimasi selesai: <b><?=date('d M Y, H:i', strtotime($o['estimasi_selesai']))?></b>
                </div>
              <?php endif; ?>

              <div class="history-actions" style="display:flex;justify-content:flex-end;gap:10px">
                <a class="btn" href="tracking.php?kode=<?=urlencode((string)($o['kode_pesanan']??''))?>" style="font-size:13px;padding:8px 16px">
                  Lacak Pesanan
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</main>

<?php if($pelanggan): ?>
  <script>window.LaundryKuPhone=<?=json_encode($hp,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;</script>
  <script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
<?php endif; ?>

</body>
</html>
