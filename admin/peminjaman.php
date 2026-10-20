<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Data Peminjaman';
$portalDescription = 'Pantau transaksi peminjaman seluruh anggota.';
$activePage = 'loans';

$loans = $pdo->query(
    'SELECT p.`peminjaman_id`, p.`tanggal_pinjam`, p.`tanggal_jatuh_tempo`, p.`status`,
            a.`nomor_anggota`, u.`nama_lengkap`, u.`email`,
            b.`isbn`, b.`judul`, b.`penulis`
     FROM `peminjaman` p
     INNER JOIN `anggota` a ON a.`anggota_id` = p.`anggota_id`
     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
     INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id`
     ORDER BY p.`tanggal_pinjam` DESC, p.`peminjaman_id` DESC'
)->fetchAll();

$activeCount = 0;
foreach ($loans as $loan) {
    if ($loan['status'] === 'dipinjam') {
        $activeCount++;
    }
}
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Transaksi seluruh anggota</h2><p class="mt-1 text-xs text-slate-400"><?= count($loans) ?> transaksi tercatat</p></div>
    <span class="badge badge-amber"><?= $activeCount ?> peminjaman aktif</span>
  </div>
  <div class="table-wrap">
    <?php if ($loans): ?>
      <table class="data-table">
        <thead><tr><th>Anggota</th><th>Buku</th><th>ISBN</th><th>Tanggal pinjam</th><th>Jatuh tempo</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($loans as $loan): ?>
            <tr>
              <td><div class="font-bold text-ink"><?= digilibEscape($loan['nama_lengkap']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['email']) ?> · <?= digilibEscape($loan['nomor_anggota']) ?></div></td>
              <td><div class="font-semibold text-ink"><?= digilibEscape($loan['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['penulis']) ?></div></td>
              <td class="text-xs"><?= digilibEscape($loan['isbn'] ?? '—') ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_pinjam']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td><span class="badge <?= $loan['status'] === 'dipinjam' ? 'badge-amber' : ($loan['status'] === 'dikembalikan' ? 'badge-green' : 'badge-gray') ?>"><?= digilibEscape(ucfirst($loan['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="px-5 py-12 text-center">
        <p class="text-sm font-semibold text-slate-600">Belum ada transaksi peminjaman.</p>
        <p class="mt-1 text-xs text-slate-400">Transaksi yang dibuat anggota akan muncul di halaman ini.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
