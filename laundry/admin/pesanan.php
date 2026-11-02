<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../config/notifikasi_helper.php';
laundry_ensure_schema($conn);

if(!isset($_SESSION['id_user']) || ($_SESSION['role']??'') !== 'admin'){
    header('Location: ../auth/login.php');
    exit;
}

function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
$msg=''; $err='';

function track($conn,$pid,$status,$ket,$uid){
    $s=$conn->prepare("INSERT INTO tracking_pesanan(id_pesanan,status,keterangan,updated_by) VALUES(?,?,?,?)");
    $s->bind_param('issi',$pid,$status,$ket,$uid);
    $s->execute();
}
function notif($conn,$uid,$pid,$title,$msg){
    laundry_notif($conn,(int)$uid,(int)$pid,$title,$msg);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $pid=(int)($_POST['id_pesanan']??0);
    $action=$_POST['action']??'';
    try{
        if(!$pid) throw new Exception('Pesanan tidak valid.');
        
        if($action==='approve'){
            $s=$conn->prepare("SELECT p.*,u.nama FROM pesanan p JOIN users u ON u.id_user=p.id_user WHERE p.id_pesanan=?");
            $s->bind_param('i',$pid);
            $s->execute();
            $p=$s->get_result()->fetch_assoc();
            if(!$p) throw new Exception('Pesanan tidak ditemukan.');
            
            $status='diterima';
            $s=$conn->prepare("UPDATE pesanan SET status_pesanan=? WHERE id_pesanan=?");
            $s->bind_param('si',$status,$pid);
            $s->execute();
            
            track($conn,$pid,$status,'Pesanan disetujui dan diterima oleh Admin.',(int)$_SESSION['id_user']);
            laundry_notif_status($conn,(int)$p['id_user'],$pid,'diterima',$p['kode_pesanan']);
            $msg='Pesanan '.$p['kode_pesanan'].' berhasil disetujui & berstatus diterima.';
        } elseif($action==='weigh'){
            $actual=(float)($_POST['berat_aktual']??0);
            if($actual<=0) throw new Exception('Berat aktual harus lebih dari 0 kg.');
            
            $s=$conn->prepare("SELECT p.*,u.id_user FROM pesanan p JOIN users u ON u.id_user=p.id_user WHERE p.id_pesanan=?");
            $s->bind_param('i',$pid);
            $s->execute();
            $p=$s->get_result()->fetch_assoc();
            if(!$p) throw new Exception('Pesanan tidak ditemukan.');
            
            $details=$conn->query("SELECT d.*,l.satuan FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan=".$pid)->fetch_all(MYSQLI_ASSOC);
            $kgCount=0;$kgId=null;
            foreach($details as $d){
                if(strtolower($d['satuan'])==='kg'){
                    $kgCount++;
                    $kgId=$d['id_detail'];
                }
            }
            $conn->begin_transaction();
            $s=$conn->prepare("UPDATE pesanan SET berat_aktual=? WHERE id_pesanan=?");
            $s->bind_param('di',$actual,$pid);
            $s->execute();
            
            if($kgCount===1){
                $d=null;
                foreach($details as $x) if($x['id_detail']==$kgId) $d=$x;
                if($d){
                    $newSub=$actual*(float)$d['harga'];
                    $s=$conn->prepare("UPDATE detail_pesanan SET jumlah=?,subtotal=? WHERE id_detail=?");
                    $s->bind_param('ddi',$actual,$newSub,$kgId);
                    $s->execute();
                }
            }
            $sum=$conn->query("SELECT COALESCE(SUM(subtotal),0) subtotal FROM detail_pesanan WHERE id_pesanan=".$pid)->fetch_assoc()['subtotal'];
            $final=max(0,(float)$sum+(float)$p['ongkir']-(float)$p['diskon']);
            $s=$conn->prepare("UPDATE pesanan SET subtotal=?,total_final=? WHERE id_pesanan=?");
            $s->bind_param('ddi',$sum,$final,$pid);
            $s->execute();
            
            $status='diterima';
            track($conn,$pid,$status,'Berat aktual ditimbang: '.$actual.' kg. Total final dihitung ulang menjadi '.rp($final).'.',(int)$_SESSION['id_user']);
            notif($conn,(int)$p['id_user'],$pid,'Berat aktual dicatat','Berat aktual '.$actual.' kg. Total akhir '.rp($final).'.');
            $conn->commit();
            $msg='Berat aktual '.$actual.' kg dan total final '.rp($final).' berhasil diperbarui.';
        } elseif($action==='status'){
            $new=$_POST['status_baru']??'';
            $allowed=['menunggu','diterima','dicuci','dikeringkan','disetrika','diantar','dijemput','terkirim','diambil','dibatalkan','selesai'];
            if(!in_array($new,$allowed,true)) throw new Exception('Status tidak valid.');
            
            $s=$conn->prepare("SELECT * FROM pesanan WHERE id_pesanan=?");
            $s->bind_param('i',$pid);
            $s->execute();
            $p=$s->get_result()->fetch_assoc();
            if(!$p) throw new Exception('Pesanan tidak ditemukan.');
            
            $previous=(string)$p['status_pesanan'];
            if($previous!==$new){
                $s=$conn->prepare("UPDATE pesanan SET status_pesanan=? WHERE id_pesanan=?");
                $s->bind_param('si',$new,$pid);
                if(!$s->execute()) throw new Exception('Gagal memperbarui status: '.$s->error);
                
                track($conn,$pid,$new,'Status diubah dari '.ucwords(str_replace('_',' ',$previous)).' menjadi '.ucwords(str_replace('_',' ',$new)).' oleh Admin.',(int)$_SESSION['id_user']);
                laundry_notif_status($conn,(int)$p['id_user'],$pid,$new,$p['kode_pesanan']);
            }
            $msg=($previous===$new)?'Pesanan sudah berstatus '.ucwords(str_replace('_',' ',$new)).'.':'Status berhasil diubah menjadi '.ucwords(str_replace('_',' ',$new)).'.';
        } elseif($action==='assign'){
            $courier=(int)($_POST['id_kurir']??0);
            if(!$courier) throw new Exception('Pilih kurir terlebih dahulu.');
            
            $s=$conn->prepare("SELECT p.*,a.id_antar_jemput,a.jenis FROM pesanan p LEFT JOIN antar_jemput a ON a.id_pesanan=p.id_pesanan AND a.jenis='jemput' WHERE p.id_pesanan=?");
            $s->bind_param('i',$pid);
            $s->execute();
            $p=$s->get_result()->fetch_assoc();
            if(!$p||!$p['id_antar_jemput']) throw new Exception('Pesanan ini tidak memiliki data penjemputan.');
            
            $token=bin2hex(random_bytes(16));
            $st='ditugaskan';
            $s=$conn->prepare("INSERT INTO penugasan_kurir(id_antar_jemput,id_kurir,status,token_akses) VALUES(?,?,?,?)");
            $s->bind_param('iiss',$p['id_antar_jemput'],$courier,$st,$token);
            $s->execute();
            $msg='Kurir berhasil ditugaskan. Token akses kurir: '.$token;
        } elseif($action==='pay'){
            $s=$conn->prepare("SELECT pb.*,p.id_user,p.kode_pesanan FROM pembayaran pb JOIN pesanan p ON p.id_pesanan=pb.id_pesanan WHERE pb.id_pembayaran=?");
            $s->bind_param('i',$_POST['id_pembayaran']);
            $s->execute();
            $pay=$s->get_result()->fetch_assoc();
            if(!$pay) throw new Exception('Pembayaran tidak ditemukan.');
            
            $st='lunas';
            $now=date('Y-m-d H:i:s');
            $s=$conn->prepare("UPDATE pembayaran SET status=?,paid_at=? WHERE id_pembayaran=?");
            $s->bind_param('ssi',$st,$now,$_POST['id_pembayaran']);
            $s->execute();
            
            notif($conn,(int)$pay['id_user'],(int)$pay['id_pesanan'],'Pembayaran dikonfirmasi','Pembayaran untuk '.$pay['kode_pesanan'].' telah dikonfirmasi lunas.');
            $msg='Pembayaran berhasil dikonfirmasi lunas.';
        }
    }catch(Throwable $e){
        if($conn->errno) $conn->rollback();
        $err=$e->getMessage();
    }
}

$filter=$_GET['status']??'';
$where=$filter ? " WHERE p.status_pesanan='".$conn->real_escape_string($filter)."' " : '';
$orders=$conn->query("SELECT p.*,u.nama,u.no_hp,(SELECT status FROM pembayaran pb WHERE pb.id_pesanan=p.id_pesanan ORDER BY id_pembayaran DESC LIMIT 1) payment_status,(SELECT id_pembayaran FROM pembayaran pb WHERE pb.id_pesanan=p.id_pesanan ORDER BY id_pembayaran DESC LIMIT 1) id_pembayaran FROM pesanan p JOIN users u ON u.id_user=p.id_user $where ORDER BY p.id_pesanan DESC");
$couriers=$conn->query("SELECT id_user,nama,no_hp FROM users WHERE role='kurir' AND status='aktif' ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

$filterOptions = [
    '' => 'Semua Pesanan',
    'menunggu' => 'Menunggu',
    'diterima' => 'Diterima',
    'dicuci' => 'Dicuci',
    'dikeringkan' => 'Dikeringkan',
    'disetrika' => 'Disetrika',
    'selesai' => 'Selesai',
    'diantar' => 'Diantar',
    'terkirim' => 'Terkirim',
    'dibatalkan' => 'Dibatalkan'
];
$pendingTopup = (int)($conn->query("SELECT COUNT(*) c FROM transaksi_dompet WHERE jenis='topup' AND status='menunggu'")->fetch_assoc()['c'] ?? 0);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengelolaan Pesanan — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="admin-body">

<div class="admin-shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <a class="side-brand" href="index.php">
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="brand-badge">LK</span>
        <span>LaundryKu</span>
      </div>
      <small>PANEL ADMINISTRATOR</small>
    </a>
    <nav class="side-nav">
      <a href="index.php">
        <span>Dashboard</span>
      </a>
      <a class="active" href="pesanan.php">
        <span>Kelola Pesanan</span>
      </a>
      <a href="layanan_voucher.php">
        <span>Layanan & Voucher</span>
      </a>
      <a href="topup.php">
        <span>Top Up Saldo</span>
        <?php if($pendingTopup > 0): ?>
          <b><?=$pendingTopup?></b>
        <?php endif; ?>
      </a>
    </nav>
    <div class="side-bottom">
      <a href="../index.php" target="_blank">
        <span>Halaman Publik</span>
      </a>
      <a class="logout" href="../auth/logout.php">
        <span>Keluar Sistem</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <section class="admin-main">
    <header class="admin-header">
      <div>
        <div class="eyebrow">MANAJEMEN OPERASIONAL</div>
        <h1>Kelola Pesanan Laundry</h1>
        <p>Konfirmasi pesanan masuk, catat timbangan aktual, perbarui status proses, dan tugaskan kurir.</p>
      </div>
      <div class="header-actions">
        <a class="outline-btn" href="pesanan.php">Muat Ulang</a>
        <a class="primary-btn" href="../pesan.php" target="_blank">Buat Pesanan Baru</a>
      </div>
    </header>

    <?php if($msg): ?>
      <div class="success"><?=htmlspecialchars($msg)?></div>
    <?php endif; ?>
    <?php if($err): ?>
      <div class="alert"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <!-- Filter by Status Buttons -->
    <div class="filter-tabs">
      <span style="font-size:12px;font-weight:700;color:var(--muted);align-self:center;margin-right:6px">FILTER STATUS:</span>
      <?php foreach($filterOptions as $val => $lbl): ?>
        <a href="pesanan.php?status=<?=urlencode($val)?>" class="filter-tab <?=$filter===$val ? 'active' : ''?>">
          <?=$lbl?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Orders Card & Table -->
    <div class="admin-card">
      <div class="card-head">
        <div>
          <h2>Daftar Pesanan Laundry</h2>
          <p>Menampilkan <?=($filter ? 'pesanan dengan status "'.ucwords(str_replace('_',' ',$filter)).'"' : 'seluruh data pesanan')?>.</p>
        </div>
      </div>

      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Kode & Tanggal</th>
              <th>Pelanggan</th>
              <th>Berat Cucian</th>
              <th>Total Biaya</th>
              <th>Status Proses</th>
              <th>Pembayaran</th>
              <th style="min-width:240px">Aksi Pengelolaan</th>
            </tr>
          </thead>
          <tbody>
            <?php if($orders && $orders->num_rows > 0): ?>
              <?php while($o = $orders->fetch_assoc()): ?>
                <tr>
                  <td>
                    <b style="color:var(--brand);font-size:14px"><?=htmlspecialchars($o['kode_pesanan'])?></b>
                    <small style="display:block;color:var(--muted);margin-top:2px">
                      <?=date('d/m/Y H:i', strtotime($o['tanggal_pesanan']))?>
                    </small>
                    <a href="../tracking.php?kode=<?=urlencode($o['kode_pesanan'])?>" target="_blank" style="font-size:11px;font-weight:600;display:inline-block;margin-top:4px">
                      Buka Tracking
                    </a>
                  </td>

                  <td>
                    <b><?=htmlspecialchars($o['nama'])?></b>
                    <small style="display:block;color:var(--muted)"><?=htmlspecialchars($o['no_hp'])?></small>
                    <?php if(!empty($o['catatan']) && str_starts_with($o['catatan'],'Atas nama:')): ?>
                      <small style="display:block;color:var(--brand-dark);font-size:11px;margin-top:2px"><?=htmlspecialchars($o['catatan'])?></small>
                    <?php endif; ?>
                    <span class="pill2" style="font-size:11px;padding:2px 8px;margin-top:4px">
                      <?=ucwords(str_replace('_',' ',$o['metode_pengiriman']))?>
                    </span>
                  </td>

                  <td>
                    <span>Est: <?=htmlspecialchars($o['berat_estimasi'])?> kg</span>
                    <?php if($o['berat_aktual'] !== null): ?>
                      <div style="margin-top:4px;font-weight:700;color:var(--brand)">
                        Aktual: <?=$o['berat_aktual']?> kg
                      </div>
                    <?php endif; ?>
                  </td>

                  <td>
                    <b style="font-size:15px;color:var(--ink)">
                      <?=rp($o['total_final'] !== null ? $o['total_final'] : $o['total_estimasi'])?>
                    </b>
                    <?php if($o['total_final'] !== null): ?>
                      <small style="display:block;color:var(--success-text);font-weight:600">Total Final</small>
                    <?php else: ?>
                      <small style="display:block;color:var(--muted)">Estimasi Awal</small>
                    <?php endif; ?>
                  </td>

                  <td>
                    <span class="status-pill <?=htmlspecialchars($o['status_pesanan'])?>">
                      <?=ucwords(str_replace('_',' ',$o['status_pesanan']))?>
                    </span>
                  </td>

                  <td>
                    <span class="status-pill <?=(($o['payment_status']??'')==='lunas'?'selesai':'menunggu')?>">
                      <?=htmlspecialchars(ucwords($o['payment_status']??'belum bayar'))?>
                    </span>
                    <?php if(($o['payment_status']??'')==='menunggu' && $o['id_pembayaran']): ?>
                      <form method="post" style="margin-top:6px">
                        <input type="hidden" name="id_pembayaran" value="<?=$o['id_pembayaran']?>">
                        <input type="hidden" name="id_pesanan" value="<?=$o['id_pesanan']?>">
                        <button class="small-btn primary" name="action" value="pay" style="font-size:11px;padding:4px 8px">
                          Konfirmasi Lunas
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>

                  <td>
                    <div class="action-stack">
                      <!-- Quick Approve if Menunggu -->
                      <?php if($o['status_pesanan']==='menunggu'): ?>
                        <form method="post">
                          <input type="hidden" name="id_pesanan" value="<?=$o['id_pesanan']?>">
                          <button class="small-btn primary" name="action" value="approve" style="width:100%;justify-content:center">
                            Terima Pesanan
                          </button>
                        </form>
                      <?php endif; ?>

                      <!-- Weigh actual input -->
                      <form method="post">
                        <input type="hidden" name="id_pesanan" value="<?=$o['id_pesanan']?>">
                        <input type="number" step="0.1" min="0.1" name="berat_aktual" placeholder="Berat kg aktual" style="width:115px" value="<?=$o['berat_aktual']!==null?$o['berat_aktual']:''?>" required>
                        <button class="small-btn" name="action" value="weigh" title="Simpan Timbangan">
                          Timbang
                        </button>
                      </form>

                      <!-- Change status dropdown -->
                      <form method="post">
                        <input type="hidden" name="id_pesanan" value="<?=$o['id_pesanan']?>">
                        <select name="status_baru" style="font-size:12px;padding:6px">
                          <option value="menunggu" <?=$o['status_pesanan']==='menunggu'?'selected':''?>>Menunggu</option>
                          <option value="diterima" <?=$o['status_pesanan']==='diterima'?'selected':''?>>Diterima</option>
                          <option value="dicuci" <?=$o['status_pesanan']==='dicuci'?'selected':''?>>Dicuci</option>
                          <option value="dikeringkan" <?=$o['status_pesanan']==='dikeringkan'?'selected':''?>>Dikeringkan</option>
                          <option value="disetrika" <?=$o['status_pesanan']==='disetrika'?'selected':''?>>Disetrika</option>
                          <option value="diantar" <?=$o['status_pesanan']==='diantar'?'selected':''?>>Diantar</option>
                          <option value="dijemput" <?=$o['status_pesanan']==='dijemput'?'selected':''?>>Dijemput</option>
                          <option value="selesai" <?=$o['status_pesanan']==='selesai'?'selected':''?>>Selesai</option>
                          <option value="terkirim" <?=$o['status_pesanan']==='terkirim'?'selected':''?>>Terkirim</option>
                          <option value="diambil" <?=$o['status_pesanan']==='diambil'?'selected':''?>>Diambil</option>
                          <option value="dibatalkan" <?=$o['status_pesanan']==='dibatalkan'?'selected':''?>>Dibatalkan</option>
                        </select>
                        <button class="small-btn" name="action" value="status">
                          Update
                        </button>
                      </form>

                      <!-- Assign courier if Antar-Jemput -->
                      <?php if($o['metode_pengiriman']==='antar_jemput'): ?>
                        <form method="post">
                          <input type="hidden" name="id_pesanan" value="<?=$o['id_pesanan']?>">
                          <select name="id_kurir" required style="font-size:12px;padding:6px">
                            <option value="">-- Pilih Kurir --</option>
                            <?php foreach($couriers as $c): ?>
                              <option value="<?=$c['id_user']?>"><?=htmlspecialchars($c['nama'])?></option>
                            <?php endforeach; ?>
                          </select>
                          <button class="small-btn" name="action" value="assign">
                            Tugaskan
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">
                  Tidak ada pesanan yang sesuai dengan filter yang dipilih.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

</body>
</html>
