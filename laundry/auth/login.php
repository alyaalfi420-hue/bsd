<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/notifikasi_helper.php';
laundry_ensure_schema($conn);
mysqli_report(MYSQLI_REPORT_OFF);

function laundryRoleDestination(string $role): string {
    return match (strtolower($role)) {
        'admin' => '../admin/index.php',
        'kasir' => '../kasir/index.php',
        'pelanggan' => '../pelanggan/index.php',
        default => '../index.php'
    };
}

if (isset($_SESSION['id_user'], $_SESSION['role'])) {
    header('Location: ' . laundryRoleDestination((string)$_SESSION['role']));
    exit;
}

$error = ''; 
$setupError = '';
$allowedRoles = ['admin','kasir','pelanggan'];
$selectedRole = strtolower(trim((string)($_POST['role'] ?? 'admin')));
if (!in_array($selectedRole, $allowedRoles, true)) $selectedRole = 'admin';

function laundryColumns(mysqli $db, string $table): array {
    $out=[]; 
    $res=$db->query("SHOW COLUMNS FROM `$table`");
    if (!$res) throw new Exception('Tidak dapat membaca struktur tabel '.$table.': '.$db->error);
    while ($row=$res->fetch_assoc()) $out[$row['Field']]=$row; 
    return $out;
}

function laundryGetUser(mysqli $db, string $email): ?array {
    $st=$db->prepare('SELECT id_user,nama,email,password,role FROM users WHERE email=? LIMIT 1');
    if (!$st) throw new Exception('Query akun gagal: '.$db->error);
    $st->bind_param('s',$email); 
    if(!$st->execute()) throw new Exception('Pemeriksaan akun gagal: '.$st->error);
    $st->bind_result($id,$nama,$em,$pass,$role); 
    $row=null;
    if($st->fetch()) $row=['id_user'=>$id,'nama'=>$nama,'email'=>$em,'password'=>$pass,'role'=>$role];
    $st->close(); 
    return $row;
}

if($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim((string)($_POST['email']??'')); 
    $password=(string)($_POST['password']??'');
    if($setupError!=='') $error='Persiapan akun gagal: '.$setupError;
    elseif($email===''||$password==='') $error='Email dan password wajib diisi.';
    else {
        try {
            $u=laundryGetUser($conn,$email);
            if(!$u) $error='Akun tidak ditemukan. Pastikan email sudah terdaftar.';
            else {
                $role=strtolower(trim((string)$u['role']));
                $ok=password_verify($password,(string)$u['password']) || hash_equals((string)$u['password'],$password);
                $colsNow=laundryColumns($conn,'users'); 
                $active=true;
                if(isset($colsNow['status'])) {
                    $st=$conn->prepare('SELECT status FROM users WHERE id_user=? LIMIT 1');
                    if($st){
                        $id=(int)$u['id_user'];
                        $st->bind_param('i',$id);
                        $st->execute();
                        $st->bind_result($status);
                        if($st->fetch())$active=in_array(strtolower(trim((string)$status)),['aktif','active','1','enabled'],true);
                        $st->close();
                    }
                }
                if(!$ok) $error='Password salah. Periksa kembali email dan password Anda.';
                elseif(!$active) $error='Akun Anda berstatus nonaktif. Silakan hubungi admin.';
                elseif(!in_array($role,$allowedRoles,true)) $error='Peran akun pada database tidak dikenali: '.htmlspecialchars($role,ENT_QUOTES,'UTF-8');
                elseif($role!==$selectedRole) $error='Peran akun tidak sesuai. Akun ini terdaftar sebagai '.ucfirst($role).'. Silakan ganti pilihan peran.';
                else {
                    session_regenerate_id(true); 
                    $_SESSION['id_user']=(int)$u['id_user']; 
                    $_SESSION['nama']=(string)$u['nama']; 
                    $_SESSION['email']=(string)$u['email']; 
                    $_SESSION['role']=$role;
                    header('Location: '.laundryRoleDestination($role)); 
                    exit;
                }
            }
        } catch(Throwable $e) { 
            $error='Login gagal karena kesalahan database: '.$e->getMessage(); 
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Akun — LaundryKu</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/theme-pro.css?v=3">
</head>
<body class="auth-page">

<main class="auth-card">
  <a class="login-brand" href="../index.php">
    <div class="login-brand-mark">LK</div>
    <div>
      <span style="display:block;font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.02em">LaundryKu</span>
      <span style="display:block;font-size:11px;font-weight:600;color:var(--muted);letter-spacing:0.08em">LAYANAN LAUNDRY MODERN</span>
    </div>
  </a>

  <div class="login-eyebrow">SELAMAT DATANG</div>
  <h1>Masuk dulu yuk</h1>
  <p class="login-subtitle">Pilih peranmu, lalu isi email dan kata sandi.</p>

  <?php if($error !== ''): ?>
    <div class="alert" role="alert">
      <?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="on">
    <label for="role">Masuk sebagai</label>
    <select id="role" name="role" required>
      <option value="admin" <?=$selectedRole==='admin'?'selected':''?>>Administrator</option>
      <option value="kasir" <?=$selectedRole==='kasir'?'selected':''?>>Kasir Outlet</option>
      <option value="pelanggan" <?=$selectedRole==='pelanggan'?'selected':''?>>Pelanggan</option>
    </select>

    <label for="email">Alamat Email</label>
    <input id="email" type="email" name="email" value="<?=htmlspecialchars((string)($_POST['email']??''), ENT_QUOTES, 'UTF-8')?>" placeholder="nama@email.com" autocomplete="username" required>

    <label for="password">Kata Sandi</label>
    <input id="password" type="password" name="password" placeholder="Masukin kata sandimu" autocomplete="current-password" required>

    <button class="btn full" type="submit" style="margin-top:24px">
      Masuk Sekarang
    </button>
  </form>

  <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--line)">
    <a href="../index.php" style="font-size:13.5px;color:var(--muted);text-decoration:none;font-weight:600">
      ← Kembali ke Beranda Utama
    </a>
  </div>
</main>

</body>
</html>
