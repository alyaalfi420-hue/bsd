<?php
require_once __DIR__.'/../config/koneksi.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
// Lokasi GPS kurir disimpan terpisah; tabel dibuat tanpa mengubah tabel pesanan yang ada.
if (!$conn->query("CREATE TABLE IF NOT EXISTS lokasi_kurir_live (
 id_antar_jemput INT NOT NULL PRIMARY KEY,
 id_pesanan INT NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy_m DECIMAL(8,2) NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX (id_pesanan), INDEX (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
 http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Tabel lokasi tidak dapat disiapkan. Periksa izin database.']); exit;
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if($method==='POST') {
 $token=trim($_POST['token']??''); $lat=filter_var($_POST['latitude']??null,FILTER_VALIDATE_FLOAT); $lng=filter_var($_POST['longitude']??null,FILTER_VALIDATE_FLOAT); $accuracy=filter_var($_POST['accuracy']??null,FILTER_VALIDATE_FLOAT);
 if($token==='' || $lat===false || $lng===false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) { http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Koordinat atau token tidak valid.']); exit; }
 $s=$conn->prepare("SELECT aj.id_antar_jemput,aj.id_pesanan FROM penugasan_kurir pk JOIN antar_jemput aj ON aj.id_antar_jemput=pk.id_antar_jemput WHERE pk.token_akses=? LIMIT 1");
 if(!$s){http_response_code(500);echo json_encode(['ok'=>false,'message'=>'Struktur tugas kurir tidak cocok.']);exit;}
 $s->bind_param('s',$token);$s->execute();$task=$s->get_result()->fetch_assoc();
 if(!$task){http_response_code(403);echo json_encode(['ok'=>false,'message'=>'Token tugas tidak ditemukan.']);exit;}
 $idaj=(int)$task['id_antar_jemput'];$pid=(int)$task['id_pesanan'];$acc=$accuracy===false?null:$accuracy;
 $s=$conn->prepare("INSERT INTO lokasi_kurir_live(id_antar_jemput,id_pesanan,latitude,longitude,accuracy_m) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE id_pesanan=VALUES(id_pesanan),latitude=VALUES(latitude),longitude=VALUES(longitude),accuracy_m=VALUES(accuracy_m),updated_at=CURRENT_TIMESTAMP");
 $s->bind_param('iiddd',$idaj,$pid,$lat,$lng,$acc);$ok=$s->execute();
 echo json_encode(['ok'=>(bool)$ok,'message'=>$ok?'Lokasi berhasil diperbarui.':'Gagal menyimpan lokasi.']);exit;
}
if($method==='GET') {
 $kode=trim($_GET['kode']??''); if($kode===''){http_response_code(400);echo json_encode(['ok'=>false,'message'=>'Kode pesanan wajib diisi.']);exit;}
 $s=$conn->prepare("SELECT l.latitude,l.longitude,l.accuracy_m,l.updated_at FROM pesanan p JOIN antar_jemput aj ON aj.id_pesanan=p.id_pesanan JOIN lokasi_kurir_live l ON l.id_antar_jemput=aj.id_antar_jemput WHERE p.kode_pesanan=? ORDER BY l.updated_at DESC LIMIT 1");
 if(!$s){http_response_code(500);echo json_encode(['ok'=>false,'message'=>'Struktur database tidak cocok.']);exit;}
 $s->bind_param('s',$kode);$s->execute();$row=$s->get_result()->fetch_assoc();
 echo json_encode(['ok'=>(bool)$row,'location'=>$row?:null,'message'=>$row?'Lokasi tersedia.':'Kurir belum membagikan lokasi GPS.']);exit;
}
http_response_code(405);echo json_encode(['ok'=>false,'message'=>'Metode tidak diizinkan.']);
