<?php
declare(strict_types=1);

$portalUser = $portalUser ?? [];
$portalTitle = $portalTitle ?? 'DIGILIB SMART LIBRARY';
$portalDescription = $portalDescription ?? '';
$activePage = $activePage ?? '';
$isAdmin = in_array($portalUser['role'] ?? '', ['admin', 'pustakawan'], true);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0f766e">
  <title><?= digilibEscape($portalTitle) ?> — DIGILIB SMART LIBRARY</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= digilibEscape(digilibUrl('assets/styles.css?v=' . (string) filemtime(__DIR__ . '/../assets/styles.css'))) ?>">
</head>
<body class="min-h-screen bg-sand text-ink antialiased">
  <div class="app-shell flex">
    <aside class="sidebar flex min-h-screen flex-col border-r border-slate-100 bg-white px-4 py-5">
      <a href="<?= digilibUrl($isAdmin ? 'admin/index.php' : 'anggota/index.php') ?>" class="mb-9 flex items-center gap-3 px-2 no-underline">
        <span class="grid h-11 w-11 flex-none place-items-center rounded-2xl bg-teal-700 text-white">
          <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/></svg>
        </span>
        <span class="brand-copy min-w-0"><span class="display-font block text-sm font-extrabold tracking-wide text-ink">DIGILIB</span><span class="block text-[10px] font-semibold tracking-[.15em] text-slate-400">SMART LIBRARY</span></span>
      </a>
      <div class="nav-label mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[.16em] text-slate-400">Menu utama</div>
      <nav class="space-y-1">
        <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl($isAdmin ? 'admin/index.php' : 'anggota/index.php') ?>">
          <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg></span>
          <span class="nav-label">Dashboard</span>
        </a>
        <?php if (!$isAdmin): ?>
          <a class="nav-link <?= $activePage === 'books' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('anggota/buku.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg></span>
            <span class="nav-label">Data Buku</span>
          </a>
        <?php endif; ?>
        <a class="nav-link <?= $activePage === 'loans' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl($isAdmin ? 'admin/peminjaman.php' : 'anggota/peminjaman.php') ?>">
          <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m17 2 4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/></svg></span>
          <span class="nav-label">Peminjaman</span>
        </a>
        <?php if ($isAdmin): ?>
          <a class="nav-link <?= $activePage === 'returns' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('admin/pengembalian.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 14-4-4 4-4"/><path d="M5 10h10a5 5 0 0 1 0 10h-2"/></svg></span>
            <span class="nav-label">Pengembalian</span>
          </a>
          <a class="nav-link <?= $activePage === 'fines' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('admin/denda.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l3-2 3 2 3-2 3 2 4-2V2l-4 2-3-2-3 2-3-2z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span>
            <span class="nav-label">Denda</span>
          </a>
          <a class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('admin/laporan.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 13h6M10 17h6"/></svg></span>
            <span class="nav-label">Laporan</span>
          </a>
          <a class="nav-link <?= $activePage === 'books' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('admin/buku.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8"/></svg></span>
            <span class="nav-label">Data Buku</span>
          </a>
          <a class="nav-link <?= $activePage === 'members' ? 'active' : '' ?> flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 no-underline" href="<?= digilibUrl('admin/anggota.php') ?>">
            <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            <span class="nav-label">Data Anggota</span>
          </a>
        <?php endif; ?>
      </nav>
      <div class="mt-auto pt-8">
        <div class="sidebar-footer flex items-center gap-3 rounded-2xl bg-sand p-3">
          <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-teal-100 font-bold text-teal-700"><?= digilibEscape(mb_strtoupper(mb_substr($portalUser['nama_lengkap'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
          <span class="sidebar-footer-copy min-w-0 flex-1">
            <span class="block truncate text-xs font-bold text-ink"><?= digilibEscape($portalUser['nama_lengkap'] ?? '') ?></span>
            <span class="block text-[11px] text-slate-500"><?= $isAdmin ? 'Admin / Pustakawan' : 'Anggota' ?></span>
          </span>
          <form action="<?= digilibUrl('logout.php') ?>" method="post" class="no-print">
            <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
            <button class="icon-btn" type="submit" aria-label="Keluar" title="Keluar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
            </button>
          </form>
        </div>
      </div>
    </aside>
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Tutup menu"></button>
    <main class="main-panel min-h-screen flex-1">
      <header class="topbar flex h-[76px] items-center justify-between border-b border-slate-100 bg-white px-5 sm:px-8">
        <div class="flex items-center gap-3">
          <button class="mobile-menu icon-btn" type="button" data-portal-menu-toggle aria-label="Buka menu" aria-expanded="false">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div><div class="text-[11px] font-semibold text-slate-400">DIGILIB SMART LIBRARY</div><div class="display-font text-sm font-extrabold text-ink"><?= digilibEscape($portalTitle) ?></div></div>
        </div>
        <div class="topbar-user flex items-center gap-3">
          <span class="topbar-avatar"><?= digilibEscape(mb_strtoupper(mb_substr($portalUser['nama_lengkap'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
          <div class="text-right"><div class="text-xs font-bold text-ink"><?= digilibEscape($portalUser['nama_lengkap'] ?? '') ?></div><div class="text-[11px] text-slate-400"><?= $isAdmin ? 'Panel Admin' : 'Panel Anggota' ?></div></div>
        </div>
      </header>
      <script>
        (() => {
          const sidebar = document.querySelector('.sidebar');
          const toggle = document.querySelector('[data-portal-menu-toggle]');
          const backdrop = document.querySelector('.mobile-sidebar-backdrop');
          if (!sidebar || !toggle || !backdrop) return;
          const closeMenu = () => {
            sidebar.classList.remove('mobile-open');
            document.body.classList.remove('menu-open');
            toggle.setAttribute('aria-expanded', 'false');
          };
          toggle.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('mobile-open');
            document.body.classList.toggle('menu-open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
          });
          backdrop.addEventListener('click', closeMenu);
          sidebar.querySelectorAll('nav a').forEach(link => link.addEventListener('click', closeMenu));
          document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeMenu();
          });
        })();
      </script>
      <section class="content-area p-5 sm:p-8">
        <div class="page-header mb-6 flex items-center justify-between gap-4">
          <div><h1 class="display-font text-xl font-extrabold text-ink"><?= digilibEscape($portalTitle) ?></h1><p class="mt-1 text-sm text-slate-500"><?= digilibEscape($portalDescription) ?></p></div>
          <span class="badge badge-green"><?= $isAdmin ? 'Admin / Pustakawan' : 'Anggota' ?></span>
        </div>
