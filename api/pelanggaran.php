<?php
// ============================================================
// SIGAP — api/pelanggaran.php
// CRUD Pelanggaran + WhatsApp Notification
//
// GET  /api/pelanggaran.php              → list (paginated, filter)
// GET  /api/pelanggaran.php?action=detail&id=N → detail lengkap
// GET  /api/pelanggaran.php?action=jenis → list jenis pelanggaran
// POST /api/pelanggaran.php action=tambah → catat + kirim WA
// POST /api/pelanggaran.php action=hapus  → hapus record   (admin & guru_bk)
// POST /api/pelanggaran.php action=update → ubah record    (admin & guru_bk)
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/wa_sender.php';

// Fallback jika isValidDate() belum ada di helpers.php
if (!function_exists('isValidDate')) {
    function isValidDate(string $date): bool
    {
        if (empty($date))
            return false;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

// Fallback jika generateKode() belum ada di helpers.php
if (!function_exists('generateKode')) {
    function generateKode(PDO $pdo, string $table, string $col, string $prefix): string
    {
        $stmt = $pdo->query("SELECT MAX(id) FROM `{$table}`");
        $maxId = (int) $stmt->fetchColumn();
        return $prefix . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
    }
}

// Fallback jika generateWAMessage() belum ada di helpers.php
if (!function_exists('generateWAMessage')) {
    function generateWAMessage(array $data): string
    {
        $namaSiswa = $data['nama_siswa'] ?? '-';
        $namaOrtu = $data['nama_ortu'] ?? null;
        $namaJenis = $data['nama_jenis'] ?? '-';
        $tanggal = $data['tanggal'] ?? date('Y-m-d');
        $keterangan = trim($data['keterangan'] ?? '');
        $tindak = $data['tindak_lanjut'] ?? 'sekolah';
        $namaSekolah = $data['nama_sekolah'] ?? 'SMK Negeri 1 Masbagik';

        // ── Format tanggal lengkap ────────────────────────
        $tgl = new DateTime($tanggal);
        $hariMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $bulanMap = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember',
        ];
        $hari = $hariMap[$tgl->format('l')] ?? $tgl->format('l');
        $bulan = $bulanMap[$tgl->format('F')] ?? $tgl->format('F');
        $tglStr = "{$hari}, {$tgl->format('j')} {$bulan} {$tgl->format('Y')}";

        // ── Sapaan — sertakan nama siswa di baris kedua ───
        $sapaan = $namaOrtu
            ? "Yth. Bapak/Ibu *{$namaOrtu}*,\nOrang tua/wali dari *{$namaSiswa}*"
            : "Yth. Orang tua/wali dari *{$namaSiswa}*";

        // ── Tindak lanjut — KONSISTEN dengan preview HTML ─
        $tindakStr = ($tindak === 'hadir')
            ? "📌 *Tindak Lanjut:* Bapak/Ibu dimohon untuk *hadir ke sekolah* guna pertemuan dengan Guru BK untuk penyelesaian lebih lanjut."
            : "📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.";

        // ── Garis pemisah ─────────────────────────────────
        $sep = "───────────────────";

        // ── Susun pesan ───────────────────────────────────
        $tingkat = $data['tingkat'] ?? '';
        $poinVal = $data['poin_val'] ?? 0;
        $tingkatStr = match ($tingkat) {
            'rendah' => 'Rendah (1 poin)',
            'sedang' => 'Sedang (2 poin)',
            'berat' => 'Berat (3 poin)',
            default => ''
        };
        $msg = "🏫 *SIGAP — {$namaSekolah}*\n";
        $msg .= "{$sep}\n\n";
        $msg .= "{$sapaan}\n\n";
        $msg .= "Kami informasikan bahwa putra/putri Bapak/Ibu (*{$namaSiswa}*) ";
        $msg .= "tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n";
        $msg .= "{$sep}\n";
        $msg .= "📋 *Jenis Pelanggaran:* {$namaJenis}\n";
        if ($tingkatStr) {
            $msg .= "⚠️ *Tingkat:* {$tingkatStr}\n";
        }
        $msg .= "📅 *Tanggal:* {$tglStr}\n";
        if ($keterangan !== '') {
            $msg .= "📝 *Keterangan:* {$keterangan}\n";
        }
        $msg .= "{$sep}\n\n";
        $msg .= "{$tindakStr}\n\n";
        $msg .= "Mohon kerjasamanya dalam membimbing putra/putri Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\n";
        $msg .= "Apabila ada pertanyaan, silakan hubungi Guru BK kami.\n\n";
        $msg .= "Hormat kami,\n";
        $msg .= "*Guru BK SIGAP*\n";
        $msg .= "*{$namaSekolah}*";

        return $msg;
    }
}

// ── Pesan WA (versi dengan identitas siswa: kelas, jurusan, gender) ──
// Fungsi ini sengaja TIDAK dibungkus function_exists agar tidak tertimpa
// generateWAMessage() lama di includes/helpers.php.
function buildWAMessage(array $data): string
{
    $namaSiswa = $data['nama_siswa'] ?? '-';
    $namaOrtu = $data['nama_ortu'] ?? null;
    $kelas = trim((string) ($data['kelas'] ?? ''));
    $jurusan = trim((string) ($data['jurusan'] ?? ''));
    $gender = $data['gender'] ?? '';
    $namaJenis = $data['nama_jenis'] ?? '-';
    $tanggal = $data['tanggal'] ?? date('Y-m-d');
    $keterangan = trim($data['keterangan'] ?? '');
    $tindak = $data['tindak_lanjut'] ?? 'sekolah';
    $namaSekolah = $data['nama_sekolah'] ?? 'SMK Negeri 1 Masbagik';

    $genderStr = match ($gender) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => '',
    };
    $sebutan = match ($gender) {
        'L' => 'putra',
        'P' => 'putri',
        default => 'putra/putri',
    };

    // ── Tanggal lengkap ──────────────────────────────────
    $tgl = new DateTime($tanggal);
    $hariMap = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $bulanMap = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    $hari = $hariMap[$tgl->format('l')] ?? $tgl->format('l');
    $bulan = $bulanMap[$tgl->format('F')] ?? $tgl->format('F');
    $tglStr = "{$hari}, {$tgl->format('j')} {$bulan} {$tgl->format('Y')}";

    $sapaan = $namaOrtu
        ? "Yth. Bapak/Ibu *{$namaOrtu}*,\nOrang tua/wali dari *{$namaSiswa}*"
        : "Yth. Orang tua/wali dari *{$namaSiswa}*";

    $tindakStr = ($tindak === 'hadir')
        ? "📌 *Tindak Lanjut:* Bapak/Ibu dimohon untuk *hadir ke sekolah* guna pertemuan dengan Guru BK untuk penyelesaian lebih lanjut."
        : "📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.";

    $tingkatStr = match ($data['tingkat'] ?? '') {
        'rendah' => 'Rendah (1 poin)',
        'sedang' => 'Sedang (2 poin)',
        'berat' => 'Berat (3 poin)',
        default => '',
    };

    $sep = "───────────────────";

    $msg = "🏫 *SIGAP — {$namaSekolah}*\n";
    $msg .= "{$sep}\n\n";
    $msg .= "{$sapaan}\n\n";
    $msg .= "Kami informasikan bahwa {$sebutan} Bapak/Ibu tercatat melakukan ";
    $msg .= "*pelanggaran tata tertib sekolah*:\n\n";
    $msg .= "{$sep}\n";
    // Identitas siswa
    $msg .= "👤 *Nama:* {$namaSiswa}\n";
    if ($kelas !== '')
        $msg .= "🏫 *Kelas:* {$kelas}\n";
    if ($jurusan !== '')
        $msg .= "🎓 *Jurusan:* {$jurusan}\n";
    if ($genderStr !== '')
        $msg .= "🚻 *Jenis Kelamin:* {$genderStr}\n";
    $msg .= "{$sep}\n";
    // Detail pelanggaran
    $msg .= "📋 *Jenis Pelanggaran:* {$namaJenis}\n";
    if ($tingkatStr !== '')
        $msg .= "⚠️ *Tingkat:* {$tingkatStr}\n";
    $msg .= "📅 *Tanggal:* {$tglStr}\n";
    if ($keterangan !== '')
        $msg .= "📝 *Keterangan:* {$keterangan}\n";
    $msg .= "{$sep}\n\n";
    $msg .= "{$tindakStr}\n\n";
    $msg .= "Mohon kerjasamanya dalam membimbing {$sebutan} Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\n";
    $msg .= "Apabila ada pertanyaan, silakan hubungi Guru BK kami.\n\n";
    $msg .= "Hormat kami,\n";
    $msg .= "*Guru BK SIGAP*\n";
    $msg .= "*{$namaSekolah}*";

    return $msg;
}

setCorsHeaders();
$currentUser = requireLogin();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');
    match ($action) {
        'detail' => getPelanggaranDetail(),
        'jenis' => getJenisPelanggaran(),
        default => getPelanggaranList(),
    };
} elseif ($method === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    match ($action) {
        'tambah' => tambahPelanggaran(),
        'hapus' => hapusPelanggaran(),
        'update' => updatePelanggaran(),
        default => jsonError("Action '{$action}' tidak dikenali.", 400),
    };
} else {
    jsonError('Method tidak diizinkan.', 405);
}

// ── GET: List Pelanggaran ─────────────────────────────────
function getPelanggaranList(): never
{
    global $pdo;

    $q = cleanInput($_GET['q'] ?? '');
    $kelas = cleanInput($_GET['kelas'] ?? '');
    $jenisId = cleanInt($_GET['jenis_id'] ?? 0);
    $siswaIdFilter = cleanInt($_GET['siswa_id'] ?? 0);
    $statusWA = cleanInput($_GET['status_wa'] ?? '');
    $dari = cleanInput($_GET['dari'] ?? '');
    $sampai = cleanInput($_GET['sampai'] ?? '');
    $page = max(1, cleanInt($_GET['page'] ?? 1));
    $perPage = max(1, cleanInt($_GET['per_page'] ?? DEFAULT_PER_PAGE));

    $where = ['1=1'];
    $params = [];

    if ($q !== '') {
        $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR s.kelas LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($kelas !== '') {
        $where[] = "(s.kelas = ? OR s.kelas LIKE CONCAT(?, ' %'))";
        $params[] = $kelas;
        $params[] = $kelas;
    }

    if ($jenisId > 0) {
        $where[] = 'p.jenis_pelanggaran_id = ?';
        $params[] = $jenisId;
    }

    if ($siswaIdFilter > 0) {
        $where[] = 'p.siswa_id = ?';
        $params[] = $siswaIdFilter;
    }

    if (in_array($statusWA, ['Pending', 'Terkirim', 'Gagal'], true)) {
        $where[] = 'p.status_wa = ?';
        $params[] = $statusWA;
    }

    if ($dari !== '' && isValidDate($dari)) {
        $where[] = 'p.tanggal >= ?';
        $params[] = $dari;
    }

    if ($sampai !== '' && isValidDate($sampai)) {
        $where[] = 'p.tanggal <= ?';
        $params[] = $sampai;
    }

    $whereSql = implode(' AND ', $where);

    $baseFrom = "FROM pelanggaran p
                 INNER JOIN siswa             s ON p.siswa_id             = s.id
                 INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
                 LEFT  JOIN users             u ON p.dicatat_oleh         = u.id
                 LEFT  JOIN jurusan           jr1 ON jr1.id = s.jurusan_id
                 LEFT  JOIN jurusan           jr2 ON s.jurusan_id IS NULL
                                                 AND jr2.kode COLLATE utf8mb4_unicode_ci
                                                   = SUBSTRING_INDEX(SUBSTRING_INDEX(s.kelas, ' ', 2), ' ', -1)
                 WHERE {$whereSql}";

    // Total
    $countStmt = $pdo->prepare("SELECT COUNT(*) {$baseFrom}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Data
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare(
        "SELECT
            p.id, p.kode_pelanggaran, p.tanggal,
            p.keterangan, j.poin AS poin, p.status_wa, p.status_penanganan, p.wa_tujuan,
            p.waktu_kirim_wa, p.created_at,
            s.id         AS siswa_id,
            s.kode_siswa, s.nama AS nama_siswa,
            s.nis, s.kelas, s.gender, s.wa_ortu, s.nama_ortu,
            COALESCE(jr1.kode, jr2.kode) AS jurusan_kode,
            COALESCE(jr1.nama, jr2.nama) AS jurusan_nama,
            j.id         AS jenis_id,
            j.kode       AS kode_jenis,
            j.nama       AS nama_jenis,
            j.tingkat, j.poin AS poin_jenis,
            j.kategori, j.icon, j.color AS jenis_color,
            u.nama       AS dicatat_oleh_nama
         {$baseFrom}
         ORDER BY p.tanggal DESC, p.id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([...$params, $perPage, $offset]);
    $data = $stmt->fetchAll();

    foreach ($data as &$row) {
        $row['id'] = (int) $row['id'];
        $row['siswa_id'] = (int) $row['siswa_id'];
        $row['jenis_id'] = (int) $row['jenis_id'];
    }
    unset($row);

    jsonPaginated($data, $total, $page, $perPage, 'Data pelanggaran berhasil dimuat.');
}

// ── GET: Detail Pelanggaran ───────────────────────────────
function getPelanggaranDetail(): never
{
    global $pdo;

    $id = cleanInt($_GET['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID pelanggaran tidak valid.', 422);

    $stmt = $pdo->prepare(
        'SELECT
            p.*,
            s.kode_siswa, s.nama AS nama_siswa, s.nis,
            s.kelas, s.gender, s.wa_ortu, s.nama_ortu, s.alamat,
            COALESCE(jr1.kode, jr2.kode) AS jurusan_kode,
            COALESCE(jr1.nama, jr2.nama) AS jurusan_nama,
            j.kode AS kode_jenis, j.nama AS nama_jenis,
            j.kategori, j.icon, j.color AS jenis_color,
            u.nama AS dicatat_oleh_nama, u.role AS dicatat_oleh_role
         FROM pelanggaran p
         INNER JOIN siswa             s ON p.siswa_id             = s.id
         INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         LEFT  JOIN users             u ON p.dicatat_oleh         = u.id
         LEFT  JOIN jurusan           jr1 ON jr1.id = s.jurusan_id
         LEFT  JOIN jurusan           jr2 ON s.jurusan_id IS NULL
                                         AND jr2.kode COLLATE utf8mb4_unicode_ci
                                           = SUBSTRING_INDEX(SUBSTRING_INDEX(s.kelas, \' \', 2), \' \', -1)
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data)
        jsonError('Data pelanggaran tidak ditemukan.', 404);

    $data['id'] = (int) $data['id'];

    // Parse wa_response JSON
    if (!empty($data['wa_response'])) {
        $decoded = json_decode($data['wa_response'], true);
        $data['wa_response_parsed'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }

    jsonSuccess($data, 'Detail pelanggaran berhasil dimuat.');
}

// ── GET: Jenis Pelanggaran ────────────────────────────────
function getJenisPelanggaran(): never
{
    global $pdo;

    $stmt = $pdo->query(
        'SELECT id, kode, nama, tingkat, poin, kategori, icon, color
     FROM jenis_pelanggaran
     WHERE is_active = 1
     ORDER BY FIELD(tingkat, "rendah", "sedang", "berat"), nama'
    );

    $data = $stmt->fetchAll();

    foreach ($data as &$row) {
        $row['id'] = (int) $row['id'];
        $row['poin'] = (int) $row['poin'];
    }
    unset($row);

    jsonSuccess($data, 'Jenis pelanggaran berhasil dimuat.');
}

// ── POST: Tambah Pelanggaran ──────────────────────────────
function tambahPelanggaran(): never
{
    global $pdo, $currentUser;

    // Validasi input
    $siswaId = cleanInt($_POST['siswa_id'] ?? 0);
    $jenisId = cleanInt($_POST['jenis_pelanggaran_id'] ?? 0);
    $tanggal = cleanInput($_POST['tanggal'] ?? '');
    $keterangan = cleanInput($_POST['keterangan'] ?? '');
    $kirimWA = filter_var($_POST['kirim_wa'] ?? true, FILTER_VALIDATE_BOOLEAN);

    $errors = [];
    if ($siswaId <= 0)
        $errors['siswa_id'] = 'Siswa wajib dipilih.';
    if ($jenisId <= 0)
        $errors['jenis_pelanggaran_id'] = 'Jenis pelanggaran wajib dipilih.';
    if (!isValidDate($tanggal))
        $errors['tanggal'] = 'Tanggal tidak valid (format: YYYY-MM-DD).';

    if (!empty($errors)) {
        jsonError('Data tidak valid.', 422, $errors);
    }

    // Ambil data siswa
    $stmtSiswa = $pdo->prepare(
        "SELECT s.id, s.nama, s.wa_ortu, s.nama_ortu, s.kelas, s.gender,
                COALESCE(jr1.nama, jr2.nama) AS jurusan_nama
         FROM siswa s
         LEFT JOIN jurusan jr1 ON jr1.id = s.jurusan_id
         LEFT JOIN jurusan jr2 ON s.jurusan_id IS NULL
                              AND jr2.kode COLLATE utf8mb4_unicode_ci
                                = SUBSTRING_INDEX(SUBSTRING_INDEX(s.kelas, ' ', 2), ' ', -1)
         WHERE s.id = ? AND s.is_active = 1"
    );
    $stmtSiswa->execute([$siswaId]);
    $siswa = $stmtSiswa->fetch();
    if (!$siswa)
        jsonError('Siswa tidak ditemukan atau tidak aktif.', 404);

    // Ambil data jenis pelanggaran (termasuk poin dan tingkat)
    $stmtJenis = $pdo->prepare(
        'SELECT id, nama, tingkat, poin FROM jenis_pelanggaran WHERE id = ? AND is_active = 1'
    );
    $stmtJenis->execute([$jenisId]);
    $jenis = $stmtJenis->fetch();
    if (!$jenis)
        jsonError('Jenis pelanggaran tidak ditemukan.', 404);

    $tindakLanjut = in_array($_POST['tindak_lanjut'] ?? '', ['sekolah', 'hadir'], true)
        ? $_POST['tindak_lanjut'] : 'sekolah';

    // Generate kode & pesan WA
    $kodePelanggaran = generateKode($pdo, 'pelanggaran', 'kode_pelanggaran', 'P');
    $waTujuan = $siswa['wa_ortu'] ?? '';
    // Ambil nama sekolah dari config jika tersedia
    $namaSekolah = defined('SCHOOL_NAME') ? SCHOOL_NAME : 'SMK Negeri 1 Masbagik';

    $waPesan = buildWAMessage([
        'nama_siswa' => $siswa['nama'],
        'nama_ortu' => $siswa['nama_ortu'] ?? null,
        'kelas' => $siswa['kelas'] ?? '',
        'jurusan' => $siswa['jurusan_nama'] ?? '',
        'gender' => $siswa['gender'] ?? '',
        'nama_jenis' => $jenis['nama'],
        'tingkat' => $jenis['tingkat'],
        'poin_val' => (int) $jenis['poin'],
        'tanggal' => $tanggal,
        'keterangan' => $keterangan,
        'tindak_lanjut' => $tindakLanjut,
        'nama_sekolah' => $namaSekolah,
    ]);

    // Insert pelanggaran (status awal Pending)
    $stmtInsert = $pdo->prepare(
        'INSERT INTO pelanggaran
            (kode_pelanggaran, siswa_id, jenis_pelanggaran_id, tanggal,
             keterangan, poin, dicatat_oleh, status_wa, wa_tujuan, wa_pesan)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmtInsert->execute([
        $kodePelanggaran,
        $siswaId,
        $jenisId,
        $tanggal,
        $keterangan,
        (int) $jenis['poin'],
        $currentUser['id'],
        'Pending',
        $waTujuan,
        $waPesan,
    ]);

    $newId = (int) $pdo->lastInsertId();
    $waResult = null;
    $statusWA = 'Pending';
    $waError = null;

    // Kirim WA jika diminta
    if ($kirimWA && !empty($waTujuan)) {
        $sender = new WASender();
        $waResult = $sender->send($waTujuan, $waPesan);

        if ($waResult['success']) {
            $statusWA = 'Terkirim';
            $waktuKirimWA = date('Y-m-d H:i:s');
            $waResponse = json_encode($waResult['response']);

            $stmtUpdate = $pdo->prepare(
                'UPDATE pelanggaran
                 SET status_wa = ?, waktu_kirim_wa = ?, wa_response = ?
                 WHERE id = ?'
            );
            $stmtUpdate->execute([$statusWA, $waktuKirimWA, $waResponse, $newId]);
        } else {
            $statusWA = 'Gagal';
            $waError = $waResult['error'];
            $waResponse = json_encode([
                'error' => $waResult['error'],
                'response' => $waResult['response'],
            ]);

            $stmtUpdate = $pdo->prepare(
                'UPDATE pelanggaran
                 SET status_wa = ?, wa_response = ?
                 WHERE id = ?'
            );
            $stmtUpdate->execute([$statusWA, $waResponse, $newId]);
        }
    } elseif ($kirimWA && empty($waTujuan)) {
        $statusWA = 'Gagal';
        $waError = 'Nomor WhatsApp orang tua belum diisi pada data siswa.';
        $stmtUpdate = $pdo->prepare('UPDATE pelanggaran SET status_wa = ?, wa_response = ? WHERE id = ?');
        $stmtUpdate->execute([$statusWA, json_encode(['error' => $waError]), $newId]);
    }

    appLog(
        "Pelanggaran ditambahkan: {$kodePelanggaran} untuk {$siswa['nama']}, " .
        "WA: {$statusWA}, oleh {$currentUser['username']}",
        'INFO'
    );

    // Fetch data lengkap yang baru disimpan
    $stmtFetch = $pdo->prepare(
        'SELECT
            p.*,
            s.nama AS nama_siswa, s.nis, s.kelas,
            j.nama AS nama_jenis, j.color AS jenis_color, j.tingkat
         FROM pelanggaran p
         INNER JOIN siswa             s ON p.siswa_id             = s.id
         INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE p.id = ?'
    );
    $stmtFetch->execute([$newId]);
    $record = $stmtFetch->fetch();

    // Hitung ulang poin + persentase siswa
    $poinData = recalculateSiswaPoin($pdo, $siswaId);

    $record['id'] = (int) $record['id'];
    $record['wa_sent'] = $kirimWA;
    $record['wa_status'] = $statusWA;
    $record['wa_error'] = $waError;
    $record['wa_result'] = $waResult;
    $record['siswa_total_poin'] = $poinData['total_poin'];
    $record['siswa_jumlah_pel'] = $poinData['jumlah_pelanggaran'];
    $record['siswa_persentase'] = $poinData['persentase'];

    $msg = match ($statusWA) {
        'Terkirim' => "Pelanggaran berhasil dicatat dan notifikasi WA terkirim ke {$waTujuan}.",
        'Gagal' => "Pelanggaran berhasil dicatat, namun notifikasi WA gagal dikirim: " . ($waError ?? 'penyebab tidak diketahui') . " Anda bisa kirim ulang dari daftar pelanggaran.",
        default => "Pelanggaran berhasil dicatat. Notifikasi WA tidak dikirim.",
    };

    jsonSuccess($record, $msg, 201);
}

// ── POST: Hapus Pelanggaran ───────────────────────────────
function hapusPelanggaran(): never
{
    global $pdo, $currentUser;

    requireManageAccess();

    $id = cleanInt($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID pelanggaran tidak valid.', 422);

    $check = $pdo->prepare(
        'SELECT p.id, p.kode_pelanggaran, p.poin AS poin_pelanggaran,
                p.siswa_id, s.nama AS nama_siswa
         FROM pelanggaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.id = ?'
    );
    $check->execute([$id]);
    $record = $check->fetch();

    if (!$record)
        jsonError('Data pelanggaran tidak ditemukan.', 404);

    $stmt = $pdo->prepare('DELETE FROM pelanggaran WHERE id = ?');
    $stmt->execute([$id]);

    // Hitung ulang poin & persentase siswa setelah pelanggaran dihapus
    recalculateSiswaPoin($pdo, $record['siswa_id']);

    appLog(
        "Pelanggaran dihapus: {$record['kode_pelanggaran']} " .
        "({$record['nama_siswa']}) oleh {$currentUser['username']}",
        'INFO'
    );

    jsonSuccess(
        ['id' => $id, 'kode' => $record['kode_pelanggaran']],
        "Data pelanggaran {$record['kode_pelanggaran']} berhasil dihapus."
    );
}


// ── Helper: hanya Admin & Guru BK yang boleh ubah/hapus ───
// Role dibaca dari database (bukan dari data kiriman browser)
function requireManageAccess(): void
{
    global $pdo, $currentUser;

    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$currentUser['id'] ?? 0]);
    $role = $stmt->fetchColumn();

    // Sama dengan aturan di halaman Riwayat: admin & guru_bk boleh ubah/hapus
    if (!in_array($role, ['admin', 'guru_bk'], true)) {
        jsonError('Anda tidak memiliki izin untuk mengubah atau menghapus data pelanggaran.', 403);
    }
}

// ── POST: Update Pelanggaran ──────────────────────────────
function updatePelanggaran(): never
{
    global $pdo, $currentUser;

    requireManageAccess();

    $id = cleanInt($_POST['id'] ?? 0);
    $jenisId = cleanInt($_POST['jenis_pelanggaran_id'] ?? 0);
    $tanggal = cleanInput($_POST['tanggal'] ?? '');
    $keterangan = cleanInput($_POST['keterangan'] ?? '');
    $keterangan = function_exists('mb_substr') ? mb_substr($keterangan, 0, 500) : substr($keterangan, 0, 500);

    // Validasi input
    $errors = [];
    if ($id <= 0)
        $errors['id'] = 'ID pelanggaran tidak valid.';
    if ($jenisId <= 0)
        $errors['jenis_pelanggaran_id'] = 'Jenis pelanggaran wajib dipilih.';
    if (!isValidDate($tanggal))
        $errors['tanggal'] = 'Tanggal tidak valid (format: YYYY-MM-DD).';
    elseif ($tanggal > date('Y-m-d'))
        $errors['tanggal'] = 'Tanggal tidak boleh di masa depan.';

    if (!empty($errors)) {
        jsonError('Data tidak valid.', 422, $errors);
    }

    // Data lama
    $stmtLama = $pdo->prepare(
        'SELECT p.id, p.kode_pelanggaran, p.siswa_id, p.jenis_pelanggaran_id,
                p.poin, s.nama AS nama_siswa
         FROM pelanggaran p
         INNER JOIN siswa s ON p.siswa_id = s.id
         WHERE p.id = ?'
    );
    $stmtLama->execute([$id]);
    $lama = $stmtLama->fetch();

    if (!$lama)
        jsonError('Data pelanggaran tidak ditemukan.', 404);

    // Jenis baru (jika jenis diganti, harus yang masih aktif)
    $jenisBerubah = (int) $lama['jenis_pelanggaran_id'] !== $jenisId;
    $sqlJenis = 'SELECT id, nama, tingkat, poin FROM jenis_pelanggaran WHERE id = ?'
        . ($jenisBerubah ? ' AND is_active = 1' : '');
    $stmtJenis = $pdo->prepare($sqlJenis);
    $stmtJenis->execute([$jenisId]);
    $jenis = $stmtJenis->fetch();

    if (!$jenis)
        jsonError('Jenis pelanggaran tidak ditemukan atau tidak aktif.', 404);

    // Poin SELALU mengikuti jenis pelanggaran (ringan=1, sedang=2, berat=3)
    $poinBaru = (int) $jenis['poin'];

    $stmtUpdate = $pdo->prepare(
        'UPDATE pelanggaran
         SET jenis_pelanggaran_id = ?, tanggal = ?, keterangan = ?, poin = ?
         WHERE id = ?'
    );
    $stmtUpdate->execute([$jenisId, $tanggal, $keterangan, $poinBaru, $id]);

    // Hitung ulang poin & persentase siswa
    $poinData = recalculateSiswaPoin($pdo, (int) $lama['siswa_id']);

    appLog(
        "Pelanggaran diubah: {$lama['kode_pelanggaran']} ({$lama['nama_siswa']}) " .
        "jenis {$lama['jenis_pelanggaran_id']}→{$jenisId}, oleh {$currentUser['username']}",
        'INFO'
    );

    jsonSuccess(
        [
            'id' => $id,
            'kode' => $lama['kode_pelanggaran'],
            'jenis_pelanggaran_id' => $jenisId,
            'nama_jenis' => $jenis['nama'],
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'poin' => $poinBaru,
            'siswa_total_poin' => $poinData['total_poin'] ?? null,
            'siswa_persentase' => $poinData['persentase'] ?? null,
        ],
        "Data pelanggaran {$lama['kode_pelanggaran']} berhasil diperbarui."
    );
}