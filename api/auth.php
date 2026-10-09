<?php
// ============================================================
// SIGAP — auth.php
// Handler login & logout
// Method: POST
// Body: action=login|logout, ...fields
// Pembuatan akun dilakukan oleh admin sekolah lewat basis data
// (lihat buat_akun.php), tidak tersedia pendaftaran mandiri.
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';

setCorsHeaders();
startSecureSession();

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method tidak diizinkan.', 405);
}

$action = cleanInput($_POST['action'] ?? '');

match ($action) {
    'login' => handleLogin(),
    'logout' => handleLogout(),
    default => jsonError('Aksi tidak dikenali.', 400),
};

// ── Handler: Login ────────────────────────────────────────
function handleLogin(): never
{
    $username = cleanInput($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');   // password jangan di-htmlspecialchars

    // Validasi input tidak kosong
    $errors = [];
    if (empty($username))
        $errors['username'] = 'Username wajib diisi.';
    if (empty($password))
        $errors['password'] = 'Password wajib diisi.';

    if (!empty($errors)) {
        jsonError('Harap lengkapi form login.', 422, $errors);
    }

    // Query user — gunakan prepared statement
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT id, nama, username, password, role, avatar, is_active
         FROM users
         WHERE username = ?
         LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Cek user ditemukan & aktif
    if (!$user || !$user['is_active']) {
        // Delay kecil untuk mencegah timing attack
        usleep(300000); // 0.3 detik
        jsonError('Username atau password salah.', 401);
    }

    // Verifikasi password dengan bcrypt
    if (!password_verify($password, $user['password'])) {
        usleep(300000);
        jsonError('Username atau password salah.', 401);
    }

    // Susun data session (tanpa password)
    $sessionData = [
        'id' => $user['id'],
        'nama' => $user['nama'],
        'username' => $user['username'],
        'role' => $user['role'],
        'avatar' => $user['avatar'],
    ];

    // Regenerate session ID untuk mencegah session fixation
    session_regenerate_id(true);
    $_SESSION['sigap_user'] = $sessionData;

    appLog("Login berhasil: {$username} (role: {$user['role']})", 'INFO');

    jsonSuccess($sessionData, 'Login berhasil. Selamat datang, ' . $user['nama'] . '!');
}

// ── Handler: Logout ───────────────────────────────────────
function handleLogout(): never
{
    $user = $_SESSION['sigap_user'] ?? null;

    if ($user) {
        appLog("Logout: {$user['username']}", 'INFO');
    }

    // Hapus semua data session
    $_SESSION = [];

    // Hapus cookie session jika ada
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    jsonSuccess(null, 'Logout berhasil.');
}