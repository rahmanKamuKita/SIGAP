<?php
// ============================================================
// SIGAP — api/dashboard.php
// Endpoint: GET /api/dashboard.php
// Kembalikan statistik untuk halaman dashboard
// ============================================================

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// Guard: harus login
requireLogin();

// Hanya izinkan GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method tidak diizinkan.', 405);
}

try {
    $pdo = getDB();

    // ── 1. Total siswa aktif ───────────────────────────────────
    // Kolom: is_active (bukan status)
    $totalSiswa = (int) $pdo->query(
        "SELECT COUNT(*) FROM siswa WHERE is_active = 1"
    )->fetchColumn();

    // ── 2. Bulan & tahun sekarang ──────────────────────────────
    $bulanIni = date('m');
    $tahunIni = date('Y');
    $namaBulan = [
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $bulanLabel = $namaBulan[(int) $bulanIni] . ' ' . $tahunIni;

    // ── 3. Pelanggaran bulan ini ───────────────────────────────
    // JOIN sama persis dengan api/pelanggaran.php (riwayat) supaya hitungannya identik
    $stmtBulan = $pdo->prepare("
        SELECT COUNT(*)
        FROM pelanggaran p
        INNER JOIN siswa             s ON p.siswa_id             = s.id
        INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
        WHERE MONTH(p.tanggal) = :bulan
          AND YEAR(p.tanggal)  = :tahun
    ");
    $stmtBulan->execute([':bulan' => $bulanIni, ':tahun' => $tahunIni]);
    $pelanggaranBulanIni = (int) $stmtBulan->fetchColumn();

    // ── 4. Status WA ───────────────────────────────────────────
    $stmtWA = $pdo->query("
        SELECT
            COUNT(*)                        AS wa_total,
            SUM(p.status_wa = 'Terkirim')   AS wa_terkirim,
            SUM(p.status_wa = 'Pending')    AS wa_pending,
            SUM(p.status_wa = 'Gagal')      AS wa_gagal
        FROM pelanggaran p
        INNER JOIN siswa             s ON p.siswa_id             = s.id
        INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
    ");
    $wa = $stmtWA->fetch();

    $waTotal = (int) $wa['wa_total'];
    $waTerkirim = (int) $wa['wa_terkirim'];
    $waPending = (int) $wa['wa_pending'];
    $waGagal = (int) $wa['wa_gagal'];
    $waRate = $waTotal > 0 ? round(($waTerkirim / $waTotal) * 100) : 0;

    // ── 5. Frekuensi jenis pelanggaran bulan ini (bar chart) ───
    // Kolom: jenis_pelanggaran_id (bukan id_jenis), jp.nama (bukan nama_jenis), jp.color (bukan warna)
    $stmtJenis = $pdo->prepare("
        SELECT jp.nama AS nama_jenis, jp.color AS jenis_color, COUNT(p.id) AS jumlah
        FROM pelanggaran p
        JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
        WHERE MONTH(p.tanggal) = :bulan
          AND YEAR(p.tanggal)  = :tahun
        GROUP BY jp.id, jp.nama, jp.color
        ORDER BY jumlah DESC
        LIMIT 6
    ");
    $stmtJenis->execute([':bulan' => $bulanIni, ':tahun' => $tahunIni]);
    $frekuensiJenis = $stmtJenis->fetchAll();

    // ── 6. Pelanggaran per kelas (pie chart) ───────────────────
    // Kolom: siswa_id (bukan id_siswa)
    $stmtKelas = $pdo->prepare("
        SELECT s.kelas, COUNT(p.id) AS jumlah
        FROM pelanggaran p
        JOIN siswa s ON p.siswa_id = s.id
        WHERE MONTH(p.tanggal) = :bulan
          AND YEAR(p.tanggal)  = :tahun
        GROUP BY s.kelas
        ORDER BY jumlah DESC
    ");
    $stmtKelas->execute([':bulan' => $bulanIni, ':tahun' => $tahunIni]);
    $perKelas = $stmtKelas->fetchAll();

    // ── 7. Pelanggaran terbaru (tabel recent) ──────────────────
    // Kolom: s.nama AS nama_siswa (bukan s.nama_siswa)
    $stmtTerbaru = $pdo->prepare("
        SELECT
            p.id,
            s.nama          AS nama_siswa,
            s.nis,
            s.kelas,
            jp.nama         AS nama_jenis,
            jp.color        AS jenis_color,
            p.tanggal,
            jp.poin         AS poin,
            p.status_wa
        FROM pelanggaran p
        JOIN siswa             s  ON p.siswa_id             = s.id
        JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
        -- Hanya 5 data terbaru, urut berdasarkan yang PALING AKHIR DICATAT (id terbesar)
        ORDER BY p.id DESC
        LIMIT 5
    ");
    $stmtTerbaru->execute();
    $pelanggaranTerbaru = $stmtTerbaru->fetchAll();

    // ── Kirim response ─────────────────────────────────────────
    jsonSuccess([
        'total_siswa' => $totalSiswa,
        'pelanggaran_bulan_ini' => $pelanggaranBulanIni,
        'pelanggaran_total' => $waTotal,   // semua pelanggaran = angka "Total Pelanggaran" di Riwayat
        'bulan_label' => $bulanLabel,
        'wa_total' => $waTotal,
        'wa_terkirim' => $waTerkirim,
        'wa_pending' => $waPending,
        'wa_gagal' => $waGagal,
        'wa_success_rate' => $waRate,
        'frekuensi_jenis_bulan_ini' => $frekuensiJenis,
        'pelanggaran_per_kelas' => $perKelas,
        'pelanggaran_terbaru' => $pelanggaranTerbaru,
    ]);

} catch (Throwable $e) {
    appLog('dashboard.php error: ' . $e->getMessage(), 'ERROR');
    jsonError('Terjadi kesalahan server: ' . $e->getMessage(), 500);
}