-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 05 Okt 2026 pada 12.25
-- Versi server: 8.4.3
-- Versi PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigap_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_pelanggaran`
--

CREATE TABLE `jenis_pelanggaran` (
  `id` int NOT NULL,
  `kode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tingkat` enum('rendah','sedang','berat') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rendah' COMMENT 'rendah=1 poin, sedang=2 poin, berat=3 poin',
  `poin` tinyint NOT NULL DEFAULT '1' COMMENT 'Otomatis: rendah=1, sedang=2, berat=3',
  `kategori` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jenis_pelanggaran`
--

INSERT INTO `jenis_pelanggaran` (`id`, `kode`, `nama`, `tingkat`, `poin`, `kategori`, `icon`, `color`, `is_active`) VALUES
(1, 'JP01', 'Terlambat masuk', 'rendah', 1, 'Disiplin', 'fa-clock', 'warning', 1),
(2, 'JP02', 'Tidak memakai seragam', 'rendah', 1, 'Penampilan', 'fa-shirt', 'warning', 1),
(3, 'JP03', 'Tidak mengerjakan tugas', 'rendah', 1, 'Akademik', 'fa-book-open', 'warning', 1),
(4, 'JP04', 'Tidak hadir tanpa izin', 'rendah', 1, 'Disiplin', 'fa-user-slash', 'warning', 1),
(5, 'JP05', 'Bolos pelajaran', 'sedang', 2, 'Disiplin', 'fa-person-running', 'warning', 1),
(6, 'JP06', 'Membawa HP', 'sedang', 2, 'Tata Tertib', 'fa-mobile-screen', 'warning', 1),
(7, 'JP07', 'Merokok', 'sedang', 2, 'Tata Tertib', 'fa-smoking', 'warning', 1),
(8, 'JP08', 'Merusak fasilitas sekolah', 'sedang', 2, 'Tata Tertib', 'fa-hammer', 'warning', 1),
(9, 'JP09', 'Bully', 'berat', 3, 'Kekerasan', 'fa-hand-fist', 'danger', 1),
(10, 'JP10', 'Kekerasan fisik', 'berat', 3, 'Kekerasan', 'fa-person-falling', 'danger', 1),
(11, 'JP11', 'Kekerasan Seksual', 'berat', 3, 'Kekerasan', 'fa-triangle-exclamation', 'danger', 1),
(12, 'JP12', 'Membawa senjata', 'berat', 3, 'Kekerasan', 'fa-gun', 'danger', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurusan`
--

CREATE TABLE `jurusan` (
  `id` int NOT NULL,
  `kode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jurusan`
--

INSERT INTO `jurusan` (`id`, `kode`, `nama`, `is_active`) VALUES
(1, 'RPL', 'Rekayasa Perangkat Lunak', 1),
(2, 'TKJ', 'Teknik Komputer dan Jaringan', 1),
(3, 'TJAT', 'Teknik Jaringan Akses Telekomunikasi', 1),
(4, 'DKV', 'Desain Komunikasi Visual', 1),
(5, 'TG', 'Teknik Grafika', 1),
(6, 'DPB', 'Desain dan Produksi Busana', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggaran`
--

CREATE TABLE `pelanggaran` (
  `id` int NOT NULL,
  `kode_pelanggaran` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. P001',
  `siswa_id` int NOT NULL,
  `jenis_pelanggaran_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `poin` int NOT NULL DEFAULT '0' COMMENT 'Poin yang DITAMBAHKAN ke siswa (1=rendah, 2=sedang, 3=berat)',
  `dicatat_oleh` int DEFAULT NULL COMMENT 'FK ke users.id',
  `status_wa` enum('Pending','Terkirim','Gagal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `status_penanganan` enum('Belum Ditangani','Sedang Diproses','Sudah Selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belum Ditangani',
  `wa_tujuan` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wa_pesan` text COLLATE utf8mb4_unicode_ci,
  `waktu_kirim_wa` datetime DEFAULT NULL,
  `wa_response` text COLLATE utf8mb4_unicode_ci COMMENT 'Raw JSON response dari Fonnte',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pelanggaran`
--

INSERT INTO `pelanggaran` (`id`, `kode_pelanggaran`, `siswa_id`, `jenis_pelanggaran_id`, `tanggal`, `keterangan`, `poin`, `dicatat_oleh`, `status_wa`, `status_penanganan`, `wa_tujuan`, `wa_pesan`, `waktu_kirim_wa`, `wa_response`, `created_at`, `updated_at`) VALUES
(1, 'P001', 1, 1, '2026-03-03', 'Terlambat 20 menit tanpa keterangan yang jelas.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456001', 'Yth. Orang tua/wali dari *Andi Prasetyo*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Senin, 3 Maret 2026\n📝 *Keterangan:* Terlambat 20 menit tanpa keterangan yang jelas.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-03 07:45:00', '{\"status\":true,\"id\":\"MSG001\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(2, 'P002', 2, 2, '2026-03-05', 'Tidak memakai dasi seragam lengkap pada hari Senin.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456002', 'Yth. Orang tua/wali dari *Siti Rahayu*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Tidak memakai seragam\n📅 *Tanggal:* Kamis, 5 Maret 2026\n📝 *Keterangan:* Tidak memakai dasi seragam lengkap pada hari Senin.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-05 08:10:00', '{\"status\":true,\"id\":\"MSG002\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(3, 'P003', 3, 3, '2026-03-07', 'Tidak hadir pada jam ke-3 dan ke-4 mata pelajaran Matematika tanpa izin.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456003', 'Yth. Orang tua/wali dari *Budi Santoso*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Bolos pelajaran\n📅 *Tanggal:* Sabtu, 7 Maret 2026\n📝 *Keterangan:* Tidak hadir pada jam ke-3 dan ke-4 mata pelajaran Matematika tanpa izin.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-07 10:30:00', '{\"status\":true,\"id\":\"MSG003\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(4, 'P004', 4, 4, '2026-03-10', 'Tidak mengumpulkan tugas Bahasa Indonesia selama 2 minggu berturut-turut.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456004', 'Yth. Orang tua/wali dari *Dewi Lestari*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Tidak mengerjakan tugas\n📅 *Tanggal:* Selasa, 10 Maret 2026\n📝 *Keterangan:* Tidak mengumpulkan tugas Bahasa Indonesia selama 2 minggu berturut-turut.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-10 09:15:00', '{\"status\":true,\"id\":\"MSG004\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(5, 'P005', 5, 5, '2026-03-12', 'Kedapatan menggunakan HP saat jam pelajaran Fisika berlangsung.', 2, 2, 'Gagal', 'Belum Ditangani', '628123456005', 'Yth. Orang tua/wali dari *Riko Firmansyah*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Membawa HP\n📅 *Tanggal:* Kamis, 12 Maret 2026\n📝 *Keterangan:* Kedapatan menggunakan HP saat jam pelajaran Fisika berlangsung.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-12 11:00:00', '{\"status\":false,\"reason\":\"number_not_registered\",\"id\":\"MSG005\"}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(6, 'P006', 6, 1, '2026-03-14', 'Terlambat 15 menit, alasan bangun kesiangan.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456006', 'Yth. Orang tua/wali dari *Anisa Putri Maharani*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Sabtu, 14 Maret 2026\n📝 *Keterangan:* Terlambat 15 menit, alasan bangun kesiangan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-14 07:30:00', '{\"status\":true,\"id\":\"MSG006\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(7, 'P007', 7, 1, '2026-03-17', 'Terlambat 10 menit karena macet, sudah membawa surat keterangan.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456007', 'Yth. Orang tua/wali dari *Fajar Nugroho*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Selasa, 17 Maret 2026\n📝 *Keterangan:* Terlambat 10 menit karena macet, sudah membawa surat keterangan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-17 07:20:00', '{\"status\":true,\"id\":\"MSG007\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(8, 'P008', 8, 4, '2026-03-19', 'Tidak mengumpulkan laporan praktikum Kimia pada batas waktu yang ditentukan.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456008', 'Yth. Orang tua/wali dari *Nur Halimah*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Tidak mengerjakan tugas\n📅 *Tanggal:* Kamis, 19 Maret 2026\n📝 *Keterangan:* Tidak mengumpulkan laporan praktikum Kimia pada batas waktu yang ditentukan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-19 10:00:00', '{\"status\":true,\"id\":\"MSG008\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(9, 'P009', 9, 2, '2026-03-21', 'Memakai sepatu berwarna selain hitam polos saat hari Rabu.', 1, 2, 'Pending', 'Belum Ditangani', '628123456009', 'Yth. Orang tua/wali dari *Kevin Ardiansyah*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Tidak memakai seragam\n📅 *Tanggal:* Sabtu, 21 Maret 2026\n📝 *Keterangan:* Memakai sepatu berwarna selain hitam polos saat hari Rabu.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', NULL, NULL, '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(10, 'P010', 1, 3, '2026-03-24', 'Tidak hadir pada jam pelajaran PKL tanpa pemberitahuan.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456001', 'Yth. Orang tua/wali dari *Andi Prasetyo*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Bolos pelajaran\n📅 *Tanggal:* Selasa, 24 Maret 2026\n📝 *Keterangan:* Tidak hadir pada jam pelajaran PKL tanpa pemberitahuan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-24 09:00:00', '{\"status\":true,\"id\":\"MSG010\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(11, 'P011', 10, 6, '2026-03-26', 'Membuat keributan di kantin saat jam istirahat dan mengganggu siswa lain.', 2, 2, 'Terkirim', 'Belum Ditangani', '628123456010', 'Yth. Orang tua/wali dari *Rizky Amalia Putri*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Lainnya\n📅 *Tanggal:* Kamis, 26 Maret 2026\n📝 *Keterangan:* Membuat keributan di kantin saat jam istirahat dan mengganggu siswa lain.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-03-26 12:15:00', '{\"status\":true,\"id\":\"MSG011\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(12, 'P012', 11, 5, '2026-03-28', 'Menggunakan HP untuk bermain game saat pelajaran Produktif.', 2, 2, 'Pending', 'Belum Ditangani', '628123456011', 'Yth. Orang tua/wali dari *Daffa Rayhan Pratama*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Membawa HP\n📅 *Tanggal:* Sabtu, 28 Maret 2026\n📝 *Keterangan:* Menggunakan HP untuk bermain game saat pelajaran Produktif.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', NULL, NULL, '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(13, 'P013', 3, 1, '2026-04-01', 'Terlambat 30 menit, kali kedua dalam bulan ini.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456003', 'Yth. Orang tua/wali dari *Budi Santoso*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Rabu, 1 April 2026\n📝 *Keterangan:* Terlambat 30 menit, kali kedua dalam bulan ini.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-04-01 07:50:00', '{\"status\":true,\"id\":\"MSG013\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(14, 'P014', 12, 4, '2026-04-03', 'Tidak mengumpulkan PR Matematika sebanyak 3 kali berturut-turut.', 1, 2, 'Pending', 'Belum Ditangani', '628123456012', 'Yth. Orang tua/wali dari *Ayu Sekar Ningrum*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Tidak mengerjakan tugas\n📅 *Tanggal:* Jumat, 3 April 2026\n📝 *Keterangan:* Tidak mengumpulkan PR Matematika sebanyak 3 kali berturut-turut.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', NULL, NULL, '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(15, 'P015', 5, 1, '2026-04-07', 'Terlambat 25 menit tanpa surat keterangan.', 1, 2, 'Gagal', 'Belum Ditangani', '628123456005', 'Yth. Orang tua/wali dari *Riko Firmansyah*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Selasa, 7 April 2026\n📝 *Keterangan:* Terlambat 25 menit tanpa surat keterangan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-04-07 07:55:00', '{\"status\":false,\"reason\":\"quota_exceeded\",\"id\":\"MSG015\"}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(16, 'P016', 2, 3, '2026-04-08', 'Tidak masuk pelajaran Biologi jam ke-5 dan ke-6 tanpa keterangan.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456002', 'Yth. Orang tua/wali dari *Siti Rahayu*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Bolos pelajaran\n📅 *Tanggal:* Rabu, 8 April 2026\n📝 *Keterangan:* Tidak masuk pelajaran Biologi jam ke-5 dan ke-6 tanpa keterangan.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-04-08 13:00:00', '{\"status\":true,\"id\":\"MSG016\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(17, 'P017', 4, 1, '2026-04-09', 'Terlambat masuk setelah jam istirahat, 20 menit.', 1, 2, 'Terkirim', 'Belum Ditangani', '628123456004', 'Yth. Orang tua/wali dari *Dewi Lestari*, kami informasikan bahwa putra/putri Bapak/Ibu tercatat melakukan pelanggaran tata tertib sekolah:\n\n📋 *Jenis Pelanggaran:* Terlambat masuk\n📅 *Tanggal:* Kamis, 9 April 2026\n📝 *Keterangan:* Terlambat masuk setelah jam istirahat, 20 menit.\n\nMohon kerjasamanya. Hormat kami, *Guru BK SIGAP* *SMK Negeri 1 Masbagik*', '2026-04-09 10:05:00', '{\"status\":true,\"id\":\"MSG017\",\"process\":true}', '2026-10-04 04:33:26', '2026-10-04 04:33:26'),
(18, 'P018', 13, 2, '2026-10-04', 'ewerwrewre', 1, 1, 'Gagal', 'Sedang Diproses', '626283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Tidak memakai seragam\n⚠️ *Tingkat:* Rendah (1 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n📝 *Keterangan:* ewerwrewre\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', NULL, '{\"error\":\"cURL error: SSL connection timeout\",\"response\":[]}', '2026-10-04 05:44:47', '2026-10-04 05:47:15'),
(19, 'P019', 13, 9, '2026-10-04', 'ssdgdfgdgfdgf', 3, 1, 'Gagal', 'Belum Ditangani', '626283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Bully\n⚠️ *Tingkat:* Berat (3 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n📝 *Keterangan:* ssdgdfgdgfdgf\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', NULL, '{\"error\":\"cURL error: Failed to connect to api.fonnte.com port 443 after 10014 ms: Timeout was reached\",\"response\":[]}', '2026-10-04 05:58:19', '2026-10-04 05:58:29'),
(20, 'P020', 13, 3, '2026-10-04', '', 1, 1, 'Gagal', 'Belum Ditangani', '626283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Tidak mengerjakan tugas\n⚠️ *Tingkat:* Rendah (1 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', NULL, '{\"error\":\"cURL error: Failed to connect to api.fonnte.com port 443 after 10007 ms: Timeout was reached\",\"response\":[]}', '2026-10-04 05:58:45', '2026-10-04 05:58:55'),
(21, 'P021', 13, 6, '2026-10-04', '', 2, 1, 'Gagal', 'Sedang Diproses', '626283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Membawa HP\n⚠️ *Tingkat:* Sedang (2 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', NULL, '{\"error\":\"target input invalid\",\"response\":{\"reason\":\"target input invalid\",\"requestid\":1025711277,\"status\":false,\"target\":\"626283848160290\"}}', '2026-10-04 05:59:15', '2026-10-04 06:01:27'),
(22, 'P022', 13, 4, '2026-10-04', 'sdasdasdas', 1, 1, 'Gagal', 'Belum Ditangani', '626283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Tidak hadir tanpa izin\n⚠️ *Tingkat:* Rendah (1 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n📝 *Keterangan:* sdasdasdas\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', NULL, '{\"error\":\"target input invalid\",\"response\":{\"reason\":\"target input invalid\",\"requestid\":1025888341,\"status\":false,\"target\":\"626283848160290\"}}', '2026-10-04 09:10:09', '2026-10-04 09:10:10'),
(23, 'P023', 13, 4, '2026-10-04', '', 1, 1, 'Terkirim', 'Sudah Selesai', '6283848160290', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *aaar (Ayah)*,\nOrang tua/wali dari *rahman 334*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* rahman 334\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Komputer dan Jaringan\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Tidak hadir tanpa izin\n⚠️ *Tingkat:* Rendah (1 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', '2026-10-04 16:13:31', '{\"detail\":\"success! message in queue\",\"id\":[183450377],\"process\":\"pending\",\"quota\":{\"083848160290\":{\"details\":\"deduced from total quota\",\"quota\":992,\"remaining\":991,\"used\":1}},\"requestid\":1025890970,\"status\":true,\"target\":[\"6283848160290\"]}', '2026-10-04 09:13:31', '2026-10-04 09:14:32'),
(24, 'P024', 14, 11, '2026-10-04', 'saeraersradtastat', 3, 1, 'Terkirim', 'Belum Ditangani', '6285953760843', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *ali (Ayah)*,\nOrang tua/wali dari *Alpi*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* Alpi\n🏫 *Kelas:* XII\n🎓 *Jurusan:* Teknik Jaringan Akses Telekomunikasi\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Kekerasan Seksual\n⚠️ *Tingkat:* Berat (3 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n📝 *Keterangan:* saeraersradtastat\n───────────────────\n\n📌 *Tindak Lanjut:* Bapak/Ibu dimohon untuk *hadir ke sekolah* guna pertemuan dengan Guru BK untuk penyelesaian lebih lanjut.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', '2026-10-04 16:39:35', '{\"detail\":\"success! message in queue\",\"id\":[183453602],\"process\":\"pending\",\"quota\":{\"083848160290\":{\"details\":\"deduced from total quota\",\"quota\":991,\"remaining\":990,\"used\":1}},\"requestid\":1025914862,\"status\":true,\"target\":[\"6285953760843\"]}', '2026-10-04 09:39:34', '2026-10-04 09:39:35'),
(25, 'P025', 15, 7, '2026-10-04', '', 2, 1, 'Terkirim', 'Belum Ditangani', '62895329125569', '🏫 *SIGAP — SMK Negeri 1 Masbagik*\n───────────────────\n\nYth. Bapak/Ibu *dree12 (Ayah)*,\nOrang tua/wali dari *dree*\n\nKami informasikan bahwa putra Bapak/Ibu tercatat melakukan *pelanggaran tata tertib sekolah*:\n\n───────────────────\n👤 *Nama:* dree\n🏫 *Kelas:* X\n🎓 *Jurusan:* Desain Komunikasi Visual\n🚻 *Jenis Kelamin:* Laki-laki\n───────────────────\n📋 *Jenis Pelanggaran:* Merokok\n⚠️ *Tingkat:* Sedang (2 poin)\n📅 *Tanggal:* Minggu, 4 Oktober 2026\n───────────────────\n\n📌 *Tindak Lanjut:* Masalah ini telah *ditangani oleh Guru BK* di sekolah. Bapak/Ibu *tidak perlu hadir* ke sekolah.\n\nMohon kerjasamanya dalam membimbing putra Bapak/Ibu agar tidak mengulangi pelanggaran serupa.\nApabila ada pertanyaan, silakan hubungi Guru BK kami.\n\nHormat kami,\n*Guru BK SIGAP*\n*SMK Negeri 1 Masbagik*', '2026-10-04 19:52:49', '{\"detail\":\"success! message in queue\",\"id\":[183477461],\"process\":\"pending\",\"quota\":{\"083848160290\":{\"details\":\"deduced from total quota\",\"quota\":990,\"remaining\":989,\"used\":1}},\"requestid\":1026111885,\"status\":true,\"target\":[\"62895329125569\"]}', '2026-10-04 12:52:48', '2026-10-04 12:52:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int NOT NULL,
  `kode_siswa` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. S001',
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` enum('X','XI','XII') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jurusan_id` int DEFAULT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_ortu` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wa_ortu` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Format 628xxx tanpa tanda baca',
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `poin` int NOT NULL DEFAULT '0' COMMENT 'Total poin akumulasi (ringan=1, sedang=2, berat=3)',
  `jumlah_pelanggaran` int NOT NULL DEFAULT '0' COMMENT 'Jumlah pelanggaran tercatat',
  `persentase` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Persentase = (total_poin / (jumlah_pel × 3)) × 100',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `kode_siswa`, `nama`, `nis`, `kelas`, `jurusan_id`, `gender`, `nama_ortu`, `wa_ortu`, `alamat`, `poin`, `jumlah_pelanggaran`, `persentase`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'S001', 'Andi Prasetyo', '2401001', 'XII', NULL, 'L', 'Bpk. Prasetyo Utomo', '628123456001', 'Jl. Merdeka No. 12, Masbagik', 2, 2, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:44:18'),
(2, 'S002', 'Siti Rahayu', '2401002', 'XI', NULL, 'P', 'Ibu Rahayu Ningsih', '628123456002', 'Jl. Pahlawan No. 7, Masbagik', 2, 2, 33.33, 1, '2026-10-04 04:33:26', '2026-10-04 04:42:21'),
(3, 'S003', 'Budi Santoso', '2401003', 'X', NULL, 'L', 'Bpk. Santoso Hadi', '628123456003', 'Jl. Slamet Riyadi No. 45, Masbagik', 2, 2, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:44:23'),
(4, 'S004', 'Dewi Lestari', '2401004', 'XI', NULL, 'P', 'Ibu Lestari Handayani', '628123456004', 'Jl. Gatot Subroto No. 3, Masbagik', 2, 2, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:52:21'),
(5, 'S005', 'Riko Firmansyah', '2401005', 'XII', NULL, 'L', 'Bpk. Firmansyah Agus', '628123456005', 'Jl. Diponegoro No. 22, Masbagik', 3, 2, 50.00, 1, '2026-10-04 04:33:26', '2026-10-04 04:42:21'),
(6, 'S006', 'Anisa Putri Maharani', '2401006', 'XI', NULL, 'P', 'Ibu Maharani Dewi', '628123456006', 'Jl. Ahmad Yani No. 18, Masbagik', 1, 1, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:44:20'),
(7, 'S007', 'Fajar Nugroho', '2401007', 'X', NULL, 'L', 'Bpk. Nugroho Wahyudi', '628123456007', 'Jl. Urip Sumoharjo No. 9, Masbagik', 1, 1, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:52:23'),
(8, 'S008', 'Nur Halimah', '2401008', 'XII', NULL, 'P', 'Ibu Halimah Susanti', '628123456008', 'Jl. Brigjen Katamso No. 31, Masbagik', 1, 1, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:51:53'),
(9, 'S009', 'Kevin Ardiansyah', '2401009', 'X', NULL, 'L', 'Bpk. Ardiansyah Rudi', '628123456009', 'Jl. Kebangkitan Nasional No. 5, Masbagik', 1, 1, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:52:25'),
(10, 'S010', 'Rizky Amalia Putri', '2401010', 'XI', NULL, 'P', 'Bpk. Amalia Hasan', '628123456010', 'Jl. Veteran No. 14, Masbagik', 2, 1, 66.67, 1, '2026-10-04 04:33:26', '2026-10-04 04:42:21'),
(11, 'S011', 'Daffa Rayhan Pratama', '2401011', 'XII', NULL, 'L', 'Ibu Pratama Sari', '628123456011', 'Jl. Kolonel Sugiono No. 27, Masbagik', 2, 1, 66.67, 0, '2026-10-04 04:33:26', '2026-10-04 05:52:19'),
(12, 'S012', 'Ayu Sekar Ningrum', '2401012', 'X', NULL, 'P', 'Bpk. Ningrum Basuki', '628123456012', 'Jl. Adi Sucipto No. 8, Masbagik', 1, 1, 33.33, 0, '2026-10-04 04:33:26', '2026-10-04 05:52:17'),
(13, 'S013', 'rahman 334', '3243213211', 'XII', 2, 'L', 'aaar (Ayah)', '6283848160290', '', 9, 6, 50.00, 1, '2026-10-04 05:44:03', '2026-10-04 09:13:31'),
(14, 'S014', 'Alpi', '3243213212', 'XII', 3, 'L', 'ali (Ayah)', '6285953760843', 'semaya', 3, 1, 100.00, 1, '2026-10-04 09:39:05', '2026-10-04 09:39:35'),
(15, 'S015', 'dree', '3243213210', 'X', 4, 'L', 'dree12 (Ayah)', '62895329125569', '', 2, 1, 66.67, 1, '2026-10-04 12:52:27', '2026-10-04 12:52:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'bcrypt hash',
  `role` enum('admin','guru_bk','wali_kelas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guru_bk',
  `avatar` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Inisial nama, misal SW',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `avatar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bpk. Hendra Kusuma, S.Pd.', 'admin', '$2y$10$uzUXYRZWWif2SOmwP/1oD.aNYi02qJZcfDCaRo06h6U7/VDOGoShy', 'admin', 'HK', 1, '2026-10-04 04:33:26', '2026-10-04 04:37:33'),
(2, 'Dra. Sri Wahyuni, M.Pd.', 'sriwahyuni', '$2y$10$7nYjtVCpKfBS70RtdA30yePBkVTOVcUnlLhM4j2eJL5Ly94HLDOQm', 'guru_bk', 'SW', 1, '2026-10-04 04:33:26', '2026-10-04 04:37:33'),
(3, 'wawan_setiawan', 'wawan_setiawan221', '$2y$10$y8K8XBVU0xzC55YjQTdhr.fsGeYu0q511yRLHF9h.CWbvmmxVA7am', 'guru_bk', 'W', 1, '2026-10-04 09:25:19', '2026-10-04 09:25:19');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode` (`kode`),
  ADD UNIQUE KEY `uq_nama` (`nama`);

--
-- Indeks untuk tabel `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_jurusan` (`kode`);

--
-- Indeks untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_pelanggaran` (`kode_pelanggaran`),
  ADD KEY `idx_siswa_id` (`siswa_id`),
  ADD KEY `idx_jenis_id` (`jenis_pelanggaran_id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_status_wa` (`status_wa`),
  ADD KEY `idx_status_penanganan` (`status_penanganan`),
  ADD KEY `idx_dicatat_oleh` (`dicatat_oleh`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_siswa` (`kode_siswa`),
  ADD UNIQUE KEY `uq_nis` (`nis`),
  ADD KEY `idx_kelas` (`kelas`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_jurusan_id` (`jurusan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pelanggaran`
--
ALTER TABLE `pelanggaran`
  ADD CONSTRAINT `fk_pelanggaran_jenis` FOREIGN KEY (`jenis_pelanggaran_id`) REFERENCES `jenis_pelanggaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pelanggaran_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pelanggaran_user` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_jurusan` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;