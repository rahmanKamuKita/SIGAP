<?php
// ============================================================
// SIGAP — Sistem Informasi Pelanggaran Siswa
// config.php — Konfigurasi Utama Aplikasi
//
// Letakkan file ini di: htdocs/sigap/config.php
// ============================================================

// ── Timezone ──────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');

// ── Error reporting (set ke 0 di production) ──────────────
error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/logs/error.log');

// ── Load Environment Variables (.env) ──────────────────────
// ── Load Environment Variables (.env) ──────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            // Abaikan baris kosong dan komentar
            if ($line === '' || strpos($line, '#') === 0)
                continue;

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Hilangkan tanda kutip jika ada
                $_ENV[$key] = trim($value, "\"'");
            }
        }
    }
}

// ── Mode aplikasi & error reporting ───────────────────────
// Default 'production': error disembunyikan dari layar, hanya dicatat ke log.
// Di komputer sendiri, tambahkan APP_ENV=development di file .env.
define('APP_ENV', env('APP_ENV', 'production'));
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/error.log');

/**
 * Helper untuk mengambil nilai Environment Variable
 */
function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? $default;
}

// ── Database ───────────────────────────────────────────────
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'sigap_db'));
define('DB_USER', env('DB_USER', 'root')); // username MySQL
define('DB_PASS', env('DB_PASS', ''));     // password MySQL
define('DB_CHARSET', 'utf8mb4');

// ── Aplikasi ───────────────────────────────────────────────
define('APP_NAME', env('APP_NAME', 'SIGAP'));
define('SCHOOL_NAME', env('SCHOOL_NAME', 'SMK Negeri 1 Masbagik'));
define('SCHOOL_BK', env('SCHOOL_BK', 'Dra. Sri Wahyuni, M.Pd.'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));

// ── Base URL (sesuaikan jika nama folder berbeda) ──────────
define('BASE_URL', env('BASE_URL', 'http://localhost/sigap'));

// ── Session ────────────────────────────────────────────────
define('SESSION_NAME', 'sigap_session');
define('SESSION_LIFETIME', 7200); // 2 jam dalam detik

// ── Fonnte WhatsApp Gateway ────────────────────────────────
define('FONNTE_TOKEN', env('FONNTE_TOKEN', '')); // Token diisi di file .env, bukan di sini
define('FONNTE_API_URL', 'https://api.fonnte.com/send');
define('FONNTE_COUNTRY_CODE', '62'); // Indonesia

// ── Pagination ─────────────────────────────────────────────
define('DEFAULT_PER_PAGE', 10);

// ── Upload (jika nanti dibutuhkan) ────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB

// ── Load Global Helpers ────────────────────────────────────
require_once __DIR__ . '/includes/helpers.php';