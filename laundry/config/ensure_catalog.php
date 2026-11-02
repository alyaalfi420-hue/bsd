<?php
/** Ensure the three requested services and a starter voucher exist in db_laundry. */
if (!isset($conn) || !($conn instanceof mysqli)) { return; }
function laundryTableExists(mysqli $conn, string $table): bool {
    $safe = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$safe}'");
    return $r && $r->num_rows > 0;
}
$catalogInitialized = false;
if ($conn->query("CREATE TABLE IF NOT EXISTS laundryku_setup_flags (flag VARCHAR(100) PRIMARY KEY, completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
    $checkFlag = $conn->query("SELECT flag FROM laundryku_setup_flags WHERE flag='services_voucher_seed_v1' LIMIT 1");
    $catalogInitialized = $checkFlag && $checkFlag->num_rows > 0;
}
if (!$catalogInitialized && laundryTableExists($conn, 'layanan')) {
    $defaults = [
        ['Cuci Kering', 7000, 'kg', 'Layanan cuci kering tanpa setrika.', 24, ['cuci kering','cuci saja']],
        ['Cuci Setrika', 10000, 'kg', 'Layanan mencuci dan menyetrika pakaian.', 48, ['cuci setrika','cuci dan setrika']],
        ['Setrika Aja', 6000, 'kg', 'Layanan menyetrika pakaian saja.', 24, ['setrika aja','setrika saja']]
    ];
    foreach ($defaults as [$name, $price, $unit, $desc, $hours, $aliases]) {
        $found = null;
        $q = $conn->query("SELECT id_layanan,nama_layanan FROM layanan");
        if ($q) {
            while ($row = $q->fetch_assoc()) {
                $n = strtolower(trim((string)$row['nama_layanan']));
                $accepted = array_map('strtolower', $aliases);
                if (in_array($n, $accepted, true)) { $found = $row; break; }
            }
        }
        if ($found) {
            $id = (int)$found['id_layanan'];
            $st = $conn->prepare("UPDATE layanan SET nama_layanan=?, status='aktif' WHERE id_layanan=?");
            if ($st) { $st->bind_param('si', $name, $id); $st->execute(); $st->close(); }
        } else {
            $st = $conn->prepare("INSERT INTO layanan (nama_layanan,harga,satuan,deskripsi,estimasi_jam,status) VALUES (?,?,?,?,?,'aktif')");
            if ($st) { $st->bind_param('sdssi', $name, $price, $unit, $desc, $hours); $st->execute(); $st->close(); }
        }
    }
}
if (!$catalogInitialized && laundryTableExists($conn, 'voucher')) {
    $code = 'HEMAT10';
    $st = $conn->prepare('SELECT id_voucher FROM voucher WHERE UPPER(kode)=? LIMIT 1');
    if ($st) {
        $st->bind_param('s', $code); $st->execute(); $existing = $st->get_result()->fetch_assoc(); $st->close();
        $name = 'Diskon 10%'; $desc = 'Diskon 10% untuk pesanan laundry Anda.'; $kind = 'persen'; $value = 10.0; $minimum = 0.0; $maximum = 10000.0;
        $start = date('Y-m-d H:i:s', strtotime('-1 day')); $end = date('Y-m-d H:i:s', strtotime('+1 year'));
        if (!$existing) {
            $st = $conn->prepare("INSERT INTO voucher (kode,nama,deskripsi,jenis_diskon,nilai_diskon,minimal_transaksi,maksimal_diskon,tanggal_mulai,tanggal_berakhir,status) VALUES (?,?,?,?,?,?,?,?,?,'aktif')");
            if ($st) { $st->bind_param('ssssdddss', $code, $name, $desc, $kind, $value, $minimum, $maximum, $start, $end); $st->execute(); $st->close(); }
        } else {
            $st = $conn->prepare("UPDATE voucher SET nama=?,deskripsi=?,jenis_diskon=?,nilai_diskon=?,minimal_transaksi=?,maksimal_diskon=?,tanggal_mulai=?,tanggal_berakhir=?,status='aktif' WHERE id_voucher=?");
            if ($st) { $vid=(int)$existing['id_voucher']; $st->bind_param('sssdddssi', $name, $desc, $kind, $value, $minimum, $maximum, $start, $end, $vid); $st->execute(); $st->close(); }
        }
    }
}
if (!$catalogInitialized && laundryTableExists($conn, 'layanan') && laundryTableExists($conn, 'voucher')) {
    $conn->query("INSERT IGNORE INTO laundryku_setup_flags (flag) VALUES ('services_voucher_seed_v1')");
}
?>
