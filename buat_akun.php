<?php
// ============================================================
// SIGAP — buat_akun.php
// Membuat akun Guru BK baru atau mereset password akun.
// HANYA bisa dijalankan dari terminal (CLI), bukan dari browser.
//
// Pakai:
//   php buat_akun.php            → buat akun Guru BK baru
//   php buat_akun.php reset      → reset password akun yang sudah ada
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

function tanya(string $label): string
{
    echo $label . ': ';
    return trim((string) fgets(STDIN));
}

function tanyaPassword(string $label): string
{
    $windows = stripos(PHP_OS, 'WIN') === 0;
    echo $label . ': ';
    if (!$windows) {
        shell_exec('stty -echo');
    }
    $pass = trim((string) fgets(STDIN));
    if (!$windows) {
        shell_exec('stty echo');
        echo PHP_EOL;
    }
    return $pass;
}

function passwordBaru(): string
{
    $windows = stripos(PHP_OS, 'WIN') === 0;
    if ($windows) {
        echo "Catatan: di Windows password terlihat saat diketik. Bersihkan layar (cls) setelah selesai.\n";
    }
    $p1 = tanyaPassword('Password (min. 8 karakter)');
    if (strlen($p1) < 8) {
        exit("Gagal: password minimal 8 karakter.\n");
    }
    if ($p1 !== tanyaPassword('Ulangi password')) {
        exit("Gagal: kedua password tidak sama.\n");
    }
    return $p1;
}

$pdo = getDB();
$mode = $argv[1] ?? 'buat';

if ($mode === 'reset') {
    $username = tanya('Username akun yang direset');
    $cek = $pdo->prepare('SELECT id, nama FROM users WHERE username = ? LIMIT 1');
    $cek->execute([$username]);
    $u = $cek->fetch();
    if (!$u) {
        exit("Gagal: username tidak ditemukan.\n");
    }
    $hash = password_hash(passwordBaru(), PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $u['id']]);
    appLog("Password direset via CLI: {$username}", 'INFO');
    exit("Berhasil: password {$u['nama']} ({$username}) sudah diganti.\n");
}

// ── Mode buat akun ────────────────────────────────────────
$nama = cleanInput(tanya('Nama lengkap'));
$username = tanya('Username (huruf kecil, angka, titik, underscore)');

if ($nama === '' || mb_strlen($nama) > 100) {
    exit("Gagal: nama wajib diisi (maks. 100 karakter).\n");
}
if (!preg_match('/^[a-z0-9_.]{3,50}$/', $username)) {
    exit("Gagal: username hanya huruf kecil, angka, titik, underscore (3–50 karakter).\n");
}
$cek = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$cek->execute([$username]);
if ($cek->fetch()) {
    exit("Gagal: username sudah dipakai.\n");
}

$hash = password_hash(passwordBaru(), PASSWORD_BCRYPT);

// Avatar = inisial maksimal 2 huruf (sama seperti logika sebelumnya)
$avatar = '';
foreach (array_slice(preg_split('/\s+/', $nama), 0, 2) as $kata) {
    $avatar .= mb_strtoupper(mb_substr($kata, 0, 1));
}

$pdo->prepare(
    'INSERT INTO users (nama, username, password, role, avatar, is_active) VALUES (?, ?, ?, ?, ?, 1)'
)->execute([$nama, $username, $hash, 'guru_bk', $avatar]);

appLog("Akun Guru BK dibuat via CLI: {$username}", 'INFO');
echo "Berhasil: akun {$nama} ({$username}) dibuat dengan role Guru BK.\n";