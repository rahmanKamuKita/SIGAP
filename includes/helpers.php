<?php
// ============================================================
// SIGAP — includes/helpers.php
// Role & akses helper — satu file, semua logika izin ada di sini
//
// Letakkan di: htdocs/sigap/includes/helpers.php
// ============================================================



// ── HTTP & Session Helpers ────────────────────────────────

/**
 * Set CORS headers agar API bisa diakses dari frontend.
 */
function setCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

    // Langsung selesai jika preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Mulai session dengan konfigurasi aman.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE)
        return;

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ── Input Sanitization ────────────────────────────────────

/**
 * Bersihkan input string dari karakter berbahaya.
 */
function cleanInput(string $value): string
{
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Ambil nilai integer dari input, default 0 jika tidak valid.
 */
function cleanInt(mixed $value): int
{
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

// ── Logging ───────────────────────────────────────────────

/**
 * Tulis log ke file logs/sigap.log
 */
function appLog(string $message, string $level = 'INFO'): void
{
    $logDir = dirname(__DIR__) . '/logs';
    $logFile = $logDir . '/sigap.log';

    // Buat folder logs jika belum ada
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $line = "[{$timestamp}] [{$level}] [{$ip}] {$message}" . PHP_EOL;

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ── Code Generator ────────────────────────────────────────

/**
 * Generate kode unik (auto-increment berformat PREFIX + nomor).
 * Contoh: S001, P001, dsb.
 *
 * @param PDO    $pdo    — koneksi database
 * @param string $table  — nama tabel
 * @param string $column — nama kolom kode
 * @param string $prefix — huruf prefix (S, P, dsb.)
 */
function generateKode(PDO $pdo, string $table, string $column, string $prefix): string
{
    $stmt = $pdo->query(
        "SELECT MAX(CAST(SUBSTRING({$column}, " . (strlen($prefix) + 1) . ") AS UNSIGNED))
         FROM `{$table}`"
    );
    $max = (int) $stmt->fetchColumn();
    return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

/**
 * Hitung ulang total poin dan persentase pelanggaran siswa.
 *
 * Logika:
 *   - Setiap pelanggaran punya tingkat: ringan=1, sedang=2, berat=3
 *   - total_poin      = SUM(poin jenis pelanggaran)
 *   - persentase (%)  = (total_poin / (jumlah_pelanggaran × 3)) × 100
 *
 * Contoh: 4 pelanggaran (sedang=2, ringan=1, berat=3, sedang=2)
 *   total_poin  = 8
 *   persentase  = (8 / (4×3)) × 100 = 66.67%
 *
 * @param PDO $pdo     - koneksi database
 * @param int $siswaId - ID siswa
 * @return array       - ['total_poin', 'jumlah_pelanggaran', 'persentase']
 */
function recalculateSiswaPoin(PDO $pdo, int $siswaId): array
{
    // Hitung total poin dari jenis pelanggaran (bukan kolom poin di pelanggaran)
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(p.id)              AS jumlah_pelanggaran,
            COALESCE(SUM(j.poin), 0) AS total_poin
         FROM pelanggaran p
         INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE p.siswa_id = ?"
    );
    $stmt->execute([$siswaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $jumlah = (int) ($row['jumlah_pelanggaran'] ?? 0);
    $totalPoin = (int) ($row['total_poin'] ?? 0);
    $maksimal = $jumlah * 3; // maks jika semua pelanggaran berat

    // Persentase: 0 jika belum ada pelanggaran
    $persentase = $maksimal > 0
        ? round(($totalPoin / $maksimal) * 100, 2)
        : 0.00;

    // Simpan ke tabel siswa
    $pdo->prepare(
        'UPDATE siswa
         SET poin               = ?,
             jumlah_pelanggaran = ?,
             persentase         = ?
         WHERE id = ?'
    )->execute([$totalPoin, $jumlah, $persentase, $siswaId]);

    return [
        'total_poin' => $totalPoin,
        'jumlah_pelanggaran' => $jumlah,
        'persentase' => $persentase,
    ];
}