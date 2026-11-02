<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/notifikasi_helper.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pelanggan') {
    header('Location: ../auth/login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pelanggan';
$uid = (int)$_SESSION['id_user'];
laundry_ensure_schema($conn);

$qn = $conn->prepare('SELECT nama FROM users WHERE id_user=?'); 
$qn->bind_param('i', $uid); 
$qn->execute(); 
$rn = $qn->get_result()->fetch_assoc(); 
$qn->close();
if ($rn && trim((string)$rn['nama']) !== '') { 
    $nama = $rn['nama']; 
    $_SESSION['nama'] = $nama; 
}

$orders = 0;
$saldo = 0;
$ids = [$uid]; 
$ph = $conn->prepare('SELECT no_hp FROM users WHERE id_user=?'); 
$ph->bind_param('i', $uid); 
$ph->execute(); 
$phRow = $ph->get_result()->fetch_assoc(); 
$ph->close();

if ($phRow && trim((string)$phRow['no_hp']) !== '') {
    $ids = array_values(array_unique(array_merge($ids, laundry_user_ids_by_phone($conn, (string)$phRow['no_hp']))));
}
$idList = implode(',', array_map('intval', $ids));
$stmt = $conn->prepare("SELECT COUNT(*) jumlah FROM pesanan WHERE id_user IN ($idList)");
$stmt->execute(); 
$orders = (int)($stmt->get_result()->fetch_assoc()['jumlah'] ?? 0);

$stmt = $conn->prepare('SELECT saldo FROM dompet WHERE id_user=? LIMIT 1');
$stmt->bind_param('i', $uid); 
$stmt->execute(); 
$wallet = $stmt->get_result()->fetch_assoc(); 
if ($wallet) $saldo = (float)$wallet['saldo'];

$profileMsg = ''; $profileErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $newNama = trim($_POST['nama'] ?? '');
    $newHp = trim($_POST['no_hp'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    if ($newNama === '') { 
        $profileErr = 'Nama tidak boleh kosong.'; 
    } else {
        $upd = $conn->prepare('UPDATE users SET nama=?, no_hp=?, email=? WHERE id_user=?');
        $upd->bind_param('sssi', $newNama, $newHp, $newEmail, $uid);
        if ($upd->execute()) {
            $_SESSION['nama'] = $newNama;
            $nama = $newNama;
            $profileMsg = 'Profil berhasil diperbarui.';
        } else { 
            $profileErr = 'Gagal memperbarui profil.'; 
        }
        $upd->close();
    }
}

// Also fetch current email and phone for the form
$qemail = $conn->prepare('SELECT email, no_hp FROM users WHERE id_user=? LIMIT 1');
$qemail->bind_param('i', $uid); 
$qemail->execute();
$userRow = $qemail->get_result()->fetch_assoc(); 
$qemail->close();
$currentEmail = $userRow['email'] ?? '';
$currentHp = $userRow['no_hp'] ?? '';

function rupiahPelanggan($value) { 
    return 'Rp ' . number_format((float)$value, 0, ',', '.'); 
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Pelanggan — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="customer-dashboard">

<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="../index.php">LaundryKu</a>
    <div class="nav-links">
      <a href="../pesan.php">Order</a>
      <a href="../tracking.php">Tracking</a>
      <a href="../riwayat.php">Riwayat</a>
      <a href="../saldo.php">Saldo</a>
      <a href="../chatbot.php">Chatbot</a>
      <a class="admin-link" href="../auth/logout.php">Keluar</a>
    </div>
  </div>
</nav>

<main class="container customer-welcome">
  <!-- Customer Hero Card -->
  <div class="customer-hero">
    <span class="pill" style="background:rgba(225,29,46,0.3);color:#fff;border-color:rgba(255,255,255,0.25)">
      AREA PELANGGAN
    </span>
    <h1>Halo, <?=htmlspecialchars($nama, ENT_QUOTES, 'UTF-8')?>!</h1>
    <p>Welcome back! Order cucian, pantau prosesnya live, dan bayar pakai saldo tanpa ribet.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a class="btn" href="../pesan.php" style="background:#ffffff;color:var(--brand)!important;border-color:#ffffff">
        Buat Pesanan Baru
      </a>
      <a class="btn secondary" href="../topup.php">
        Isi Saldo Dompet
      </a>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="stats customer-stats">
    <div class="stat">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <small>TOTAL ORDER</small>
        <span class="brand-badge" style="width:28px;height:28px;font-size:11px">LK</span>
      </div>
      <strong><?= $orders ?></strong>
      <span class="muted" style="font-size:12px">Order yang tercatat di akunmu</span>
    </div>
    <div class="stat">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <small>SALDO KAMU</small>
        <span class="brand-badge" style="width:28px;height:28px;font-size:11px">RP</span>
      </div>
      <strong><?=rupiahPelanggan($saldo)?></strong>
      <span class="muted" style="font-size:12px">Bisa langsung dipakai pas checkout</span>
    </div>
  </div>

  <!-- Edit Profile Section -->
  <div class="card" style="margin-top:24px;background:#ffffff;border:1px solid var(--line);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-xs)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
      <div>
        <h2 style="font-size:18px;margin:0 0 4px">Profil Saya</h2>
        <p style="margin:0;font-size:13px;color:var(--muted)">Atur nama, nomor HP, dan emailmu di sini.</p>
      </div>
      <span class="pill" style="font-size:12px;padding:4px 12px">Pelanggan Aktif</span>
    </div>

    <?php if($profileMsg): ?>
      <div class="success" style="margin-bottom:16px"><?=htmlspecialchars($profileMsg)?></div>
    <?php endif; ?>
    <?php if($profileErr): ?>
      <div class="alert" style="margin-bottom:16px"><?=htmlspecialchars($profileErr)?></div>
    <?php endif; ?>

    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;align-items:flex-end">
      <input type="hidden" name="action" value="update_profile">
      
      <div>
        <label for="nama" style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--ink)">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" value="<?=htmlspecialchars($nama)?>" required style="width:100%;padding:10px 14px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:14px">
      </div>

      <div>
        <label for="no_hp" style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--ink)">Nomor WhatsApp / HP</label>
        <input type="text" id="no_hp" name="no_hp" value="<?=htmlspecialchars($currentHp)?>" placeholder="08xxxxxxxxxx" style="width:100%;padding:10px 14px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:14px">
      </div>

      <div>
        <label for="email" style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:var(--ink)">Alamat Email</label>
        <input type="email" id="email" name="email" value="<?=htmlspecialchars($currentEmail)?>" placeholder="email@contoh.com" style="width:100%;padding:10px 14px;border:1px solid var(--line);border-radius:var(--radius-sm);font-size:14px">
      </div>

      <div style="grid-column:1/-1;display:flex;justify-content:flex-end;margin-top:4px">
        <button type="submit" class="btn" style="padding:10px 24px;font-size:14px">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

  <!-- Shortcuts Grid -->
  <div class="section-title" style="text-align:left;margin-top:32px;margin-bottom:18px">
    <h2 style="font-size:20px">Menu Cepat</h2>
    <p>Langsung ke fitur yang kamu butuhin.</p>
  </div>

  <div class="customer-shortcuts">
    <a class="feature-card link-card" href="../pesan.php">
      <b>Pesan Laundry Baru</b>
      <span>Pilih paket kiloan atau satuan dan tentukan antar-jemput.</span>
    </a>
    <a class="feature-card link-card" href="../tracking.php">
      <b>Lacak Cucian (Tracking)</b>
      <span>Pantau proses cuci, kering, dan setrika secara real-time.</span>
    </a>
    <a class="feature-card link-card" href="../riwayat.php">
      <b>Riwayat Pesanan</b>
      <span>Lihat seluruh catatan transaksi dan status cucian lampau.</span>
    </a>
    <a class="feature-card link-card" href="../saldo.php">
      <b>Cek Saldo Dompet</b>
      <span>Periksa sisa saldo dan informasi pengisian saldo Anda.</span>
    </a>
    <a class="feature-card link-card" href="../topup.php">
      <b>Pengajuan Top Up</b>
      <span>Tambah saldo dompet via Cash atau scan QRIS instan.</span>
    </a>
    <a class="feature-card link-card" href="../chatbot.php">
      <b>Asisten Chatbot 24/7</b>
      <span>Tanyakan tarif, jangkauan antar-jemput, dan info laundry.</span>
    </a>
  </div>
</main>

<script src="../assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
</body>
</html>
