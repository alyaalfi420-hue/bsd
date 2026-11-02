<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../config/ensure_catalog.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') { 
    header('Location: ../auth/login.php'); 
    exit; 
}

function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function rp2($n){return 'Rp '.number_format((float)$n,0,',','.');}

$notice=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    try {
        if ($action==='save_service') {
            $id=(int)($_POST['id_layanan']??0); 
            $price=(float)($_POST['harga']??0); 
            $status=($_POST['status']??'aktif')==='aktif'?'aktif':'nonaktif';
            if($id<1 || $price<0) throw new Exception('Harga layanan tidak valid.');
            $st=$conn->prepare('UPDATE layanan SET harga=?, status=? WHERE id_layanan=?'); 
            $st->bind_param('dsi',$price,$status,$id); 
            $st->execute(); 
            $st->close(); 
            $notice='Perubahan tarif dan status layanan berhasil disimpan.';
        } elseif ($action==='save_voucher') {
            $id=(int)($_POST['id_voucher']??0); 
            $status=($_POST['status']??'aktif')==='aktif'?'aktif':'nonaktif';
            $st=$conn->prepare('UPDATE voucher SET status=? WHERE id_voucher=?'); 
            $st->bind_param('si',$status,$id); 
            $st->execute(); 
            $st->close(); 
            $notice='Status voucher berhasil diperbarui.';
        } elseif ($action==='add_voucher') {
            $code=strtoupper(trim($_POST['kode']??'')); 
            $name=trim($_POST['nama']??''); 
            $desc=trim($_POST['deskripsi']??'');
            $kind=($_POST['jenis_diskon']??'persen')==='nominal'?'nominal':'persen'; 
            $value=(float)($_POST['nilai_diskon']??0); 
            $min=(float)($_POST['minimal_transaksi']??0); 
            $maxRaw=trim($_POST['maksimal_diskon']??''); 
            $max=$maxRaw===''?null:(float)$maxRaw;
            $start=str_replace('T',' ',trim($_POST['tanggal_mulai']??'')); 
            $end=str_replace('T',' ',trim($_POST['tanggal_berakhir']??''));
            
            if(!preg_match('/^[A-Z0-9_-]{3,30}$/',$code)) throw new Exception('Kode voucher harus 3–30 karakter (huruf kapital, angka, - atau _).');
            if($name==='' || $value<=0 || $min<0 || ($kind==='persen' && $value>100) || ($max!==null && $max<0)) {
                throw new Exception('Periksa kembali nama, nilai diskon, minimum transaksi, dan batas diskon.');
            }
            if($start==='' || $end==='' || strtotime($end)<=strtotime($start)) {
                throw new Exception('Tanggal berakhir harus lebih lama dari tanggal mulai.');
            }
            $check=$conn->prepare('SELECT id_voucher FROM voucher WHERE UPPER(kode)=? LIMIT 1'); 
            $check->bind_param('s',$code); 
            $check->execute(); 
            $exists=$check->get_result()->fetch_assoc(); 
            $check->close(); 
            if($exists) throw new Exception('Kode voucher tersebut sudah terdaftar.');

            $st=$conn->prepare("INSERT INTO voucher (kode,nama,deskripsi,jenis_diskon,nilai_diskon,minimal_transaksi,maksimal_diskon,tanggal_mulai,tanggal_berakhir,status) VALUES (?,?,?,?,?,?,?,?,?,'aktif')");
            $st->bind_param('ssssdddss',$code,$name,$desc,$kind,$value,$min,$max,$start,$end); 
            $st->execute(); 
            $st->close(); 
            $notice='Voucher baru berhasil ditambahkan dan langsung aktif!';
        }
    } catch(Throwable $e) { 
        $error=$e->getMessage() ?: 'Perubahan gagal disimpan.'; 
    }
}

$services=$conn->query('SELECT id_layanan,nama_layanan,harga,satuan,deskripsi,estimasi_jam,status FROM layanan ORDER BY id_layanan')->fetch_all(MYSQLI_ASSOC);
$vouchers=$conn->query('SELECT id_voucher,kode,nama,deskripsi,jenis_diskon,nilai_diskon,minimal_transaksi,maksimal_diskon,tanggal_mulai,tanggal_berakhir,status FROM voucher ORDER BY id_voucher DESC')->fetch_all(MYSQLI_ASSOC);
$pendingTopup = (int)($conn->query("SELECT COUNT(*) c FROM transaksi_dompet WHERE jenis='topup' AND status='menunggu'")->fetch_assoc()['c'] ?? 0);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Layanan & Voucher — LaundryKu</title>
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
      <a class="active" href="layanan_voucher.php">
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

  <!-- Main Content Area -->
  <section class="admin-main">
    <header class="admin-header">
      <div>
        <div class="eyebrow">KATALOG & PROMOSI</div>
        <h1>Kelola Layanan & Voucher</h1>
        <p>Atur tarif layanan per kg/satuan, aktifkan paket, dan terbitkan voucher promo baru.</p>
      </div>
      <div class="header-actions">
        <a class="outline-btn" href="../index.php#layanan" target="_blank">Lihat Layanan Publik</a>
      </div>
    </header>

    <?php if($notice): ?>
      <div class="notice"><?=h($notice)?></div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="error"><?=h($error)?></div>
    <?php endif; ?>

    <!-- Layanan Laundry Table -->
    <section class="admin-card" style="margin-bottom:24px">
      <div class="card-head">
        <div>
          <h2>Daftar Paket Layanan Laundry</h2>
          <p>Ubah tarif dan status aktifitas layanan yang tampil pada halaman pemesanan.</p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nama Paket Layanan</th>
              <th>Harga Satuan</th>
              <th>Satuan</th>
              <th>Estimasi Selesai</th>
              <th>Status</th>
              <th style="text-align:center">Aksi Simpan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($services as $s): ?>
              <tr>
                <td>
                  <b><?=h($s['nama_layanan'])?></b><br>
                  <small class="hint"><?=h($s['deskripsi'])?></small>
                </td>
                <td>
                  <form method="post" style="display:flex;gap:8px;align-items:center;margin:0">
                    <input type="hidden" name="action" value="save_service">
                    <input type="hidden" name="id_layanan" value="<?=$s['id_layanan']?>">
                    <input class="price-input" type="number" name="harga" min="0" step="500" value="<?=h($s['harga'])?>" required style="width:130px;padding:8px 10px">
                </td>
                <td>
                  <b>/ <?=h($s['satuan'])?></b>
                </td>
                <td>
                  <?=h($s['estimasi_jam'])?> jam
                </td>
                <td>
                  <select class="status-select" name="status" style="padding:8px 10px">
                    <option value="aktif" <?=$s['status']==='aktif'?'selected':''?>>Aktif</option>
                    <option value="nonaktif" <?=$s['status']!=='aktif'?'selected':''?>>Nonaktif</option>
                  </select>
                </td>
                <td style="text-align:center">
                    <button class="small-btn primary" type="submit">
                      Simpan
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Voucher Table -->
    <section class="admin-card" style="margin-bottom:24px">
      <div class="card-head">
        <div>
          <h2>Daftar Voucher Promo Aktif</h2>
          <p>Kelola voucher promo yang dapat digunakan pelanggan saat checkout.</p>
        </div>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Kode Promo</th>
              <th>Nama Voucher</th>
              <th>Nilai Diskon</th>
              <th>Min. Transaksi</th>
              <th>Masa Berlaku</th>
              <th>Status</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!$vouchers): ?>
              <tr>
                <td colspan="7" style="text-align:center;padding:20px;color:var(--muted)">Belum ada voucher promo.</td>
              </tr>
            <?php else: ?>
              <?php foreach($vouchers as $v): ?>
                <tr>
                  <td>
                    <b style="color:var(--brand);font-size:15px"><?=h($v['kode'])?></b>
                  </td>
                  <td>
                    <b><?=h($v['nama'])?></b><br>
                    <small class="hint"><?=h($v['deskripsi'])?></small>
                  </td>
                  <td>
                    <b style="color:var(--success)">
                      <?=h($v['jenis_diskon']==='persen' ? $v['nilai_diskon'].'%' : rp2($v['nilai_diskon']))?>
                    </b>
                    <?php if($v['maksimal_diskon']!==null): ?>
                      <br><small class="hint">Maks. <?=rp2($v['maksimal_diskon'])?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?=rp2($v['minimal_transaksi'])?>
                  </td>
                  <td>
                    <small class="muted">
                      <?=h($v['tanggal_mulai'])?><br>s.d. <?=h($v['tanggal_berakhir'])?>
                    </small>
                  </td>
                  <td>
                    <span class="status-pill <?=$v['status']==='aktif'?'selesai':'dibatalkan'?>">
                      <?=h(ucwords($v['status']))?>
                    </span>
                  </td>
                  <td style="text-align:center">
                    <form method="post" style="display:inline-flex;gap:6px;align-items:center;margin:0">
                      <input type="hidden" name="action" value="save_voucher">
                      <input type="hidden" name="id_voucher" value="<?=$v['id_voucher']?>">
                      <select name="status" style="font-size:12px;padding:6px 10px">
                        <option value="aktif" <?=$v['status']==='aktif'?'selected':''?>>Aktif</option>
                        <option value="nonaktif" <?=$v['status']!=='aktif'?'selected':''?>>Nonaktif</option>
                      </select>
                      <button class="small-btn" type="submit">
                        Ubah
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Create New Voucher Form -->
    <section class="admin-card">
      <div class="card-head">
        <div>
          <h2>Terbitkan Voucher Baru</h2>
          <p>Voucher yang dibuat akan langsung aktif dan dapat digunakan pelanggan saat memesan.</p>
        </div>
      </div>
      <form method="post" class="voucher-form">
        <input type="hidden" name="action" value="add_voucher">
        
        <div class="field">
          <label for="kode">Kode Voucher (Kapital, tanpa spasi)</label>
          <input id="kode" name="kode" placeholder="Misal: PROMOHEMAT20" required maxlength="30" style="text-transform:uppercase">
        </div>

        <div class="field">
          <label for="nama">Nama Promo</label>
          <input id="nama" name="nama" placeholder="Contoh: Diskon Pelanggan Setia" required>
        </div>

        <div class="field">
          <label for="jenis">Jenis Diskon</label>
          <select id="jenis" name="jenis_diskon">
            <option value="persen">Persentase (%)</option>
            <option value="nominal">Nominal Tunai (Rp)</option>
          </select>
        </div>

        <div class="field">
          <label for="nilai">Nilai Diskon</label>
          <input id="nilai" name="nilai_diskon" type="number" min="1" step="1" placeholder="Contoh: 10 (jika %) atau 10000 (jika Rp)" required>
        </div>

        <div class="field">
          <label for="minimal">Minimum Transaksi (Rp)</label>
          <input id="minimal" name="minimal_transaksi" type="number" min="0" step="1000" value="0" required>
        </div>

        <div class="field">
          <label for="maksimal">Maksimum Diskon (Rp, opsional)</label>
          <input id="maksimal" name="maksimal_diskon" type="number" min="0" step="1000" placeholder="Kosongkan jika tanpa batas">
        </div>

        <div class="field">
          <label for="mulai">Tanggal Mulai Berlaku</label>
          <input id="mulai" name="tanggal_mulai" type="datetime-local" required value="<?=date('Y-m-d\TH:i')?>">
        </div>

        <div class="field">
          <label for="akhir">Tanggal Berakhir</label>
          <input id="akhir" name="tanggal_berakhir" type="datetime-local" required value="<?=date('Y-m-d\TH:i',strtotime('+30 days'))?>">
        </div>

        <div class="field full">
          <label for="deskripsi">Deskripsi & Syarat Penggunaan</label>
          <input id="deskripsi" name="deskripsi" placeholder="Syarat atau keterangan ketentuan voucher">
        </div>

        <div class="field full" style="margin-top:10px">
          <button class="btn" type="submit">
            Terbitkan & Aktifkan Voucher
          </button>
        </div>
      </form>
    </section>

  </section>
</div>

</body>
</html>
