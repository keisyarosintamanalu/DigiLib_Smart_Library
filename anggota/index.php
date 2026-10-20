<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['anggota']);
$portalTitle = 'Halaman Anggota';
$portalDescription = 'Anda berhasil masuk ke area anggota perpustakaan.';
$activePage = 'dashboard';
require __DIR__ . '/../includes/portal_header.php';
?>
<section class="card-shadow rounded-2xl border border-slate-100 bg-white p-6 sm:p-8">
  <div class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-700">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
  </div>
  <h2 class="display-font text-lg font-extrabold">Halo, <?= digilibEscape($portalUser['nama_lengkap']) ?></h2>
  <p class="mt-2 text-sm leading-6 text-slate-500">Sesi anggota telah aktif. Halaman ini hanya dapat dibuka oleh akun dengan status keanggotaan aktif.</p>
  <a class="btn btn-primary mt-5 no-underline" href="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">Lihat buku dan pinjam</a>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
