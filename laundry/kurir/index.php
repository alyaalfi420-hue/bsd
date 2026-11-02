<?php
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../config/notifikasi_helper.php';
laundry_ensure_schema($conn);

$msg = '';
$err = '';
$token = trim($_REQUEST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $token = trim($_POST['token'] ?? '');
    $new = $_POST['status'] ?? '';
    $allowed = ['ditugaskan', 'dalam_perjalanan', 'selesai'];
    
    if (!in_array($new, $allowed, true)) {
        $err = 'Status tugas tidak valid.';
    } else {
        $s = $conn->prepare("SELECT pk.*, aj.id_pesanan, aj.jenis, p.kode_pesanan, p.id_user, p.metode_pengiriman, p.status_pesanan, u.nama pelanggan, u.no_hp, ap.alamat_lengkap, ap.latitude, ap.longitude FROM penugasan_kurir pk JOIN antar_jemput aj ON aj.id_antar_jemput=pk.id_antar_jemput JOIN pesanan p ON p.id_pesanan=aj.id_pesanan JOIN users u ON u.id_user=p.id_user LEFT JOIN alamat_pelanggan ap ON ap.id_alamat=aj.id_alamat WHERE pk.token_akses=? LIMIT 1");
        $s->bind_param('s', $token);
        $s->execute();
        $task = $s->get_result()->fetch_assoc();
        
        if (!$task) {
            $err = 'Kode token tugas tidak ditemukan.';
        } else {
            $s = $conn->prepare("UPDATE penugasan_kurir SET status=? WHERE id_penugasan=?");
            $s->bind_param('si', $new, $task['id_penugasan']);
            $s->execute();
            
            $statusPesanan = $new === 'dalam_perjalanan' ? 'dijemput' : ($new === 'selesai' ? 'diterima' : 'menunggu');
            $s = $conn->prepare("UPDATE antar_jemput SET status=? WHERE id_antar_jemput=?");
            $s->bind_param('si', $new, $task['id_antar_jemput']);
            $s->execute();
            
            if ($new === 'dalam_perjalanan') {
                $s = $conn->prepare("UPDATE pesanan SET status_pesanan='dijemput' WHERE id_pesanan=? AND status_pesanan IN ('menunggu','diterima')");
                $s->bind_param('i', $task['id_pesanan']);
                $s->execute();
                try {
                    laundry_notif_status($conn, (int)$task['id_user'], (int)$task['id_pesanan'], 'dijemput', $task['kode_pesanan']);
                } catch (Throwable $e) {
                    $err = 'Notifikasi gagal: '.$e->getMessage();
                }
            }
            
            if ($new === 'selesai') {
                $s = $conn->prepare("UPDATE pesanan SET status_pesanan='diterima' WHERE id_pesanan=? AND status_pesanan IN ('menunggu','dijemput')");
                $s->bind_param('i', $task['id_pesanan']);
                $s->execute();
            }
            
            $s = $conn->prepare("INSERT INTO tracking_pesanan(id_pesanan,status,keterangan) VALUES(?,?,?)");
            $ket = 'Kurir memperbarui tugas menjadi '.ucwords(str_replace('_', ' ', $new)).'.';
            $s->bind_param('iss', $task['id_pesanan'], $statusPesanan, $ket);
            $s->execute();
            
            if ($new === 'selesai') {
                try {
                    laundry_notif_status($conn, (int)$task['id_user'], (int)$task['id_pesanan'], 'diterima', $task['kode_pesanan']);
                } catch (Throwable $e) {
                    $err = 'Notifikasi gagal: '.$e->getMessage();
                }
            }
            $msg = 'Status tugas berhasil diperbarui ke "'.ucwords(str_replace('_', ' ', $new)).'".';
        }
    }
}

$task = null;
if ($token !== '') {
    $s = $conn->prepare("SELECT pk.*, aj.id_antar_jemput, aj.id_alamat, aj.jenis, aj.latitude, aj.longitude, aj.jarak_km, aj.berat_kg, aj.ongkos, p.id_pesanan, p.kode_pesanan, p.id_user, p.status_pesanan, u.nama pelanggan, u.no_hp, ap.alamat_lengkap, ap.catatan FROM penugasan_kurir pk JOIN antar_jemput aj ON aj.id_antar_jemput=pk.id_antar_jemput JOIN pesanan p ON p.id_pesanan=aj.id_pesanan JOIN users u ON u.id_user=p.id_user LEFT JOIN alamat_pelanggan ap ON ap.id_alamat=aj.id_alamat WHERE pk.token_akses=? LIMIT 1");
    $s->bind_param('s', $token);
    $s->execute();
    $task = $s->get_result()->fetch_assoc();
    if (!$task && $err === '') {
        $err = 'Kode akses tugas tidak valid atau tidak ditemukan.';
    }
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Portal Tugas Kurir — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<nav class="public-nav">
  <div class="nav-inner">
    <a class="brand" href="../index.php">
      LaundryKu <small style="color:var(--brand);font-weight:700;font-size:11px;margin-left:4px">KURIR</small>
    </a>
    <div class="nav-links">
      <a href="../tracking.php">Tracking</a>
      <a href="../auth/login.php" class="btn-nav">Masuk</a>
    </div>
  </div>
</nav>

<main class="page-container" style="max-width:760px">
  <div class="page-header" style="text-align:center">
    <div class="eyebrow" style="justify-content:center">LOGISTIK & PENGIRIMAN</div>
    <h1 style="font-size:2rem;margin-top:4px">Portal Tugas Kurir</h1>
    <p class="muted">Akses tanpa login menggunakan kode token tugas pengantaran/penjemputan.</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:20px"><?=h($msg)?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger" style="margin-bottom:20px"><?=h($err)?></div>
  <?php endif; ?>

  <!-- Token Input Card -->
  <div class="card" style="margin-bottom:24px">
    <h3 style="font-size:16px;margin-bottom:12px">Masukkan Kode Akses Tugas</h3>
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
      <input type="text" name="token" value="<?=h($token)?>" placeholder="Contoh: kurir-xyz123..." required style="flex:1;min-width:220px">
      <button class="btn btn-primary" type="submit">Buka Tugas</button>
    </form>
  </div>

  <?php if ($task): ?>
    <!-- Task Details Card -->
    <div class="card" style="margin-bottom:24px">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)">
        <div>
          <span style="font-size:12px;color:var(--muted);font-weight:700">KODE PESANAN</span>
          <h2 style="font-size:1.4rem;color:var(--brand);margin:2px 0 0"><?=h($task['kode_pesanan'])?></h2>
        </div>
        <div>
          <?php 
            $tskSt = $task['status'];
            $stBadge = ($tskSt === 'selesai' ? 'selesai' : ($tskSt === 'dalam_perjalanan' ? 'diantar' : 'menunggu'));
          ?>
          <span class="status-badge <?=$stBadge?>" style="font-size:13px;padding:6px 14px">
            <?=strtoupper(str_replace('_', ' ', $task['status']))?>
          </span>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px">
        <div style="background:var(--bg-elevated);padding:14px;border-radius:12px;border:1px solid var(--border)">
          <div style="font-size:11px;color:var(--muted);font-weight:700">PELANGGAN</div>
          <div style="font-weight:700;margin-top:4px"><?=h($task['pelanggan'])?></div>
          <div style="font-size:13px;color:var(--brand)"><?=h($task['no_hp'])?></div>
        </div>
        <div style="background:var(--bg-elevated);padding:14px;border-radius:12px;border:1px solid var(--border)">
          <div style="font-size:11px;color:var(--muted);font-weight:700">ESTIMASI & JARAK</div>
          <div style="font-weight:700;margin-top:4px"><?=$task['berat_kg']?> Kg</div>
          <div style="font-size:13px;color:var(--muted)"><?=number_format((float)$task['jarak_km'], 2, ',', '.')?> km</div>
        </div>
      </div>

      <div style="background:var(--bg-elevated);padding:14px;border-radius:12px;border:1px solid var(--border);margin-bottom:20px">
        <div style="font-size:11px;color:var(--muted);font-weight:700;margin-bottom:4px">ALAMAT TUJUAN</div>
        <div style="color:var(--text);line-height:1.5"><?=h($task['alamat_lengkap'] ?? 'Alamat tidak tercatat')?></div>
        <?php if (!empty($task['catatan'])): ?>
          <div style="font-size:12px;color:var(--muted);margin-top:6px"><em>Catatan: <?=h($task['catatan'])?></em></div>
        <?php endif; ?>
      </div>

      <!-- Action Buttons -->
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <form method="post" style="flex:1">
          <input type="hidden" name="token" value="<?=h($token)?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="status" value="dalam_perjalanan">
          <button class="btn btn-primary" type="submit" style="width:100%" <?=$task['status']==='dalam_perjalanan'?'disabled':''?>>
            Mulai Perjalanan
          </button>
        </form>

        <form method="post" style="flex:1">
          <input type="hidden" name="token" value="<?=h($token)?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="status" value="selesai">
          <button class="btn btn-success" type="submit" style="width:100%" <?=$task['status']==='selesai'?'disabled':''?>>
            Selesai Jemput / Antar
          </button>
        </form>
      </div>
    </div>

    <!-- Live GPS Section -->
    <div class="card">
      <h3 style="font-size:16px;margin-bottom:8px">
        Lokasi GPS Kurir Real-Time
      </h3>
      <p class="muted" style="font-size:13px;margin-bottom:16px">
        Aktifkan pelacakan langsung agar pelanggan dapat memantau estimasi penjemputan atau pengantaran di peta live tracking.
      </p>

      <div id="courierMap" style="height:320px;border-radius:14px;border:1px solid var(--border);background:#111;margin-bottom:14px"></div>
      
      <p id="gpsMessage" class="muted" style="font-size:13px;margin-bottom:14px;color:var(--brand)" aria-live="polite">
        GPS belum aktif.
      </p>

      <button class="btn btn-outline" type="button" id="startGps" style="width:100%;border-color:var(--brand);color:var(--brand)">
        Aktifkan Berbagi Lokasi Real-time
      </button>
      <p class="muted" style="font-size:11px;margin-top:8px;text-align:center">
        Biarkan halaman ini tetap terbuka selama dalam perjalanan agar koordinat kurir terkirim secara berkala.
      </p>
    </div>

    <script>
    (function(){
      const token = <?=json_encode($token)?>;
      const status = document.getElementById('gpsMessage');
      const btn = document.getElementById('startGps');
      const map = L.map('courierMap').setView([-5.3971, 105.2668], 13);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(map);

      let marker = null;
      let watch = null;

      btn.addEventListener('click', function(){
        if (!navigator.geolocation) {
          status.textContent = 'Browser Anda tidak mendukung fitur Geolocation GPS.';
          return;
        }

        if (watch !== null) {
          navigator.geolocation.clearWatch(watch);
          watch = null;
          btn.textContent = 'Aktifkan Berbagi Lokasi Real-time';
          btn.style.background = 'transparent';
          btn.style.color = 'var(--brand)';
          status.textContent = 'Berbagi lokasi dihentikan.';
          return;
        }

        btn.textContent = 'Hentikan Berbagi Lokasi';
        btn.style.background = 'var(--danger)';
        btn.style.color = '#fff';
        status.textContent = 'Meminta izin akses GPS...';

        watch = navigator.geolocation.watchPosition(async function(pos){
          const c = pos.coords;
          const body = new URLSearchParams({
            token: token,
            latitude: String(c.latitude),
            longitude: String(c.longitude),
            accuracy: String(c.accuracy || '')
          });

          try {
            const r = await fetch('../api/lokasi_kurir.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: body.toString(),
              cache: 'no-store'
            });
            const j = await r.json();
            if (!j.ok) {
              status.textContent = j.message || 'Gagal mengirim koordinat.';
              return;
            }
            const ll = [c.latitude, c.longitude];
            if (!marker) {
              marker = L.marker(ll).addTo(map).bindPopup('Posisi Kurir Saat Ini');
            } else {
              marker.setLatLng(ll);
            }
            map.setView(ll, 16);
            status.textContent = 'Lokasi terhubung · Akurasi ±' + Math.round(c.accuracy) + 'm · ' + new Date().toLocaleTimeString('id-ID');
          } catch(e) {
            status.textContent = 'Gagal menghubungi server.';
          }
        }, function(err){
          status.textContent = 'GPS Error: ' + err.message + ' (Pastikan izin lokasi diizinkan).';
        }, {
          enableHighAccuracy: true,
          maximumAge: 5000,
          timeout: 15000
        });
      });
    })();
    </script>
  <?php endif; ?>
</main>

<footer class="footer">
  <div class="footer-bottom" style="border:none">
    <p>&copy; <?=date('Y')?> LaundryKu. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
