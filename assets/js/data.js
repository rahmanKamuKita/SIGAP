/**
 * ============================================================
 * SIGAP — Sistem Informasi Pelanggaran Siswa
 * data.js — Centralized Dummy Data & Helper Utilities
 * ============================================================
 */

'use strict';

/* ============================================================
   KONFIGURASI SISTEM
   ============================================================ */
const SIGAP_CONFIG = {
  namaSekolah:  'SMK Negeri 1 Surakarta',
  namaSistem:   'SIGAP',
  tahunAjaran:  '2025/2026',
  semester:     'Genap',
  guruBK:       'Dra. Sri Wahyuni, M.Pd.',
  nomorWABK:    '08112345678',
};

/* ============================================================
   DATA PENGGUNA (USER / GURU)
   ============================================================ */
const USERS = [
  {
    id:       'U001',
    nama:     'Dra. Sri Wahyuni, M.Pd.',
    username: 'sriwahyuni',
    password: 'sigap123',
    role:     'Guru BK',
    avatar:   'SW',
  },
  {
    id:       'U002',
    nama:     'Bpk. Hendra Kusuma, S.Pd.',
    username: 'admin',
    password: 'admin123',
    role:     'Admin',
    avatar:   'HK',
  },
];

/* ============================================================
   DATA KELAS
   ============================================================ */
const DAFTAR_KELAS = [
  'X TKJ 1', 'X TKJ 2',
  'X MM 1',  'X MM 2',  'X MM 3',
  'X IPA 1', 'X IPA 2',
  'X IPS 1', 'X IPS 2',
  'XI TKJ 1', 'XI TKJ 2',
  'XI MM 1',  'XI MM 2',
  'XI IPA 1', 'XI IPA 2',
  'XI IPS 1', 'XI IPS 2',
  'XII TKJ 1', 'XII TKJ 2',
  'XII MM 1',  'XII MM 2',
  'XII IPA 1', 'XII IPA 2',
  'XII IPS 1',
];

/* ============================================================
   JENIS PELANGGARAN & POIN
   ============================================================ */
const JENIS_PELANGGARAN = [
  {
    id:    'JP01',
    nama:  'Terlambat masuk',
    poin:  5,
    kategori: 'Disiplin',
    icon:  'fa-clock',
    color: 'warning',
  },
  {
    id:    'JP02',
    nama:  'Tidak memakai seragam',
    poin:  10,
    kategori: 'Penampilan',
    icon:  'fa-shirt',
    color: 'warning',
  },
  {
    id:    'JP03',
    nama:  'Bolos pelajaran',
    poin:  25,
    kategori: 'Disiplin',
    icon:  'fa-person-running',
    color: 'danger',
  },
  {
    id:    'JP04',
    nama:  'Tidak mengerjakan tugas',
    poin:  15,
    kategori: 'Akademik',
    icon:  'fa-book-open',
    color: 'warning',
  },
  {
    id:    'JP05',
    nama:  'Membawa HP',
    poin:  20,
    kategori: 'Tata Tertib',
    icon:  'fa-mobile-screen',
    color: 'danger',
  },
  {
    id:    'JP06',
    nama:  'Lainnya',
    poin:  10,
    kategori: 'Umum',
    icon:  'fa-circle-exclamation',
    color: 'info',
  },
];

/**
 * Ambil poin berdasarkan nama jenis pelanggaran
 * @param {string} namaJenis
 * @returns {number}
 */
function getPoinByJenis(namaJenis) {
  const found = JENIS_PELANGGARAN.find(j => j.nama === namaJenis);
  return found ? found.poin : 10;
}

/**
 * Ambil objek jenis pelanggaran berdasarkan nama
 * @param {string} namaJenis
 * @returns {object|null}
 */
function getJenisByNama(namaJenis) {
  return JENIS_PELANGGARAN.find(j => j.nama === namaJenis) || null;
}

/* ============================================================
   DATA SISWA
   ============================================================ */
const SISWA = [
  {
    id:       'S001',
    nama:     'Andi Prasetyo',
    nis:      '2401001',
    kelas:    'XII TKJ 1',
    waOrtu:   '08123456001',
    namaOrtu: 'Bpk. Prasetyo Utomo',
    gender:   'L',
    alamat:   'Jl. Merdeka No. 12, Surakarta',
  },
  {
    id:       'S002',
    nama:     'Siti Rahayu',
    nis:      '2401002',
    kelas:    'XI IPA 2',
    waOrtu:   '08123456002',
    namaOrtu: 'Ibu Rahayu Ningsih',
    gender:   'P',
    alamat:   'Jl. Pahlawan No. 7, Surakarta',
  },
  {
    id:       'S003',
    nama:     'Budi Santoso',
    nis:      '2401003',
    kelas:    'X MM 3',
    waOrtu:   '08123456003',
    namaOrtu: 'Bpk. Santoso Hadi',
    gender:   'L',
    alamat:   'Jl. Slamet Riyadi No. 45, Surakarta',
  },
  {
    id:       'S004',
    nama:     'Dewi Lestari',
    nis:      '2401004',
    kelas:    'XI IPS 1',
    waOrtu:   '08123456004',
    namaOrtu: 'Ibu Lestari Handayani',
    gender:   'P',
    alamat:   'Jl. Gatot Subroto No. 3, Surakarta',
  },
  {
    id:       'S005',
    nama:     'Riko Firmansyah',
    nis:      '2401005',
    kelas:    'XII TKJ 2',
    waOrtu:   '08123456005',
    namaOrtu: 'Bpk. Firmansyah Agus',
    gender:   'L',
    alamat:   'Jl. Diponegoro No. 22, Surakarta',
  },
  {
    id:       'S006',
    nama:     'Anisa Putri Maharani',
    nis:      '2401006',
    kelas:    'XI TKJ 1',
    waOrtu:   '08123456006',
    namaOrtu: 'Ibu Maharani Dewi',
    gender:   'P',
    alamat:   'Jl. Ahmad Yani No. 18, Surakarta',
  },
  {
    id:       'S007',
    nama:     'Fajar Nugroho',
    nis:      '2401007',
    kelas:    'X TKJ 1',
    waOrtu:   '08123456007',
    namaOrtu: 'Bpk. Nugroho Wahyudi',
    gender:   'L',
    alamat:   'Jl. Urip Sumoharjo No. 9, Surakarta',
  },
  {
    id:       'S008',
    nama:     'Nur Halimah',
    nis:      '2401008',
    kelas:    'XII IPA 1',
    waOrtu:   '08123456008',
    namaOrtu: 'Ibu Halimah Susanti',
    gender:   'P',
    alamat:   'Jl. Brigjen Katamso No. 31, Surakarta',
  },
  {
    id:       'S009',
    nama:     'Kevin Ardiansyah',
    nis:      '2401009',
    kelas:    'X MM 1',
    waOrtu:   '08123456009',
    namaOrtu: 'Bpk. Ardiansyah Rudi',
    gender:   'L',
    alamat:   'Jl. Kebangkitan Nasional No. 5, Surakarta',
  },
  {
    id:       'S010',
    nama:     'Rizky Amalia Putri',
    nis:      '2401010',
    kelas:    'XI IPS 1',
    waOrtu:   '08123456010',
    namaOrtu: 'Bpk. Amalia Hasan',
    gender:   'P',
    alamat:   'Jl. Veteran No. 14, Surakarta',
  },
  {
    id:       'S011',
    nama:     'Daffa Rayhan Pratama',
    nis:      '2401011',
    kelas:    'XII TKJ 1',
    waOrtu:   '08123456011',
    namaOrtu: 'Ibu Pratama Sari',
    gender:   'L',
    alamat:   'Jl. Kolonel Sugiono No. 27, Surakarta',
  },
  {
    id:       'S012',
    nama:     'Ayu Sekar Ningrum',
    nis:      '2401012',
    kelas:    'X IPA 1',
    waOrtu:   '08123456012',
    namaOrtu: 'Bpk. Ningrum Basuki',
    gender:   'P',
    alamat:   'Jl. Adi Sucipto No. 8, Surakarta',
  },
];

/* ============================================================
   DATA PELANGGARAN
   ============================================================ */
const PELANGGARAN = [
  {
    id:            'P001',
    siswaId:       'S001',
    namaSiswa:     'Andi Prasetyo',
    kelas:         'XII TKJ 1',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-03-03',
    poin:          5,
    keterangan:    'Terlambat 20 menit tanpa keterangan yang jelas.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456001',
    waktuKirimWA:  '2026-03-03 07:45',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P002',
    siswaId:       'S002',
    namaSiswa:     'Siti Rahayu',
    kelas:         'XI IPA 2',
    jenisPelanggaran: 'Tidak memakai seragam',
    tanggal:       '2026-03-05',
    poin:          10,
    keterangan:    'Tidak memakai dasi seragam lengkap pada hari Senin.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456002',
    waktuKirimWA:  '2026-03-05 08:10',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P003',
    siswaId:       'S003',
    namaSiswa:     'Budi Santoso',
    kelas:         'X MM 3',
    jenisPelanggaran: 'Bolos pelajaran',
    tanggal:       '2026-03-07',
    poin:          25,
    keterangan:    'Tidak hadir pada jam ke-3 dan ke-4 mata pelajaran Matematika tanpa izin.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456003',
    waktuKirimWA:  '2026-03-07 10:30',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P004',
    siswaId:       'S004',
    namaSiswa:     'Dewi Lestari',
    kelas:         'XI IPS 1',
    jenisPelanggaran: 'Tidak mengerjakan tugas',
    tanggal:       '2026-03-10',
    poin:          15,
    keterangan:    'Tidak mengumpulkan tugas Bahasa Indonesia selama 2 minggu berturut-turut.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456004',
    waktuKirimWA:  '2026-03-10 09:15',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P005',
    siswaId:       'S005',
    namaSiswa:     'Riko Firmansyah',
    kelas:         'XII TKJ 2',
    jenisPelanggaran: 'Membawa HP',
    tanggal:       '2026-03-12',
    poin:          20,
    keterangan:    'Kedapatan menggunakan HP saat jam pelajaran Fisika berlangsung.',
    statusWA:      'Gagal',
    waOrtu:        '08123456005',
    waktuKirimWA:  '2026-03-12 11:00',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P006',
    siswaId:       'S006',
    namaSiswa:     'Anisa Putri Maharani',
    kelas:         'XI TKJ 1',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-03-14',
    poin:          5,
    keterangan:    'Terlambat 15 menit, alasan bangun kesiangan.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456006',
    waktuKirimWA:  '2026-03-14 07:30',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P007',
    siswaId:       'S007',
    namaSiswa:     'Fajar Nugroho',
    kelas:         'X TKJ 1',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-03-17',
    poin:          5,
    keterangan:    'Terlambat 10 menit karena macet, sudah membawa surat keterangan.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456007',
    waktuKirimWA:  '2026-03-17 07:20',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P008',
    siswaId:       'S008',
    namaSiswa:     'Nur Halimah',
    kelas:         'XII IPA 1',
    jenisPelanggaran: 'Tidak mengerjakan tugas',
    tanggal:       '2026-03-19',
    poin:          15,
    keterangan:    'Tidak mengumpulkan laporan praktikum Kimia pada batas waktu yang ditentukan.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456008',
    waktuKirimWA:  '2026-03-19 10:00',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P009',
    siswaId:       'S009',
    namaSiswa:     'Kevin Ardiansyah',
    kelas:         'X MM 1',
    jenisPelanggaran: 'Tidak memakai seragam',
    tanggal:       '2026-03-21',
    poin:          10,
    keterangan:    'Memakai sepatu berwarna selain hitam polos saat hari Rabu.',
    statusWA:      'Pending',
    waOrtu:        '08123456009',
    waktuKirimWA:  null,
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P010',
    siswaId:       'S001',
    namaSiswa:     'Andi Prasetyo',
    kelas:         'XII TKJ 1',
    jenisPelanggaran: 'Bolos pelajaran',
    tanggal:       '2026-03-24',
    poin:          25,
    keterangan:    'Tidak hadir pada jam pelajaran PKL tanpa pemberitahuan.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456001',
    waktuKirimWA:  '2026-03-24 09:00',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P011',
    siswaId:       'S010',
    namaSiswa:     'Rizky Amalia Putri',
    kelas:         'XI IPS 1',
    jenisPelanggaran: 'Lainnya',
    tanggal:       '2026-03-26',
    poin:          10,
    keterangan:    'Membuat keributan di kantin saat jam istirahat dan mengganggu siswa lain.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456010',
    waktuKirimWA:  '2026-03-26 12:15',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P012',
    siswaId:       'S011',
    namaSiswa:     'Daffa Rayhan Pratama',
    kelas:         'XII TKJ 1',
    jenisPelanggaran: 'Membawa HP',
    tanggal:       '2026-03-28',
    poin:          20,
    keterangan:    'Menggunakan HP untuk bermain game saat pelajaran Produktif.',
    statusWA:      'Pending',
    waOrtu:        '08123456011',
    waktuKirimWA:  null,
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P013',
    siswaId:       'S003',
    namaSiswa:     'Budi Santoso',
    kelas:         'X MM 3',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-04-01',
    poin:          5,
    keterangan:    'Terlambat 30 menit, kali kedua dalam bulan ini.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456003',
    waktuKirimWA:  '2026-04-01 07:50',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P014',
    siswaId:       'S012',
    namaSiswa:     'Ayu Sekar Ningrum',
    kelas:         'X IPA 1',
    jenisPelanggaran: 'Tidak mengerjakan tugas',
    tanggal:       '2026-04-03',
    poin:          15,
    keterangan:    'Tidak mengumpulkan PR Matematika sebanyak 3 kali berturut-turut.',
    statusWA:      'Pending',
    waOrtu:        '08123456012',
    waktuKirimWA:  null,
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P015',
    siswaId:       'S005',
    namaSiswa:     'Riko Firmansyah',
    kelas:         'XII TKJ 2',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-04-07',
    poin:          5,
    keterangan:    'Terlambat 25 menit tanpa surat keterangan.',
    statusWA:      'Gagal',
    waOrtu:        '08123456005',
    waktuKirimWA:  '2026-04-07 07:55',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P016',
    siswaId:       'S002',
    namaSiswa:     'Siti Rahayu',
    kelas:         'XI IPA 2',
    jenisPelanggaran: 'Bolos pelajaran',
    tanggal:       '2026-04-08',
    poin:          25,
    keterangan:    'Tidak masuk pelajaran Biologi jam ke-5 dan ke-6 tanpa keterangan.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456002',
    waktuKirimWA:  '2026-04-08 13:00',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
  {
    id:            'P017',
    siswaId:       'S004',
    namaSiswa:     'Dewi Lestari',
    kelas:         'XI IPS 1',
    jenisPelanggaran: 'Terlambat masuk',
    tanggal:       '2026-04-09',
    poin:          5,
    keterangan:    'Terlambat masuk setelah jam istirahat, 20 menit.',
    statusWA:      'Terkirim',
    waOrtu:        '08123456004',
    waktuKirimWA:  '2026-04-09 10:05',
    dicatatOleh:   'Dra. Sri Wahyuni, M.Pd.',
  },
];

/* ============================================================
   STATISTIK AGREGAT (untuk Dashboard & Laporan)
   ============================================================ */

/**
 * Hitung total siswa
 * @returns {number}
 */
function getTotalSiswa() {
  return 247; // total siswa seluruh sekolah (SISWA array adalah sampel)
}

/**
 * Hitung pelanggaran bulan ini (April 2026)
 * @returns {number}
 */
function getPelanggaranBulanIni() {
  const now = new Date();
  const bulan = now.getMonth(); // 0-indexed, tapi kita pakai hardcode untuk demo
  // Hardcode untuk demo: April 2026 = bulan ke-4
  return PELANGGARAN.filter(p => p.tanggal.startsWith('2026-04')).length;
}

/**
 * Hitung status WA
 * @returns {{ terkirim: number, pending: number, gagal: number }}
 */
function getStatusWASummary() {
  const terkirim = PELANGGARAN.filter(p => p.statusWA === 'Terkirim').length;
  const pending  = PELANGGARAN.filter(p => p.statusWA === 'Pending').length;
  const gagal    = PELANGGARAN.filter(p => p.statusWA === 'Gagal').length;
  return { terkirim, pending, gagal };
}

/**
 * Hitung frekuensi tiap jenis pelanggaran
 * @returns {Array<{ nama: string, jumlah: number }>}
 */
function getFrekuensiJenis() {
  const map = {};
  PELANGGARAN.forEach(p => {
    map[p.jenisPelanggaran] = (map[p.jenisPelanggaran] || 0) + 1;
  });
  return Object.entries(map)
    .map(([nama, jumlah]) => ({ nama, jumlah }))
    .sort((a, b) => b.jumlah - a.jumlah);
}

/**
 * Hitung pelanggaran per kelas
 * @returns {Array<{ kelas: string, jumlah: number }>}
 */
function getPelanggaranPerKelas() {
  const map = {};
  PELANGGARAN.forEach(p => {
    map[p.kelas] = (map[p.kelas] || 0) + 1;
  });
  return Object.entries(map)
    .map(([kelas, jumlah]) => ({ kelas, jumlah }))
    .sort((a, b) => b.jumlah - a.jumlah);
}

/**
 * Hitung pelanggaran per bulan
 * @returns {Array<{ bulan: string, jumlah: number }>}
 */
function getPelanggaranPerBulan() {
  const BULAN = [
    'Jan','Feb','Mar','Apr','Mei','Jun',
    'Jul','Agt','Sep','Okt','Nov','Des',
  ];
  const map = {};
  PELANGGARAN.forEach(p => {
    const idx = parseInt(p.tanggal.split('-')[1]) - 1;
    const label = BULAN[idx];
    map[label] = (map[label] || 0) + 1;
  });
  // Kembalikan hanya bulan yang ada data + bulan sebelumnya untuk tren
  const hasil = [];
  ['Jan','Feb','Mar','Apr'].forEach(b => {
    hasil.push({ bulan: b, jumlah: map[b] || 0 });
  });
  return hasil;
}

/**
 * Ambil 5 pelanggaran terbaru
 * @returns {Array}
 */
function getPelanggaranTerbaru(limit = 5) {
  return [...PELANGGARAN]
    .sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal))
    .slice(0, limit);
}

/**
 * Ambil siswa berdasarkan ID
 * @param {string} siswaId
 * @returns {object|null}
 */
function getSiswaById(siswaId) {
  return SISWA.find(s => s.id === siswaId) || null;
}

/**
 * Ambil pelanggaran berdasarkan ID
 * @param {string} pelanggaranId
 * @returns {object|null}
 */
function getPelanggaranById(pelanggaranId) {
  return PELANGGARAN.find(p => p.id === pelanggaranId) || null;
}

/**
 * Cari siswa berdasarkan query (nama atau NIS)
 * @param {string} query
 * @returns {Array}
 */
function cariSiswa(query) {
  const q = query.toLowerCase().trim();
  if (!q) return SISWA;
  return SISWA.filter(s =>
    s.nama.toLowerCase().includes(q) ||
    s.nis.includes(q) ||
    s.kelas.toLowerCase().includes(q)
  );
}

/**
 * Filter pelanggaran dengan berbagai kriteria
 * @param {{ query, kelas, jenis, dari, sampai }} filter
 * @returns {Array}
 */
function filterPelanggaran({ query = '', kelas = '', jenis = '', dari = '', sampai = '' } = {}) {
  return PELANGGARAN.filter(p => {
    const matchQuery = !query ||
      p.namaSiswa.toLowerCase().includes(query.toLowerCase()) ||
      p.kelas.toLowerCase().includes(query.toLowerCase());
    const matchKelas  = !kelas  || p.kelas === kelas;
    const matchJenis  = !jenis  || p.jenisPelanggaran === jenis;
    const matchDari   = !dari   || p.tanggal >= dari;
    const matchSampai = !sampai || p.tanggal <= sampai;
    return matchQuery && matchKelas && matchJenis && matchDari && matchSampai;
  }).sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal));
}

/* ============================================================
   WA MESSAGE GENERATOR
   ============================================================ */

/**
 * Generate teks pesan WhatsApp untuk notifikasi pelanggaran
 * @param {{ namaSiswa, jenisPelanggaran, tanggal, keterangan }} params
 * @returns {string}
 */
function generatePesanWA({ namaSiswa, jenisPelanggaran, tanggal, keterangan }) {
  const tglFormatted = formatTanggal(tanggal);
  return `Yth. Orang tua/wali dari *${namaSiswa || '[Nama Siswa]'}*,

Kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:

📋 *Jenis Pelanggaran:* ${jenisPelanggaran || '[Jenis Pelanggaran]'}
📅 *Tanggal:* ${tglFormatted}
📝 *Keterangan:* ${keterangan || '-'}

Mohon kerjasamanya untuk memberikan pembinaan dan perhatian lebih kepada putra/putri Bapak/Ibu.

Apabila ada pertanyaan, silakan hubungi Guru BK kami.

Hormat kami,
*Guru BK ${SIGAP_CONFIG.namaSistem}*
*${SIGAP_CONFIG.namaSekolah}*`;
}

/* ============================================================
   DATE & FORMAT UTILITIES
   ============================================================ */
const BULAN_ID = [
  'Januari','Februari','Maret','April','Mei','Juni',
  'Juli','Agustus','September','Oktober','November','Desember',
];

const HARI_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

/**
 * Format tanggal ISO ke format lokal Indonesia
 * @param {string} isoDate  — format "YYYY-MM-DD"
 * @param {boolean} withDay — sertakan nama hari
 * @returns {string}
 */
function formatTanggal(isoDate, withDay = false) {
  if (!isoDate) return '-';
  const d = new Date(isoDate + 'T00:00:00');
  const tgl  = d.getDate();
  const bln  = BULAN_ID[d.getMonth()];
  const thn  = d.getFullYear();
  const hari = HARI_ID[d.getDay()];
  return withDay ? `${hari}, ${tgl} ${bln} ${thn}` : `${tgl} ${bln} ${thn}`;
}

/**
 * Format tanggal+waktu ke "HH:MM"
 * @param {string|null} waktu  — "YYYY-MM-DD HH:MM"
 * @returns {string}
 */
function formatWaktu(waktu) {
  if (!waktu) return '-';
  const parts = waktu.split(' ');
  return parts[1] || '-';
}

/**
 * Ambil tanggal hari ini dalam format ISO YYYY-MM-DD
 * @returns {string}
 */
function getTodayISO() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${dd}`;
}

/**
 * Ambil jam sekarang dalam format HH:MM
 * @returns {string}
 */
function getNowTime() {
  const d = new Date();
  const h = String(d.getHours()).padStart(2, '0');
  const m = String(d.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
}

/* ============================================================
   ID GENERATOR
   ============================================================ */

/**
 * Generate ID pelanggaran baru
 * @returns {string}
 */
function generatePelanggaranId() {
  const num = PELANGGARAN.length + 1;
  return `P${String(num).padStart(3, '0')}`;
}

/**
 * Generate ID siswa baru
 * @returns {string}
 */
function generateSiswaId() {
  const num = SISWA.length + 1;
  return `S${String(num).padStart(3, '0')}`;
}

/* ============================================================
   SESSION / AUTH UTILITIES
   ============================================================ */

/**
 * Simpan sesi login ke sessionStorage
 * @param {object} user
 */
function setSession(user) {
  sessionStorage.setItem('sigap_user', JSON.stringify(user));
}

/**
 * Ambil data sesi user yang sedang login
 * @returns {object|null}
 */
function getSession() {
  try {
    const raw = sessionStorage.getItem('sigap_user');
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

/**
 * Hapus sesi (logout)
 */
function clearSession() {
  sessionStorage.removeItem('sigap_user');
}

/**
 * Cek apakah user sudah login; jika belum, redirect ke login.html
 */
function requireAuth() {
  const user = getSession();
  if (!user) {
    window.location.href = 'login.html';
    return null;
  }
  return user;
}

/**
 * Validasi login
 * @param {string} username
 * @param {string} password
 * @returns {object|null}
 */
function loginValidate(username, password) {
  return USERS.find(u => u.username === username && u.password === password) || null;
}

/* ============================================================
   TOAST NOTIFICATION UTILITY
   ============================================================ */

/**
 * Tampilkan toast notifikasi
 * @param {{ type, title, message, duration }} options
 * type: 'success' | 'danger' | 'warning' | 'info'
 */
function showToast({ type = 'success', title = '', message = '', duration = 3500 } = {}) {
  const ICONS = {
    success: 'fa-circle-check',
    danger:  'fa-circle-xmark',
    warning: 'fa-triangle-exclamation',
    info:    'fa-circle-info',
  };

  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="fa-solid ${ICONS[type] || ICONS.info}"></i>
    <div class="toast-text">
      ${title    ? `<div class="toast-title">${title}</div>` : ''}
      ${message  ? `<div class="toast-body">${message}</div>` : ''}
    </div>
  `;

  container.appendChild(toast);
  requestAnimationFrame(() => {
    requestAnimationFrame(() => toast.classList.add('show'));
  });

  setTimeout(() => {
    toast.classList.replace('show', 'hide');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }, duration);
}

/* ============================================================
   MODAL UTILITY
   ============================================================ */

/**
 * Buka modal
 * @param {string} modalId — ID dari elemen .modal-overlay
 */
function openModal(modalId) {
  const overlay = document.getElementById(modalId);
  if (!overlay) return;
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

/**
 * Tutup modal
 * @param {string} modalId
 */
function closeModal(modalId) {
  const overlay = document.getElementById(modalId);
  if (!overlay) return;
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

/**
 * Inisialisasi semua modal: tutup saat klik overlay atau tombol X
 */
function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    // Klik backdrop
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeModal(overlay.id);
    });
    // Tombol close (X)
    overlay.querySelectorAll('[data-modal-close]').forEach(btn => {
      btn.addEventListener('click', () => closeModal(overlay.id));
    });
  });
  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(m => {
        closeModal(m.id);
      });
    }
  });
}

/* ============================================================
   SIDEBAR & TOPBAR UTILITY
   ============================================================ */

/**
 * Render topbar dan sisipkan nama user + tombol logout
 * @param {string} pageTitle — judul halaman yang ditampilkan di topbar
 */
function initTopbar(pageTitle = '') {
  const user = getSession();
  const topbarTitle = document.getElementById('topbar-title');
  const topbarUser  = document.getElementById('topbar-username');
  const topbarAvatar = document.getElementById('topbar-avatar');
  const logoutBtns  = document.querySelectorAll('[data-logout]');

  if (topbarTitle && pageTitle) topbarTitle.textContent = pageTitle;
  if (topbarUser  && user)      topbarUser.textContent  = user.nama;
  if (topbarAvatar && user)     topbarAvatar.textContent = user.avatar || 'U';

  logoutBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      clearSession();
      window.location.href = 'login.html';
    });
  });
}

/**
 * Tandai menu aktif di sidebar berdasarkan filename halaman saat ini
 */
function initSidebarActive() {
  const currentFile = window.location.pathname.split('/').pop() || 'dashboard.html';
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentFile) {
      link.classList.add('active');
    }
  });
}

/**
 * Inisialisasi hamburger menu untuk mobile
 */
function initHamburger() {
  const hamburger = document.getElementById('hamburger-btn');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebar-overlay');

  if (!hamburger || !sidebar) return;

  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('active');
  });

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }
}

/**
 * Inisialisasi seluruh komponen layout (topbar + sidebar)
 * @param {string} pageTitle
 */
function initLayout(pageTitle = '') {
  initTopbar(pageTitle);
  initSidebarActive();
  initHamburger();
}

/* ============================================================
   FORM VALIDATION UTILITY
   ============================================================ */

/**
 * Validasi field input — set class is-invalid + tampilkan error
 * @param {HTMLElement} input
 * @param {string} message — pesan error (kosong = valid)
 * @returns {boolean} true jika valid
 */
function validateField(input, message = '') {
  const errorEl = input.parentElement.querySelector('.form-error');
  if (message) {
    input.classList.add('is-invalid');
    if (errorEl) { errorEl.textContent = message; errorEl.style.display = 'block'; }
    return false;
  } else {
    input.classList.remove('is-invalid');
    if (errorEl) errorEl.style.display = 'none';
    return true;
  }
}

/**
 * Reset semua error validasi dalam sebuah form
 * @param {HTMLFormElement|HTMLElement} formEl
 */
function clearValidation(formEl) {
  formEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  formEl.querySelectorAll('.form-error').forEach(el => { el.style.display = 'none'; });
}

/* ============================================================
   PAGINATION UTILITY
   ============================================================ */

/**
 * Buat state paginasi
 * @param {Array}  data       — semua data
 * @param {number} perPage    — jumlah baris per halaman
 * @returns {{ currentPage, totalPages, getPage, info }}
 */
function createPagination(data, perPage = 5) {
  let currentPage = 1;
  const totalPages = () => Math.max(1, Math.ceil(data.length / perPage));

  return {
    get currentPage() { return currentPage; },
    set currentPage(v) { currentPage = Math.min(Math.max(1, v), totalPages()); },
    get totalPages()   { return totalPages(); },
    getPage() {
      const start = (currentPage - 1) * perPage;
      return data.slice(start, start + perPage);
    },
    info() {
      const start = Math.min((currentPage - 1) * perPage + 1, data.length);
      const end   = Math.min(currentPage * perPage, data.length);
      return { start, end, total: data.length };
    },
  };
}

/**
 * Render tombol-tombol paginasi ke dalam container
 * @param {HTMLElement} container — elemen .pagination-controls
 * @param {object}      pagi      — hasil createPagination()
 * @param {Function}    onChange  — callback(newPage) saat halaman berubah
 */
function renderPaginationControls(container, pagi, onChange) {
  container.innerHTML = '';

  const addBtn = (label, page, disabled, active) => {
    const btn = document.createElement('button');
    btn.className = `page-btn${active ? ' active' : ''}`;
    btn.innerHTML = label;
    btn.disabled  = disabled;
    btn.addEventListener('click', () => { if (!disabled && !active) onChange(page); });
    container.appendChild(btn);
  };

  addBtn('<i class="fa-solid fa-chevron-left" style="font-size:10px"></i>',
    pagi.currentPage - 1, pagi.currentPage === 1, false);

  for (let i = 1; i <= pagi.totalPages; i++) {
    addBtn(i, i, false, i === pagi.currentPage);
  }

  addBtn('<i class="fa-solid fa-chevron-right" style="font-size:10px"></i>',
    pagi.currentPage + 1, pagi.currentPage === pagi.totalPages, false);
}

/* ============================================================
   CHART COLOR PALETTE
   ============================================================ */
const CHART_COLORS = {
  blue:    'rgba(26,  79, 160, 0.85)',
  red:     'rgba(220,  38,  38, 0.85)',
  orange:  'rgba(217, 119,   6, 0.85)',
  green:   'rgba( 22, 163,  74, 0.85)',
  purple:  'rgba(124,  58, 237, 0.85)',
  cyan:    'rgba(  8, 145, 178, 0.85)',
  pink:    'rgba(219,  39, 119, 0.85)',
  teal:    'rgba( 20, 184, 166, 0.85)',

  blueLight:   'rgba(26,  79, 160, 0.15)',
  redLight:    'rgba(220,  38,  38, 0.15)',
  orangeLight: 'rgba(217, 119,   6, 0.15)',
  greenLight:  'rgba( 22, 163,  74, 0.15)',
  purpleLight: 'rgba(124,  58, 237, 0.15)',

  pie: [
    'rgba( 26,  79, 160, 0.88)',
    'rgba( 22, 163,  74, 0.88)',
    'rgba(220,  38,  38, 0.88)',
    'rgba(217, 119,   6, 0.88)',
    'rgba(124,  58, 237, 0.88)',
    'rgba(  8, 145, 178, 0.88)',
    'rgba(219,  39, 119, 0.88)',
  ],
};

/**
 * Default Chart.js global options
 */
const CHART_DEFAULTS = {
  font: {
    family: "'DM Sans', sans-serif",
    size: 12,
  },
  color: '#64748b',
};

/* ============================================================
   EXPORT (untuk browser — semua jadi global)
   ============================================================ */
// Semua variabel dan fungsi di atas sudah global karena
// file ini di-load via <script src="data.js"> tanpa module.
// Tidak perlu export statement.

// Freeze config agar tidak tidak sengaja diubah
Object.freeze(SIGAP_CONFIG);