<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
if(!isset($_SESSION['id_user']) || ($_SESSION['role']??'')!=='admin'){
    header('Location: ../auth/login.php');
    exit;
}
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
function total($conn,$sql){$r=$conn->query($sql);return (int)$r->fetch_assoc()['total'];}
$totalPesanan=total($conn,"SELECT COUNT(*) total FROM pesanan");
$proses=total($conn,"SELECT COUNT(*) total FROM pesanan WHERE status_pesanan NOT IN ('selesai','terkirim','diambil','dibatalkan')");
$pelanggan=total($conn,"SELECT COUNT(*) total FROM users WHERE role='pelanggan'");
$kasir=total($conn,"SELECT COUNT(*) total FROM users WHERE role='kasir'");
$kurir=total($conn,"SELECT COUNT(*) total FROM users WHERE role='kurir'");
$topup=total($conn,"SELECT COUNT(*) total FROM transaksi_dompet WHERE jenis='topup' AND status='menunggu'");
$recent=$conn->query("SELECT p.kode_pesanan,u.nama,p.status_pesanan,p.total_estimasi,p.tanggal_pesanan FROM pesanan p JOIN users u ON u.id_user=p.id_user ORDER BY p.id_pesanan DESC LIMIT 8");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Admin — LaundryKu</title>
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
      <a class="active" href="index.php">
        <span>Dashboard</span>
      </a>
      <a href="pesanan.php">
        <span>Kelola Pesanan</span>
      </a>
      <a href="layanan_voucher.php">
        <span>Layanan & Voucher</span>
      </a>
      <a href="topup.php">
        <span>Top Up Saldo</span>
        <?php if($topup > 0): ?>
          <b><?=$topup?></b>
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
        <div class="eyebrow">RINGKASAN OPERASIONAL</div>
        <h1>Dashboard Administrator</h1>
        <p>Selamat datang kembali, <b><?=htmlspecialchars($_SESSION['nama']??'Admin')?></b>. Berikut ringkasan aktivitas LaundryKu.</p>
      </div>
      <div class="header-actions">
        <a class="outline-btn" href="../index.php" target="_blank">Website Publik</a>
        <a class="primary-btn" href="topup.php">
          Top Up Menunggu <?php if($topup>0): ?><span style="background:rgba(255,255,255,0.25);padding:2px 7px;border-radius:99px;font-size:12px"><?=$topup?></span><?php endif; ?>
        </a>
      </div>
    </header>

    <!-- Stat Cards -->
    <div class="stat-grid">
      <div class="admin-stat">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>TOTAL PESANAN</span>
        </div>
        <strong><?=$totalPesanan?></strong>
        <small>Seluruh pesanan masuk</small>
      </div>

      <div class="admin-stat">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>SEDANG DIPROSES</span>
        </div>
        <strong><?=$proses?></strong>
        <small>Pesanan aktif</small>
      </div>

      <div class="admin-stat">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>PELANGGAN</span>
        </div>
        <strong><?=$pelanggan?></strong>
        <small>Akun pelanggan terdaftar</small>
      </div>

      <div class="admin-stat">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>KASIR OUTLET</span>
        </div>
        <strong><?=$kasir?></strong>
        <small>Petugas kasir aktif</small>
      </div>

      <div class="admin-stat">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span>TIM KURIR</span>
        </div>
        <strong><?=$kurir?></strong>
        <small>Kurir antar-jemput aktif</small>
      </div>
    </div>

    <!-- Admin Content Grid -->
    <div class="admin-grid">
      <!-- Recent Orders Table -->
      <section class="admin-card wide">
        <div class="card-head">
          <div>
            <h2>Pesanan Masuk Terbaru</h2>
            <p>Daftar 8 transaksi pesanan paling baru yang masuk ke sistem.</p>
          </div>
          <a class="small-btn" href="pesanan.php">Lihat Semua Pesanan</a>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Kode Pesanan</th>
                <th>Nama Pelanggan</th>
                <th>Status Cucian</th>
                <th>Estimasi Total</th>
                <th>Waktu Transaksi</th>
                <th style="text-align:center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if($recent && $recent->num_rows > 0): ?>
                <?php while($x = $recent->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <b style="color:var(--brand)"><?=htmlspecialchars($x['kode_pesanan'])?></b>
                    </td>
                    <td>
                      <b><?=htmlspecialchars($x['nama'])?></b>
                    </td>
                    <td>
                      <span class="status-pill <?=htmlspecialchars($x['status_pesanan'])?>">
                        <?=ucwords(str_replace('_',' ',$x['status_pesanan']))?>
                      </span>
                    </td>
                    <td>
                      <b><?=rp($x['total_estimasi'])?></b>
                    </td>
                    <td>
                      <?=date('d/m/Y H:i', strtotime($x['tanggal_pesanan']))?>
                    </td>
                    <td style="text-align:center">
                      <a class="small-btn primary" href="pesanan.php?status=<?=urlencode($x['status_pesanan'])?>">
                        Kelola
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">Belum ada data pesanan yang masuk.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Quick Action Shortcuts -->
      <section class="admin-card">
        <div class="card-head">
          <div>
            <h2>Akses Cepat Pengelolaan</h2>
            <p>Pintasan ke modul utama yang sering digunakan.</p>
          </div>
        </div>
        <div class="quick-grid">
          <a href="layanan_voucher.php" class="quick-card">
            <strong>Layanan & Voucher</strong>
            <span>Kelola tarif kg/satuan, aktifasi layanan & kode voucher promo</span>
          </a>
          <a href="topup.php" class="quick-card">
            <strong>Verifikasi Saldo Top Up</strong>
            <span>Ada <b><?=$topup?></b> pengajuan top up saldo yang menunggu verifikasi</span>
          </a>
          <a href="pesanan.php" class="quick-card">
            <strong>Pengelolaan Pesanan</strong>
            <span>Terima pesanan, timbang berat aktual, dan tugaskan kurir</span>
          </a>
        </div>
      </section>
    </div>
  </section>
</div>

</body>
</html>
