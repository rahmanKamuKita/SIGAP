<?php
// ============================================================
// SIGAP — api/siswa.php
// CRUD Data Siswa
//
// GET  /api/siswa.php              → list (paginated, searchable)
// GET  /api/siswa.php?action=detail&id=N  → detail siswa + riwayat
// GET  /api/siswa.php?action=jurusan      → daftar jurusan aktif
// POST /api/siswa.php action=tambah       → tambah siswa baru
// POST /api/siswa.php action=edit         → edit data siswa
// POST /api/siswa.php action=hapus        → hapus siswa
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/response.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
if (file_exists(dirname(__DIR__) . '/includes/helpers.php')) {
    require_once dirname(__DIR__) . '/includes/helpers.php';
}

// Fallback bila generateKode() tidak ada di helpers.php: S001, S002, ... (berdasarkan nomor kode terbesar)
if (!function_exists('generateKode')) {
    function generateKode(PDO $pdo, string $table, string $col, string $prefix): string
    {
        $len = strlen($prefix);
        $stmt = $pdo->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(`{$col}`, " . ($len + 1) . ") AS UNSIGNED)), 0) FROM `{$table}`"
        );
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }
}

setCorsHeaders();
$currentUser = requireLogin();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');
    match ($action) {
        'detail' => getSiswaDetail(),
        'jurusan' => getJurusanList(),
        default => getSiswaList(),
    };
} elseif ($method === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    match ($action) {
        'tambah' => tambahSiswa(),
        'edit' => editSiswa(),
        'hapus' => hapusSiswa(),
        default => jsonError("Action '{$action}' tidak dikenali.", 400),
    };
} else {
    jsonError('Method tidak diizinkan.', 405);
}

// ── GET: Daftar Jurusan aktif ─────────────────────────────
function getJurusanList(): never
{
    global $pdo;

    $rows = $pdo->query(
        'SELECT kode, nama FROM jurusan WHERE is_active = 1 ORDER BY kode'
    )->fetchAll();

    jsonSuccess($rows, 'Daftar jurusan berhasil dimuat.');
}

// ── GET: List Siswa ───────────────────────────────────────
function getSiswaList(): never
{
    global $pdo;

    $q = cleanInput($_GET['q'] ?? '');
    $kelas = cleanInput($_GET['kelas'] ?? '');
    $gender = cleanInput($_GET['gender'] ?? '');
    $jurusan = cleanInput($_GET['jurusan'] ?? '');
    $page = max(1, cleanInt($_GET['page'] ?? 1));
    $perPage = max(1, cleanInt($_GET['per_page'] ?? DEFAULT_PER_PAGE));

    // Build WHERE clause
    $where = ['s.is_active = 1'];
    $params = [];

    if ($q !== '') {
        $where[] = '(s.nama LIKE ? OR s.nis LIKE ? OR s.kelas LIKE ? OR COALESCE(jr1.kode, jr2.kode) LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Kelas = tingkat (X/XI/XII). Data lama berformat "X TKJ 1" tetap ikut terfilter.
    if ($kelas !== '') {
        $where[] = "(s.kelas = ? OR s.kelas LIKE CONCAT(?, ' %'))";
        $params[] = $kelas;
        $params[] = $kelas;
    }

    // Jurusan: dari kolom siswa.jurusan_id (fallback data lama: kata kedua nama kelas)
    if ($jurusan !== '') {
        $where[] = 'COALESCE(jr1.kode, jr2.kode) = ?';
        $params[] = $jurusan;
    }

    if (in_array($gender, ['L', 'P'], true)) {
        $where[] = 's.gender = ?';
        $params[] = $gender;
    }

    $whereSql = implode(' AND ', $where);

    $jurusanJoin = jurusanJoinSql();

    // Hitung total
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM siswa s {$jurusanJoin} WHERE {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Ambil data dengan hitung poin & persentase dari jenis pelanggaran
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare(
        "SELECT
            s.id, s.kode_siswa, s.nama, s.nis, s.kelas,
            s.gender, s.nama_ortu, s.wa_ortu, s.alamat, s.is_active,
            s.created_at,
            MAX(COALESCE(jr1.kode, jr2.kode))                                AS jurusan_kode,
            MAX(COALESCE(jr1.nama, jr2.nama))                                AS jurusan_nama,
            COUNT(p.id)                                                      AS total_pelanggaran,
            COUNT(p.id)                                                      AS jumlah_pelanggaran,
            COALESCE(SUM(j.poin), 0)                                         AS total_poin,
            COALESCE(SUM(j.poin), 0)                                         AS poin,
            CASE WHEN COUNT(p.id) = 0 THEN 0
                 ELSE ROUND((COALESCE(SUM(j.poin),0) / (COUNT(p.id)*3))*100, 2)
            END                                                              AS persentase
         FROM siswa s
         {$jurusanJoin}
         LEFT JOIN pelanggaran       p ON p.siswa_id             = s.id
         LEFT JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE {$whereSql}
         GROUP BY s.id
         ORDER BY s.nama ASC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([...$params, $perPage, $offset]);
    $siswa = $stmt->fetchAll();

    // Konversi tipe data
    foreach ($siswa as &$row) {
        $row['id'] = (int) $row['id'];
        $row['total_pelanggaran'] = (int) $row['total_pelanggaran'];
        $row['jumlah_pelanggaran'] = (int) $row['jumlah_pelanggaran'];
        $row['total_poin'] = (int) $row['total_poin'];
        $row['poin'] = (int) $row['poin'];
        $row['persentase'] = (float) $row['persentase'];
        $row['is_active'] = (bool) $row['is_active'];
    }
    unset($row);

    jsonPaginated($siswa, $total, $page, $perPage, 'Data siswa berhasil dimuat.');
}

// ── GET: Detail Siswa ─────────────────────────────────────
function getSiswaDetail(): never
{
    global $pdo;

    $id = cleanInt($_GET['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID siswa tidak valid.', 422);

    // Data siswa lengkap dengan poin & persentase
    $stmt = $pdo->prepare(
        'SELECT
            s.*,
            MAX(COALESCE(jr1.kode, jr2.kode))                                AS jurusan_kode,
            MAX(COALESCE(jr1.nama, jr2.nama))                                AS jurusan_nama,
            COUNT(p.id)                                                      AS total_pelanggaran,
            COALESCE(SUM(j.poin), 0)                                         AS total_poin,
            CASE WHEN COUNT(p.id) = 0 THEN 0
                 ELSE ROUND((COALESCE(SUM(j.poin),0) / (COUNT(p.id)*3))*100, 2)
            END                                                              AS persentase
         FROM siswa s
         ' . jurusanJoinSql() . '
         LEFT JOIN pelanggaran       p ON p.siswa_id             = s.id
         LEFT JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE s.id = ? AND s.is_active = 1
         GROUP BY s.id'
    );
    $stmt->execute([$id]);
    $siswa = $stmt->fetch();

    if (!$siswa)
        jsonError('Siswa tidak ditemukan.', 404);

    // Riwayat pelanggaran siswa (max 20 terbaru) — sertakan tingkat & poin jenis
    $stmtP = $pdo->prepare(
        'SELECT
            p.id, p.kode_pelanggaran, p.tanggal,
            p.keterangan, p.status_wa, p.waktu_kirim_wa,
            j.nama AS nama_jenis, j.color AS jenis_color,
            j.tingkat, j.poin AS poin
         FROM pelanggaran p
         INNER JOIN jenis_pelanggaran j ON p.jenis_pelanggaran_id = j.id
         WHERE p.siswa_id = ?
         ORDER BY p.tanggal DESC
         LIMIT 20'
    );
    $stmtP->execute([$id]);
    $riwayat = $stmtP->fetchAll();

    $siswa['id'] = (int) $siswa['id'];
    $siswa['total_pelanggaran'] = (int) $siswa['total_pelanggaran'];
    $siswa['total_poin'] = (int) $siswa['total_poin'];
    // Timpa kolom cache siswa.poin / jumlah_pelanggaran dengan hasil hitung langsung
    $siswa['poin'] = $siswa['total_poin'];
    $siswa['jumlah_pelanggaran'] = $siswa['total_pelanggaran'];
    $siswa['persentase'] = (float) $siswa['persentase'];
    $siswa['is_active'] = (bool) $siswa['is_active'];
    $siswa['riwayat_pelanggaran'] = $riwayat;

    jsonSuccess($siswa, 'Detail siswa berhasil dimuat.');
}

// ── POST: Tambah Siswa ────────────────────────────────────
function tambahSiswa(): never
{
    global $pdo, $currentUser;

    $data = collectSiswaInput();
    $errors = validateSiswaInput($data);

    // Cek NIS duplikat
    $stmt = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? LIMIT 1');
    $stmt->execute([$data['nis']]);
    if ($stmt->fetch()) {
        $errors['nis'] = 'NIS sudah digunakan oleh siswa lain.';
    }

    if (!empty($errors)) {
        jsonError('Data tidak valid. Periksa kembali form.', 422, $errors);
    }

    // Generate kode siswa
    $kodeSiswa = generateKode($pdo, 'siswa', 'kode_siswa', 'S');

    // Format WA
    $wa = formatWaNumber($data['wa_ortu']);

    $jurusanId = $data['jurusan'] !== '' ? findJurusanId($pdo, $data['jurusan']) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO siswa (kode_siswa, nama, nis, kelas, jurusan_id, gender, nama_ortu, wa_ortu, alamat)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    try {
        $stmt->execute([
            $kodeSiswa,
            $data['nama'],
            $data['nis'],
            $data['kelas'],
            $jurusanId,
            $data['gender'],
            $data['nama_ortu'],
            $wa,
            $data['alamat'],
        ]);
    } catch (PDOException $e) {
        appLog('Gagal INSERT siswa: ' . $e->getMessage(), 'ERROR');
        $msg = $e->getMessage();
        if (str_contains($msg, 'jurusan_id')) {
            jsonError('Kolom jurusan_id belum ada di tabel siswa. Jalankan migrasi_jurusan.sql di database terlebih dahulu.', 500);
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            jsonError('Data tidak valid. Periksa kembali form.', 422, ['nis' => 'NIS atau kode siswa sudah digunakan.']);
        }
        jsonError('Gagal menyimpan ke database: ' . $msg, 500);
    }

    $newId = (int) $pdo->lastInsertId();
    appLog("Siswa ditambahkan: {$data['nama']} (ID: {$newId}) oleh {$currentUser['username']}", 'INFO');

    // Kembalikan data yang baru dibuat
    $newSiswa = fetchSiswaWithJurusan($pdo, $newId);

    jsonSuccess($newSiswa, "Siswa {$data['nama']} berhasil ditambahkan.", 201);
}

// ── POST: Edit Siswa ──────────────────────────────────────
function editSiswa(): never
{
    global $pdo, $currentUser;

    $id = cleanInt($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID siswa tidak valid.', 422);

    // Pastikan siswa ada
    $check = $pdo->prepare('SELECT id, nama FROM siswa WHERE id = ? AND is_active = 1');
    $check->execute([$id]);
    $existing = $check->fetch();
    if (!$existing)
        jsonError('Siswa tidak ditemukan.', 404);

    $data = collectSiswaInput();
    $errors = validateSiswaInput($data);

    // Cek duplikat NIS (kecuali record ini sendiri)
    $stmt = $pdo->prepare('SELECT id FROM siswa WHERE nis = ? AND id != ? LIMIT 1');
    $stmt->execute([$data['nis'], $id]);
    if ($stmt->fetch()) {
        $errors['nis'] = 'NIS sudah digunakan oleh siswa lain.';
    }

    if (!empty($errors)) {
        jsonError('Data tidak valid. Periksa kembali form.', 422, $errors);
    }

    $wa = formatWaNumber($data['wa_ortu']);

    // Jurusan hanya diubah jika dikirim dari form; kalau kosong, nilai lama dipertahankan
    $jurusanSql = '';
    $jurusanParams = [];
    if ($data['jurusan'] !== '') {
        $jurusanSql = ', jurusan_id = ?';
        $jurusanParams[] = findJurusanId($pdo, $data['jurusan']);
    }

    $stmt = $pdo->prepare(
        "UPDATE siswa
         SET nama = ?, nis = ?, kelas = ?, gender = ?,
             nama_ortu = ?, wa_ortu = ?, alamat = ?{$jurusanSql}
         WHERE id = ?"
    );
    $stmt->execute([
        $data['nama'],
        $data['nis'],
        $data['kelas'],
        $data['gender'],
        $data['nama_ortu'],
        $wa,
        $data['alamat'],
        ...$jurusanParams,
        $id,
    ]);

    appLog("Siswa diupdate: ID {$id} ({$existing['nama']}) oleh {$currentUser['username']}", 'INFO');

    $updated = fetchSiswaWithJurusan($pdo, $id);

    jsonSuccess($updated, "Data siswa {$data['nama']} berhasil diperbarui.");
}

// ── POST: Hapus Siswa ─────────────────────────────────────
function hapusSiswa(): never
{
    global $pdo, $currentUser;

    $id = cleanInt($_POST['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID siswa tidak valid.', 422);

    $check = $pdo->prepare('SELECT id, nama FROM siswa WHERE id = ? AND is_active = 1');
    $check->execute([$id]);
    $siswa = $check->fetch();
    if (!$siswa)
        jsonError('Siswa tidak ditemukan.', 404);

    // Cek apakah ada riwayat pelanggaran
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = ?');
    $countStmt->execute([$id]);
    $hasPelanggaran = (int) $countStmt->fetchColumn() > 0;

    if ($hasPelanggaran) {
        // Soft delete — siswa punya riwayat, tidak boleh dihapus fisik
        $stmt = $pdo->prepare('UPDATE siswa SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
        $msg = "Data siswa {$siswa['nama']} dinonaktifkan (memiliki riwayat pelanggaran).";
        appLog("Siswa dinonaktifkan: ID {$id} ({$siswa['nama']}) oleh {$currentUser['username']}", 'INFO');
    } else {
        // Hard delete — tidak ada riwayat
        $stmt = $pdo->prepare('DELETE FROM siswa WHERE id = ?');
        $stmt->execute([$id]);
        $msg = "Data siswa {$siswa['nama']} berhasil dihapus.";
        appLog("Siswa dihapus: ID {$id} ({$siswa['nama']}) oleh {$currentUser['username']}", 'INFO');
    }

    jsonSuccess(['id' => $id, 'soft_delete' => $hasPelanggaran], $msg);
}

// ── Helper: Kumpulkan input siswa ─────────────────────────
function collectSiswaInput(): array
{
    // Bersihkan NIS: hanya digit
    $nisRaw = cleanInput($_POST['nis'] ?? '');
    $nis = preg_replace('/[^0-9]/', '', $nisRaw);

    // Bersihkan WA: hanya digit, lalu format ke 62xxx
    $waRaw = cleanInput($_POST['wa_ortu'] ?? '');

    return [
        'nama' => cleanInput($_POST['nama'] ?? ''),
        'nis' => $nis,
        'kelas' => cleanInput($_POST['kelas'] ?? ''),
        'jurusan' => cleanInput($_POST['jurusan'] ?? ''),   // kode jurusan, mis. RPL
        'gender' => cleanInput($_POST['gender'] ?? ''),
        'nama_ortu' => cleanInput($_POST['nama_ortu'] ?? ''),
        'wa_ortu' => $waRaw,
        'alamat' => cleanInput($_POST['alamat'] ?? ''),
    ];
}

// ── Helper: Validasi input siswa ──────────────────────────
function validateSiswaInput(array $data): array
{
    $errors = [];

    if (empty($data['nama'])) {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($data['nama']) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    // Bersihkan NIS dari spasi sebelum validasi
    $nisClean = preg_replace('/[^0-9]/', '', $data['nis'] ?? '');
    if (empty($nisClean)) {
        $errors['nis'] = 'NIS wajib diisi.';
    } elseif (strlen($nisClean) > 20) {
        $errors['nis'] = 'NIS maksimal 20 karakter.';
    }
    // Gunakan NIS yang sudah dibersihkan
    if (empty($errors['nis'])) {
        $data['nis'] = $nisClean;
    }

    if (empty($data['kelas'])) {
        $errors['kelas'] = 'Kelas wajib dipilih.';
    }

    // Jurusan opsional di API, tapi kalau diisi harus kode jurusan aktif
    if (($data['jurusan'] ?? '') !== '' && findJurusanId(getDB(), $data['jurusan']) === null) {
        $errors['jurusan'] = 'Jurusan tidak valid.';
    }

    if (!in_array($data['gender'], ['L', 'P'], true)) {
        $errors['gender'] = 'Jenis kelamin tidak valid.';
    }

    // WA: terima format 08xxx, 628xxx, +628xxx — min 9 digit bersih
    $waClean = preg_replace('/[^0-9]/', '', $data['wa_ortu'] ?? '');
    if (empty($waClean)) {
        $errors['wa_ortu'] = 'Nomor WhatsApp orang tua wajib diisi.';
    } elseif (strlen($waClean) < 9 || strlen($waClean) > 15) {
        $errors['wa_ortu'] = 'Nomor WhatsApp tidak valid (9–15 digit).';
    }

    return $errors;
}

// ── Helper: Format nomor WA ───────────────────────────────
function formatWaNumber(string $wa): string
{
    // Hapus semua non-digit kecuali "+"
    $clean = preg_replace('/[^0-9+]/', '', $wa);
    $clean = ltrim($clean, '+');

    if (str_starts_with($clean, '62'))
        return $clean;
    if (str_starts_with($clean, '0'))
        return '62' . substr($clean, 1);
    if (str_starts_with($clean, '8'))
        return '62' . $clean;

    return $clean;
}

// ── Helper: JOIN jurusan ──────────────────────────────────
// jr1 = jurusan dari kolom siswa.jurusan_id
// jr2 = fallback data lama: kata kedua nama kelas ("X TKJ 1" → "TKJ")
function jurusanJoinSql(): string
{
    return "LEFT JOIN jurusan jr1 ON jr1.id = s.jurusan_id
            LEFT JOIN jurusan jr2 ON s.jurusan_id IS NULL
                                 AND jr2.kode COLLATE utf8mb4_unicode_ci
                                   = SUBSTRING_INDEX(SUBSTRING_INDEX(s.kelas, ' ', 2), ' ', -1)";
}

// ── Helper: cari id jurusan aktif dari kode ───────────────
function findJurusanId(PDO $pdo, string $kode): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM jurusan WHERE kode = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$kode]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

// ── Helper: ambil siswa + jurusan ─────────────────────────
function fetchSiswaWithJurusan(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare(
        'SELECT s.*,
                COALESCE(jr1.kode, jr2.kode) AS jurusan_kode,
                COALESCE(jr1.nama, jr2.nama) AS jurusan_nama
         FROM siswa s
         ' . jurusanJoinSql() . '
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}