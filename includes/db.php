<?php
// ============================================================
// SIGAP — includes/db.php
// PDO Singleton — koneksi database tunggal per request
// ============================================================

require_once dirname(__DIR__) . '/config.php';

/**
 * Kembalikan instance PDO tunggal (singleton pattern).
 * Koneksi hanya dibuat sekali per request PHP.
 *
 * @return PDO
 * @throws RuntimeException jika koneksi gagal
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log error detail ke file — jangan tampilkan ke client
        appLog('Koneksi database gagal: ' . $e->getMessage(), 'ERROR');

        // Kembalikan JSON error generik
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal terhubung ke database. Hubungi administrator.',
            'data'    => null,
        ]);
        exit;
    }

    return $pdo;
}
