<?php
// ============================================================
// SIGAP — api/wa.php
// WhatsApp resend & status endpoint
//
// POST /api/wa.php action=resend&pelanggaran_id=N
// GET  /api/wa.php action=status&pelanggaran_id=N
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/wa_sender.php';

setCorsHeaders();
$currentUser = requireLogin();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    match ($action) {
        'resend' => resendWA(),
        default => jsonError("Action '{$action}' tidak dikenali.", 400),
    };
} elseif ($method === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');
    match ($action) {
        'status' => getWAStatus(),
        default => jsonError("Action '{$action}' tidak dikenali.", 400),
    };
} else {
    jsonError('Method tidak diizinkan.', 405);
}

// ── POST: Resend WA ───────────────────────────────────────
function resendWA(): never
{
    global $pdo, $currentUser;

    $pelanggaranId = cleanInt($_POST['pelanggaran_id'] ?? 0);
    if ($pelanggaranId <= 0) {
        jsonError('ID pelanggaran tidak valid.', 422);
    }

    // Ambil data pelanggaran + siswa
    $stmt = $pdo->prepare(
        'SELECT
            p.id, p.kode_pelanggaran, p.wa_tujuan,
            p.wa_pesan, p.status_wa, p.tanggal, p.keterangan,
            s.nama AS nama_siswa, s.wa_ortu,
            j.nama AS nama_jenis
         FROM pelanggaran p
         INNER JOIN siswa             s ON p.siswa_id             = s.id
         INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE p.id = ?'
    );
    $stmt->execute([$pelanggaranId]);
    $record = $stmt->fetch();

    if (!$record) {
        jsonError('Data pelanggaran tidak ditemukan.', 404);
    }

    // Tentukan nomor tujuan (wa_tujuan di DB atau fallback ke wa_ortu siswa)
    $nomorTujuan = $record['wa_tujuan'] ?: $record['wa_ortu'];
    if (empty($nomorTujuan)) {
        jsonError('Nomor WhatsApp orang tua tidak tersedia untuk siswa ini.', 422);
    }

    // Gunakan wa_pesan yang sudah tersimpan, atau generate ulang jika kosong
    $pesan = $record['wa_pesan'];
    if (empty($pesan)) {
        $pesan = generateWAMessage([
            'nama_siswa' => $record['nama_siswa'],
            'nama_jenis' => $record['nama_jenis'],
            'tanggal' => $record['tanggal'],
            'keterangan' => $record['keterangan'],
        ]);
    }

    // Kirim ulang via Fonnte
    $sender = new WASender();
    $waResult = $sender->send($nomorTujuan, $pesan);

    $waktuKirim = date('Y-m-d H:i:s');
    $statusBaru = $waResult['success'] ? 'Terkirim' : 'Gagal';
    $waResponse = json_encode([
        'resend' => true,
        'response' => $waResult['response'],
        'error' => $waResult['error'],
    ]);

    // Update status di database
    $updateStmt = $pdo->prepare(
        'UPDATE pelanggaran
         SET status_wa       = ?,
             waktu_kirim_wa  = ?,
             wa_tujuan       = ?,
             wa_pesan        = ?,
             wa_response     = ?
         WHERE id = ?'
    );
    $updateStmt->execute([
        $statusBaru,
        $waktuKirim,
        $nomorTujuan,
        $pesan,
        $waResponse,
        $pelanggaranId,
    ]);

    appLog(
        "WA resend: {$record['kode_pelanggaran']} ke {$nomorTujuan}, " .
        "status: {$statusBaru}, oleh {$currentUser['username']}",
        'INFO'
    );

    $msg = $waResult['success']
        ? "Notifikasi WhatsApp berhasil dikirim ulang ke {$nomorTujuan}."
        : "Pengiriman ulang gagal: " . ($waResult['error'] ?? 'Kesalahan tidak diketahui.');

    jsonSuccess([
        'pelanggaran_id' => $pelanggaranId,
        'kode' => $record['kode_pelanggaran'],
        'nomor_tujuan' => $nomorTujuan,
        'status_wa' => $statusBaru,
        'waktu_kirim' => $waktuKirim,
        'wa_success' => $waResult['success'],
        'wa_error' => $waResult['error'],
    ], $msg);
}

// ── GET: Status WA ────────────────────────────────────────
function getWAStatus(): never
{
    global $pdo;

    $pelanggaranId = cleanInt($_GET['pelanggaran_id'] ?? 0);
    if ($pelanggaranId <= 0) {
        jsonError('ID pelanggaran tidak valid.', 422);
    }

    $stmt = $pdo->prepare(
        'SELECT
            p.id, p.kode_pelanggaran, p.status_wa,
            p.wa_tujuan, p.waktu_kirim_wa, p.wa_response,
            s.nama AS nama_siswa
         FROM pelanggaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.id = ?'
    );
    $stmt->execute([$pelanggaranId]);
    $record = $stmt->fetch();

    if (!$record) {
        jsonError('Data pelanggaran tidak ditemukan.', 404);
    }

    // Parse wa_response
    $waResponseParsed = null;
    if (!empty($record['wa_response'])) {
        $decoded = json_decode($record['wa_response'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $waResponseParsed = $decoded;
        }
    }

    jsonSuccess([
        'id' => (int) $record['id'],
        'kode_pelanggaran' => $record['kode_pelanggaran'],
        'nama_siswa' => $record['nama_siswa'],
        'status_wa' => $record['status_wa'],
        'wa_tujuan' => $record['wa_tujuan'],
        'waktu_kirim_wa' => $record['waktu_kirim_wa'],
        'wa_response' => $waResponseParsed,
    ], 'Status WA berhasil dimuat.');
}