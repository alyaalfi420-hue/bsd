<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';

$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['id_user']) || !in_array($role, ['admin', 'kasir'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp '.number_format((float)$n, 0, ',', '.'); }

$msg = ''; 
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_transaksi'] ?? 0);
    $action = $_POST['action'] ?? '';
    $conn->begin_transaction();
    try {
        $s = $conn->prepare("SELECT t.*, d.saldo FROM transaksi_dompet t JOIN dompet d ON d.id_dompet=t.id_dompet WHERE t.id_transaksi=? FOR UPDATE");
        $s->bind_param('i', $id);
        $s->execute();
        $t = $s->get_result()->fetch_assoc();
        
        if (!$t || $t['jenis'] !== 'topup' || $t['status'] !== 'menunggu') {
            throw new Exception('Transaksi top up tidak valid atau sudah diproses.');
        }
        
        if ($action === 'approve') {
            $before = (float)$t['saldo'];
            $after = $before + (float)$t['nominal'];
            $ok = 'berhasil';
            $s = $conn->prepare("UPDATE dompet SET saldo=? WHERE id_dompet=?");
            $s->bind_param('di', $after, $t['id_dompet']);
            $s->execute();
            
            $s = $conn->prepare("UPDATE transaksi_dompet SET saldo_sebelum=?, saldo_sesudah=?, status=? WHERE id_transaksi=?");
            $s->bind_param('ddsi', $before, $after, $ok, $id);
            $s->execute();
            $msg = 'Pengajuan top up sebesar '.rp($t['nominal']).' berhasil disetujui.';
        } else {
            $tolak = 'ditolak';
            $s = $conn->prepare("UPDATE transaksi_dompet SET status=? WHERE id_transaksi=?");
            $s->bind_param('si', $tolak, $id);
            $s->execute();
            $msg = 'Pengajuan top up berhasil ditolak.';
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        $err = $e->getMessage();
    }
}

$pendingCount = 0;
$cRes = $conn->query("SELECT COUNT(*) c FROM transaksi_dompet WHERE jenis='topup' AND status='menunggu'");
if ($cRes) $pendingCount = (int)$cRes->fetch_assoc()['c'];

$list = $conn->query("SELECT t.id_transaksi, t.reference_no, t.nominal, t.metode, t.status, t.created_at, u.nama, u.no_hp FROM transaksi_dompet t JOIN dompet d ON d.id_dompet=t.id_dompet JOIN users u ON u.id_user=d.id_user WHERE t.jenis='topup' ORDER BY t.id_transaksi DESC LIMIT 35");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi Top Up — Kasir LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="admin-body">

<main class="cashier-wrap">
  <!-- Header Bar -->
  <header class="cashier-head">
    <div>
      <div class="eyebrow">PORTAL KASIR OUTLET</div>
      <h1>Verifikasi Top Up Saldo</h1>
      <p class="cashier-note">Tinjau dan setujui permintaan pengisian saldo pelanggan melalui transfer manual atau QRIS.</p>
    </div>
    <nav class="cashier-nav">
      <a href="index.php">Kasir Pesanan</a>
      <a href="topup.php" style="background:var(--brand);color:#fff;border-color:var(--brand)">Verifikasi Top Up</a>
      <a href="../index.php">Website Publik</a>
      <a href="../auth/logout.php" style="color:var(--danger)">Keluar</a>
    </nav>
  </header>

  <?php if ($msg): ?>
    <div class="cashier-alert cashier-success"><?=h($msg)?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="cashier-alert cashier-error"><?=h($err)?></div>
  <?php endif; ?>

  <!-- Cashier Pending Stat -->
  <div class="cashier-stat">
    <div style="display:flex;align-items:center;gap:8px">
      <span style="font-size:12px;color:var(--muted);font-weight:700;letter-spacing:0.05em">PERMINTAAN MENUNGGU VERIFIKASI</span>
    </div>
    <div style="font-size:32px;font-weight:900;color:<?=$pendingCount > 0 ? 'var(--warning)' : '#10b981'?>">
      <?=$pendingCount?>
    </div>
  </div>

  <!-- Table Container -->
  <div class="cashier-table-card">
    <div class="cashier-table-head">
      <h3 style="margin:0;font-size:18px;font-weight:800">
        Daftar Pengajuan Top Up
      </h3>
      <span class="muted" style="font-size:13px">35 Transaksi Terbaru</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode Referensi</th>
            <th>Pelanggan</th>
            <th>No. Handphone</th>
            <th>Nominal</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Waktu</th>
            <th>Aksi Kasir</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($list->num_rows === 0): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:40px;color:var(--muted)">Belum ada data pengajuan top up.</td>
            </tr>
          <?php else: ?>
            <?php while ($x = $list->fetch_assoc()): ?>
              <tr>
                <td><strong style="color:var(--text)"><?=h($x['reference_no'])?></strong></td>
                <td><strong><?=h($x['nama'])?></strong></td>
                <td><code style="color:var(--muted)"><?=h($x['no_hp'])?></code></td>
                <td><strong style="color:var(--text);font-size:15px"><?=rp($x['nominal'])?></strong></td>
                <td><span class="chip"><?=strtoupper(h($x['metode']))?></span></td>
                <td>
                  <?php 
                    $st = strtolower($x['status']);
                    $badgeCls = ($st==='berhasil'?'selesai':($st==='ditolak'?'dibatalkan':'menunggu'));
                  ?>
                  <span class="status-badge <?=$badgeCls?>"><?=strtoupper(h($x['status']))?></span>
                </td>
                <td style="color:var(--muted);font-size:12px"><?=date('d/m/Y H:i', strtotime($x['created_at']))?></td>
                <td>
                  <?php if ($x['status'] === 'menunggu'): ?>
                    <form method="post" style="display:flex;gap:6px" onsubmit="return confirm('Apakah Anda yakin ingin memproses transaksi ini?')">
                      <input type="hidden" name="id_transaksi" value="<?=$x['id_transaksi']?>">
                      <button class="btn btn-sm btn-primary" name="action" value="approve" type="submit" style="padding:6px 14px;font-size:12px">
                        Setujui
                      </button>
                      <button class="btn btn-sm btn-outline" name="action" value="reject" type="submit" style="padding:6px 14px;font-size:12px;color:var(--danger);border-color:var(--danger)">
                        Tolak
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="color:var(--muted);font-size:12px">— Selesai —</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

</body>
</html>
