<?php
declare(strict_types=1);

$landingLoginUrl = digilibEscape(digilibUrl('index.php?login=1'));
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#17364b">
  <title>DIGILIB SMART LIBRARY — Digital Library</title>
  <meta name="description" content="DIGILIB SMART LIBRARY membantu pengguna menjelajahi koleksi dan aktivitas perpustakaan digital.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= digilibEscape(digilibUrl('assets/styles.css?v=' . (string) filemtime(__DIR__ . '/../assets/styles.css'))) ?>">
</head>
<body>
  <main class="landing-page" id="home">
    <header class="landing-nav">
      <a class="landing-brand" href="#home" aria-label="DIGILIB Smart Library, beranda">
        <span class="landing-brand-mark" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg></span>
        <span><strong>DIGILIB</strong><small>SMART LIBRARY</small></span>
      </a>
      <nav class="landing-nav-links" id="landing-nav-links" aria-label="Navigasi utama">
        <a href="#home">Home</a><a href="#about">About</a><a href="#features">Features</a><a href="#services">Services</a><a href="#contact">Contact</a>
        <a class="landing-mobile-login" href="<?= $landingLoginUrl ?>">Login</a>
        <a class="landing-mobile-cta" href="<?= $landingLoginUrl ?>">Get Started <span aria-hidden="true">&#8594;</span></a>
      </nav>
      <div class="landing-nav-actions"><a class="landing-login-button" href="<?= $landingLoginUrl ?>">Login</a><a class="landing-cta-button" href="<?= $landingLoginUrl ?>">Get Started <span aria-hidden="true">&#8594;</span></a></div>
      <button class="landing-menu-button" type="button" data-landing-menu aria-label="Buka navigasi" aria-controls="landing-nav-links" aria-expanded="false"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    </header>

    <section class="landing-hero" aria-labelledby="landing-title">
      <div class="landing-hero-content">
        <p class="landing-eyebrow"><span></span> DIGILIB SMART LIBRARY</p>
        <h1 id="landing-title">Discover Knowledge.<br><em>Explore Possibilities.</em></h1>
        <p class="landing-hero-subtitle">Your Digital Gateway to Knowledge and Learning.</p>
        <p class="landing-hero-description">DIGILIB SMART LIBRARY adalah sistem informasi perpustakaan digital yang membantu pengguna mengakses, menemukan, dan mengelola koleksi buku serta aktivitas perpustakaan secara lebih mudah, cepat, dan terorganisir.</p>
        <div class="landing-hero-actions"><a class="landing-cta-button landing-cta-light" href="<?= $landingLoginUrl ?>">Explore Library <span aria-hidden="true">&#8594;</span></a><a class="landing-text-link" href="<?= $landingLoginUrl ?>">Get Started <span aria-hidden="true">&#8594;</span></a></div>
      </div>
      <a class="landing-scroll-cue" href="#about" aria-label="Lanjut ke informasi DIGILIB"><span></span> Scroll to explore</a>
    </section>

    <section class="landing-about landing-section" id="about">
      <div class="landing-section-kicker">ABOUT DIGILIB</div>
      <div class="landing-about-grid">
        <h2>More Than<br><em>Just a Library</em></h2>
        <div class="landing-about-copy">
          <p class="landing-lead">Satu ruang digital untuk koleksi, anggota, dan aktivitas perpustakaan.</p>
          <p>DIGILIB SMART LIBRARY mendukung pengelolaan perpustakaan dari katalog buku hingga administrasi peminjaman, pengembalian, dan laporan. Informasi disusun dalam satu alur yang mudah dijelajahi oleh pengelola maupun anggota.</p>
          <a class="landing-inline-link" href="<?= $landingLoginUrl ?>">Masuk dan jelajahi sistem <span aria-hidden="true">&#8594;</span></a>
        </div>
      </div>
    </section>

    <section class="landing-features landing-section" id="features">
      <div class="landing-section-kicker">WHAT YOU CAN DO</div>
      <div class="landing-section-heading"><div><h2>Everything in its<br><em>right place.</em></h2></div><p>Alat yang dibutuhkan untuk menjaga aktivitas perpustakaan tetap tertata, dari katalog sampai laporan.</p></div>
      <div class="landing-feature-grid">
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg></span><h3>Book Management</h3><p>Kelola ISBN, detail bibliografi, kategori, stok, dan cover koleksi.</p></article>
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span><h3>Member Management</h3><p>Atur profil, akun, status aktif, dan informasi keanggotaan.</p></article>
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 5h16M4 12h16M4 19h16"/></svg></span><h3>Organized Catalog</h3><p>Temukan buku melalui pencarian judul, ISBN, penulis, atau kategori.</p></article>
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4M21 13v2a3 3 0 0 1-3 3H3"/></svg></span><h3>Book Circulation</h3><p>Pantau peminjaman, jatuh tempo, dan riwayat transaksi anggota.</p></article>
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2l-4 2-3-2-3 2-3-2z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span><h3>Returns &amp; Fines</h3><p>Catat pengembalian, hitung keterlambatan, dan pantau status denda.</p></article>
        <article class="landing-feature"><span class="landing-feature-icon" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-5 5"/></svg></span><h3>Library Reports</h3><p>Tinjau laporan buku, anggota, sirkulasi, pengembalian, dan denda.</p></article>
      </div>
    </section>

    <section class="landing-services landing-section" id="services">
      <div class="landing-section-kicker">HOW IT WORKS</div>
      <div class="landing-section-heading"><div><h2>A clear path<br><em>to every book.</em></h2></div><p>Mulai dari menemukan layanan hingga mengelola aktivitas sesuai peran pengguna.</p></div>
      <ol class="landing-steps">
        <li><span>01</span><div><h3>Discover</h3><p>Kenali koleksi dan layanan perpustakaan.</p></div></li>
        <li><span>02</span><div><h3>Access</h3><p>Masuk dengan akun admin atau anggota.</p></div></li>
        <li><span>03</span><div><h3>Explore</h3><p>Buka katalog dan fitur sesuai hak akses.</p></div></li>
        <li><span>04</span><div><h3>Manage</h3><p>Kelola aktivitas perpustakaan secara teratur.</p></div></li>
      </ol>
    </section>

    <section class="landing-benefits landing-section">
      <div class="landing-section-kicker">BUILT FOR BETTER FLOW</div>
      <div class="landing-benefits-grid"><h2>Less friction.<br><em>More discovery.</em></h2><ul>
        <li><span aria-hidden="true">&#10003;</span> Efficient library management</li>
        <li><span aria-hidden="true">&#10003;</span> Organized book collections</li>
        <li><span aria-hidden="true">&#10003;</span> Simplified circulation process</li>
        <li><span aria-hidden="true">&#10003;</span> Better data management</li>
        <li><span aria-hidden="true">&#10003;</span> Accessible digital experience</li>
      </ul></div>
    </section>

    <section class="landing-contact" id="contact">
      <p class="landing-eyebrow"><span></span> YOUR NEXT CHAPTER STARTS HERE</p>
      <h2>Start Your Digital Library<br><em>Journey Today.</em></h2>
      <p>Experience a more organized and efficient way to manage library activities.</p>
      <a class="landing-cta-button landing-cta-light" href="<?= $landingLoginUrl ?>">Access Your Library <span aria-hidden="true">&#8594;</span></a>
    </section>

    <footer class="landing-footer">
      <a class="landing-brand" href="#home" aria-label="DIGILIB Smart Library, kembali ke atas">
        <span class="landing-brand-mark" aria-hidden="true"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg></span>
        <span><strong>DIGILIB</strong><small>SMART LIBRARY</small></span>
      </a>
      <p>Ruang digital untuk mengelola dan menjelajahi layanan perpustakaan.</p>
      <nav aria-label="Footer navigation"><a href="#about">About</a><a href="#features">Features</a><a href="#services">Services</a><a href="<?= $landingLoginUrl ?>">Login</a></nav>
      <small class="landing-copyright">&copy; <?= date('Y') ?> DIGILIB SMART LIBRARY</small>
    </footer>
  </main>
  <script src="<?= digilibEscape(digilibUrl('assets/landing.js')) ?>" defer></script>
</body>
</html>