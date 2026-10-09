/**
 * ============================================================
 * SIGAP — api.js
 * Core utilities for API fetches
 * ============================================================
 */

// Konfigurasi Sistem (guard agar tidak konflik dengan data.js)
if (typeof SIGAP_CONFIG === 'undefined') {
  var SIGAP_CONFIG = {
    namaSekolah: 'SMK Negeri 1 Surakarta',
    namaSistem: 'SIGAP',
    tahunAjaran: '2025/2026',
    semester: 'Genap',
    guruBK: 'Dra. Sri Wahyuni, M.Pd.',
    nomorWABK: '08112345678',
  };
}

/**
 * Wrapper fetch ke API dengan handling error otomatis.
 * @param {string} url - base url relative
 * @param {object} options — fetch options
 * @returns {Promise<object>} — parsed JSON response
 */
async function apiRequest(url, options = {}) {
  try {
    // If we are in pages/ we might need to adjust relative path or we just use absolute from root
    // To handle being in the root (login.html) or pages/ (dashboard.html) gracefully:
    const isPagesDir = window.location.pathname.includes('/pages/');
    const baseUrl = isPagesDir ? '../' : './';
    const fetchUrl = url.startsWith('http') ? url : baseUrl + url;

    const res = await fetch(fetchUrl, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      ...options,
    });

    // Ambil raw text dulu supaya bisa didiagnosis kalau bukan JSON valid
    // (mis. PHP mengeluarkan Warning/Fatal error sebelum JSON)
    const rawText = await res.text();
    let json;
    try {
      json = JSON.parse(rawText);
    } catch (parseErr) {
      console.error('Respons server bukan JSON valid. Raw response dari server:\n', rawText);
      if (typeof showToast === 'function') {
        showToast({
          type: 'danger',
          title: 'Server Error',
          message: 'Server mengembalikan respons tidak valid. Cek console untuk detail.',
        });
      }
      return null;
    }

    if (res.status === 401) {
      window.location.href = isPagesDir ? '../pages/login.html' : 'pages/login.html';
      return null;
    }

    return json;
  } catch (err) {
    console.error('API Request gagal:', err);
    if (typeof showToast === 'function') {
      showToast({
        type: 'danger',
        title: 'Koneksi Gagal',
        message: 'Tidak dapat terhubung ke server. Pastikan server berjalan.',
      });
    }
    return null;
  }
}

/**
 * Buat FormData dari objek biasa.
 */
function toFormData(data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v ?? ''));
  return fd;
}

/**
 * Format tanggal ISO -> Indonesia
 */
function formatTanggalJS(isoDate) {
  if (!isoDate) return '-';
  const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
  const d = new Date(isoDate + 'T00:00:00');
  return `${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;
}

/**
 * Format tanggal -> 14 Maret 2026 format (for riwayat table)
 */
function formatTanggal(isoDate, withDay = false) {
  if (!isoDate) return '-';
  const BULAN_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ];
  const HARI_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const d = new Date(isoDate + 'T00:00:00');
  const tgl = d.getDate();
  const bln = BULAN_ID[d.getMonth()];
  const thn = d.getFullYear();
  const hari = HARI_ID[d.getDay()];
  return withDay ? `${hari}, ${tgl} ${bln} ${thn}` : `${tgl} ${bln} ${thn}`;
}

/* Format Waktu */
function formatWaktu(waktu) {
  if (!waktu) return '-';
  const parts = waktu.split(' ');
  return parts[1] || '-';
}

function getTodayISO() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${dd}`;
}

function getNowTime() {
  const d = new Date();
  const h = String(d.getHours()).padStart(2, '0');
  const m = String(d.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
}

/**
 * Count animation
 */
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

/**
 * AUTHENICATION (Require Auth)
 */
function requireAuth() {
  const user = sessionStorage.getItem('sigap_user');
  if (!user) {
    const isPagesDir = window.location.pathname.includes('/pages/');
    window.location.href = isPagesDir ? 'login.html' : 'pages/login.html';
    return null;
  }
  try { return JSON.parse(user); } catch { return null; }
}

// Global logout handler
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-logout]').forEach(btn => {
    btn.addEventListener('click', async () => {
      await apiRequest('api/auth.php', {
        method: 'POST',
        body: toFormData({ action: 'logout' }),
      });
      sessionStorage.removeItem('sigap_user');
      const isPagesDir = window.location.pathname.includes('/pages/');
      window.location.href = isPagesDir ? 'login.html' : 'pages/login.html';
    });
  });
});

/* ============================================================
   UI HELPERS MIGRATED FROM DATA.JS
   ============================================================ */

/**
 * Validasi field input — set class is-invalid + tampilkan error
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
 */
function clearValidation(formEl) {
  formEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  formEl.querySelectorAll('.form-error').forEach(el => { el.style.display = 'none'; });
}

/**
 * Tampilkan toast notifikasi
 */
function showToast({ type = 'success', title = '', message = '', duration = 3500 } = {}) {
  const ICONS = {
    success: 'fa-circle-check',
    danger: 'fa-circle-xmark',
    warning: 'fa-triangle-exclamation',
    info: 'fa-circle-info',
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
      ${title ? `<div class="toast-title">${title}</div>` : ''}
      ${message ? `<div class="toast-body">${message}</div>` : ''}
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

/**
 * Modal Utility
 */
function openModal(modalId) {
  const overlay = document.getElementById(modalId);
  if (!overlay) return;
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
  const overlay = document.getElementById(modalId);
  if (!overlay) return;
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal(overlay.id);
    });
    overlay.querySelectorAll('[data-modal-close]').forEach(btn => {
      btn.addEventListener('click', () => closeModal(overlay.id));
    });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(m => {
        closeModal(m.id);
      });
    }
  });
}

/**
 * Sidebar & Layout
 */
function initTopbar(pageTitle = '') {
  const user = requireAuth();
  const topbarTitle = document.getElementById('topbar-title');
  const topbarUser = document.getElementById('topbar-username');
  const topbarAvatar = document.getElementById('topbar-avatar');

  if (topbarTitle && pageTitle) topbarTitle.textContent = pageTitle;
  if (topbarUser && user) topbarUser.textContent = user.nama;
  if (topbarAvatar && user) topbarAvatar.textContent = user.avatar || 'U';
}

function initSidebarActive() {
  const currentFile = window.location.pathname.split('/').pop() || 'dashboard.html';
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentFile) {
      link.classList.add('active');
    }
  });
}

function initHamburger() {
  const hamburger = document.getElementById('hamburger-btn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');

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

function initLayout(pageTitle = '') {
  initTopbar(pageTitle);
  initSidebarActive();
  initHamburger();
}

/**
 * Constants / Defaults
 */
if (typeof CHART_DEFAULTS === 'undefined') {
  var CHART_DEFAULTS = {
    font: { family: "'Inter', sans-serif", size: 12 },
    color: '#64748b',
  };
}

if (typeof CHART_COLORS === 'undefined') {
  var CHART_COLORS = {
    blue: 'rgba(26,  79, 160, 0.85)',
    red: 'rgba(220,  38,  38, 0.85)',
    orange: 'rgba(217, 119,   6, 0.85)',
    green: 'rgba( 22, 163,  74, 0.85)',
    purple: 'rgba(124,  58, 237, 0.85)',
    cyan: 'rgba(  8, 145, 178, 0.85)',
    pink: 'rgba(219,  39, 119, 0.85)',
    teal: 'rgba( 20, 184, 166, 0.85)',
    blueLight: 'rgba(26,  79, 160, 0.15)',
    redLight: 'rgba(220,  38,  38, 0.15)',
    orangeLight: 'rgba(217, 119,   6, 0.15)',
    greenLight: 'rgba( 22, 163,  74, 0.15)',
    purpleLight: 'rgba(124,  58, 237, 0.15)',
    pie: [
      'rgba( 26,  79, 160, 0.88)', 'rgba( 22, 163,  74, 0.88)',
      'rgba(220,  38,  38, 0.88)', 'rgba(217, 119,   6, 0.88)',
      'rgba(124,  58, 237, 0.88)', 'rgba(  8, 145, 178, 0.88)',
      'rgba(219,  39, 119, 0.88)',
    ],
  };
}