<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../config/notifikasi_helper.php';
laundry_ensure_schema($conn);

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'kasir') {
    header('Location: ../auth/login.php'); 
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp '.number_format((float)$n, 0, ',', '.'); }

$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='confirm_payment') {
    $paymentId=(int)($_POST['id_pembayaran'] ?? 0);
    try {
        if ($paymentId < 1) throw new Exception('Data pembayaran tidak valid.');
        $conn->begin_transaction();
        $st=$conn->prepare("SELECT pb.id_pembayaran,pb.id_pesanan,pb.jumlah,pb.status,p.kode_pesanan,p.id_user FROM pembayaran pb JOIN pesanan p ON p.id_pesanan=pb.id_pesanan WHERE pb.id_pembayaran=? FOR UPDATE");
        $st->bind_param('i',$paymentId); 
        $st->execute(); 
        $payment=$st->get_result()->fetch_assoc(); 
        $st->close();

        if (!$payment) throw new Exception('Pembayaran tidak ditemukan.');
        if ($payment['status'] !== 'menunggu') throw new Exception('Pembayaran ini sudah pernah diproses.');
        
        $status='lunas'; 
        $paidAt=date('Y-m-d H:i:s');
        $st=$conn->prepare("UPDATE pembayaran SET status=?,paid_at=? WHERE id_pembayaran=? AND status='menunggu'");
        $st->bind_param('ssi',$status,$paidAt,$paymentId); 
        $st->execute();
        if ($st->affected_rows !== 1) throw new Exception('Pembayaran gagal diubah. Silakan muat ulang halaman.'); 
        $st->close();

        $title='Pembayaran Dikonfirmasi';
        $message='Pembayaran untuk pesanan '.$payment['kode_pesanan'].' sebesar '.rp($payment['jumlah']).' telah berhasil diverifikasi oleh kasir.';
        $jenis='pembayaran'; 
        $unread=0;
        $st=$conn->prepare("INSERT INTO notifikasi (id_user,id_pesanan,judul,pesan,jenis,sudah_dibaca) VALUES (?,?,?,?,?,?)");
        $st->bind_param('iisssi',$payment['id_user'],$payment['id_pesanan'],$title,$message,$jenis,$unread); 
        $st->execute(); 
        $st->close();

        $conn->commit(); 
        $msg='Pembayaran pesanan '.$payment['kode_pesanan'].' berhasil dikonfirmasi sebagai LUNAS.';
    } catch(Throwable $e) { 
        if($conn->errno || $conn->connect_errno===0) { 
            try{$conn->rollback();}catch(Throwable $ignored){} 
        } 
        $err=$e->getMessage(); 
    }
}

$pending=$conn->query("SELECT pb.id_pembayaran,pb.id_pesanan,pb.metode,pb.jumlah,pb.reference_no,pb.status,p.kode_pesanan,p.tanggal_pesanan,u.nama,u.no_hp FROM pembayaran pb JOIN pesanan p ON p.id_pesanan=pb.id_pesanan JOIN users u ON u.id_user=p.id_user WHERE pb.status='menunggu' ORDER BY pb.id_pembayaran ASC");
$recent=$conn->query("SELECT pb.id_pembayaran,pb.metode,pb.jumlah,pb.reference_no,pb.status,pb.paid_at,p.kode_pesanan,u.nama FROM pembayaran pb JOIN pesanan p ON p.id_pesanan=pb.id_pesanan JOIN users u ON u.id_user=p.id_user ORDER BY pb.id_pembayaran DESC LIMIT 12");
$countPending=$pending->num_rows;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel Kasir — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="admin-body">

<main class="cashier-wrap">
  <!-- Header Bar -->
  <header class="cashier-head">
    <div>
      <div class="eyebrow">PORTAL KASIR OUTLET</div>
      <h1>Konfirmasi Pembayaran</h1>
      <p class="cashier-note">Kelola dan verifikasi pembayaran Cash & QRIS pelanggan dengan cepat dan aman.</p>
    </div>
    <nav class="cashier-nav">
      <a href="index.php" style="background:var(--brand);color:#fff;border-color:var(--brand)">Kasir</a>
      <a href="topup.php">Verifikasi Top Up</a>
      <a href="../index.php">Website Publik</a>
      <a href="../auth/logout.php" style="color:var(--danger)">Keluar</a>
    </nav>
  </header>

  <?php if($msg): ?>
    <div class="cashier-alert cashier-success"><?=h($msg)?></div>
  <?php endif; ?>
  <?php if($err): ?>
    <div class="cashier-alert cashier-error"><?=h($err)?></div>
  <?php endif; ?>

  <!-- Cashier Pending Stat -->
  <div class="cashier-stat">
    <div style="display:flex;align-items:center;gap:8px">
      <span style="font-size:12px;color:var(--muted);font-weight:700;letter-spacing:0.05em">PEMBAYARAN MENUNGGU KONFIRMASI</span>
    </div>
    <strong><?=$countPending?> Transaksi</strong>
    <small class="muted">Perlu diperiksa dan diverifikasi oleh kasir</small>
  </div>

  <!-- Pending Payments Section -->
  <section class="cashier-panel">
    <h2>Pembayaran Menunggu Verifikasi</h2>
    <p class="cashier-muted">Pastikan pembayaran fisik (uang tunai) telah diterima atau mutasi QRIS telah berhasil sebelum menekan konfirmasi.</p>
    
    <div class="table-wrap">
      <table class="cashier-table">
        <thead>
          <tr>
            <th>Kode Pesanan</th>
            <th>Pelanggan</th>
            <th>Waktu Pemesanan</th>
            <th>Metode</th>
            <th>Nomor Referensi</th>
            <th>Jumlah Tagihan</th>
            <th style="text-align:center">Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody>
          <?php if($countPending === 0): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:24px;color:var(--muted)">
                Seluruh pembayaran telah terverifikasi. Tidak ada tagihan yang menunggu konfirmasi saat ini.
              </td>
            </tr>
          <?php else: ?>
            <?php while($p = $pending->fetch_assoc()): ?>
              <tr>
                <td>
                  <b style="color:var(--brand)"><?=h($p['kode_pesanan'])?></b>
                </td>
                <td>
                  <b><?=h($p['nama'])?></b><br>
                  <small class="muted"><?=h($p['no_hp'])?></small>
                </td>
                <td>
                  <?=h(date('d/m/Y H:i', strtotime($p['tanggal_pesanan'])))?>
                </td>
                <td>
                  <span class="status-pill <?=(strtolower($p['metode'])==='qris'?'diterima':'menunggu')?>">
                    <?=h(strtoupper((string)$p['metode']))?>
                  </span>
                </td>
                <td>
                  <code><?=h($p['reference_no'] ?: '-')?></code>
                </td>
                <td>
                  <b style="font-size:15px"><?=rp($p['jumlah'])?></b>
                </td>
                <td style="text-align:center">
                  <form method="post" onsubmit="return confirm('Konfirmasi pembayaran <?=h($p['kode_pesanan'])?> sebesar <?=rp($p['jumlah'])?> sebagai LUNAS?')">
                    <input type="hidden" name="id_pembayaran" value="<?=(int)$p['id_pembayaran']?>">
                    <button class="cashier-btn" type="submit" name="action" value="confirm_payment">
                      Konfirmasi Lunas
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Recent Payments History -->
  <section class="cashier-panel">
    <h2>Riwayat Pembayaran Terbaru</h2>
    <p class="cashier-muted">Daftar transaksi pembayaran terakhir yang telah tercatat pada sistem.</p>
    
    <div class="table-wrap">
      <table class="cashier-table">
        <thead>
          <tr>
            <th>Kode Pesanan</th>
            <th>Nama Pelanggan</th>
            <th>Metode</th>
            <th>Nominal</th>
            <th>Status Pembayaran</th>
            <th>Waktu Konfirmasi</th>
          </tr>
        </thead>
        <tbody>
          <?php if($recent->num_rows === 0): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">Belum ada riwayat pembayaran yang tercatat.</td>
            </tr>
          <?php else: ?>
            <?php while($r = $recent->fetch_assoc()): ?>
              <tr>
                <td>
                  <b><?=h($r['kode_pesanan'])?></b>
                </td>
                <td>
                  <?=h($r['nama'])?>
                </td>
                <td>
                  <span class="status-pill <?=(strtolower($r['metode'])==='qris'?'diterima':'menunggu')?>">
                    <?=h(strtoupper((string)$r['metode']))?>
                  </span>
                </td>
                <td>
                  <b><?=rp($r['jumlah'])?></b>
                </td>
                <td>
                  <span class="status-pill <?=htmlspecialchars($r['status'])?>">
                    <?=h(ucwords((string)$r['status']))?>
                  </span>
                </td>
                <td>
                  <?=h($r['paid_at'] ? date('d/m/Y H:i', strtotime($r['paid_at'])) : '-')?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

</body>
</html>
