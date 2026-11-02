<?php
session_start();
require_once __DIR__.'/config/koneksi.php';
require_once __DIR__ . '/config/ensure_catalog.php';

function rupiah($n){return 'Rp '.number_format((float)$n,0,',','.');}
function norm($s){$s=strtolower(trim($s));$s=str_replace(['?','!','.',',',':',';','/','\\','\n','\r','-','_'],' ',$s);return trim(preg_replace('/\s+/',' ',$s));}
function any($t,$a){foreach($a as $x) if(str_contains($t,$x)) return true; return false;}
function kg($t){if(preg_match('/(?:^|\s)(\d+(?:[\.,]\d+)?)\s*(?:kg|kilo|kilos|kilogram)(?:\s|$)/i',$t,$m)) return (float)str_replace(',','.',$m[1]);return null;}
function distKm($lat1,$lon1,$lat2,$lon2){$r=6371;$p=pi()/180;$a=0.5-cos(($lat2-$lat1)*$p)/2+cos($lat1*$p)*cos($lat2*$p)*(1-cos(($lon2-$lon1)*$p))/2;return $r*2*asin(sqrt($a));}
function pickService($conn,$text){$rows=[];$r=$conn->query("SELECT * FROM layanan WHERE status='aktif' ORDER BY id_layanan");while($x=$r->fetch_assoc())$rows[]=$x;$t=norm($text);$best=null;$score=0;$aliases=['cuci kering'=>['cuci kering','cuci saja','kering'],'cuci setrika'=>['cuci setrika','cuci dan setrika','cuci + setrika','laundry biasa'],'setrika'=>['setrika','gosok'],'express'=>['express','kilat','cepat'],'bed cover'=>['bed cover','bedcover','selimut']];foreach($rows as $s){$sc=0;$n=norm($s['nama_layanan']);foreach(explode(' ',$n) as $w)if(strlen($w)>=3&&str_contains($t,$w))$sc+=2;foreach($aliases as $key=>$arr)if(str_contains($n,$key))foreach($arr as $a)if(str_contains($t,$a))$sc+=5;if($sc>$score){$score=$sc;$best=$s;}}return [$best,$score,$rows];}
function statusText($s){$m=['menunggu'=>'Pesanan baru dan sedang menunggu konfirmasi.','dikonfirmasi'=>'Pesanan sudah dikonfirmasi dan siap masuk proses.','dijemput'=>'Pesanan sedang dalam proses penjemputan.','diterima'=>'Laundry sudah diterima oleh outlet.','ditimbang'=>'Laundry sudah ditimbang dan berat aktual sedang digunakan untuk perhitungan.','diproses'=>'Laundry sedang diproses.','dicuci'=>'Laundry sedang dicuci.','dikeringkan'=>'Laundry sedang dikeringkan.','disetrika'=>'Laundry sedang disetrika dan dirapikan.','selesai'=>'Laundry sudah selesai dan menunggu diambil atau diantar.','diambil'=>'Laundry sudah diambil pelanggan.','diantar'=>'Laundry sedang diantar ke pelanggan.','terkirim'=>'Laundry sudah diterima pelanggan.','dibatalkan'=>'Pesanan dibatalkan.'];return $m[$s]??'Status pesanan sedang diperbarui.';}

if(!isset($_SESSION['chat_history']))$_SESSION['chat_history']=[];
if(!isset($_SESSION['chat_ctx']))$_SESSION['chat_ctx']=[];
$q=trim($_POST['message']??'');$lat=(float)($_POST['latitude']??0);$lng=(float)($_POST['longitude']??0);
if($lat&&$lng)$_SESSION['chat_location']=['lat'=>$lat,'lng'=>$lng];
elseif(isset($_SESSION['chat_location'])){$lat=(float)$_SESSION['chat_location']['lat'];$lng=(float)$_SESSION['chat_location']['lng'];}

if($q!==''){
 $t=norm($q);$k=kg($t);$ans='';$ctx=$_SESSION['chat_ctx'];
 $pickupIntent=any($t,['jemput','antar jemput','pickup','pick up','diambil ke rumah','ambil laundry','jemput laundry','ongkir']);
 $consent=any($t,['saya izinkan','aku izinkan','oke izinkan','okay izinkan','silakan','boleh akses lokasi','sudah izinkan','aku sudah izinkan','saya sudah izinkan']);
 if($consent && !$lat){
   $ans='Siap. Untuk alasan keamanan, browser tetap perlu meminta izin lokasi secara langsung. Silakan klik tombol "📍 Kirim Lokasi Saya" di bawah. Setelah lokasi terkirim, aku bisa cek apakah alamatmu masuk area jemput dan menghitung perkiraan ongkirnya.';
   $_SESSION['chat_ctx']=['intent'=>'pickup','weight'=>$k??($ctx['weight']??null)];
 }
 elseif($pickupIntent){
   $_SESSION['chat_ctx']=['intent'=>'pickup','weight'=>$k??($ctx['weight']??null)];
   $weight=$k??($ctx['weight']??null);
   if(!$lat){
     $ans='Bisa, tentu! Aku bisa bantu cek jangkauan antar-jemput. Aku butuh perkiraan berat cucian dan titik lokasimu. Kalau beratnya sudah tahu, klik tombol "📍 Kirim Lokasi Saya" supaya aku bisa ukur jarak dan ongkirnya.';
   }
   else{
    $trs=$conn->query("SELECT * FROM tarif_antar_jemput WHERE status='aktif' ORDER BY jarak_min")->fetch_all(MYSQLI_ASSOC);
    $d=distKm(-5.3971,105.2668,$lat,$lng);
    $f=null;
    foreach($trs as $tr)if($d>=$tr['jarak_min']&&$d<=$tr['jarak_max']){$f=$tr;break;}
    if(!$f)$ans='Aku sudah menerima lokasimu. Jaraknya sekitar '.number_format($d,2,',','.').' km dari outlet kami, tetapi saat ini belum masuk rentang area antar-jemput yang tersedia.';
    elseif($weight!==null&&$weight<$f['minimal_kg'])$ans='Aku sudah cek lokasimu. Jaraknya sekitar '.number_format($d,2,',','.').' km. Ongkir area ini '.rupiah($f['tarif']).' dengan syarat minimal '.$f['minimal_kg'].' kg. Perkiraan beratmu '.$weight.' kg, jadi belum mencapai minimal berat.';
    else{
      $ans='Bisa diantar-jemput! Jarak lokasimu dari outlet sekitar '.number_format($d,2,',','.').' km, ongkir '.rupiah($f['tarif']).', dan minimal laundry '.$f['minimal_kg'].' kg.';
      if($weight!==null)$ans.=' Berat perkiraanmu '.$weight.' kg, jadi memenuhi syarat.';
      else $ans.=' Beri tahu perkiraan berat cucianmu untuk memastikan syarat minimalnya.';
    }
   }
 } elseif($ctx && $k!==null && ($ctx['intent']??'')==='pickup'){
   $_SESSION['chat_ctx']['weight']=$k;
   $ans=$lat?'Berat '.$k.' kg sudah aku catat. '.$_POST['message'].'': 'Berat '.$k.' kg sudah aku catat. Sekarang klik tombol "📍 Kirim Lokasi Saya" supaya aku bisa cek area dan ongkir antar-jemput.';
   if($lat){
     $q='bisa antar jemput '.$k.' kg';$t=norm($q);
     $trs=$conn->query("SELECT * FROM tarif_antar_jemput WHERE status='aktif' ORDER BY jarak_min")->fetch_all(MYSQLI_ASSOC);
     $d=distKm(-5.3971,105.2668,$lat,$lng);
     $f=null;
     foreach($trs as $tr)if($d>=$tr['jarak_min']&&$d<=$tr['jarak_max']){$f=$tr;break;}
     if(!$f)$ans='Berat '.$k.' kg sudah dicatat. Lokasimu sekitar '.number_format($d,2,',','.').' km dan saat ini di luar area jangkauan antar-jemput.';
     elseif($k<$f['minimal_kg'])$ans='Berat '.$k.' kg sudah dicatat. Jarak lokasimu sekitar '.number_format($d,2,',','.').' km dengan ongkir '.rupiah($f['tarif']).', tetapi minimalnya '.$f['minimal_kg'].' kg.';
     else $ans='Berat '.$k.' kg sudah dicatat. Lokasimu sekitar '.number_format($d,2,',','.').' km, ongkir '.rupiah($f['tarif']).', dan memenuhi syarat minimal antar-jemput.';
   }
 } elseif(any($t,['halo','hai','hi','hello','pagi','siang','sore','malam'])){
   $ans='Halo! Selamat datang di asisten pintar LaundryKu. Ada yang bisa aku bantu seputar layanan, harga, antar-jemput, voucher, atau tracking pesanan?';
 } elseif(any($t,['makasih','terima kasih','thanks','thx'])){
   $ans='Sama-sama! 😊 Senang bisa membantu. Kalau ada yang ingin ditanyakan lagi, jangan ragu untuk menuliskan di sini.';
 } elseif(any($t,['bisa apa','kamu bisa','bantuan','help','menu'])){
   $ans='Aku bisa membantumu untuk:\n- Mengecek daftar paket layanan dan harga laundry\n- Menghitung perkiraan ongkir antar-jemput berdasarkan GPS\n- Melacak posisi pesanan (kirim kode LDR-...)\n- Menjelaskan voucher promo dan metode pembayaran yang tersedia';
 } elseif(any($t,['harga','tarif','biaya','berapa'])&&!any($t,['ongkir','antar'])){
   [$sv,$sc,$rows]=pickService($conn,$t);
   if($sv&&$sc>=2){
     $_SESSION['chat_ctx']=['intent'=>'price','service'=>$sv];
     $ans=($k!==null&&strtolower($sv['satuan'])==='kg')?'Untuk paket '.$sv['nama_layanan'].' seberat '.$k.' kg, perkiraan biaya layanan '.rupiah($sv['harga']*$k).'. (Belum termasuk ongkir atau voucher).':'Paket layanan '.$sv['nama_layanan'].' saat ini '.rupiah($sv['harga']).'/'.$sv['satuan'].'. Estimasi waktu selesai sekitar '.$sv['estimasi_jam'].' jam.';
   }else{
     $a=[];foreach($rows as $x)$a[]=$x['nama_layanan'].' — '.rupiah($x['harga']).'/'.$x['satuan'];
     $ans='Berikut daftar tarif layanan kami:\n- '.implode("\n- ",$a).'\n\nSebutkan layanan dan berat cucian yang kamu inginkan, nanti aku hitungkan.';
   }
 } elseif(preg_match('/(LDR-[A-Z0-9\-]+)/i',$q,$m)||any($t,['tracking','lacak','status pesanan','pesanan saya','order saya'])){
   $code=$m[1]??'';
   if(!$code)$ans='Untuk melacak pesanan, silakan kirimkan kode pesananmu yang diawali "LDR-" (contoh: LDR-20261024-123).';
   else{
     $s=$conn->prepare("SELECT p.*,u.nama FROM pesanan p JOIN users u ON u.id_user=p.id_user WHERE p.kode_pesanan=? LIMIT 1");
     $s->bind_param('s',$code);$s->execute();$d=$s->get_result()->fetch_assoc();
     $ans=$d?'Pesanan '.$d['kode_pesanan'].' atas nama '.$d['nama'].' saat ini berstatus '.ucwords(str_replace('_',' ',$d['status_pesanan'])).'. '.statusText($d['status_pesanan']).($d['berat_aktual']!==null?' Berat aktual: '.$d['berat_aktual'].' kg.':'').($d['total_final']!==null?' Total final: '.rupiah($d['total_final']).'.':($d['total_estimasi']!==null?' Total sementara: '.rupiah($d['total_estimasi']).'.':'')).($d['estimasi_selesai']?' Estimasi selesai: '.date('d-m-Y H:i',strtotime($d['estimasi_selesai'])).'.':''):'Kode pesanan '.$code.' tidak ditemukan. Mohon periksa kembali.';
   }
 } elseif(any($t,['voucher','promo','diskon','kode promo'])){
   $r=$conn->query("SELECT kode,nama,jenis_diskon,nilai_diskon,minimal_transaksi FROM voucher WHERE status='aktif' AND tanggal_mulai<=NOW() AND tanggal_berakhir>=NOW() ORDER BY id_voucher");
   $a=[];
   while($v=$r->fetch_assoc())$a[]=$v['kode'].' — '.($v['jenis_diskon']==='persen'?$v['nilai_diskon'].'%':rupiah($v['nilai_diskon'])).', minimal transaksi '.rupiah($v['minimal_transaksi']);
   $ans=$a?'Berikut voucher promo aktif saat ini:\n- '.implode("\n- ",$a).'\n\nMasukkan kodenya pada saat checkout di halaman Pesan Laundry.':'Saat ini belum ada voucher promo yang aktif.';
 } elseif(any($t,['qris','cash','tunai','bayar','pembayaran','saldo'])){
   $ans='LaundryKu menerima pembayaran via:\n1. 💵 Cash (Tunai saat serah terima pakaian)\n2. 📱 QRIS (Scan instan dari semua e-wallet & m-banking)\n3. 💳 Saldo LaundryKu (Otomatis potong dari dompet pelanggan)';
 } elseif(any($t,['kenapa harga','harga berubah','berat aktual','ditimbang','timbang'])){
   $ans='Harga awal yang tampil saat checkout adalah estimasi berdasarkan berat perkiraan. Setelah pakaian tiba di outlet, tim kami akan menimbang ulang secara akurat sehingga total biaya akhir dihitung berdasarkan berat aktual.';
 } elseif(any($t,['refund','uang kembali','kelebihan bayar','selisih'])){
   $ans='Bila pembayaran awal melebihi total akhir setelah ditimbang ulang, selisih dananya dapat dikembalikan ke Saldo Dompet LaundryKu Anda.';
 } elseif(any($t,['keranjang','beberapa layanan','lebih dari satu'])){
   $ans='Tentu bisa! Anda dapat memilih beberapa jenis paket sekaligus di keranjang belanja sebelum melakukan checkout.';
 } elseif(any($t,['riwayat','pesanan sebelumnya'])){
   $ans='Anda dapat mengecek riwayat pesanan dengan membuka menu "Riwayat" di atas, lalu masukkan nomor HP WhatsApp yang digunakan saat memesan.';
 } elseif(any($t,['cara pesan','buat pesanan','checkout','order','pemesanan'])){
   $ans='Langkah mudah pesan di LaundryKu:\n1. Buka menu "Pesan Laundry"\n2. Pilih paket layanan & jumlahnya\n3. Pilih datang sendiri atau antar-jemput\n4. Gunakan voucher promo bila ada\n5. Pilih metode pembayaran lalu konfirmasi pesanan!';
 } elseif(any($t,['berapa lama','lama selesai','estimasi selesai','waktu selesai','durasi','kapan selesai','hari selesai'])){
   [$sv,$sc,$rows]=pickService($conn,$t);
   if($sv&&$sc>=2)$ans='Layanan '.$sv['nama_layanan'].' memiliki estimasi waktu pengerjaan sekitar '.$sv['estimasi_jam'].' jam kerja.';
   else{
     $a=[];foreach($rows as $x)$a[]=$x['nama_layanan'].': ±'.$x['estimasi_jam'].' jam';
     $ans='Estimasi pengerjaan bergantung pada layanan yang dipilih: '.implode('; ',$a).'.';
   }
 } else {
   $tokens=array_filter(explode(' ',$t),fn($x)=>strlen($x)>=3);
   $r=$conn->query("SELECT pertanyaan,jawaban,keyword FROM chatbot_faq WHERE status='aktif'");
   $best=null;$bs=0;
   if($r){
     while($x=$r->fetch_assoc()){
       $hay=norm($x['pertanyaan'].' '.$x['keyword']);
       $sc=0;
       foreach($tokens as $w)if(str_contains($hay,$w))$sc+=2;
       foreach(explode(' ',norm($x['keyword'])) as $kw)if(strlen($kw)>=3&&str_contains($t,$kw))$sc+=2;
       if($hay!==''&&str_contains($t,$hay))$sc+=5;
       similar_text($t,$hay,$pct);
       if($pct>=38)$sc++;
       if($sc>$bs){$bs=$sc;$best=$x;}
     }
   }
   $ans=$best&&$bs>=2?$best['jawaban']:'Maaf, aku belum menangkap pertanyaanmu dengan jelas. Coba tanyakan seputar tarif laundry, antar-jemput, cara pembayaran, voucher, atau kirimkan kode pesanan LDR-... untuk melacak cucian.';
 }
 $_SESSION['chat_history'][]=['q'=>$q,'a'=>$ans];
 if(count($_SESSION['chat_history'])>20)array_shift($_SESSION['chat_history']);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Chatbot Bantuan 24/7 — LaundryKu</title>
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
      <a href="riwayat.php">Riwayat</a>
      <a href="saldo.php">Saldo</a>
      <a href="auth/login.php" class="admin-link">Masuk</a>
    </div>
  </div>
</nav>

<main class="container narrow">
  <div class="chat-card">
    <div class="chat-head">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="brand-badge" style="width:30px;height:30px;font-size:11px">LK</span>
        <div>
          <b>Asisten Virtual LaundryKu</b>
          <span style="display:block;font-size:11px;color:#9ca3af;font-weight:400;background:transparent;border:none;padding:0">Online 24 Jam • Respons Cepat</span>
        </div>
      </div>
      <span style="color:#10b981;font-weight:700">Online</span>
    </div>

    <div class="chat-body" id="chatBody">
      <div class="bot">
        <b>Halo!</b> Saya adalah asisten virtual LaundryKu. Silakan tanyakan hal seputar harga laundry, layanan antar-jemput, kode promo voucher, pembayaran, atau cek tracking pesanan Anda.
      </div>

      <div class="quick">
        <button type="button" onclick="ask('Harga cuci setrika berapa?')">Harga Cuci Setrika</button>
        <button type="button" onclick="ask('Bisa antar jemput ke rumah?')">Antar-Jemput</button>
        <button type="button" onclick="ask('Ada voucher promo sekarang?')">Promo Voucher</button>
        <button type="button" onclick="ask('Kenapa harga total bisa berubah?')">Kok Harga Bisa Berubah?</button>
        <button type="button" onclick="ask('Cara bayar dengan QRIS bagaimana?')">Bayar QRIS</button>
      </div>

      <div class="locrow">
        <button id="locBtn" class="locbtn" type="button" onclick="sendLocation()">
          Kirim Lokasi GPS Saya
        </button>
        <span id="locStatus" class="muted2">Biar jarak dan biaya jemput bisa dihitung.</span>
      </div>

      <?php foreach($_SESSION['chat_history'] as $h): ?>
        <div class="user"><?=htmlspecialchars($h['q'])?></div>
        <div class="bot"><?=htmlspecialchars($h['a'])?></div>
      <?php endforeach; ?>
    </div>

    <form method="post" class="chat-form" id="chatForm">
      <input id="message" name="message" placeholder="Ketik pertanyaanmu di sini..." autocomplete="off" required>
      <input type="hidden" name="latitude" id="lat">
      <input type="hidden" name="longitude" id="lng">
      <button class="btn" type="submit">Kirim</button>
    </form>
  </div>
</main>

<script>
const chatBody = document.getElementById('chatBody');
if (chatBody) {
  chatBody.scrollTop = chatBody.scrollHeight;
}

function ask(t){
  document.getElementById('message').value = t;
  document.getElementById('chatForm').submit();
}

function sendLocation(){
  const s = document.getElementById('locStatus');
  const b = document.getElementById('locBtn');
  if(!navigator.geolocation){
    s.textContent = 'Browser Anda tidak mendukung deteksi lokasi.';
    return;
  }
  b.disabled = true;
  s.textContent = 'Meminta izin akses lokasi browser...';
  navigator.geolocation.getCurrentPosition(p => {
    document.getElementById('lat').value = p.coords.latitude.toFixed(8);
    document.getElementById('lng').value = p.coords.longitude.toFixed(8);
    document.getElementById('message').value = 'Saya sudah mengirimkan titik lokasi GPS. Tolong cek apakah lokasi saya terjangkau antar-jemput dan berapa ongkirnya.';
    s.textContent = '✓ Lokasi terkirim. Memeriksa area...';
    document.getElementById('chatForm').submit();
  }, e => {
    b.disabled = false;
    s.textContent = 'Izin lokasi tidak diberikan. Izinkan akses lokasi di browser untuk melanjutkan.';
  });
}
</script>

<script src="assets/js/notifikasi-pelanggan.js?v=20261023-fix4"></script>
</body>
</html>
