<?php
// ============================================================
// SIGAP — includes/auth_check.php
// Session guard — wajib di-include di setiap API endpoint
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/response.php';

// Mulai session yang aman
startSecureSession();

/**
 * Periksa apakah user sudah login.
 * Jika belum, kirim JSON 401 dan hentikan eksekusi.
 *
 * @return array Data user dari session
 */
function requireLogin(): array
{
    if (empty($_SESSION['sigap_user'])) {
        jsonError('Sesi tidak valid. Silakan login kembali.', 401);
    }

    return $_SESSION['sigap_user'];
}

/**
 * Periksa apakah user memiliki role tertentu.
 * Jika tidak, kirim JSON 403.
 *
 * @param string|array $roles — role yang diizinkan
 * @param array        $user  — data user dari requireLogin()
 */
function requireRole(string|array $roles, array $user): void
{
    $allowed = is_array($roles) ? $roles : [$roles];

    if (!in_array($user['role'], $allowed, true)) {
        jsonError('Anda tidak memiliki akses untuk melakukan tindakan ini.', 403);
    }
}

/**
 * Kembalikan data user yang sedang login tanpa menghentikan eksekusi.
 * Return null jika belum login.
 *
 * @return array|null
 */
function getLoginUser(): ?array
{
    return $_SESSION['sigap_user'] ?? null;
}