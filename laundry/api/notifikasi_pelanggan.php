<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__.'/../config/koneksi.php';require_once __DIR__.'/../config/notifikasi_helper.php';
function jsonOut($data,$code=200){http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
try {
  $uid=0;
  if(isset($_SESSION['id_user']) && ($_SESSION['role']??'')==='pelanggan') $uid=(int)$_SESSION['id_user'];
  // Public riwayat page is already looked up by phone; allow that same page to poll its own updates.
  if(!$uid){
    $hp=trim((string)($_GET['no_hp']??''));
    if($hp==='') jsonOut(['ok'=>false,'message'=>'Nomor HP diperlukan.'],400);
    // Nomor HP sering tersimpan dengan format berbeda (08..., +62..., spasi, atau tanda hubung).
    // Cocokkan berdasarkan digit agar riwayat dan API notifikasi menemukan akun yang sama.
    $digits=preg_replace('/\\D+/', '', $hp);
    $u=$conn->prepare("SELECT id_user,no_hp FROM users WHERE role='pelanggan'");
    if(!$u) throw new RuntimeException('Query pengguna gagal: '.$conn->error);
    $u->execute();$rs=$u->get_result();$row=null;
    while($candidate=$rs->fetch_assoc()){
      $saved=preg_replace('/\\D+/', '', (string)$candidate['no_hp']);
      $variants=[$saved];
      if(str_starts_with($saved,'62')) $variants[]='0'.substr($saved,2);
      if(str_starts_with($saved,'0')) $variants[]='62'.substr($saved,1);
      if(in_array($digits,$variants,true)){ $row=$candidate; break; }
    }
    $u->close();
    if(!$row) jsonOut(['ok'=>false,'message'=>'Pelanggan tidak ditemukan. Pastikan nomor HP riwayat sama dengan akun pelanggan.'],404);
    $uid=(int)$row['id_user'];
  }
  laundry_ensure_schema($conn);
  $uids=[$uid];
  $pu=$conn->prepare('SELECT no_hp FROM users WHERE id_user=?');$pu->bind_param('i',$uid);$pu->execute();$prow=$pu->get_result()->fetch_assoc();$pu->close();
  if($prow&&trim((string)$prow['no_hp'])!=='')$uids=array_values(array_unique(array_merge($uids,laundry_user_ids_by_phone($conn,(string)$prow['no_hp']))));
  $inList=implode(',',array_map('intval',$uids));
  $cols=[];$cr=$conn->query('SHOW COLUMNS FROM `notifikasi`');if(!$cr)throw new RuntimeException('Tabel notifikasi tidak ditemukan.');while($c=$cr->fetch_assoc())$cols[$c['Field']]=true;
  foreach(['id_user','judul','pesan'] as $required)if(!isset($cols[$required]))throw new RuntimeException('Kolom '.$required.' tidak ada pada tabel notifikasi.');
  $idCol=isset($cols['id_notifikasi'])?'id_notifikasi':(isset($cols['id'])?'id':null);
  if(!$idCol) throw new RuntimeException('Tabel notifikasi membutuhkan kolom id_notifikasi atau id auto-increment.');
  $dateCol=isset($cols['created_at'])?'created_at':(isset($cols['waktu'])?'waktu':null);
  $since=max(0,(int)($_GET['since']??0));
  // Initial history load is explicit; since=0 during later polling must still return newly-created rows as updates.
  $initial=($_GET['initial']??'')==='1';
  $select=["`$idCol` AS id_notifikasi",isset($cols['id_pesanan'])?'id_pesanan':'NULL AS id_pesanan','judul','pesan',isset($cols['jenis'])?'jenis':"'status_laundry' AS jenis",isset($cols['sudah_dibaca'])?'sudah_dibaca':(isset($cols['dibaca'])?'dibaca AS sudah_dibaca':'0 AS sudah_dibaca'),$dateCol?"`$dateCol` AS created_at":"NOW() AS created_at"];
  if($initial){
    $q=$conn->prepare('SELECT '.implode(',',$select)." FROM notifikasi WHERE id_user IN ($inList) ORDER BY `$idCol` DESC LIMIT 12");
    if(!$q)throw new RuntimeException($conn->error);$q->execute();$rows=array_reverse($q->get_result()->fetch_all(MYSQLI_ASSOC));$q->close();
    $m=$conn->prepare("SELECT COALESCE(MAX(`$idCol`),0) max_id FROM notifikasi WHERE id_user IN ($inList)");$m->execute();$max=(int)$m->get_result()->fetch_assoc()['max_id'];$m->close();
    jsonOut(['ok'=>true,'initial'=>true,'max_id'=>$max,'notifications'=>$rows]);
  }
  $q=$conn->prepare('SELECT '.implode(',',$select)." FROM notifikasi WHERE id_user IN ($inList) AND `$idCol`>? ORDER BY `$idCol` ASC LIMIT 50");
  if(!$q)throw new RuntimeException($conn->error);$q->bind_param('i',$since);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);$q->close();$max=$since;foreach($rows as $n)$max=max($max,(int)$n['id_notifikasi']);
  jsonOut(['ok'=>true,'initial'=>false,'max_id'=>$max,'notifications'=>$rows]);
} catch(Throwable $e) { jsonOut(['ok'=>false,'message'=>'Notifikasi gagal dimuat. '.$e->getMessage()],500); }
