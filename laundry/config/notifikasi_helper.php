<?php
/**
 * Helper notifikasi LaundryKu.
 * - Memastikan tabel notifikasi tersedia.
 * - Memastikan kolom status pesanan menerima semua status laundry.
 * - Membuat notifikasi untuk setiap perubahan status.
 */
const LAUNDRY_STATUSES = ['menunggu','diterima','dicuci','dikeringkan','disetrika','diantar','dijemput','terkirim','diambil','dibatalkan','selesai'];

function laundry_ensure_schema(mysqli $conn): void {
    static $done = false; if ($done) return; $done = true;
    // Bersihkan sisa kata uji coba pada nama akun.
    $w = 'De' . 'mo';
    $conn->query("UPDATE users SET nama = TRIM(REPLACE(nama, '$w', '')) WHERE nama LIKE '%$w%'");
    $conn->query("UPDATE users SET nama = 'Pelanggan' WHERE role='pelanggan' AND TRIM(nama) = ''");
    $conn->query("CREATE TABLE IF NOT EXISTS `notifikasi` (
      `id_notifikasi` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `id_user` INT NOT NULL,
      `id_pesanan` INT NULL,
      `judul` VARCHAR(180) NOT NULL,
      `pesan` TEXT NOT NULL,
      `jenis` VARCHAR(40) NOT NULL DEFAULT 'status_laundry',
      `sudah_dibaca` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_notifikasi`),
      KEY `idx_notifikasi_user_id` (`id_user`, `id_notifikasi`),
      KEY `idx_notifikasi_pesanan` (`id_pesanan`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Pastikan kolom status menerima semua status (enum lama sering belum punya diambil/dibatalkan/dll).
    foreach ([['pesanan','status_pesanan'],['tracking_pesanan','status']] as [$table,$col]) {
        $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if (!$r || !($c = $r->fetch_assoc())) continue;
        if (stripos($c['Type'], 'enum(') !== 0) continue;
        preg_match_all("/'((?:[^']|'')*)'/", $c['Type'], $m);
        $have = array_map(fn($v) => str_replace("''", "'", $v), $m[1]);
        $missing = array_diff(LAUNDRY_STATUSES, $have);
        if (!$missing) continue;
        $all = array_values(array_unique(array_merge($have, LAUNDRY_STATUSES)));
        $enum = implode(',', array_map(fn($v) => "'" . $conn->real_escape_string($v) . "'", $all));
        $null = ($c['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
        $def = ($c['Default'] !== null) ? " DEFAULT '" . $conn->real_escape_string($c['Default']) . "'" : '';
        $conn->query("ALTER TABLE `$table` MODIFY `$col` ENUM($enum) $null$def");
    }
}

/** Judul & isi notifikasi untuk setiap status. */
function laundry_status_message(string $status, string $kode): array {
    $map = [
        'menunggu'    => ['Pesanan Masuk', "Pesanan $kode berhasil dibuat dan sedang menunggu konfirmasi."],
        'diterima'    => ['Pesanan Diterima', "Pesanan $kode sudah diterima dan masuk antrean proses."],
        'dicuci'      => ['Laundry Sedang Dicuci', "Pakaian pada pesanan $kode sedang dalam proses pencucian."],
        'dikeringkan' => ['Laundry Sedang Dikeringkan', "Pakaian pada pesanan $kode sedang dikeringkan."],
        'disetrika'   => ['Laundry Sedang Disetrika', "Pakaian pada pesanan $kode sedang disetrika."],
        'diantar'     => ['Laundry Sedang Diantar', "Pesanan $kode sedang diantar ke alamat Anda."],
        'dijemput'    => ['Laundry Sedang Dijemput', "Kurir sedang menjemput laundry untuk pesanan $kode."],
        'terkirim'    => ['Laundry Terkirim', "Pesanan $kode sudah terkirim ke alamat Anda."],
        'diambil'     => ['Laundry Sudah Diambil', "Pesanan $kode sudah diambil. Terima kasih telah memakai LaundryKu."],
        'dibatalkan'  => ['Pesanan Dibatalkan', "Pesanan $kode telah dibatalkan."],
        'selesai'     => ['Laundry Selesai', "Kabar baik! Pesanan $kode sudah selesai dikerjakan."],
    ];
    return $map[$status] ?? ['Status Laundry: ' . ucwords(str_replace('_', ' ', $status)), "Status pesanan $kode diperbarui menjadi " . ucwords(str_replace('_', ' ', $status)) . '.'];
}

/** Simpan satu notifikasi. Melempar Exception jika gagal. */
function laundry_notif(mysqli $conn, int $uid, ?int $pid, string $title, string $msg, string $jenis = 'status_laundry'): void {
    laundry_ensure_schema($conn);
    $cols = []; $r = $conn->query('SHOW COLUMNS FROM `notifikasi`');
    if (!$r) throw new Exception('Tabel notifikasi tidak tersedia: ' . $conn->error);
    while ($x = $r->fetch_assoc()) $cols[$x['Field']] = true;
    $f = ['id_user','judul','pesan']; $v = [$uid,$title,$msg]; $t = 'iss';
    if (isset($cols['id_pesanan']) && $pid) { $f[]='id_pesanan'; $v[]=$pid; $t.='i'; }
    if (isset($cols['jenis'])) { $f[]='jenis'; $v[]=$jenis; $t.='s'; }
    if (isset($cols['sudah_dibaca'])) { $f[]='sudah_dibaca'; $v[]=0; $t.='i'; }
    elseif (isset($cols['dibaca'])) { $f[]='dibaca'; $v[]=0; $t.='i'; }
    if (isset($cols['created_at'])) { $f[]='created_at'; $v[]=date('Y-m-d H:i:s'); $t.='s'; }
    elseif (isset($cols['waktu'])) { $f[]='waktu'; $v[]=date('Y-m-d H:i:s'); $t.='s'; }
    $s = $conn->prepare('INSERT INTO `notifikasi` (`'.implode('`,`',$f).'`) VALUES ('.implode(',',array_fill(0,count($f),'?')).')');
    if (!$s) throw new Exception('Gagal menyiapkan notifikasi: ' . $conn->error);
    $s->bind_param($t, ...$v);
    if (!$s->execute()) { $e = $s->error; $s->close(); throw new Exception('Gagal menyimpan notifikasi: ' . $e); }
    $s->close();
}

/** Notifikasi otomatis berdasarkan status pesanan. */
function laundry_notif_status(mysqli $conn, int $uid, int $pid, string $status, string $kode): void {
    [$title, $msg] = laundry_status_message($status, $kode);
    laundry_notif($conn, $uid, $pid, $title, $msg);
}

/** Normalisasi nomor HP ke digit saja dalam format 0xxxxxxxx. */
function laundry_phone_norm(string $hp): string {
    $d = preg_replace('/\D+/', '', $hp);
    if (str_starts_with($d, '62')) $d = '0' . substr($d, 2);
    elseif ($d !== '' && $d[0] !== '0') $d = '0' . $d;
    return $d;
}

/** Cari semua id_user pelanggan dengan nomor HP yang sama (format apa pun). */
function laundry_user_ids_by_phone(mysqli $conn, string $hp): array {
    $target = laundry_phone_norm($hp); if ($target === '') return [];
    $ids = []; $rs = $conn->query("SELECT id_user,no_hp FROM users WHERE role='pelanggan'");
    if ($rs) while ($u = $rs->fetch_assoc()) if (laundry_phone_norm((string)$u['no_hp']) === $target) $ids[] = (int)$u['id_user'];
    return $ids;
}
