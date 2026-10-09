/**
 * ============================================================
 * SIGAP — js-fetch-snippets.js
 * Potongan kode JavaScript fetch() untuk menggantikan
 * dummy data.js di setiap halaman HTML.
 *
 * Cara pakai:
 * 1. Hapus <script src="data.js"></script> dari setiap halaman
 * 2. Ganti bagian logic dummy data dengan kode di bawah ini
 * 3. Pastikan semua file PHP sudah ada di folder sigap/
 * ============================================================
 */


// ============================================================
// UTILITAS BERSAMA — Tambahkan di SEMUA halaman
// (letakkan di atas sebelum kode spesifik halaman)
// ============================================================

/**
 * Wrapper fetch ke API dengan handling error otomatis.
 * Semua endpoint mengembalikan { success, message, data }.
 *
 * @param {string} url
 * @param {object} options — fetch options (method, body, dsb.)
 * @returns {Promise<object>} — parsed JSON response
 */
async function apiRequest(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      ...options,
    });

    const json = await res.json();

    // Session expired → redirect ke login
    if (res.status === 401) {
      window.location.href = 'login.html';
      return null;
    }

    return json;
  } catch (err) {
    console.error('API Request gagal:', err);
    showToast({
      type: 'danger',
      title: 'Koneksi Gagal',
      message: 'Tidak dapat terhubung ke server. Pastikan XAMPP berjalan.',
    });
    return null;
  }
}

/**
 * Buat FormData dari objek biasa.
 * @param {object} data
 * @returns {FormData}
 */
function toFormData(data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v ?? ''));
  return fd;
}


// ============================================================
// 1. login.html — Ganti fungsi handleLogin()
// ============================================================

// HAPUS baris ini dari login.html:
//   <script src="data.js"></script>
//   const user = loginValidate(username, password);
//   setSession(user);

// GANTI blok form submit dengan:
document.getElementById('login-form').addEventListener('submit', async function (e) {
  e.preventDefault();

  const usernameEl = document.getElementById('username');
  const passwordEl = document.getElementById('password');
  const btn        = document.getElementById('login-btn');
  const banner     = document.getElementById('error-banner');

  const username = usernameEl.value.trim();
  const password = passwordEl.value.trim();

  // Reset error
  banner.classList.remove('visible');
  usernameEl.classList.remove('error');
  passwordEl.classList.remove('error');

  let valid = true;
  if (!username) {
    usernameEl.classList.add('error');
    document.getElementById('username-error').classList.add('visible');
    valid = false;
  }
  if (!password) {
    passwordEl.classList.add('error');
    document.getElementById('password-error').classList.add('visible');
    valid = false;
  }
  if (!valid) return;

  // Loading state
  btn.classList.add('loading');
  btn.disabled = true;

  const res = await apiRequest('auth.php', {
    method: 'POST',
    body: toFormData({ action: 'login', username, password }),
  });

  btn.classList.remove('loading');
  btn.disabled = false;

  if (!res || !res.success) {
    document.getElementById('error-message').textContent =
      res?.message || 'Username atau password salah.';
    banner.classList.add('visible');
    document.getElementById('login-form').classList.add('shake');
    setTimeout(() => document.getElementById('login-form').classList.remove('shake'), 400);
    passwordEl.value = '';
    return;
  }

  // Simpan data user di sessionStorage (untuk UI)
  sessionStorage.setItem('sigap_user', JSON.stringify(res.data));

  showToast({ type: 'success', title: 'Login Berhasil', message: res.message, duration: 1500 });
  setTimeout(() => { window.location.href = 'dashboard.html'; }, 900);
});

// Cek redirect jika sudah login (session PHP akan handle di server)
// Hapus blok (function() { const user = getSession(); ... })() dari login.html


// ============================================================
// 2. dashboard.html — Ganti semua dummy data dengan fetch
// ============================================================

// HAPUS: <script src="data.js"></script>
// TAMBAH di atas <script src="https://...chart.js...">:
//   <script src="js-fetch-snippets.js"></script>  (opsional, atau inline)

async function loadDashboard() {
  const res = await apiRequest('api/dashboard.php');
  if (!res || !res.success) return;

  const d = res.data;

  // Metric cards
  animateCount(document.getElementById('m-total-siswa'),   d.total_siswa,            900);
  animateCount(document.getElementById('m-pelanggaran'),   d.pelanggaran_bulan_ini,  700);
  animateCount(document.getElementById('m-pending'),       d.wa_pending,             600);
  animateCount(document.getElementById('m-wa-sent'),       d.wa_terkirim,            750);

  // WA status detail
  document.getElementById('ws-terkirim').textContent = d.wa_terkirim;
  document.getElementById('ws-pending').textContent  = d.wa_pending;
  document.getElementById('ws-gagal').textContent    = d.wa_gagal;
  document.getElementById('ws-total').textContent    = d.wa_total;
  document.getElementById('ws-rate').textContent     = d.wa_success_rate + '%';

  // Legend bar
  renderWALegendBar(d);

  // Tabel pelanggaran terbaru
  renderRecentTable(d.pelanggaran_terbaru);

  // Update footer total
  document.getElementById('total-records').textContent = d.wa_total;

  // Charts (setelah DOM siap)
  renderBarChart(d.frekuensi_jenis_bulan_ini);
  renderPieChart(d.pelanggaran_per_kelas);
}

// Panggil saat halaman load (gantikan window.addEventListener('DOMContentLoaded',...))
document.addEventListener('DOMContentLoaded', loadDashboard);

// Bar chart — terima data dari API
function renderBarChart(frekuensiData) {
  const labels = frekuensiData.map(f => f.nama_jenis);
  const counts = frekuensiData.map(f => parseInt(f.jumlah));

  const barColors = [
    'rgba(26,79,160,0.85)', 'rgba(220,38,38,0.85)', 'rgba(217,119,6,0.85)',
    'rgba(22,163,74,0.85)', 'rgba(124,58,237,0.85)', 'rgba(8,145,178,0.85)',
  ];

  const ctx = document.getElementById('chart-bar').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Jumlah',
        data: counts,
        backgroundColor: barColors,
        borderRadius: 6,
        borderSkipped: false,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
      },
    },
  });
}

// Pie chart
function renderPieChart(perKelasData) {
  const labels = perKelasData.map(k => k.kelas);
  const counts = perKelasData.map(k => parseInt(k.jumlah));
  const colors = ['rgba(26,79,160,0.88)','rgba(22,163,74,0.88)','rgba(220,38,38,0.88)',
                  'rgba(217,119,6,0.88)','rgba(124,58,237,0.88)','rgba(8,145,178,0.88)'];

  const ctx = document.getElementById('chart-pie').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: counts, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }],
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '60%',
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, usePointStyle: true } } },
    },
  });
}

// Recent table — terima array dari API
function renderRecentTable(data) {
  const tbody = document.getElementById('recent-tbody');
  if (!data || data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-inbox"></i><h4>Belum ada data pelanggaran</h4></div></td></tr>`;
    return;
  }
  tbody.innerHTML = data.map((p, idx) => {
    const waMap = {
      Terkirim: '<span class="badge badge-success"><i class="fa-solid fa-check-double"></i> Terkirim</span>',
      Pending:  '<span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Pending</span>',
      Gagal:    '<span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Gagal</span>',
    };
    return `
      <tr>
        <td style="font-size:12px;color:var(--gray-400);font-family:monospace;">${idx+1}</td>
        <td><div class="student-cell"><span class="s-name">${p.nama_siswa}</span><span class="s-nis">${p.nis}</span></div></td>
        <td><span class="badge badge-primary">${p.kelas}</span></td>
        <td><span class="badge badge-${p.jenis_color||'info'}">${p.nama_jenis}</span></td>
        <td style="font-size:13px;">${formatTanggalJS(p.tanggal)}</td>
        <td><strong style="color:var(--warning)">${p.poin}</strong> <small style="color:var(--gray-400)">poin</small></td>
        <td>${waMap[p.status_wa] || p.status_wa}</td>
        <td><a href="riwayat.html" class="btn-action btn-action-view"><i class="fa-solid fa-eye"></i> Detail</a></td>
      </tr>`;
  }).join('');
}

function renderWALegendBar(d) {
  const bar = document.getElementById('wa-legend-bar');
  if (!bar || d.wa_total === 0) return;
  bar.innerHTML = '';
  [
    { color: 'var(--success)', count: d.wa_terkirim },
    { color: 'var(--warning)', count: d.wa_pending },
    { color: 'var(--danger)',  count: d.wa_gagal },
  ].forEach(s => {
    if (s.count > 0) {
      const seg = document.createElement('div');
      const pct = (s.count / d.wa_total) * 100;
      seg.style.cssText = `flex:${pct};background:${s.color};border-radius:100px;`;
      bar.appendChild(seg);
    }
  });
}

// Format tanggal ISO → Indonesia (tanpa data.js)
function formatTanggalJS(isoDate) {
  if (!isoDate) return '-';
  const BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
  const d = new Date(isoDate + 'T00:00:00');
  return `${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;
}

function animateCount(el, target, duration = 800) {
  if (!el) return;
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) { start = target; clearInterval(timer); }
    el.textContent = Math.floor(start);
  }, 16);
}


// ============================================================
// 3. data-siswa.html — Ganti semua dummy data dengan fetch
// ============================================================

// State
let siswaCurrentPage = 1;
const SISWA_PER_PAGE = 8;

async function loadSiswa() {
  const q      = document.getElementById('search-input').value.trim();
  const kelas  = document.getElementById('filter-kelas').value;
  const gender = document.getElementById('filter-gender').value;

  const params = new URLSearchParams({
    q, kelas, gender,
    page: siswaCurrentPage,
    per_page: SISWA_PER_PAGE,
  });

  const res = await apiRequest(`api/siswa.php?${params}`);
  if (!res || !res.success) return;

  renderSiswaTable(res.data, res.meta?.pagination);
  updateSiswaStats(res.meta?.pagination?.total || 0);
}

async function saveSiswa(isEdit, id) {
  const fields  = ['nama','nis','kelas','gender','alamat','nama_ortu','wa_ortu'];
  const formData = new FormData();
  formData.append('action', isEdit ? 'edit' : 'tambah');
  if (isEdit) formData.append('id', id);
  fields.forEach(f => {
    const el = document.getElementById(`f-${f}`);
    if (el) formData.append(f, el.value);
  });

  const res = await apiRequest('api/siswa.php', { method: 'POST', body: formData });
  if (!res) return false;

  if (!res.success) {
    if (res.errors) {
      Object.entries(res.errors).forEach(([field, msg]) => {
        const el = document.getElementById(`f-${field}`);
        const errEl = document.getElementById(`err-${field}`);
        if (el) el.classList.add('is-invalid');
        if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
      });
    }
    showToast({ type: 'danger', title: 'Gagal', message: res.message });
    return false;
  }

  showToast({ type: 'success', title: 'Berhasil', message: res.message });
  return true;
}

async function deleteSiswa(id) {
  const res = await apiRequest('api/siswa.php', {
    method: 'POST',
    body: toFormData({ action: 'hapus', id }),
  });
  if (!res) return;
  showToast({
    type: res.success ? 'success' : 'danger',
    title: res.success ? 'Berhasil' : 'Gagal',
    message: res.message,
  });
  if (res.success) loadSiswa();
}

async function loadSiswaDetail(id) {
  const res = await apiRequest(`api/siswa.php?action=detail&id=${id}`);
  if (!res || !res.success) return;
  // Isi modal detail dengan res.data (sama seperti sebelumnya)
  // res.data.riwayat_pelanggaran sudah berisi array riwayat
  return res.data;
}

// Panggil saat DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
  loadKelasOptions(); // lihat di bawah
  loadSiswa();
});

// Load kelas untuk dropdown filter
async function loadKelasOptions() {
  const res = await apiRequest('api/laporan.php?tahun=2026');
  if (!res || !res.success) return;

  const kelasList = res.data.daftar_kelas || [];
  const filterEl  = document.getElementById('filter-kelas');
  const formEl    = document.getElementById('f-kelas');

  kelasList.forEach(k => {
    if (filterEl) filterEl.innerHTML += `<option value="${k}">${k}</option>`;
    if (formEl)   formEl.innerHTML   += `<option value="${k}">${k}</option>`;
  });
}


// ============================================================
// 4. input-pelanggaran.html — Ganti dummy data dengan fetch
// ============================================================

// Load daftar siswa untuk searchable dropdown
async function loadSiswaForDropdown() {
  const res = await apiRequest('api/siswa.php?per_page=100');
  if (!res || !res.success) return [];
  return res.data; // array siswa
}

// Load jenis pelanggaran untuk select
async function loadJenisPelanggaran() {
  const res = await apiRequest('api/pelanggaran.php?action=jenis');
  if (!res || !res.success) return [];
  return res.data; // array jenis
}

// Submit pelanggaran baru
async function submitPelanggaran(formData) {
  formData.append('action', 'tambah');
  const res = await apiRequest('api/pelanggaran.php', { method: 'POST', body: formData });
  return res;
}

// Contoh pemanggilan saat DOMContentLoaded:
document.addEventListener('DOMContentLoaded', async () => {
  // Load siswa ke dropdown
  const siswaList = await loadSiswaForDropdown();
  // Render ke dd-options (gunakan renderDropdownOptions() yang sudah ada)
  // — gantikan SISWA dengan siswaList

  // Load jenis ke select
  const jenisList = await loadJenisPelanggaran();
  const selJenis  = document.getElementById('f-jenis');
  if (selJenis) {
    selJenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
    jenisList.forEach(j => {
      selJenis.innerHTML += `<option value="${j.id}" data-poin="${j.poin}" data-nama="${j.nama}">
        ${j.nama} (${j.poin} poin)
      </option>`;
    });
  }
});

// Submit handler (gantikan blok btn-submit di input-pelanggaran.html)
document.getElementById('btn-submit').addEventListener('click', async function () {
  if (!validateForm()) return; // fungsi validateForm() tetap sama

  const btn     = this;
  const spinner = document.getElementById('submit-spinner');
  const text    = document.getElementById('submit-text');
  const sendWA  = document.getElementById('f-send-wa').checked;
  const selJenis = document.getElementById('f-jenis');
  const jenisId  = selJenis.value;
  const jenisNama = selJenis.options[selJenis.selectedIndex]?.dataset?.nama || '';

  btn.disabled = true;
  text.style.display   = 'none';
  spinner.style.display = 'inline-block';

  const fd = new FormData();
  fd.append('action',               'tambah');
  fd.append('siswa_id',             selectedSiswa?.id || '');
  fd.append('jenis_pelanggaran_id', jenisId);
  fd.append('tanggal',              document.getElementById('f-tanggal').value);
  fd.append('poin',                 document.getElementById('f-poin').value);
  fd.append('keterangan',           document.getElementById('f-keterangan').value);
  fd.append('kirim_wa',             sendWA ? '1' : '0');

  const res = await apiRequest('api/pelanggaran.php', { method: 'POST', body: fd });

  btn.disabled = false;
  text.style.display  = 'inline';
  spinner.style.display = 'none';

  if (!res || !res.success) {
    showToast({ type: 'danger', title: 'Gagal', message: res?.message || 'Terjadi kesalahan.' });
    return;
  }

  // Tampilkan success overlay (logika sama seperti sebelumnya)
  document.getElementById('form-body').style.display = 'none';
  document.getElementById('success-overlay').classList.add('visible');

  const waStatus = res.data?.status_wa;
  document.getElementById('success-desc').textContent = waStatus === 'Terkirim'
    ? `Pelanggaran berhasil dicatat dan notifikasi WA terkirim ke ${res.data?.wa_tujuan}.`
    : waStatus === 'Gagal'
    ? 'Pelanggaran berhasil dicatat, namun notifikasi WA gagal dikirim.'
    : 'Pelanggaran berhasil dicatat. Notifikasi WA tidak dikirim.';

  showToast({ type: 'success', title: 'Berhasil Disimpan!', message: res.message, duration: 4000 });
});


// ============================================================
// 5. riwayat.html — Ganti dummy data dengan fetch
// ============================================================

let riwayatPage = 1;
const RIWAYAT_PER_PAGE = 10;

async function loadRiwayat() {
  const q        = document.getElementById('search-input')?.value.trim() || '';
  const kelas    = document.getElementById('filter-kelas')?.value    || '';
  const jenisId  = document.getElementById('filter-jenis')?.value    || '';
  const statusWA = document.getElementById('filter-status')?.value   || '';
  const dari     = document.getElementById('filter-dari')?.value     || '';
  const sampai   = document.getElementById('filter-sampai')?.value   || '';

  const params = new URLSearchParams({
    q, kelas, jenis_id: jenisId, status_wa: statusWA,
    dari, sampai,
    page: riwayatPage,
    per_page: RIWAYAT_PER_PAGE,
  });

  const res = await apiRequest(`api/pelanggaran.php?${params}`);
  if (!res || !res.success) return;

  renderRiwayatTable(res.data);
  updateRiwayatPagination(res.meta?.pagination);
}

async function loadRiwayatDetail(id) {
  const res = await apiRequest(`api/pelanggaran.php?action=detail&id=${id}`);
  if (!res || !res.success) return null;
  return res.data;
}

async function deleteRiwayat(id) {
  const res = await apiRequest('api/pelanggaran.php', {
    method: 'POST',
    body: toFormData({ action: 'hapus', id }),
  });
  if (!res) return;
  showToast({
    type: res.success ? 'success' : 'danger',
    title: res.success ? 'Dihapus' : 'Gagal',
    message: res.message,
  });
  if (res.success) loadRiwayat();
}

async function resendWA(pelanggaranId) {
  const res = await apiRequest('api/wa.php', {
    method: 'POST',
    body: toFormData({ action: 'resend', pelanggaran_id: pelanggaranId }),
  });
  if (!res) return;
  showToast({
    type: res.success ? 'success' : 'danger',
    title: res.success ? 'WA Terkirim' : 'WA Gagal',
    message: res.message,
  });
  if (res.success) loadRiwayat();
}

// Load jenis untuk dropdown filter
async function loadJenisOptions() {
  const res = await apiRequest('api/pelanggaran.php?action=jenis');
  if (!res || !res.success) return;
  const el = document.getElementById('filter-jenis');
  if (!el) return;
  el.innerHTML = '<option value="">Semua Jenis</option>';
  res.data.forEach(j => {
    el.innerHTML += `<option value="${j.id}">${j.nama}</option>`;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  loadJenisOptions();
  loadKelasOptions();
  loadRiwayat();
});


// ============================================================
// 6. laporan.html — Ganti dummy data dengan fetch
// ============================================================

async function loadLaporan() {
  const bulan = document.getElementById('filter-bulan')?.value || '';
  const tahun = document.getElementById('filter-tahun')?.value || new Date().getFullYear();
  const kelas = document.getElementById('filter-kelas')?.value || '';

  const params = new URLSearchParams({ bulan, tahun, kelas });
  const res    = await apiRequest(`api/laporan.php?${params}`);
  if (!res || !res.success) return;

  const d = res.data;

  // Summary cards
  document.getElementById('l-total').textContent      = d.summary.total_pelanggaran;
  document.getElementById('l-top-jenis').textContent  = d.summary.top_jenis || '-';
  document.getElementById('l-top-kelas').textContent  = d.summary.top_kelas || '-';
  document.getElementById('l-wa-rate').textContent    = d.summary.wa_success_rate + '%';

  // Charts
  renderLaporanBarChart(d.chart_bulanan);
  renderLaporanPieChart(d.chart_kelas);

  // Tabel detail
  renderLaporanTable(d.detail);

  // Kelas dropdown
  const kelasEl = document.getElementById('filter-kelas');
  if (kelasEl && kelasEl.options.length <= 1) {
    d.daftar_kelas.forEach(k => {
      kelasEl.innerHTML += `<option value="${k}">${k}</option>`;
    });
  }
}

function renderLaporanBarChart(chartBulanan) {
  const ctx = document.getElementById('chart-monthly')?.getContext('2d');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartBulanan.map(b => b.bulan),
      datasets: [{
        label: 'Pelanggaran',
        data: chartBulanan.map(b => b.jumlah),
        backgroundColor: 'rgba(26,79,160,0.8)',
        borderRadius: 5,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
    },
  });
}

function renderLaporanPieChart(chartKelas) {
  const ctx = document.getElementById('chart-class')?.getContext('2d');
  if (!ctx) return;
  const colors = ['rgba(26,79,160,0.85)','rgba(22,163,74,0.85)','rgba(220,38,38,0.85)',
                  'rgba(217,119,6,0.85)','rgba(124,58,237,0.85)','rgba(8,145,178,0.85)'];
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: chartKelas.map(k => k.kelas),
      datasets: [{
        data: chartKelas.map(k => k.jumlah),
        backgroundColor: colors,
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
    },
  });
}

document.addEventListener('DOMContentLoaded', loadLaporan);

// ============================================================
// AUTH — Logout (gantikan di SEMUA halaman)
// ============================================================

// Gantikan semua event listener data-logout dengan:
document.querySelectorAll('[data-logout]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const res = await apiRequest('auth.php', {
      method: 'POST',
      body: toFormData({ action: 'logout' }),
    });
    sessionStorage.removeItem('sigap_user');
    window.location.href = 'login.html';
  });
});

// Gantikan requireAuth() dengan:
function requireAuth() {
  const user = sessionStorage.getItem('sigap_user');
  if (!user) {
    window.location.href = 'login.html';
    return null;
  }
  try { return JSON.parse(user); } catch { return null; }
}
// Catatan: session PHP yang sebenarnya divalidasi di server.
// sessionStorage hanya untuk menampilkan nama user di UI.
