<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Data Peminjaman';
$portalDescription = 'Pantau transaksi peminjaman seluruh anggota.';
$activePage = 'loans';

$search = trim((string) ($_GET['q'] ?? ''));
$conditions = [];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(p.`status` LIKE :status OR b.`judul` LIKE :judul OR u.`nama_lengkap` LIKE :nama OR u.`email` LIKE :email OR a.`nomor_anggota` LIKE :nomor)';
    $term = '%' . $search . '%';
    $parameters = [
        'status' => $term,
        'judul' => $term,
        'nama' => $term,
        'email' => $term,
        'nomor' => $term,
    ];
}
$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$loansStatement = $pdo->prepare(
    'SELECT p.`peminjaman_id`, p.`tanggal_pinjam`, p.`tanggal_jatuh_tempo`, p.`status`,
            a.`nomor_anggota`, u.`nama_lengkap`, u.`email`,
            b.`isbn`, b.`judul`, b.`penulis`
     FROM `peminjaman` p
     INNER JOIN `anggota` a ON a.`anggota_id` = p.`anggota_id`
     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
     INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id`
     ' . $whereSql . '
     ORDER BY p.`tanggal_pinjam` DESC, p.`peminjaman_id` DESC'
);
$loansStatement->execute($parameters);
$loans = $loansStatement->fetchAll();

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
  <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-end" action="<?= digilibEscape(digilibUrl('admin/peminjaman.php')) ?>" method="get">
    <div class="min-w-0 flex-1"><label class="field-label" for="search-loans">Cari transaksi</label><input class="field" id="search-loans" type="search" name="q" value="<?= digilibEscape($search) ?>" placeholder="Nama, email, nomor anggota, buku, atau status"></div>
    <button class="btn btn-primary" type="submit">Cari</button>
    <?php if ($search !== ''): ?><a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/peminjaman.php')) ?>">Reset</a><?php endif; ?>
  </form>
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
