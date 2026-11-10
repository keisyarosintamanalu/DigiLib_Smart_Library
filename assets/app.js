(() => {
  'use strict';

  const DATA_KEY = 'digilib-smart-library-data-v1';
  const SESSION_KEY = 'digilib-smart-library-session-v1';
  const FINE_PER_DAY = 2000;
  const MAX_ACTIVE_LOANS = 3;
  const LOAN_DAYS = 7;
  const app = document.getElementById('app');
  const modalRoot = document.getElementById('modal-root');
  const toastRoot = document.getElementById('toast-root');
  const icons = {
    grid: '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    book: '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    repeat: '<path d="m17 2 4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/>',
    return: '<path d="m9 14-4-4 4-4"/><path d="M5 10h10a5 5 0 0 1 0 10h-2"/>',
    receipt: '<path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2l-4 2-3-2-3 2-3-2z"/><path d="M8 8h8M8 12h8M8 16h4"/>',
    chart: '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-5 5"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    chevron: '<path d="m9 18 6-6-6-6"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    close: '<path d="m18 6-12 12M6 6l12 12"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
    trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    'eye-off': '<path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a14 14 0 0 1-3.2 4.1M6.2 6.2C3.5 8.1 2 12 2 12s3.5 7 10 7a10.5 10.5 0 0 0 4-.8"/>',
    printer: '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    spark: '<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-2-5.8L4 11l6-2.2L12 3Z"/><path d="m19 14 1 2.5 2.5 1-2.5 1L19 21l-1-2.5-2.5-1 2.5-1L19 14Z"/>'
  };
  const navItems = [
    { id: 'dashboard', label: 'Dashboard', icon: 'grid', roles: ['admin', 'member'] },
    { id: 'books', label: 'Data Buku', icon: 'book', roles: ['admin', 'member'] },
    { id: 'members', label: 'Data Anggota', icon: 'users', roles: ['admin'] },
    { id: 'loans', label: 'Peminjaman', icon: 'repeat', roles: ['admin', 'member'] },
    { id: 'returns', label: 'Pengembalian', icon: 'return', roles: ['admin'] },
    { id: 'fines', label: 'Denda', icon: 'receipt', roles: ['admin'] },
    { id: 'reports', label: 'Laporan', icon: 'chart', roles: ['admin'] }
  ];
  const coverColors = ['#0f766e', '#a65b3e', '#5576a8', '#8a6ab2', '#37806a', '#a57b2d'];

  function makeId(prefix) {
    return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;
  }

  function dayOffset(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date.toISOString();
  }

  function createSeedData() {
    const memberId = 'member-001';
    return {
      users: [
        { id: 'user-admin', role: 'admin', name: 'Nadia Pratama', email: 'admin@digilib.id', password: 'admin123' },
        { id: 'user-member-001', role: 'member', memberId, name: 'Raka Putra', email: 'anggota@digilib.id', password: 'anggota123' },
        { id: 'user-member-002', role: 'member', memberId: 'member-002', name: 'Sinta Maharani', email: 'sinta@digilib.id', password: 'sinta123' },
        { id: 'user-member-003', role: 'member', memberId: 'member-003', name: 'Bima Saputra', email: 'bima@digilib.id', password: 'bima123' },
        { id: 'user-member-004', role: 'member', memberId: 'member-004', name: 'Alya Permata', email: 'alya@digilib.id', password: 'alya123' }
      ],
      members: [
        { id: memberId, name: 'Raka Putra', email: 'anggota@digilib.id', phone: '0812-3456-7890', address: 'Jl. Melati No. 12, Bandung', joinedAt: '2025-01-15', status: 'Aktif' },
        { id: 'member-002', name: 'Sinta Maharani', email: 'sinta@digilib.id', phone: '0813-2222-4567', address: 'Jl. Cendana No. 8, Bandung', joinedAt: '2025-03-02', status: 'Aktif' },
        { id: 'member-003', name: 'Bima Saputra', email: 'bima@digilib.id', phone: '0821-7788-9911', address: 'Jl. Kenanga No. 21, Cimahi', joinedAt: '2025-04-18', status: 'Aktif' },
        { id: 'member-004', name: 'Alya Permata', email: 'alya@digilib.id', phone: '0857-1234-8866', address: 'Jl. Anggrek No. 5, Bandung', joinedAt: '2025-06-11', status: 'Aktif' }
      ],
      books: [
        { id: 'book-001', title: 'Laut Bercerita', author: 'Leila S. Chudori', publisher: 'Kepustakaan Populer Gramedia', year: 2017, category: 'Fiksi', shelf: 'A-01', stock: 4 },
        { id: 'book-002', title: 'Atomic Habits', author: 'James Clear', publisher: 'Gramedia Pustaka Utama', year: 2019, category: 'Pengembangan Diri', shelf: 'B-03', stock: 5 },
        { id: 'book-003', title: 'Filosofi Teras', author: 'Henry Manampiring', publisher: 'Kompas', year: 2018, category: 'Filsafat', shelf: 'C-02', stock: 3 },
        { id: 'book-004', title: 'Bumi Manusia', author: 'Pramoedya Ananta Toer', publisher: 'Lentera Dipantara', year: 1980, category: 'Fiksi', shelf: 'A-02', stock: 2 },
        { id: 'book-005', title: 'Sapiens', author: 'Yuval Noah Harari', publisher: 'Kepustakaan Populer Gramedia', year: 2017, category: 'Sejarah', shelf: 'D-01', stock: 4 },
        { id: 'book-006', title: 'Laskar Pelangi', author: 'Andrea Hirata', publisher: 'Bentang Pustaka', year: 2005, category: 'Fiksi', shelf: 'A-03', stock: 0 },
        { id: 'book-007', title: 'Dasar-Dasar Pemrograman Web', author: 'R. S. Pressman', publisher: 'Informatika', year: 2021, category: 'Teknologi', shelf: 'E-01', stock: 3 },
        { id: 'book-008', title: 'Filosofi untuk Hidup dan Bertahan', author: 'Massimo Pigliucci', publisher: 'Kepustakaan Populer Gramedia', year: 2020, category: 'Filsafat', shelf: 'C-03', stock: 2 }
      ],
      loans: [
        { id: 'loan-sample-001', bookId: 'book-001', memberId: 'member-002', borrowedAt: dayOffset(-3), dueAt: dayOffset(4), returnedAt: null, status: 'Dipinjam', fine: 0, finePaid: false },
        { id: 'loan-sample-002', bookId: 'book-003', memberId: 'member-003', borrowedAt: dayOffset(-12), dueAt: dayOffset(-5), returnedAt: null, status: 'Dipinjam', fine: 0, finePaid: false },
        { id: 'loan-sample-003', bookId: 'book-005', memberId: 'member-001', borrowedAt: dayOffset(-18), dueAt: dayOffset(-11), returnedAt: dayOffset(-9), status: 'Dikembalikan', fine: 4000, finePaid: false }
      ],
      createdAt: new Date().toISOString()
    };
  }

  function loadData() {
    try {
      const stored = localStorage.getItem(DATA_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && Array.isArray(parsed.users) && Array.isArray(parsed.books) && Array.isArray(parsed.members) && Array.isArray(parsed.loans)) return parsed;
      }
    } catch (error) {
      console.error('Gagal membaca data perpustakaan dari localStorage:', error);
      notify('Data tersimpan tidak dapat dibaca. Pulihkan data contoh untuk melanjutkan.', 'error');
    }
    const initial = createSeedData();
    localStorage.setItem(DATA_KEY, JSON.stringify(initial));
    return initial;
  }

  let data = loadData();
  let currentUser = findSessionUser();
  let currentPage = 'dashboard';
  let bookSearch = '';
  let bookCategory = '';
  let memberSearch = '';
  let loanSearch = '';
  let reportType = 'books';
  let categoryChart = null;

  function findSessionUser() {
    try {
      const userId = localStorage.getItem(SESSION_KEY);
      return userId ? data.users.find(user => user.id === userId) || null : null;
    } catch (error) {
      console.error('Gagal membaca sesi login:', error);
      return null;
    }
  }

  function saveData() {
    try {
      localStorage.setItem(DATA_KEY, JSON.stringify(data));
    } catch (error) {
      console.error('Gagal menyimpan data perpustakaan:', error);
      notify('Data tidak dapat disimpan. Periksa ruang penyimpanan browser.', 'error');
      return false;
    }
    return true;
  }

  function saveSession(userId) {
    try {
      if (userId) localStorage.setItem(SESSION_KEY, userId);
      else localStorage.removeItem(SESSION_KEY);
      return true;
    } catch (error) {
      console.error('Gagal menyimpan sesi login:', error);
      notify('Sesi login tidak dapat disimpan pada browser ini.', 'error');
      return false;
    }
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
  }

  function icon(name, size = 18) {
    return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[name] || icons.spark}</svg>`;
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(amount) || 0);
  }

  function formatDate(dateValue) {
    if (!dateValue) return '—';
    const date = new Date(dateValue);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
  }

  function dateOnly(dateValue) {
    if (!dateValue) return '';
    const date = new Date(dateValue);
    return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
  }

  function bookById(id) { return data.books.find(book => book.id === id); }
  function memberById(id) { return data.members.find(member => member.id === id); }
  function activeLoans() { return data.loans.filter(loan => loan.status === 'Dipinjam'); }
  function activeLoansForMember(id) { return activeLoans().filter(loan => loan.memberId === id); }
  function totalBookCopies() { return data.books.reduce((sum, book) => sum + Number(book.stock || 0) + activeLoans().filter(loan => loan.bookId === book.id).length, 0); }
  function outstandingFines() { return data.loans.filter(loan => loan.status === 'Dikembalikan' && !loan.finePaid).reduce((sum, loan) => sum + Number(loan.fine || 0), 0); }
  function overdueDays(dueAt, returnAt = new Date()) {
    const due = new Date(dueAt);
    const actual = new Date(returnAt);
    if (Number.isNaN(due.getTime()) || Number.isNaN(actual.getTime())) return 0;
    return Math.max(0, Math.floor((new Date(actual.getFullYear(), actual.getMonth(), actual.getDate()) - new Date(due.getFullYear(), due.getMonth(), due.getDate())) / 86400000));
  }

  function notify(message, type = 'success') {
    const node = document.createElement('div');
    const color = type === 'error' ? 'bg-red-600' : type === 'warning' ? 'bg-amber-600' : 'bg-ink';
    node.className = `toast ${color} mb-2 flex max-w-sm items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl`;
    node.innerHTML = `<span class="grid h-6 w-6 place-items-center rounded-full bg-white/15">${icon(type === 'error' ? 'close' : 'check', 15)}</span><span>${escapeHtml(message)}</span>`;
    toastRoot.appendChild(node);
    window.setTimeout(() => node.remove(), 3600);
  }

  function openModal(content) {
    modalRoot.innerHTML = `<div class="modal-backdrop" data-dismiss-modal><section class="modal-card" role="dialog" aria-modal="true">${content}</section></div>`;
    const firstInput = modalRoot.querySelector('input,select,textarea,button');
    if (firstInput) firstInput.focus();
  }

  function closeModal() { modalRoot.innerHTML = ''; }

  function pageTitle() {
    const item = navItems.find(nav => nav.id === currentPage);
    return item ? item.label : 'Dashboard';
  }

  function renderLanding() {
    app.innerHTML = `
      <main class="landing-page" id="home">
        <header class="landing-nav">
          <a class="landing-brand" href="#home" aria-label="DIGILIB Smart Library, beranda">
            <span class="landing-brand-mark">${icon('book', 22)}</span>
            <span><strong>DIGILIB</strong><small>SMART LIBRARY</small></span>
          </a>
          <nav class="landing-nav-links" id="landing-nav-links" aria-label="Navigasi utama">
            <a href="#home" data-action="close-landing-menu">Home</a>
            <a href="#about" data-action="close-landing-menu">About</a>
            <a href="#features" data-action="close-landing-menu">Features</a>
            <a href="#services" data-action="close-landing-menu">Services</a>
            <a href="#contact" data-action="close-landing-menu">Contact</a>
            <button class="landing-mobile-login" type="button" data-action="open-login">Login</button>
            <button class="landing-mobile-cta" type="button" data-action="open-login">Get Started ${icon('chevron', 16)}</button>
          </nav>
          <div class="landing-nav-actions">
            <button class="landing-login-button" type="button" data-action="open-login">Login</button>
            <button class="landing-cta-button" type="button" data-action="open-login">Get Started ${icon('chevron', 16)}</button>
          </div>
          <button class="landing-menu-button" type="button" data-action="toggle-landing-menu" aria-label="Buka navigasi" aria-controls="landing-nav-links" aria-expanded="false">${icon('menu', 21)}</button>
        </header>

        <section class="landing-hero" aria-labelledby="landing-title">
          <div class="landing-hero-content">
            <p class="landing-eyebrow"><span></span> DIGILIB SMART LIBRARY</p>
            <h1 id="landing-title">Discover Knowledge.<br><em>Explore Possibilities.</em></h1>
            <p class="landing-hero-subtitle">Your Digital Gateway to Knowledge and Learning.</p>
            <p class="landing-hero-description">DIGILIB SMART LIBRARY adalah sistem informasi perpustakaan digital yang membantu pengguna mengakses, menemukan, dan mengelola koleksi buku serta aktivitas perpustakaan secara lebih mudah, cepat, dan terorganisir.</p>
            <div class="landing-hero-actions">
              <button class="landing-cta-button landing-cta-light" type="button" data-action="open-login">Explore Library ${icon('chevron', 17)}</button>
              <button class="landing-text-link" type="button" data-action="open-login">Get Started <span>${icon('chevron', 15)}</span></button>
            </div>
          </div>
          <a class="landing-scroll-cue" href="#about" aria-label="Lanjut ke informasi DIGILIB"><span></span> Scroll to explore</a>
          <div class="landing-hero-index" aria-hidden="true">01 <span></span> DIGITAL LIBRARY</div>
        </section>

        <section class="landing-about landing-section" id="about">
          <div class="landing-section-kicker">ABOUT DIGILIB <span>01 / 04</span></div>
          <div class="landing-about-grid">
            <h2>More Than<br><em>Just a Library</em></h2>
            <div class="landing-about-copy">
              <p class="landing-lead">Satu ruang digital untuk koleksi, anggota, dan aktivitas perpustakaan.</p>
              <p>DIGILIB SMART LIBRARY mendukung pengelolaan perpustakaan dari katalog buku hingga administrasi peminjaman, pengembalian, dan laporan. Informasi disusun dalam satu alur yang mudah dijelajahi oleh pengelola maupun anggota.</p>
              <button class="landing-inline-link" type="button" data-action="open-login">Masuk dan jelajahi sistem ${icon('chevron', 16)}</button>
            </div>
          </div>
        </section>

        <section class="landing-features landing-section" id="features">
          <div class="landing-section-kicker">WHAT YOU CAN DO <span>02 / 04</span></div>
          <div class="landing-section-heading">
            <div><h2>Everything in its<br><em>right place.</em></h2></div>
            <p>Alat yang dibutuhkan untuk menjaga aktivitas perpustakaan tetap tertata, dari katalog sampai laporan.</p>
          </div>
          <div class="landing-feature-grid">
            <article class="landing-feature"><span class="landing-feature-icon">${icon('book', 21)}</span><h3>Book Management</h3><p>Kelola ISBN, detail bibliografi, kategori, stok, dan cover koleksi.</p></article>
            <article class="landing-feature"><span class="landing-feature-icon">${icon('users', 21)}</span><h3>Member Management</h3><p>Atur profil, akun, status aktif, dan informasi keanggotaan.</p></article>
            <article class="landing-feature"><span class="landing-feature-icon">${icon('grid', 21)}</span><h3>Organized Catalog</h3><p>Temukan buku melalui pencarian judul, ISBN, penulis, atau kategori.</p></article>
            <article class="landing-feature"><span class="landing-feature-icon">${icon('repeat', 21)}</span><h3>Book Circulation</h3><p>Pantau peminjaman, jatuh tempo, dan riwayat transaksi anggota.</p></article>
            <article class="landing-feature"><span class="landing-feature-icon">${icon('receipt', 21)}</span><h3>Returns & Fines</h3><p>Catat pengembalian, hitung keterlambatan, dan pantau status denda.</p></article>
            <article class="landing-feature"><span class="landing-feature-icon">${icon('chart', 21)}</span><h3>Library Reports</h3><p>Tinjau laporan buku, anggota, sirkulasi, pengembalian, dan denda.</p></article>
          </div>
        </section>

        <section class="landing-services landing-section" id="services">
          <div class="landing-section-kicker">HOW IT WORKS <span>03 / 04</span></div>
          <div class="landing-section-heading">
            <div><h2>A clear path<br><em>to every book.</em></h2></div>
            <p>Mulai dari menemukan layanan hingga mengelola aktivitas sesuai peran pengguna.</p>
          </div>
          <ol class="landing-steps">
            <li><span>01</span><div><h3>Discover</h3><p>Kenali koleksi dan layanan perpustakaan.</p></div></li>
            <li><span>02</span><div><h3>Access</h3><p>Masuk dengan akun admin atau anggota.</p></div></li>
            <li><span>03</span><div><h3>Explore</h3><p>Buka katalog dan fitur sesuai hak akses.</p></div></li>
            <li><span>04</span><div><h3>Manage</h3><p>Kelola aktivitas perpustakaan secara teratur.</p></div></li>
          </ol>
        </section>

        <section class="landing-benefits landing-section">
          <div class="landing-section-kicker">BUILT FOR BETTER FLOW <span>04 / 04</span></div>
          <div class="landing-benefits-grid">
            <h2>Less friction.<br><em>More discovery.</em></h2>
            <ul>
              <li><span>${icon('check', 17)}</span> Efficient library management</li>
              <li><span>${icon('check', 17)}</span> Organized book collections</li>
              <li><span>${icon('check', 17)}</span> Simplified circulation process</li>
              <li><span>${icon('check', 17)}</span> Better data management</li>
              <li><span>${icon('check', 17)}</span> Accessible digital experience</li>
            </ul>
          </div>
        </section>

        <section class="landing-contact" id="contact">
          <p class="landing-eyebrow"><span></span> YOUR NEXT CHAPTER STARTS HERE</p>
          <h2>Start Your Digital Library<br><em>Journey Today.</em></h2>
          <p>Experience a more organized and efficient way to manage library activities.</p>
          <button class="landing-cta-button landing-cta-light" type="button" data-action="open-login">Access Your Library ${icon('chevron', 17)}</button>
        </section>

        <footer class="landing-footer">
          <a class="landing-brand" href="#home" aria-label="DIGILIB Smart Library, kembali ke atas">
            <span class="landing-brand-mark">${icon('book', 21)}</span>
            <span><strong>DIGILIB</strong><small>SMART LIBRARY</small></span>
          </a>
          <p>Ruang digital untuk mengelola dan menjelajahi layanan perpustakaan.</p>
          <nav aria-label="Tautan footer"><a href="#about">About</a><a href="#features">Features</a><a href="#services">Services</a><button type="button" data-action="open-login">Login</button></nav>
          <small class="landing-copyright">© ${new Date().getFullYear()} DIGILIB SMART LIBRARY</small>
        </footer>
      </main>`;
  }

  function renderLogin() {
    app.innerHTML = `
      <main class="login-bg login-page">
        <div class="login-card">
          <section class="login-brand-panel text-white">
            <div class="login-brand-identity">
              <span class="login-brand-mark">${icon('book', 25)}</span>
              <span><span class="login-brand-name">DIGILIB</span><span class="login-brand-subtitle">SMART LIBRARY</span></span>
            </div>
            <div class="login-brand-copy">
              <p class="login-brand-eyebrow">YOUR GATEWAY TO KNOWLEDGE</p>
              <h1 class="login-brand-heading">Setiap Buku, Membuka Dunia Baru.</h1>
              <p class="login-brand-description">Temukan inspirasi dari setiap halaman, jelajahi beragam koleksi bacaan, dan bangun pengalaman literasi yang lebih bermakna bersama DIGILIB.</p>
            </div>
          </section>
          <section class="login-form-panel">
            <div class="login-form-heading">
              <button class="login-back-link" type="button" data-action="open-landing">${icon('chevron', 15)} Kembali ke beranda</button>
              <p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-teal-700">Selamat datang kembali</p>
              <h2 class="display-font text-2xl font-extrabold">Masuk ke akun Anda</h2>
              <p class="mt-2 text-sm text-slate-500">Masuk sebagai admin atau anggota perpustakaan.</p>
            </div>
            <form id="login-form" class="space-y-5">
              <div><label class="field-label" for="login-email">Email</label><input class="field" id="login-email" name="email" type="email" autocomplete="username" placeholder="nama@email.com" required></div>
              <div><label class="field-label" for="login-password">Kata sandi</label><div class="login-password-control"><input class="field" id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required><button class="login-password-toggle" type="button" data-action="toggle-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi">${icon('eye', 17)}</button></div></div>
              <p id="login-error" class="hidden rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-700" role="alert"></p>
              <button class="btn btn-primary w-full py-3" type="submit">Masuk ke perpustakaan ${icon('chevron', 16)}</button>
            </form>
            <div class="mt-7 border-t border-slate-100 pt-5">
              <div class="mb-3 flex items-center justify-between"><span class="text-xs font-bold text-slate-500">AKUN DEMO</span><button class="text-xs font-bold text-teal-700 hover:underline" data-action="restore-seed">Pulihkan data contoh</button></div>
              <div class="grid gap-2 sm:grid-cols-2">
                <button class="rounded-xl border border-slate-200 p-3 text-left hover:border-teal-400 hover:bg-teal-50" data-demo="admin"><span class="block text-xs font-bold text-slate-700">Admin / Pustakawan</span><span class="mt-1 block text-[11px] text-slate-500">admin@digilib.id</span></button>
                <button class="rounded-xl border border-slate-200 p-3 text-left hover:border-teal-400 hover:bg-teal-50" data-demo="member"><span class="block text-xs font-bold text-slate-700">Anggota</span><span class="mt-1 block text-[11px] text-slate-500">anggota@digilib.id</span></button>
              </div>
            </div>
            <p class="mt-5 text-center text-[11px] leading-5 text-slate-400">Data tersimpan secara lokal pada browser ini. Gunakan akun demo untuk mencoba aplikasi.</p>
          </section>
        </div>
      </main>`;
  }

  function renderShell() {
    const visibleItems = navItems.filter(item => item.roles.includes(currentUser.role));
    const initial = (currentUser.name || 'U').trim().charAt(0).toUpperCase();
    app.innerHTML = `
      <div class="app-shell flex">
        <aside class="sidebar flex min-h-screen flex-col border-r border-slate-100 bg-white px-4 py-5">
          <div class="mb-9 flex items-center gap-3 px-2">
            <div class="grid h-11 w-11 flex-none place-items-center rounded-2xl bg-teal-700 text-white">${icon('book', 23)}</div>
            <div class="brand-copy min-w-0"><div class="display-font text-sm font-extrabold tracking-wide text-ink">DIGILIB</div><div class="text-[10px] font-semibold tracking-[.15em] text-slate-400">SMART LIBRARY</div></div>
          </div>
          <div class="nav-label mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[.16em] text-slate-400">Menu utama</div>
          <nav class="space-y-1">
            ${visibleItems.map(item => `<button class="nav-link ${currentPage === item.id ? 'active' : ''} flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-500" data-page="${item.id}" title="${item.label}"><span class="nav-icon">${icon(item.icon, 18)}</span><span class="nav-label">${item.label}</span></button>`).join('')}
          </nav>
          <div class="mt-auto pt-8">
            <div class="sidebar-footer flex items-center gap-3 rounded-2xl bg-sand p-3">
              <div class="grid h-10 w-10 flex-none place-items-center rounded-full bg-teal-100 font-bold text-teal-700">${escapeHtml(initial)}</div>
              <div class="sidebar-footer-copy min-w-0 flex-1"><div class="truncate text-xs font-bold text-ink">${escapeHtml(currentUser.name)}</div><div class="text-[11px] text-slate-500">${currentUser.role === 'admin' ? 'Admin / Pustakawan' : 'Anggota'}</div></div>
              <button class="icon-btn no-print" data-action="logout" aria-label="Keluar" title="Keluar">${icon('logout', 16)}</button>
            </div>
          </div>
        </aside>
        <main class="main-panel min-h-screen flex-1">
          <header class="topbar flex h-[76px] items-center justify-between border-b border-slate-100 bg-white px-5 sm:px-8">
            <div class="flex items-center gap-3">
              <button class="mobile-menu icon-btn" data-action="toggle-menu" aria-label="Buka menu">${icon('menu', 19)}</button>
              <div><div class="text-[11px] font-semibold text-slate-400">DIGILIB SMART LIBRARY</div><div class="display-font text-sm font-extrabold text-ink">${pageTitle()}</div></div>
            </div>
            <div class="flex items-center gap-3">
              <div class="hidden text-right sm:block"><div class="text-xs font-bold text-ink">${escapeHtml(currentUser.name)}</div><div class="text-[11px] text-slate-400">${new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())}</div></div>
              <div class="grid h-10 w-10 place-items-center rounded-full bg-teal-100 text-sm font-extrabold text-teal-700">${escapeHtml(initial)}</div>
            </div>
          </header>
          <section class="content-area p-5 sm:p-8">${renderPageContent()}</section>
        </main>
      </div>`;
    if (currentPage === 'dashboard') renderCategoryChart();
  }

  function pageHeader(title, subtitle, actions = '') {
    return `<div class="page-header mb-6 flex items-center justify-between gap-4"><div><h1 class="display-font text-xl font-extrabold text-ink">${title}</h1><p class="mt-1 text-sm text-slate-500">${subtitle}</p></div><div class="flex flex-wrap gap-2">${actions}</div></div>`;
  }

  function statCard(label, value, note, iconName, tint, footnote) {
    return `<article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold text-slate-500">${label}</p><p class="display-font mt-3 text-2xl font-extrabold text-ink">${value}</p><p class="mt-1 text-[11px] text-slate-400">${note}</p></div><div class="grid h-11 w-11 place-items-center rounded-2xl ${tint}">${icon(iconName, 20)}</div></div>${footnote ? `<div class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-500">${footnote}</div>` : ''}</article>`;
  }

  function renderPageContent() {
    switch (currentPage) {
      case 'dashboard': return renderDashboard();
      case 'books': return renderBooks();
      case 'members': return renderMembers();
      case 'loans': return renderLoans();
      case 'returns': return renderReturns();
      case 'fines': return renderFines();
      case 'reports': return renderReports();
      default: return renderDashboard();
    }
  }

  function renderDashboard() {
    const roleAdmin = currentUser.role === 'admin';
    const active = activeLoans();
    const overdue = active.filter(loan => overdueDays(loan.dueAt) > 0).length;
    const latest = [...data.loans].sort((a, b) => new Date(b.borrowedAt) - new Date(a.borrowedAt)).slice(0, 5);
    const ownLoans = activeLoansForMember(currentUser.memberId);
    const actions = `<button class="btn btn-primary" data-page="books">${icon('book', 16)} Jelajahi buku</button>`;
    return `${pageHeader(roleAdmin ? `Selamat datang, ${escapeHtml(currentUser.name.split(' ')[0])} 👋` : `Halo, ${escapeHtml(currentUser.name.split(' ')[0])} 👋`, roleAdmin ? 'Ringkasan aktivitas perpustakaan hari ini.' : 'Temukan bacaan berikutnya dan pantau peminjaman Anda.', actions)}
      ${roleAdmin ? `
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        ${statCard('Total koleksi buku', totalBookCopies(), `${data.books.length} judul dalam katalog`, 'book', 'bg-teal-50 text-teal-700', `${data.books.reduce((sum, b) => sum + b.stock, 0)} eksemplar tersedia`)}
        ${statCard('Anggota terdaftar', data.members.length, `${data.members.filter(m => m.status === 'Aktif').length} anggota aktif`, 'users', 'bg-blue-50 text-blue-700', 'Data anggota perpustakaan')}
        ${statCard('Sedang dipinjam', active.length, `${overdue} peminjaman terlambat`, 'repeat', 'bg-amber-50 text-amber-700', 'Pinjaman yang belum dikembalikan')}
        ${statCard('Denda belum dibayar', formatCurrency(outstandingFines()), `${data.loans.filter(l => l.fine > 0 && !l.finePaid).length} transaksi denda`, 'receipt', 'bg-rose-50 text-rose-700', `Tarif keterlambatan ${formatCurrency(FINE_PER_DAY)} / hari`)}
      </div>
      <div class="mb-6 grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
        <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6"><div class="mb-5 flex items-center justify-between"><div><h2 class="display-font text-sm font-extrabold">Kategori koleksi</h2><p class="mt-1 text-xs text-slate-400">Jumlah judul buku per kategori</p></div><span class="badge badge-green">${data.books.length} judul</span></div><div class="relative h-64"><canvas id="category-chart" aria-label="Grafik kategori buku"></canvas></div></section>
        <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6"><div class="mb-5 flex items-center justify-between"><div><h2 class="display-font text-sm font-extrabold">Akses cepat</h2><p class="mt-1 text-xs text-slate-400">Kelola kegiatan perpustakaan</p></div>${icon('spark', 20)}</div><div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
          <button class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:border-teal-200 hover:bg-teal-50" data-page="books"><span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-50 text-teal-700">${icon('book', 18)}</span><span><b class="block text-xs">Koleksi buku</b><small class="text-[11px] text-slate-400">Atur katalog buku</small></span></button>
          ${roleAdmin ? `<button class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:border-blue-200 hover:bg-blue-50" data-page="members"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700">${icon('users', 18)}</span><span><b class="block text-xs">Data anggota</b><small class="text-[11px] text-slate-400">Kelola keanggotaan</small></span></button><button class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:border-amber-200 hover:bg-amber-50" data-page="returns"><span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700">${icon('return', 18)}</span><span><b class="block text-xs">Proses pengembalian</b><small class="text-[11px] text-slate-400">${active.length} buku aktif dipinjam</small></span></button><button class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:border-rose-200 hover:bg-rose-50" data-page="reports"><span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 text-rose-700">${icon('chart', 18)}</span><span><b class="block text-xs">Lihat laporan</b><small class="text-[11px] text-slate-400">Cetak ringkasan data</small></span></button>` : `<button class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:border-blue-200 hover:bg-blue-50" data-page="loans"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700">${icon('repeat', 18)}</span><span><b class="block text-xs">Peminjaman saya</b><small class="text-[11px] text-slate-400">${ownLoans.length} buku sedang dipinjam</small></span></button>`}
        </div></section>
      </div>
      ${renderRecentLoans(latest)}
      ` : `
      <div class="mb-6 grid gap-4 sm:grid-cols-3">
        ${statCard('Pinjaman aktif', ownLoans.length, `Dari maksimal ${MAX_ACTIVE_LOANS} buku`, 'repeat', 'bg-teal-50 text-teal-700', `Batas pengembalian ${LOAN_DAYS} hari`)}
        ${statCard('Buku tersedia', data.books.reduce((sum, book) => sum + book.stock, 0), `${data.books.length} judul di katalog`, 'book', 'bg-blue-50 text-blue-700', 'Pilih buku yang ingin dipinjam')}
        ${statCard('Denda berjalan', formatCurrency(data.loans.filter(loan => loan.memberId === currentUser.memberId && loan.status === 'Dikembalikan' && !loan.finePaid).reduce((sum, loan) => sum + loan.fine, 0)), 'Denda yang belum dibayar', 'receipt', 'bg-amber-50 text-amber-700', `Tarif ${formatCurrency(FINE_PER_DAY)} per hari terlambat`)}
      </div>
      ${renderMemberLoanSummary(ownLoans)}
      <section class="card-shadow mt-5 rounded-2xl border border-slate-100 bg-white p-5"><div class="mb-4 flex items-center justify-between"><div><h2 class="display-font text-sm font-extrabold">Rekomendasi bacaan</h2><p class="mt-1 text-xs text-slate-400">Buku pilihan yang tersedia untuk dipinjam</p></div><button class="text-xs font-bold text-teal-700" data-page="books">Lihat katalog →</button></div>${renderFeaturedBooks()}</section>
      `}`;
  }

  function renderCategoryChart() {
    const canvas = document.getElementById('category-chart');
    if (!canvas || typeof Chart === 'undefined') return;
    if (categoryChart) categoryChart.destroy();
    const counts = {};
    data.books.forEach(book => { counts[book.category] = (counts[book.category] || 0) + 1; });
    const colors = ['#0f887d', '#e1a84b', '#6d91c5', '#d17b62', '#9276b8', '#5eaa83', '#da7790'];
    categoryChart = new Chart(canvas, {
      type: 'doughnut',
      data: { labels: Object.keys(counts), datasets: [{ data: Object.values(counts), backgroundColor: Object.keys(counts).map((_, index) => colors[index % colors.length]), borderWidth: 0, hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 15, font: { family: 'DM Sans', size: 11 } } }, tooltip: { callbacks: { label: context => ` ${context.label}: ${context.raw} judul` } } } }
    });
  }

  function renderRecentLoans(loans) {
    return `        <section class="card-shadow rounded-2xl border border-slate-100 bg-white"><div class="flex items-center justify-between px-5 py-4"><div><h2 class="display-font text-sm font-extrabold">Aktivitas peminjaman terbaru</h2><p class="mt-1 text-xs text-slate-400">Transaksi terbaru dari seluruh anggota</p></div><button class="text-xs font-bold text-teal-700" data-page="loans">Lihat semua →</button></div><div class="table-wrap">${loans.length ? `<table class="data-table"><thead><tr><th>Buku</th><th>Anggota</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Status</th></tr></thead><tbody>${loans.map(loan => loanRow(loan, false, true, false)).join('')}</tbody></table>` : emptyState('Belum ada aktivitas peminjaman')}</div></section>`;
  }

  function renderMemberLoanSummary(loans) {
    return `<section class="card-shadow rounded-2xl border border-slate-100 bg-white"><div class="flex items-center justify-between px-5 py-4"><div><h2 class="display-font text-sm font-extrabold">Buku yang sedang Anda pinjam</h2><p class="mt-1 text-xs text-slate-400">Kembalikan tepat waktu untuk menghindari denda.</p></div><button class="btn btn-soft btn-small" data-page="loans">Riwayat</button></div><div class="table-wrap">${loans.length ? `<table class="data-table"><thead><tr><th>Buku</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Status</th></tr></thead><tbody>${loans.map(loan => loanRow(loan, false, false, false)).join('')}</tbody></table>` : emptyState('Belum ada buku yang dipinjam. Jelajahi katalog untuk mulai membaca.')}</div></section>`;
  }

  function renderFeaturedBooks() {
    const books = data.books.filter(book => book.stock > 0).slice(0, 4);
    if (!books.length) return emptyState('Belum ada buku tersedia saat ini.');
    return `<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">${books.map((book, index) => `<article class="flex items-center gap-3 rounded-xl border border-slate-100 p-3"><div class="cover-art" style="background:${coverColors[index % coverColors.length]}">${icon('book', 20)}</div><div class="min-w-0 flex-1"><h3 class="truncate text-xs font-bold">${escapeHtml(book.title)}</h3><p class="truncate text-[11px] text-slate-500">${escapeHtml(book.author)}</p><span class="mt-1 inline-block text-[10px] font-bold text-teal-700">${book.stock} tersedia</span></div><button class="icon-btn" data-action="borrow" data-id="${escapeHtml(book.id)}" aria-label="Pinjam ${escapeHtml(book.title)}">${icon('plus', 16)}</button></article>`).join('')}</div>`;
  }

  function renderBooks() {
    const categories = [...new Set(data.books.map(book => book.category).filter(Boolean))].sort();
    const filtered = data.books.filter(book => {
      const term = bookSearch.trim().toLowerCase();
      const matches = !term || [book.title, book.author, book.publisher, book.category, book.shelf].some(value => String(value).toLowerCase().includes(term));
      return matches && (!bookCategory || book.category === bookCategory);
    }).sort((a, b) => a.title.localeCompare(b.title, 'id'));
    const isAdmin = currentUser.role === 'admin';
    const actions = `${isAdmin ? `<button class="btn btn-primary" data-action="book-form">${icon('plus', 16)} Tambah buku</button>` : `<button class="btn btn-muted" data-action="my-loans">${icon('repeat', 16)} Peminjaman saya</button>`}`;
    return `${pageHeader('Katalog buku', isAdmin ? 'Kelola koleksi dan ketersediaan buku perpustakaan.' : 'Cari buku pilihan dan pinjam langsung dari katalog.', actions)}
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
          <div class="relative flex-1"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">${icon('search', 17)}</span><input class="field pl-10" type="search" data-search="books" value="${escapeHtml(bookSearch)}" placeholder="Cari judul, penulis, penerbit..."></div>
          <select class="field sm:w-52" data-filter="book-category"><option value="">Semua kategori</option>${categories.map(category => `<option value="${escapeHtml(category)}" ${bookCategory === category ? 'selected' : ''}>${escapeHtml(category)}</option>`).join('')}</select>
          <div class="text-xs text-slate-500"><b>${filtered.length}</b> dari ${data.books.length} judul</div>
        </div>
        <div class="table-wrap">${filtered.length ? `<table class="data-table"><thead><tr><th>Informasi buku</th><th>Kategori</th><th>Rak</th><th>Tahun</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${filtered.map(book => bookTableRow(book, isAdmin)).join('')}</tbody></table>` : emptyState('Buku tidak ditemukan. Coba kata kunci atau kategori lain.')}</div>
      </section>`;
  }

  function bookTableRow(book, isAdmin) {
    const activeForBook = activeLoans().filter(loan => loan.bookId === book.id).length;
    const status = book.stock > 0 ? 'Tersedia' : activeForBook > 0 ? 'Dipinjam' : 'Stok habis';
    return `<tr>
      <td><div class="flex min-w-[210px] items-center gap-3"><div class="cover-art" style="background:${coverColors[Math.abs(book.id.split('').reduce((sum, char) => sum + char.charCodeAt(0), 0)) % coverColors.length]}">${icon('book', 19)}</div><div class="min-w-0"><button class="max-w-[260px] truncate text-left text-sm font-bold text-ink hover:text-teal-700" data-action="book-detail" data-id="${escapeHtml(book.id)}">${escapeHtml(book.title)}</button><p class="mt-1 text-xs text-slate-500">${escapeHtml(book.author)} · ${escapeHtml(book.publisher)}</p></div></div></td>
      <td><span class="badge badge-blue">${escapeHtml(book.category)}</span></td><td>${escapeHtml(book.shelf)}</td><td>${escapeHtml(book.year)}</td><td><span class="font-bold ${book.stock ? 'text-ink' : 'text-red-600'}">${book.stock}</span><span class="text-slate-400"> / ${book.stock + activeForBook}</span></td><td><span class="badge ${book.stock ? 'badge-green' : 'badge-red'}">${status}</span></td>
      <td><div class="flex items-center gap-1">${isAdmin ? `<button class="icon-btn" data-action="book-detail" data-id="${escapeHtml(book.id)}" title="Detail">${icon('eye', 15)}</button><button class="icon-btn" data-action="book-edit" data-id="${escapeHtml(book.id)}" title="Edit">${icon('edit', 15)}</button><button class="icon-btn hover:!bg-red-50 hover:!text-red-600" data-action="book-delete" data-id="${escapeHtml(book.id)}" title="Hapus">${icon('trash', 15)}</button>` : `<button class="btn ${book.stock ? 'btn-primary' : 'btn-muted'} btn-small" data-action="borrow" data-id="${escapeHtml(book.id)}" ${book.stock ? '' : 'disabled'}>${book.stock ? 'Pinjam' : 'Habis'}</button>`}</div></td>
    </tr>`;
  }

  function renderMembers() {
    const term = memberSearch.trim().toLowerCase();
    const members = data.members.filter(member => !term || [member.name, member.email, member.phone, member.address].some(value => String(value).toLowerCase().includes(term))).sort((a, b) => a.name.localeCompare(b.name, 'id'));
    return `${pageHeader('Data anggota', 'Kelola identitas, status, dan akun anggota perpustakaan.', `<button class="btn btn-primary" data-action="member-form">${icon('plus', 16)} Tambah anggota</button>`)}
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center"><div class="relative flex-1"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">${icon('search', 17)}</span><input class="field pl-10" type="search" data-search="members" value="${escapeHtml(memberSearch)}" placeholder="Cari nama, email, atau telepon..."></div><span class="text-xs text-slate-500"><b>${members.length}</b> anggota</span></div>
        <div class="table-wrap">${members.length ? `<table class="data-table"><thead><tr><th>Nama anggota</th><th>Kontak</th><th>Tanggal daftar</th><th>Pinjaman aktif</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${members.map(member => memberTableRow(member)).join('')}</tbody></table>` : emptyState('Anggota tidak ditemukan.')}</div>
      </section>`;
  }

  function memberTableRow(member) {
    const count = activeLoansForMember(member.id).length;
    return `<tr><td><div class="flex items-center gap-3"><div class="grid h-10 w-10 place-items-center rounded-full bg-blue-50 text-sm font-bold text-blue-700">${escapeHtml(member.name.trim().charAt(0).toUpperCase())}</div><div><div class="font-bold text-ink">${escapeHtml(member.name)}</div><div class="mt-1 text-xs text-slate-500">${escapeHtml(member.address)}</div></div></div></td><td><div>${escapeHtml(member.email)}</div><div class="mt-1 text-xs text-slate-500">${escapeHtml(member.phone)}</div></td><td>${formatDate(member.joinedAt)}</td><td><span class="badge ${count ? 'badge-blue' : 'badge-gray'}">${count} buku</span></td><td><span class="badge ${member.status === 'Aktif' ? 'badge-green' : 'badge-gray'}">${escapeHtml(member.status)}</span></td><td><div class="flex gap-1"><button class="icon-btn" data-action="member-edit" data-id="${escapeHtml(member.id)}" title="Edit">${icon('edit', 15)}</button><button class="icon-btn hover:!bg-red-50 hover:!text-red-600" data-action="member-delete" data-id="${escapeHtml(member.id)}" title="Hapus">${icon('trash', 15)}</button></div></td></tr>`;
  }

  function renderLoans() {
    const own = currentUser.role === 'member';
    const loans = [...data.loans].filter(loan => !own || loan.memberId === currentUser.memberId)
      .filter(loan => {
        const term = loanSearch.trim().toLowerCase();
        const book = bookById(loan.bookId);
        const member = memberById(loan.memberId);
        return !term || [book?.title, member?.name, loan.status].some(value => String(value || '').toLowerCase().includes(term));
      }).sort((a, b) => new Date(b.borrowedAt) - new Date(a.borrowedAt));
    const actions = own ? `<button class="btn btn-primary" data-page="books">${icon('plus', 16)} Pinjam buku</button>` : `<button class="btn btn-muted" data-page="returns">${icon('return', 16)} Proses pengembalian</button>`;
    return `${pageHeader(own ? 'Peminjaman saya' : 'Data peminjaman', own ? 'Pantau status, tanggal pinjam, dan batas pengembalian buku.' : 'Pantau seluruh transaksi peminjaman anggota.', actions)}
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white"><div class="border-b border-slate-100 p-4"><div class="relative max-w-md"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">${icon('search', 17)}</span><input class="field pl-10" type="search" data-search="loans" value="${escapeHtml(loanSearch)}" placeholder="Cari judul buku atau nama anggota..."></div></div>
      <div class="table-wrap">${loans.length ? `<table class="data-table"><thead><tr><th>Buku</th>${own ? '' : '<th>Anggota</th>'}<th>Tanggal pinjam</th><th>Batas kembali</th><th>Denda</th><th>Status</th>${own ? '' : '<th>Aksi</th>'}</tr></thead><tbody>${loans.map(loan => loanRow(loan, !own)).join('')}</tbody></table>` : emptyState('Belum ada transaksi peminjaman.')}</div></section>`;
  }

  function loanRow(loan, withAction, showMember = withAction, showFine = true) {
    const book = bookById(loan.bookId);
    const member = memberById(loan.memberId);
    const isOverdue = loan.status === 'Dipinjam' && overdueDays(loan.dueAt) > 0;
    const status = loan.status === 'Dikembalikan' ? '<span class="badge badge-green">Dikembalikan</span>' : isOverdue ? '<span class="badge badge-red">Terlambat</span>' : '<span class="badge badge-amber">Dipinjam</span>';
    const fine = loan.fine > 0 ? formatCurrency(loan.fine) : isOverdue ? formatCurrency(overdueDays(loan.dueAt) * FINE_PER_DAY) : '—';
    return `<tr><td><div class="font-bold text-ink">${escapeHtml(book?.title || 'Buku dihapus')}</div><div class="mt-1 text-xs text-slate-500">${escapeHtml(book?.author || '')}</div></td>${showMember ? `<td>${escapeHtml(member?.name || 'Anggota dihapus')}</td>` : ''}<td>${formatDate(loan.borrowedAt)}</td><td><span class="${isOverdue ? 'font-bold text-red-600' : ''}">${formatDate(loan.dueAt)}</span></td>${showFine ? `<td>${fine}</td>` : ''}<td>${status}</td>${withAction ? `<td>${loan.status === 'Dipinjam' ? `<button class="btn btn-soft btn-small" data-action="return-loan" data-id="${escapeHtml(loan.id)}">Kembalikan</button>` : '—'}</td>` : ''}</tr>`;
  }

  function renderReturns() {
    const loans = activeLoans().sort((a, b) => new Date(a.dueAt) - new Date(b.dueAt));
    return `${pageHeader('Pengembalian buku', 'Proses buku yang dikembalikan dan hitung denda secara otomatis.', `<span class="badge badge-amber self-center">${loans.length} masih dipinjam</span>`)}
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white"><div class="table-wrap">${loans.length ? `<table class="data-table"><thead><tr><th>Buku</th><th>Anggota</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Keterlambatan</th><th>Aksi</th></tr></thead><tbody>${loans.map(loan => {
        const days = overdueDays(loan.dueAt);
        const book = bookById(loan.bookId);
        const member = memberById(loan.memberId);
        return `<tr><td><div class="font-bold text-ink">${escapeHtml(book?.title || 'Buku dihapus')}</div><div class="mt-1 text-xs text-slate-500">${escapeHtml(book?.author || '')}</div></td><td><div class="font-semibold">${escapeHtml(member?.name || 'Anggota dihapus')}</div><div class="mt-1 text-xs text-slate-500">${escapeHtml(member?.phone || '')}</div></td><td>${formatDate(loan.borrowedAt)}</td><td>${formatDate(loan.dueAt)}</td><td>${days ? `<span class="badge badge-red">${days} hari · ${formatCurrency(days * FINE_PER_DAY)}</span>` : '<span class="badge badge-green">Tepat waktu</span>'}</td><td><button class="btn btn-primary btn-small" data-action="return-loan" data-id="${escapeHtml(loan.id)}">Proses kembali</button></td></tr>`;
      }).join('')}</tbody></table>` : emptyState('Semua buku sudah dikembalikan. Tidak ada transaksi aktif.')}</div></section>`;
  }

  function renderFines() {
    const fines = [...data.loans].filter(loan => loan.status === 'Dikembalikan' && loan.fine > 0).sort((a, b) => Number(a.finePaid) - Number(b.finePaid) || new Date(b.returnedAt) - new Date(a.returnedAt));
    const unpaid = fines.filter(loan => !loan.finePaid).reduce((sum, loan) => sum + loan.fine, 0);
    const paid = fines.filter(loan => loan.finePaid).reduce((sum, loan) => sum + loan.fine, 0);
    return `${pageHeader('Pengelolaan denda', 'Pantau keterlambatan dan catat pembayaran denda anggota.', '')}
      <div class="mb-5 grid gap-4 sm:grid-cols-3">${statCard('Belum dibayar', formatCurrency(unpaid), `${fines.filter(loan => !loan.finePaid).length} transaksi`, 'receipt', 'bg-rose-50 text-rose-700')}${statCard('Sudah dibayar', formatCurrency(paid), `${fines.filter(loan => loan.finePaid).length} transaksi`, 'check', 'bg-teal-50 text-teal-700')}${statCard('Tarif denda', `${formatCurrency(FINE_PER_DAY)} / hari`, 'Dihitung saat buku dikembalikan', 'clock', 'bg-amber-50 text-amber-700')}</div>
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white"><div class="table-wrap">${fines.length ? `<table class="data-table"><thead><tr><th>Anggota</th><th>Buku</th><th>Terlambat</th><th>Tanggal kembali</th><th>Jumlah denda</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${fines.map(loan => {
        const member = memberById(loan.memberId);
        const book = bookById(loan.bookId);
        const days = overdueDays(loan.dueAt, loan.returnedAt);
        return `<tr><td class="font-semibold">${escapeHtml(member?.name || 'Anggota dihapus')}</td><td>${escapeHtml(book?.title || 'Buku dihapus')}</td><td>${days} hari</td><td>${formatDate(loan.returnedAt)}</td><td class="font-bold">${formatCurrency(loan.fine)}</td><td><span class="badge ${loan.finePaid ? 'badge-green' : 'badge-red'}">${loan.finePaid ? 'Lunas' : 'Belum dibayar'}</span></td><td>${loan.finePaid ? '<span class="text-xs text-slate-400">Selesai</span>' : `<button class="btn btn-soft btn-small" data-action="pay-fine" data-id="${escapeHtml(loan.id)}">Tandai lunas</button>`}</td></tr>`;
      }).join('')}</tbody></table>` : emptyState('Belum ada denda. Denda keterlambatan akan muncul setelah pengembalian.')}</div></section>`;
  }

  function renderReports() {
    const tabs = [
      ['books', 'Data buku'], ['members', 'Data anggota'], ['loans', 'Peminjaman'], ['returns', 'Pengembalian'], ['fines', 'Denda']
    ];
    return `${pageHeader('Laporan perpustakaan', 'Pilih jenis laporan, lalu cetak atau simpan sebagai PDF.', `<button class="btn btn-primary no-print" data-action="print">${icon('printer', 16)} Cetak laporan</button>`)}
      <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
        <div class="no-print flex flex-wrap gap-2 border-b border-slate-100 p-4">${tabs.map(([id, label]) => `<button class="btn ${reportType === id ? 'btn-primary' : 'btn-muted'} btn-small" data-report="${id}">${label}</button>`).join('')}</div>
        <div class="p-5 sm:p-6"><div class="mb-5 flex items-start justify-between gap-3"><div><p class="text-[10px] font-bold uppercase tracking-[.16em] text-teal-700">DIGILIB SMART LIBRARY</p><h2 class="display-font mt-1 text-lg font-extrabold">Laporan ${tabs.find(item => item[0] === reportType)?.[1]}</h2><p class="mt-1 text-xs text-slate-500">Dicetak pada ${formatDate(new Date())}</p></div><div class="text-right text-xs text-slate-500">${reportSummary()}</div></div>
        <div class="table-wrap">${reportTable()}</div></div>
      </section>`;
  }

  function reportSummary() {
    const count = reportType === 'books' ? data.books.length : reportType === 'members' ? data.members.length : reportType === 'loans' ? data.loans.length : reportType === 'returns' ? data.loans.filter(loan => loan.status === 'Dikembalikan').length : data.loans.filter(loan => loan.fine > 0).length;
    return `<b class="text-ink">${count}</b><br>baris data`;
  }

  function reportTable() {
    if (reportType === 'books') return `<table class="data-table"><thead><tr><th>Judul</th><th>Penulis</th><th>Penerbit</th><th>Tahun</th><th>Kategori</th><th>Rak</th><th>Stok tersedia</th></tr></thead><tbody>${data.books.map(book => `<tr><td class="font-semibold">${escapeHtml(book.title)}</td><td>${escapeHtml(book.author)}</td><td>${escapeHtml(book.publisher)}</td><td>${book.year}</td><td>${escapeHtml(book.category)}</td><td>${escapeHtml(book.shelf)}</td><td>${book.stock}</td></tr>`).join('')}</tbody></table>`;
    if (reportType === 'members') return `<table class="data-table"><thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Alamat</th><th>Tanggal daftar</th><th>Status</th></tr></thead><tbody>${data.members.map(member => `<tr><td class="font-semibold">${escapeHtml(member.name)}</td><td>${escapeHtml(member.email)}</td><td>${escapeHtml(member.phone)}</td><td>${escapeHtml(member.address)}</td><td>${formatDate(member.joinedAt)}</td><td>${escapeHtml(member.status)}</td></tr>`).join('')}</tbody></table>`;
    if (reportType === 'fines') return `<table class="data-table"><thead><tr><th>Anggota</th><th>Buku</th><th>Tanggal kembali</th><th>Terlambat</th><th>Denda</th><th>Status</th></tr></thead><tbody>${data.loans.filter(loan => loan.status === 'Dikembalikan' && loan.fine > 0).map(loan => `<tr><td>${escapeHtml(memberById(loan.memberId)?.name || 'Anggota dihapus')}</td><td>${escapeHtml(bookById(loan.bookId)?.title || 'Buku dihapus')}</td><td>${formatDate(loan.returnedAt)}</td><td>${overdueDays(loan.dueAt, loan.returnedAt)} hari</td><td>${formatCurrency(loan.fine)}</td><td>${loan.finePaid ? 'Lunas' : 'Belum dibayar'}</td></tr>`).join('')}</tbody></table>`;
    const returns = reportType === 'returns';
    return `<table class="data-table"><thead><tr><th>Buku</th><th>Anggota</th><th>Tanggal pinjam</th><th>Batas kembali</th>${returns ? '<th>Tanggal kembali</th><th>Denda</th>' : '<th>Status</th>'}</tr></thead><tbody>${data.loans.filter(loan => !returns || loan.status === 'Dikembalikan').map(loan => `<tr><td>${escapeHtml(bookById(loan.bookId)?.title || 'Buku dihapus')}</td><td>${escapeHtml(memberById(loan.memberId)?.name || 'Anggota dihapus')}</td><td>${formatDate(loan.borrowedAt)}</td><td>${formatDate(loan.dueAt)}</td>${returns ? `<td>${formatDate(loan.returnedAt)}</td><td>${formatCurrency(loan.fine)}</td>` : `<td>${escapeHtml(loan.status)}</td>`}</tr>`).join('')}</tbody></table>`;
  }

  function emptyState(message) {
    return `<div class="flex flex-col items-center justify-center px-5 py-14 text-center"><div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-50 text-slate-400">${icon('book', 22)}</div><p class="text-sm font-semibold text-slate-600">${escapeHtml(message)}</p></div>`;
  }

  function showBookForm(book = null) {
    const editing = Boolean(book);
    openModal(`<div class="flex items-start justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="display-font text-lg font-extrabold">${editing ? 'Edit data buku' : 'Tambah buku baru'}</h2><p class="mt-1 text-xs text-slate-500">Isi informasi koleksi perpustakaan dengan lengkap.</p></div><button class="icon-btn" data-action="close-modal" aria-label="Tutup">${icon('close', 17)}</button></div>
      <form data-form="book" data-id="${editing ? escapeHtml(book.id) : ''}" class="space-y-4 p-6">
        <div><label class="field-label">Judul buku *</label><input class="field" name="title" value="${escapeHtml(book?.title || '')}" required maxlength="150"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label">Penulis *</label><input class="field" name="author" value="${escapeHtml(book?.author || '')}" required maxlength="100"></div><div><label class="field-label">Penerbit *</label><input class="field" name="publisher" value="${escapeHtml(book?.publisher || '')}" required maxlength="100"></div></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label">Tahun terbit *</label><input class="field" name="year" type="number" min="1000" max="${new Date().getFullYear() + 1}" value="${escapeHtml(book?.year || new Date().getFullYear())}" required></div><div><label class="field-label">Kategori *</label><input class="field" name="category" list="category-options" value="${escapeHtml(book?.category || '')}" placeholder="Pilih atau ketik kategori" required maxlength="50"><datalist id="category-options">${[...new Set(data.books.map(item => item.category))].map(category => `<option value="${escapeHtml(category)}">`).join('')}</datalist></div></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label">Lokasi rak *</label><input class="field" name="shelf" value="${escapeHtml(book?.shelf || '')}" placeholder="Contoh: A-01" required maxlength="30"></div><div><label class="field-label">Stok tersedia *</label><input class="field" name="stock" type="number" min="0" value="${escapeHtml(book?.stock ?? 1)}" required></div></div>
        ${editing && activeLoans().some(loan => loan.bookId === book.id) ? '<p class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Buku ini sedang dipinjam. Stok yang tersedia tidak boleh dikurangi di bawah jumlah pinjaman aktif.</p>' : ''}
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button class="btn btn-muted" type="button" data-action="close-modal">Batal</button><button class="btn btn-primary" type="submit">${editing ? 'Simpan perubahan' : 'Simpan buku'}</button></div>
      </form>`);
  }

  function showMemberForm(member = null) {
    const editing = Boolean(member);
    const linkedUser = editing && data.users.find(user => user.memberId === member.id);
    openModal(`<div class="flex items-start justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="display-font text-lg font-extrabold">${editing ? 'Edit data anggota' : 'Tambah anggota baru'}</h2><p class="mt-1 text-xs text-slate-500">Akun anggota digunakan untuk login dan meminjam buku.</p></div><button class="icon-btn" data-action="close-modal" aria-label="Tutup">${icon('close', 17)}</button></div>
      <form data-form="member" data-id="${editing ? escapeHtml(member.id) : ''}" class="space-y-4 p-6">
        <div><label class="field-label">Nama lengkap *</label><input class="field" name="name" value="${escapeHtml(member?.name || '')}" required maxlength="100"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label">Email login *</label><input class="field" name="email" type="email" value="${escapeHtml(member?.email || '')}" required></div><div><label class="field-label">Nomor telepon *</label><input class="field" name="phone" value="${escapeHtml(member?.phone || '')}" required maxlength="30"></div></div>
        <div><label class="field-label">Alamat *</label><textarea class="field" name="address" rows="2" required maxlength="250">${escapeHtml(member?.address || '')}</textarea></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="field-label">Tanggal daftar *</label><input class="field" name="joinedAt" type="date" value="${escapeHtml(member?.joinedAt || dateOnly(new Date()))}" required></div><div><label class="field-label">Status anggota</label><select class="field" name="status"><option ${member?.status !== 'Nonaktif' ? 'selected' : ''}>Aktif</option><option ${member?.status === 'Nonaktif' ? 'selected' : ''}>Nonaktif</option></select></div></div>
        <div><label class="field-label">Kata sandi ${editing && linkedUser ? '(kosongkan jika tidak diubah)' : '*'}</label><input class="field" name="password" type="password" minlength="6" ${editing && linkedUser ? '' : 'required'} autocomplete="new-password" placeholder="Minimal 6 karakter"></div>
        ${linkedUser ? `<p class="text-[11px] text-slate-400">Akun login: ${escapeHtml(linkedUser.email)}</p>` : ''}
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button class="btn btn-muted" type="button" data-action="close-modal">Batal</button><button class="btn btn-primary" type="submit">${editing ? 'Simpan perubahan' : 'Simpan anggota'}</button></div>
      </form>`);
  }

  function showBookDetail(book) {
    const activeCount = activeLoans().filter(loan => loan.bookId === book.id).length;
    const status = book.stock > 0 ? 'Tersedia' : activeCount > 0 ? 'Dipinjam' : 'Stok habis';
    openModal(`<div class="flex items-start justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-teal-700">Detail koleksi</p><h2 class="display-font mt-1 text-lg font-extrabold">${escapeHtml(book.title)}</h2></div><button class="icon-btn" data-action="close-modal">${icon('close', 17)}</button></div><div class="space-y-4 p-6"><div class="grid grid-cols-[auto_1fr] gap-x-5 gap-y-4 text-sm"><span class="text-slate-400">Penulis</span><b>${escapeHtml(book.author)}</b><span class="text-slate-400">Penerbit</span><b>${escapeHtml(book.publisher)}</b><span class="text-slate-400">Tahun terbit</span><b>${escapeHtml(book.year)}</b><span class="text-slate-400">Kategori</span><b>${escapeHtml(book.category)}</b><span class="text-slate-400">Lokasi rak</span><b>${escapeHtml(book.shelf)}</b><span class="text-slate-400">Stok tersedia</span><b>${book.stock} eksemplar</b><span class="text-slate-400">Sedang dipinjam</span><b>${activeCount} eksemplar</b><span class="text-slate-400">Status</span><b>${status}</b></div><div class="flex justify-end border-t border-slate-100 pt-4"><button class="btn btn-muted" data-action="close-modal">Tutup</button></div></div>`);
  }

  function showBorrowConfirmation(book) {
    const currentCount = activeLoansForMember(currentUser.memberId).length;
    if (currentCount >= MAX_ACTIVE_LOANS) {
      notify(`Anda sudah mencapai batas maksimal ${MAX_ACTIVE_LOANS} buku.`, 'warning');
      return;
    }
    if (!book || book.stock < 1) {
      notify('Buku tidak tersedia untuk dipinjam.', 'warning');
      return;
    }
    openModal(`<div class="flex items-start justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-teal-700">Konfirmasi peminjaman</p><h2 class="display-font mt-1 text-lg font-extrabold">Pinjam buku ini?</h2></div><button class="icon-btn" data-action="close-modal">${icon('close', 17)}</button></div><div class="p-6"><div class="flex items-center gap-4 rounded-2xl bg-sand p-4"><div class="cover-art" style="background:${coverColors[0]}">${icon('book', 20)}</div><div><h3 class="font-bold">${escapeHtml(book.title)}</h3><p class="mt-1 text-xs text-slate-500">${escapeHtml(book.author)}</p><p class="mt-1 text-xs font-semibold text-teal-700">${book.stock} eksemplar tersedia</p></div></div><div class="mt-4 rounded-xl border border-teal-100 bg-teal-50 p-4 text-xs leading-6 text-teal-900"><b>Ketentuan peminjaman</b><br>• Batas pengembalian: ${formatDate(dayOffset(LOAN_DAYS))}<br>• Maksimal ${MAX_ACTIVE_LOANS} buku dipinjam bersamaan<br>• Denda keterlambatan ${formatCurrency(FINE_PER_DAY)} per hari</div><div class="mt-5 flex justify-end gap-2"><button class="btn btn-muted" data-action="close-modal">Batal</button><button class="btn btn-primary" data-action="confirm-borrow" data-id="${escapeHtml(book.id)}">Ya, pinjam buku</button></div></div>`);
  }

  function showReturnConfirmation(loan) {
    const book = bookById(loan.bookId);
    const member = memberById(loan.memberId);
    const days = overdueDays(loan.dueAt);
    const fine = days * FINE_PER_DAY;
    openModal(`<div class="flex items-start justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-bold uppercase tracking-[.15em] text-teal-700">Proses pengembalian</p><h2 class="display-font mt-1 text-lg font-extrabold">Konfirmasi buku kembali</h2></div><button class="icon-btn" data-action="close-modal">${icon('close', 17)}</button></div><div class="p-6"><div class="rounded-2xl bg-sand p-4"><div class="font-bold">${escapeHtml(book?.title || 'Buku dihapus')}</div><div class="mt-1 text-xs text-slate-500">Dipinjam oleh ${escapeHtml(member?.name || 'Anggota dihapus')}</div><div class="mt-3 grid grid-cols-2 gap-2 text-xs"><span class="text-slate-500">Batas kembali</span><b>${formatDate(loan.dueAt)}</b><span class="text-slate-500">Tanggal hari ini</span><b>${formatDate(new Date())}</b><span class="text-slate-500">Keterlambatan</span><b class="${days ? 'text-red-600' : 'text-teal-700'}">${days} hari</b></div></div>${fine ? `<div class="mt-4 rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-800">Denda otomatis: <b>${formatCurrency(FINE_PER_DAY)} × ${days} hari = ${formatCurrency(fine)}</b><p class="mt-1 text-xs">Denda akan tercatat sebagai belum dibayar.</p></div>` : `<div class="mt-4 rounded-xl border border-teal-100 bg-teal-50 p-4 text-sm text-teal-800">Tidak ada keterlambatan. Tidak ada denda untuk transaksi ini.</div>`}<div class="mt-5 flex justify-end gap-2"><button class="btn btn-muted" data-action="close-modal">Batal</button><button class="btn btn-primary" data-action="confirm-return" data-id="${escapeHtml(loan.id)}">Konfirmasi pengembalian</button></div></div>`);
  }

  function handleBookSubmit(form) {
    const fields = new FormData(form);
    const id = form.dataset.id;
    const existing = id ? bookById(id) : null;
    const book = {
      id: existing?.id || makeId('book'),
      title: String(fields.get('title')).trim(),
      author: String(fields.get('author')).trim(),
      publisher: String(fields.get('publisher')).trim(),
      year: Number(fields.get('year')),
      category: String(fields.get('category')).trim(),
      shelf: String(fields.get('shelf')).trim(),
      stock: Number(fields.get('stock'))
    };
    if (!book.title || !book.author || !book.publisher || !book.category || !book.shelf || !Number.isInteger(book.year) || book.year < 1000 || !Number.isInteger(book.stock) || book.stock < 0) {
      notify('Periksa kembali data buku. Tahun dan stok harus berupa bilangan valid.', 'error');
      return;
    }
    const borrowedCount = existing ? activeLoans().filter(loan => loan.bookId === existing.id).length : 0;
    if (book.stock < borrowedCount) {
      notify(`Stok tersedia tidak boleh kurang dari ${borrowedCount} buku yang sedang dipinjam.`, 'error');
      return;
    }
    if (existing) data.books[data.books.findIndex(item => item.id === existing.id)] = book;
    else data.books.push(book);
    if (!saveData()) return;
    closeModal();
    renderShell();
    notify(existing ? 'Data buku berhasil diperbarui.' : 'Buku berhasil ditambahkan.');
  }

  function handleMemberSubmit(form) {
    const fields = new FormData(form);
    const id = form.dataset.id;
    const existing = id ? memberById(id) : null;
    const email = String(fields.get('email')).trim().toLowerCase();
    const password = String(fields.get('password') || '');
    const linkedUser = existing ? data.users.find(user => user.memberId === existing.id) : null;
    if (data.users.some(user => user.email.toLowerCase() === email && user.id !== linkedUser?.id)) {
      notify('Email tersebut sudah digunakan oleh akun lain.', 'error');
      return;
    }
    if (!linkedUser && !password) {
      notify('Kata sandi diperlukan untuk membuat akun login anggota.', 'error');
      return;
    }
    const member = {
      id: existing?.id || makeId('member'),
      name: String(fields.get('name')).trim(),
      email,
      phone: String(fields.get('phone')).trim(),
      address: String(fields.get('address')).trim(),
      joinedAt: String(fields.get('joinedAt')),
      status: String(fields.get('status'))
    };
    if (password && password.length < 6) {
      notify('Kata sandi harus memiliki minimal 6 karakter.', 'error');
      return;
    }
    if (existing) {
      data.members[data.members.findIndex(item => item.id === existing.id)] = member;
      const user = linkedUser;
      if (user) {
        user.name = member.name;
        user.email = member.email;
        if (password) user.password = password;
      } else {
        data.users.push({ id: makeId('user'), role: 'member', memberId: member.id, name: member.name, email: member.email, password });
      }
    } else {
      data.members.push(member);
      data.users.push({ id: makeId('user'), role: 'member', memberId: member.id, name: member.name, email: member.email, password });
    }
    if (!saveData()) return;
    closeModal();
    renderShell();
    notify(existing ? 'Data anggota berhasil diperbarui.' : 'Anggota dan akun login berhasil dibuat.');
  }

  function borrowBook(bookId) {
    const book = bookById(bookId);
    if (currentUser.role !== 'member' || !book || book.stock <= 0) {
      notify('Peminjaman tidak dapat diproses. Periksa ketersediaan buku.', 'error');
      return;
    }
    const member = memberById(currentUser.memberId);
    if (!member || member.status !== 'Aktif') {
      notify('Status keanggotaan tidak aktif. Hubungi pustakawan.', 'error');
      return;
    }
    if (activeLoansForMember(member.id).length >= MAX_ACTIVE_LOANS) {
      notify(`Batas maksimal peminjaman adalah ${MAX_ACTIVE_LOANS} buku.`, 'warning');
      closeModal();
      return;
    }
    book.stock -= 1;
    data.loans.push({ id: makeId('loan'), bookId: book.id, memberId: member.id, borrowedAt: new Date().toISOString(), dueAt: dayOffset(LOAN_DAYS), returnedAt: null, status: 'Dipinjam', fine: 0, finePaid: false });
    if (!saveData()) {
      book.stock += 1;
      data.loans.pop();
      return;
    }
    closeModal();
    currentPage = 'loans';
    renderShell();
    notify(`Peminjaman "${book.title}" berhasil. Kembalikan dalam ${LOAN_DAYS} hari.`);
  }

  function returnLoan(loanId) {
    const loan = data.loans.find(item => item.id === loanId && item.status === 'Dipinjam');
    if (!loan) {
      notify('Transaksi aktif tidak ditemukan.', 'error');
      closeModal();
      return;
    }
    loan.returnedAt = new Date().toISOString();
    loan.status = 'Dikembalikan';
    loan.fine = overdueDays(loan.dueAt, loan.returnedAt) * FINE_PER_DAY;
    loan.finePaid = loan.fine === 0;
    const book = bookById(loan.bookId);
    if (book) book.stock += 1;
    if (!saveData()) {
      loan.returnedAt = null;
      loan.status = 'Dipinjam';
      loan.fine = 0;
      loan.finePaid = false;
      if (book) book.stock -= 1;
      return;
    }
    closeModal();
    renderShell();
    notify(loan.fine ? `Buku kembali. Denda ${formatCurrency(loan.fine)} tercatat belum dibayar.` : 'Buku berhasil dikembalikan tepat waktu.');
  }

  function deleteBook(bookId) {
    const book = bookById(bookId);
    if (!book) return;
    if (activeLoans().some(loan => loan.bookId === bookId)) {
      notify('Buku tidak dapat dihapus karena masih memiliki peminjaman aktif.', 'warning');
      return;
    }
    if (!window.confirm(`Hapus buku "${book.title}" dari katalog?`)) return;
    data.books = data.books.filter(item => item.id !== bookId);
    if (saveData()) { renderShell(); notify('Buku berhasil dihapus.'); }
  }

  function deleteMember(memberId) {
    const member = memberById(memberId);
    if (!member) return;
    if (activeLoansForMember(memberId).length) {
      notify('Anggota tidak dapat dihapus karena masih memiliki pinjaman aktif.', 'warning');
      return;
    }
    if (!window.confirm(`Hapus anggota "${member.name}" beserta akun loginnya? Riwayat transaksi tetap tersimpan.`)) return;
    data.members = data.members.filter(item => item.id !== memberId);
    data.users = data.users.filter(user => user.memberId !== memberId);
    if (saveData()) { renderShell(); notify('Data anggota dan akun login berhasil dihapus.'); }
  }

  function toggleFinePaid(loanId) {
    const loan = data.loans.find(item => item.id === loanId && item.fine > 0);
    if (!loan || loan.status !== 'Dikembalikan') return;
    loan.finePaid = true;
    if (saveData()) { renderShell(); notify('Denda ditandai sudah dibayar.'); }
  }

  function handleLogin(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonContent = submitButton.innerHTML;
    const error = document.getElementById('login-error');
    const restoreSubmitButton = () => {
      submitButton.disabled = false;
      submitButton.removeAttribute('aria-busy');
      submitButton.innerHTML = originalButtonContent;
    };

    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.innerHTML = `${icon('clock', 16)} Memeriksa...`;
    window.setTimeout(() => {
      const fields = new FormData(form);
      const email = String(fields.get('email')).trim().toLowerCase();
      const password = String(fields.get('password'));
      const user = data.users.find(item => item.email.toLowerCase() === email && item.password === password);
      if (!user) {
        error.textContent = 'Email atau kata sandi tidak sesuai.';
        error.classList.remove('hidden');
        restoreSubmitButton();
        return;
      }
      if (user.role === 'member') {
        const member = memberById(user.memberId);
        if (!member || member.status !== 'Aktif') {
          error.textContent = 'Akun anggota tidak aktif. Silakan hubungi pustakawan.';
          error.classList.remove('hidden');
          restoreSubmitButton();
          return;
        }
      }
      if (!saveSession(user.id)) {
        restoreSubmitButton();
        return;
      }
      currentUser = user;
      currentPage = 'dashboard';
      renderShell();
    }, 80);
  }

  function restoreSeed() {
    if (!window.confirm('Pulihkan data contoh? Semua perubahan data lokal saat ini akan diganti.')) return;
    data = createSeedData();
    saveData();
    saveSession(null);
    currentUser = null;
    renderLogin();
    notify('Data contoh berhasil dipulihkan.');
  }

  function navigate(page) {
    const item = navItems.find(nav => nav.id === page);
    if (!item || !item.roles.includes(currentUser.role)) return;
    currentPage = page;
    renderShell();
    const sidebar = app.querySelector('.sidebar');
    if (sidebar) sidebar.classList.remove('mobile-open');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function onAppClick(event) {
    const target = event.target.closest('[data-page],[data-action],[data-report]');
    if (!target) return;
    if (target.dataset.page) { navigate(target.dataset.page); return; }
    if (target.dataset.report) { reportType = target.dataset.report; renderShell(); return; }
    const action = target.dataset.action;
    switch (action) {
      case 'open-login':
        renderLogin();
        window.history.replaceState(null, '', window.location.pathname + window.location.search);
        window.scrollTo({ top: 0, behavior: 'instant' });
        break;
      case 'open-landing':
        renderLanding();
        window.history.replaceState(null, '', window.location.pathname + window.location.search);
        window.scrollTo({ top: 0, behavior: 'instant' });
        break;
      case 'toggle-landing-menu': {
        const menu = app.querySelector('.landing-nav-links');
        const isOpen = menu?.classList.toggle('is-open') || false;
        target.setAttribute('aria-expanded', String(isOpen));
        target.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
        break;
      }
      case 'close-landing-menu': {
        const menu = app.querySelector('.landing-nav-links');
        menu?.classList.remove('is-open');
        app.querySelector('[data-action="toggle-landing-menu"]')?.setAttribute('aria-expanded', 'false');
        break;
      }
      case 'toggle-password': {
        const passwordInput = app.querySelector('#login-password');
        if (!passwordInput) break;
        const showingPassword = passwordInput.type === 'password';
        passwordInput.type = showingPassword ? 'text' : 'password';
        target.setAttribute('aria-pressed', String(showingPassword));
        target.setAttribute('aria-label', showingPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        target.title = showingPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
        target.innerHTML = icon(showingPassword ? 'eye-off' : 'eye', 17);
        break;
      }
      case 'logout':
        saveSession(null);
        currentUser = null;
        categoryChart = null;
        renderLanding();
        break;
      case 'toggle-menu':
        app.querySelector('.sidebar')?.classList.toggle('mobile-open');
        break;
      case 'book-form': showBookForm(); break;
      case 'book-edit': showBookForm(bookById(target.dataset.id)); break;
      case 'book-detail': { const book = bookById(target.dataset.id); if (book) showBookDetail(book); break; }
      case 'book-delete': deleteBook(target.dataset.id); break;
      case 'member-form': showMemberForm(); break;
      case 'member-edit': showMemberForm(memberById(target.dataset.id)); break;
      case 'member-delete': deleteMember(target.dataset.id); break;
      case 'borrow': showBorrowConfirmation(bookById(target.dataset.id)); break;
      case 'confirm-borrow': borrowBook(target.dataset.id); break;
      case 'return-loan': { const loan = data.loans.find(item => item.id === target.dataset.id); if (loan) showReturnConfirmation(loan); break; }
      case 'confirm-return': returnLoan(target.dataset.id); break;
      case 'pay-fine': toggleFinePaid(target.dataset.id); break;
      case 'close-modal': closeModal(); break;
      case 'print': window.print(); break;
      case 'restore-seed': restoreSeed(); break;
      case 'my-loans': navigate('loans'); break;
    }
  }

  function onInput(event) {
    const input = event.target;
    if (input.matches('[data-search="books"]')) {
      bookSearch = input.value;
      rerenderPreservingInput(input, renderBooks);
    } else if (input.matches('[data-search="members"]')) {
      memberSearch = input.value;
      rerenderPreservingInput(input, renderMembers);
    } else if (input.matches('[data-search="loans"]')) {
      loanSearch = input.value;
      rerenderPreservingInput(input, renderLoans);
    }
  }

  function rerenderPreservingInput(input, renderer) {
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const html = renderer();
    const content = app.querySelector('.content-area');
    if (!content) return;
    content.innerHTML = html;
    const replacement = content.querySelector(`[data-search="${input.dataset.search}"]`);
    if (replacement) {
      replacement.focus();
      if (start !== null && end !== null) replacement.setSelectionRange(start, end);
    }
  }

  function onChange(event) {
    const select = event.target;
    if (select.matches('[data-filter="book-category"]')) {
      bookCategory = select.value;
      const content = app.querySelector('.content-area');
      if (content) content.innerHTML = renderBooks();
    }
  }

  function onSubmit(event) {
    const form = event.target;
    if (form.id === 'login-form') {
      event.preventDefault();
      handleLogin(form);
    } else if (form.matches('[data-form="book"]')) {
      event.preventDefault();
      handleBookSubmit(form);
    } else if (form.matches('[data-form="member"]')) {
      event.preventDefault();
      handleMemberSubmit(form);
    }
  }

  app.addEventListener('click', onAppClick);
  app.addEventListener('input', onInput);
  app.addEventListener('change', onChange);
  app.addEventListener('submit', onSubmit);
  modalRoot.addEventListener('click', event => {
    if (event.target === modalRoot.querySelector('.modal-backdrop')) closeModal();
    const button = event.target.closest('[data-action]');
    if (button) onAppClick(event);
  });
  modalRoot.addEventListener('submit', onSubmit);
  app.addEventListener('click', event => {
    const demo = event.target.closest('[data-demo]');
    if (!demo) return;
    const admin = demo.dataset.demo === 'admin';
    document.getElementById('login-email').value = admin ? 'admin@digilib.id' : 'anggota@digilib.id';
    document.getElementById('login-password').value = admin ? 'admin123' : 'anggota123';
  });

  if (currentUser) renderShell();
  else if (new URLSearchParams(window.location.search).get('login') === '1') renderLogin();
  else renderLanding();
})();
