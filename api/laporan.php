<?php
// ============================================================
// SIGAP — api/laporan.php
// Laporan & statistik dengan filter bulan/tahun/kelas
//
// GET  /api/laporan.php?bulan=4&tahun=2026&kelas=XII+TKJ+1
// POST /api/laporan.php  action=update_status, id=N, status=...
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';

setCorsHeaders();
requireLogin();
$pdo = getDB();

// ── POST: update status penanganan ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');

    if ($action !== 'update_status') {
        jsonError("Action '{$action}' tidak dikenali.", 400);
    }

    $id = cleanInt($_POST['id'] ?? 0);
    $status = cleanInput($_POST['status'] ?? '');

    $allowedStatus = ['Belum Ditangani', 'Sedang Diproses', 'Sudah Selesai'];

    if ($id <= 0) {
        jsonError('ID pelanggaran tidak valid.', 422);
    }
    if (!in_array($status, $allowedStatus, true)) {
        jsonError('Status tidak valid.', 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE pelanggaran SET status_penanganan = ? WHERE id = ?'
    );
    $stmt->execute([$status, $id]);

    if ($stmt->rowCount() === 0) {
        jsonError('Data tidak ditemukan atau status tidak berubah.', 404);
    }

    appLog("Update status penanganan id={$id} → {$status}", 'INFO');

    jsonSuccess(
        ['id' => $id, 'status_penanganan' => $status],
        'Status penanganan berhasil diperbarui.'
    );
}

// ── Hanya terima GET setelah ini ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method tidak diizinkan.', 405);
}

// ── Filter params ─────────────────────────────────────────
$bulan = cleanInt($_GET['bulan'] ?? 0);
$tahun = cleanInt($_GET['tahun'] ?? (int) date('Y'));
$kelas = cleanInput($_GET['kelas'] ?? '');

// Validasi
if ($bulan < 0 || $bulan > 12)
    $bulan = 0;
if ($tahun < 2020 || $tahun > 2099)
    $tahun = (int) date('Y');

// ── Build base filter ─────────────────────────────────────
$where = ['YEAR(p.tanggal) = ?'];
$params = [$tahun];

if ($bulan > 0) {
    $where[] = 'MONTH(p.tanggal) = ?';
    $params[] = $bulan;
}

if ($kelas !== '') {
    $where[] = 's.kelas = ?';
    $params[] = $kelas;
}

$whereSql = implode(' AND ', $where);
$baseFrom = "FROM pelanggaran p
             INNER JOIN siswa             s ON p.siswa_id             = s.id
             INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
             WHERE {$whereSql}";

// ── 1. Summary cards ──────────────────────────────────────
$stmtSum = $pdo->prepare(
    "SELECT
        COUNT(*)                                        AS total_pelanggaran,
        COALESCE(SUM(j.poin), 0)                       AS total_poin,
        SUM(p.status_wa = 'Terkirim')                  AS wa_terkirim,
        SUM(p.status_wa = 'Pending')                   AS wa_pending,
        SUM(p.status_wa = 'Gagal')                     AS wa_gagal,
        SUM(p.status_penanganan = 'Belum Ditangani')   AS penanganan_belum,
        SUM(p.status_penanganan = 'Sedang Diproses')   AS penanganan_proses,
        SUM(p.status_penanganan = 'Sudah Selesai')     AS penanganan_selesai
     {$baseFrom}"
);
$stmtSum->execute($params);
$summary = $stmtSum->fetch();

$totalPelanggaran = (int) ($summary['total_pelanggaran'] ?? 0);
$waTerkirim = (int) ($summary['wa_terkirim'] ?? 0);
$waRate = $totalPelanggaran > 0
    ? round(($waTerkirim / $totalPelanggaran) * 100, 1)
    : 0;

// ── 2. Jenis paling sering ────────────────────────────────
$stmtJenis = $pdo->prepare(
    "SELECT j.nama AS nama_jenis, COUNT(*) AS jumlah
     {$baseFrom}
     GROUP BY j.id, j.nama
     ORDER BY jumlah DESC
     LIMIT 1"
);
$stmtJenis->execute($params);
$topJenis = $stmtJenis->fetch();

// ── 3. Kelas paling banyak ────────────────────────────────
$stmtKelas = $pdo->prepare(
    "SELECT s.kelas, COUNT(*) AS jumlah
     {$baseFrom}
     GROUP BY s.kelas
     ORDER BY jumlah DESC
     LIMIT 1"
);
$stmtKelas->execute($params);
$topKelas = $stmtKelas->fetch();

// ── 4. Chart: per bulan (tahun ini) ──────────────────────
$stmtBulanan = $pdo->prepare(
    "SELECT
        MONTH(p.tanggal) AS bulan_num,
        COUNT(*)          AS jumlah
     FROM pelanggaran p
     INNER JOIN siswa             s ON p.siswa_id             = s.id
     INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
     WHERE YEAR(p.tanggal) = ?" .
    ($kelas !== '' ? ' AND s.kelas = ?' : '') .
    " GROUP BY MONTH(p.tanggal)
      ORDER BY bulan_num"
);
$bulananParams = [$tahun];
if ($kelas !== '')
    $bulananParams[] = $kelas;
$stmtBulanan->execute($bulananParams);
$rawBulanan = $stmtBulanan->fetchAll();

$namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
$bulanMap = array_column($rawBulanan, 'jumlah', 'bulan_num');
$chartBulanan = [];
for ($i = 1; $i <= 12; $i++) {
    $chartBulanan[] = [
        'bulan' => $namaBulan[$i - 1],
        'bulan_num' => $i,
        'jumlah' => (int) ($bulanMap[$i] ?? 0),
    ];
}

// ── 5. Chart: per jenis pelanggaran ──────────────────────
$stmtChartJenis = $pdo->prepare(
    "SELECT j.nama AS nama_jenis, j.color, COUNT(*) AS jumlah
     {$baseFrom}
     GROUP BY j.id, j.nama, j.color
     ORDER BY jumlah DESC"
);
$stmtChartJenis->execute($params);
$chartJenis = $stmtChartJenis->fetchAll();
foreach ($chartJenis as &$row)
    $row['jumlah'] = (int) $row['jumlah'];
unset($row);

// ── 6. Chart: per kelas ───────────────────────────────────
$stmtChartKelas = $pdo->prepare(
    "SELECT s.kelas, COUNT(*) AS jumlah
     {$baseFrom}
     GROUP BY s.kelas
     ORDER BY jumlah DESC"
);
$stmtChartKelas->execute($params);
$chartKelas = $stmtChartKelas->fetchAll();
foreach ($chartKelas as &$row)
    $row['jumlah'] = (int) $row['jumlah'];
unset($row);

// ── 7. Data detail lengkap ────────────────────────────────
$page = max(1, cleanInt($_GET['page'] ?? 1));
$perPage = max(1, cleanInt($_GET['per_page'] ?? DEFAULT_PER_PAGE));
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) {$baseFrom}");
$countStmt->execute($params);
$totalDetail = (int) $countStmt->fetchColumn();

$stmtDetail = $pdo->prepare(
    "SELECT
        p.id, p.kode_pelanggaran, p.tanggal, j.poin AS poin, j.tingkat,
        p.keterangan, p.status_wa, p.waktu_kirim_wa,
        p.status_penanganan,
        s.nama AS nama_siswa, s.nis, s.kelas,
        j.nama AS nama_jenis, j.color AS jenis_color
     {$baseFrom}
     ORDER BY p.id DESC
     LIMIT ? OFFSET ?"
);
$stmtDetail->execute([...$params, $perPage, $offset]);
$detail = $stmtDetail->fetchAll();

foreach ($detail as &$row) {
    $row['id'] = (int) $row['id'];
    $row['poin'] = (int) $row['poin'];
}
unset($row);

// ── 8. Daftar kelas yang tersedia (untuk filter dropdown) ─
$stmtKelasAll = $pdo->query(
    'SELECT DISTINCT kelas FROM siswa WHERE is_active = 1 ORDER BY kelas'
);
$daftarKelas = array_column($stmtKelasAll->fetchAll(), 'kelas');

// ── Susun response ────────────────────────────────────────
$totalPages = (int) ceil($totalDetail / max($perPage, 1));

jsonSuccess([
    'filter' => [
        'bulan' => $bulan,
        'tahun' => $tahun,
        'kelas' => $kelas,
    ],
    'summary' => [
        'total_pelanggaran' => $totalPelanggaran,
        'total_poin' => (int) ($summary['total_poin'] ?? 0),
        'wa_terkirim' => $waTerkirim,
        'wa_pending' => (int) ($summary['wa_pending'] ?? 0),
        'wa_gagal' => (int) ($summary['wa_gagal'] ?? 0),
        'wa_success_rate' => $waRate,
        'penanganan_belum' => (int) ($summary['penanganan_belum'] ?? 0),
        'penanganan_proses' => (int) ($summary['penanganan_proses'] ?? 0),
        'penanganan_selesai' => (int) ($summary['penanganan_selesai'] ?? 0),
        'top_jenis' => $topJenis['nama_jenis'] ?? '-',
        'top_jenis_count' => (int) ($topJenis['jumlah'] ?? 0),
        'top_kelas' => $topKelas['kelas'] ?? '-',
        'top_kelas_count' => (int) ($topKelas['jumlah'] ?? 0),
    ],
    'chart_bulanan' => $chartBulanan,
    'chart_jenis' => $chartJenis,
    'chart_kelas' => $chartKelas,
    'daftar_kelas' => $daftarKelas,
    'detail' => $detail,
    'pagination' => [
        'total' => $totalDetail,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
    ],
], 'Data laporan berhasil dimuat.');