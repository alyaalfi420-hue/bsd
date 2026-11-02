<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';

$role=$_SESSION['role']??'';
if(!isset($_SESSION['id_user']) || $role!=='admin'){
    header('Location: ../auth/login.php');
    exit;
}

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id_transaksi']??0);
    $action=$_POST['action']??'';
    $conn->begin_transaction();
    try{
        $s=$conn->prepare("SELECT t.*,d.saldo FROM transaksi_dompet t JOIN dompet d ON d.id_dompet=t.id_dompet WHERE t.id_transaksi=? FOR UPDATE");
        $s->bind_param('i',$id);
        $s->execute();
        $t=$s->get_result()->fetch_assoc();
        
        if(!$t||$t['jenis']!=='topup'||$t['status']!=='menunggu'){
            throw new Exception('Transaksi top up tidak valid atau sudah diproses.');
        }
        
        if($action==='approve'){
            $before=(float)$t['saldo'];
            $after=$before+(float)$t['nominal'];
            $ok='berhasil';
            $s=$conn->prepare("UPDATE dompet SET saldo=? WHERE id_dompet=?");
            $s->bind_param('di',$after,$t['id_dompet']);
            $s->execute();
            
            $s=$conn->prepare("UPDATE transaksi_dompet SET saldo_sebelum=?,saldo_sesudah=?,status=? WHERE id_transaksi=?");
            $s->bind_param('ddsi',$before,$after,$ok,$id);
            $s->execute();
            $msg='Pengajuan top up berhasil disetujui. Saldo pelanggan otomatis bertambah sebesar '.rp($t['nominal']).'.';
        } else {
            $tolak='ditolak';
            $s=$conn->prepare("UPDATE transaksi_dompet SET status=? WHERE id_transaksi=?");
            $s->bind_param('si',$tolak,$id);
            $s->execute();
            $msg='Pengajuan top up berhasil ditolak.';
        }
        $conn->commit();
    }catch(Throwable $e){
        $conn->rollback();
        $err=$e->getMessage();
    }
}

$pendingCount = 0;
$cRes = $conn->query("SELECT COUNT(*) c FROM transaksi_dompet WHERE jenis='topup' AND status='menunggu'");
if($cRes) $pendingCount = (int)$cRes->fetch_assoc()['c'];

$list=$conn->query("SELECT t.id_transaksi,t.reference_no,t.nominal,t.metode,t.status,t.created_at,u.nama,u.no_hp FROM transaksi_dompet t JOIN dompet d ON d.id_dompet=t.id_dompet JOIN users u ON u.id_user=d.id_user WHERE t.jenis='topup' ORDER BY t.id_transaksi DESC LIMIT 30");
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi Top Up Saldo — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="admin-body">

<div class="admin-shell">
  <!-- Sidebar Navigation -->
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
      <a href="pesanan.php">
        <span>Kelola Pesanan</span>
      </a>
      <a href="layanan_voucher.php">
        <span>Layanan & Voucher</span>
      </a>
      <a class="active" href="topup.php">
        <span>Top Up Saldo</span>
        <?php if($pendingCount > 0): ?>
          <b><?=$pendingCount?></b>
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

  <!-- Main Content Area -->
  <section class="admin-main">
    <header class="admin-header">
      <div>
        <div class="eyebrow">PENGELOLAAN SALDO</div>
        <h1>Pengajuan Top Up Saldo</h1>
        <p>Verifikasi bukti pembayaran pengisian saldo pelanggan melalui tunai maupun QRIS.</p>
      </div>
      <div class="header-actions">
        <a class="outline-btn" href="topup.php">Refresh</a>
        <a class="primary-btn" href="../topup.php" target="_blank">Form Top Up Publik</a>
      </div>
    </header>

    <?php if($msg): ?>
      <div class="success"><?=htmlspecialchars($msg)?></div>
    <?php endif; ?>
    <?php if($err): ?>
      <div class="alert"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <!-- Pending Stat -->
    <div class="admin-stat" style="display:inline-block;margin-bottom:20px;min-width:240px">
      <div style="display:flex;align-items:center;gap:8px">
        <span>MENUNGGU VERIFIKASI</span>
      </div>
      <strong><?=$pendingCount?> Pengajuan</strong>
      <small>Perlu persetujuan Admin</small>
    </div>

    <!-- Table Card -->
    <div class="admin-card">
      <div class="card-head">
        <div>
          <h2>Riwayat & Pengajuan Top Up</h2>
          <p>Daftar 30 pengajuan top up terbaru oleh pelanggan.</p>
        </div>
      </div>

      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Kode Referensi</th>
              <th>Nama Pelanggan</th>
              <th>Nomor HP</th>
              <th>Nominal Pengisian</th>
              <th>Metode</th>
              <th>Waktu Pengajuan</th>
              <th>Status</th>
              <th style="text-align:center">Aksi Verifikasi</th>
            </tr>
          </thead>
          <tbody>
            <?php if($list && $list->num_rows > 0): ?>
              <?php while($x = $list->fetch_assoc()): ?>
                <tr>
                  <td>
                    <b style="color:var(--brand)"><?=htmlspecialchars($x['reference_no'])?></b>
                  </td>
                  <td>
                    <b><?=htmlspecialchars($x['nama'])?></b>
                  </td>
                  <td>
                    <?=htmlspecialchars($x['no_hp'])?>
                  </td>
                  <td>
                    <b style="font-size:15px"><?=rp($x['nominal'])?></b>
                  </td>
                  <td>
                    <span class="status-pill <?=(strtolower($x['metode'])==='qris'?'diterima':'menunggu')?>">
                      <?=strtoupper($x['metode'])?>
                    </span>
                  </td>
                  <td>
                    <small class="muted">
                      <?=!empty($x['created_at']) ? date('d/m/Y H:i', strtotime($x['created_at'])) : '-'?>
                    </small>
                  </td>
                  <td>
                    <?php 
                      $stClass = match($x['status']) {
                        'berhasil' => 'selesai',
                        'ditolak' => 'dibatalkan',
                        default => 'menunggu'
                      };
                    ?>
                    <span class="status-pill <?=$stClass?>">
                      <?=htmlspecialchars(ucwords($x['status']))?>
                    </span>
                  </td>
                  <td style="text-align:center">
                    <?php if($x['status'] === 'menunggu'): ?>
                      <form method="post" style="display:inline-flex;gap:6px;align-items:center;margin:0" onsubmit="return confirm('Proses pengajuan top up ini?')">
                        <input type="hidden" name="id_transaksi" value="<?=$x['id_transaksi']?>">
                        <button class="small-btn primary" name="action" value="approve">
                          Setujui
                        </button>
                        <button class="small-btn" name="action" value="reject" style="color:var(--danger)">
                          Tolak
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="muted">- Selesai -</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" style="text-align:center;padding:24px;color:var(--muted)">Belum ada data pengajuan top up saldo.</td>
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
