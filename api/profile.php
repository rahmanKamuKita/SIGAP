<?php
// ============================================================
// SIGAP — api/profile.php
// Manajemen Profil User + Registrasi Akun Baru
//
// POST action=register         → daftar akun baru (publik)
// POST action=update_profile   → update data profil (auth)
// POST action=change_password  → ganti password (auth)
// GET  action=me               → ambil data profil sendiri (auth)
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';

setCorsHeaders();
startSecureSession();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

if ($method === 'GET') {
    // Butuh auth untuk GET profil
    require_once dirname(__DIR__) . '/includes/auth_check.php';
    $currentUser = requireLogin();
    handleGetProfile($pdo, $currentUser);
} elseif ($method === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');

    require_once dirname(__DIR__) . '/includes/auth_check.php';
    $currentUser = requireLogin();

    match ($action) {
        'update_profile' => handleUpdateProfile($pdo, $currentUser),
        'change_password' => handleChangePassword($pdo, $currentUser),
        default => jsonError('Aksi tidak dikenali.', 400),
    };
} else {
    jsonError('Method tidak diizinkan.', 405);
}

// ── GET: Profil Sendiri ───────────────────────────────────
function handleGetProfile(PDO $pdo, array $currentUser): never
{
    $stmt = $pdo->prepare(
        'SELECT
            u.id, u.nama, u.username, u.role, u.avatar, u.is_active, u.created_at, u.updated_at,
            COUNT(p.id) AS total_input_pelanggaran
         FROM users u
         LEFT JOIN pelanggaran p ON p.dicatat_oleh = u.id
         WHERE u.id = ?
         GROUP BY u.id'
    );
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Pengguna tidak ditemukan.', 404);
    }

    // Pelanggaran bulan ini
    $stmtBulan = $pdo->prepare(
        'SELECT COUNT(*) FROM pelanggaran
         WHERE dicatat_oleh = ? AND MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())'
    );
    $stmtBulan->execute([$currentUser['id']]);
    $user['pelanggaran_bulan_ini'] = (int) $stmtBulan->fetchColumn();

    // Total WA terkirim oleh user ini
    $stmtWA = $pdo->prepare(
        "SELECT COUNT(*) FROM pelanggaran WHERE dicatat_oleh = ? AND status_wa = 'Terkirim'"
    );
    $stmtWA->execute([$currentUser['id']]);
    $user['wa_terkirim'] = (int) $stmtWA->fetchColumn();

    // Total siswa aktif (global — info untuk semua user)
    $stmtSiswa = $pdo->query('SELECT COUNT(*) FROM siswa WHERE is_active = 1');
    $user['total_siswa'] = (int) $stmtSiswa->fetchColumn();

    $user['id'] = (int) $user['id'];
    $user['total_input_pelanggaran'] = (int) $user['total_input_pelanggaran'];
    $user['is_active'] = (bool) $user['is_active'];

    jsonSuccess($user, 'Data profil berhasil dimuat.');
}

// ── POST: Registrasi Akun Baru ────────────────────────────
function handleRegister(PDO $pdo): never
{
    // Kumpulkan & bersihkan input
    $nama = cleanInput($_POST['nama'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = cleanInput($_POST['role'] ?? '');
    $nip = cleanInput($_POST['nip'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $wa = cleanInput($_POST['wa'] ?? '');

    // ── Validasi ──────────────────────────────────────────
    $errors = [];

    if (empty($nama)) {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-z0-9_.]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf kecil, angka, titik, dan underscore.';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Username maksimal 50 karakter.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    }

    $validRoles = ['guru_bk', 'admin', 'wali_kelas'];
    if (!in_array($role, $validRoles, true)) {
        $errors['role'] = 'Role tidak valid.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if (!empty($errors)) {
        jsonError('Data tidak valid. Periksa kembali form.', 422, $errors);
    }

    // ── Cek username duplikat ─────────────────────────────
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $checkStmt->execute([$username]);
    if ($checkStmt->fetch()) {
        jsonError("Username '{$username}' sudah digunakan. Pilih username lain.", 409, [
            'username' => "Username '{$username}' sudah digunakan."
        ]);
    }

    // ── Generate avatar (inisial nama) ────────────────────
    $namaParts = explode(' ', trim($nama));
    $avatar = '';
    foreach (array_slice($namaParts, 0, 2) as $part) {
        if (!empty($part))
            $avatar .= strtoupper($part[0]);
    }

    // ── Format nomor WA ───────────────────────────────────
    if (!empty($wa)) {
        $wa = preg_replace('/[^0-9+]/', '', $wa);
        $wa = ltrim($wa, '+');
        if (str_starts_with($wa, '0'))
            $wa = '62' . substr($wa, 1);
        elseif (str_starts_with($wa, '8'))
            $wa = '62' . $wa;
    }

    // ── Hash password & simpan ────────────────────────────
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'INSERT INTO users (nama, username, password, role, avatar, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([$nama, $username, $hashedPassword, $role, $avatar]);
    $newId = (int) $pdo->lastInsertId();

    // Simpan info tambahan ke tabel profil jika ada (opsional — kolom email/wa/nip)
    // Jika tabel users Anda tidak punya kolom email/wa/nip, hapus blok ini
    try {
        // Coba update kolom tambahan jika ada di skema
        $tryUpdate = $pdo->prepare(
            'UPDATE users SET
                email    = COALESCE(?, email),
                wa       = COALESCE(?, wa),
                nip      = COALESCE(?, nip)
             WHERE id = ?'
        );
        $tryUpdate->execute([
            !empty($email) ? $email : null,
            !empty($wa) ? $wa : null,
            !empty($nip) ? $nip : null,
            $newId,
        ]);
    } catch (PDOException $e) {
        // Kolom tidak ada di skema — abaikan saja
        appLog('Kolom email/wa/nip tidak tersedia di tabel users: ' . $e->getMessage(), 'INFO');
    }

    appLog("Akun baru didaftarkan: {$username} (role: {$role})", 'INFO');

    jsonSuccess(
        [
            'id' => $newId,
            'nama' => $nama,
            'username' => $username,
            'role' => $role,
            'avatar' => $avatar,
        ],
        "Akun '{$username}' berhasil dibuat. Silakan login.",
        201
    );
}

// ── POST: Update Profil ───────────────────────────────────
function handleUpdateProfile(PDO $pdo, array $currentUser): never
{
    $nama = cleanInput($_POST['nama'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $wa = cleanInput($_POST['wa'] ?? '');
    $nip = cleanInput($_POST['nip'] ?? '');
    $sekolah = cleanInput($_POST['sekolah'] ?? '');

    $errors = [];

    if (empty($nama)) {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    if (empty($username)) {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-z0-9_.]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh huruf kecil, angka, titik, underscore.';
    } elseif (strlen($username) > 50) {
        $errors['username'] = 'Username maksimal 50 karakter.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if (!empty($errors)) {
        jsonError('Data tidak valid.', 422, $errors);
    }

    // Cek username duplikat (kecuali milik user ini)
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
    $checkStmt->execute([$username, $currentUser['id']]);
    if ($checkStmt->fetch()) {
        jsonError("Username '{$username}' sudah digunakan.", 409, [
            'username' => "Username tersebut sudah dipakai pengguna lain."
        ]);
    }

    // Generate avatar baru dari nama
    $namaParts = explode(' ', trim($nama));
    $avatar = '';
    foreach (array_slice($namaParts, 0, 2) as $part) {
        if (!empty($part))
            $avatar .= strtoupper($part[0]);
    }

    // Update kolom dasar (selalu ada di skema)
    $stmt = $pdo->prepare(
        'UPDATE users SET nama = ?, username = ?, avatar = ? WHERE id = ?'
    );
    $stmt->execute([$nama, $username, $avatar, $currentUser['id']]);

    // Update kolom tambahan jika tersedia
    try {
        $tryUpdate = $pdo->prepare(
            'UPDATE users SET
                email  = COALESCE(?, email),
                wa     = COALESCE(?, wa),
                nip    = COALESCE(?, nip)
             WHERE id = ?'
        );
        $tryUpdate->execute([
            !empty($email) ? $email : null,
            !empty($wa) ? $wa : null,
            !empty($nip) ? $nip : null,
            $currentUser['id'],
        ]);
    } catch (PDOException $e) {
        appLog('Kolom tambahan tidak tersedia: ' . $e->getMessage(), 'INFO');
    }

    appLog("Profil diperbarui: {$currentUser['username']} → {$username}", 'INFO');

    // Ambil data terbaru
    $fetchStmt = $pdo->prepare(
        'SELECT id, nama, username, role, avatar, is_active FROM users WHERE id = ?'
    );
    $fetchStmt->execute([$currentUser['id']]);
    $updated = $fetchStmt->fetch();
    $updated['id'] = (int) $updated['id'];

    jsonSuccess($updated, 'Profil berhasil diperbarui.');
}

// ── POST: Ganti Password ──────────────────────────────────
function handleChangePassword(PDO $pdo, array $currentUser): never
{
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    $errors = [];

    if (empty($currentPassword)) {
        $errors['current_password'] = 'Password saat ini wajib diisi.';
    }
    if (empty($newPassword)) {
        $errors['new_password'] = 'Password baru wajib diisi.';
    } elseif (strlen($newPassword) < 8) {
        $errors['new_password'] = 'Password baru minimal 8 karakter.';
    }

    if (!empty($errors)) {
        jsonError('Data tidak valid.', 422, $errors);
    }

    // Ambil hash password saat ini dari DB
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        usleep(300000); // 0.3 detik anti-timing attack
        jsonError('Password saat ini tidak sesuai.', 401);
    }

    // Pastikan password baru tidak sama dengan yang lama
    if (password_verify($newPassword, $user['password'])) {
        jsonError('Password baru tidak boleh sama dengan password saat ini.', 422);
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->execute([$newHash, $currentUser['id']]);

    appLog("Password diubah oleh: {$currentUser['username']}", 'INFO');

    jsonSuccess(null, 'Password berhasil diubah. Silakan login ulang dengan password baru.');
}