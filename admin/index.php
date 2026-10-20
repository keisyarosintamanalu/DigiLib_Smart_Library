<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Dashboard Admin';
$portalDescription = 'Ringkasan kondisi perpustakaan berdasarkan data terbaru.';
$activePage = 'dashboard';

$lowStockLimit = 3;
$statsStatement = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM `buku`) AS `total_buku`,
        (SELECT COUNT(*) FROM `anggota`) AS `total_anggota`,
        (SELECT COUNT(*) FROM `peminjaman` WHERE `status` = \'dipinjam\') AS `buku_dipinjam`,
        (SELECT COALESCE(SUM(`jumlah_denda`), 0) FROM `denda`) AS `total_denda`,
        (SELECT COUNT(*) FROM `buku` WHERE `status` = \'aktif\' AND `stok` <= :batas_stok) AS `stok_rendah`'
);
$statsStatement->execute(['batas_stok' => $lowStockLimit]);
$stats = $statsStatement->fetch();
if ($stats === false) {
    throw new RuntimeException('Dashboard statistics could not be loaded.');
}

$lowStockStatement = $pdo->prepare(
    'SELECT `judul`, `isbn`, `stok`, `rak`
     FROM `buku`
     WHERE `status` = \'aktif\' AND `stok` <= :batas_stok
     ORDER BY `stok` ASC, `judul` ASC
     LIMIT 8'
);
$lowStockStatement->execute(['batas_stok' => $lowStockLimit]);
$lowStockBooks = $lowStockStatement->fetchAll();

$categoryStats = $pdo->query(
    'SELECT k.`nama_kategori`, COUNT(b.`buku_id`) AS `jumlah_buku`
     FROM `kategori` k
     LEFT JOIN `buku` b ON b.`kategori_id` = k.`kategori_id`
     GROUP BY k.`kategori_id`, k.`nama_kategori`
     HAVING COUNT(b.`buku_id`) > 0
     ORDER BY k.`nama_kategori`'
)->fetchAll();

$loanStats = $pdo->query(
    'SELECT
        COALESCE(SUM(CASE WHEN `status` = \'dipinjam\' AND `tanggal_jatuh_tempo` < CURRENT_DATE THEN 1 ELSE 0 END), 0) AS `terlambat`,
        COALESCE(SUM(CASE WHEN `status` = \'dipinjam\' AND `tanggal_jatuh_tempo` >= CURRENT_DATE THEN 1 ELSE 0 END), 0) AS `dipinjam`,
        COALESCE(SUM(CASE WHEN `status` = \'dikembalikan\' THEN 1 ELSE 0 END), 0) AS `dikembalikan`,
        COALESCE(SUM(CASE WHEN `status` = \'dibatalkan\' THEN 1 ELSE 0 END), 0) AS `dibatalkan`
     FROM `peminjaman`'
)->fetch();
if ($loanStats === false) {
    throw new RuntimeException('Loan statistics could not be loaded.');
}

$categoryChartData = [
    'labels' => array_column($categoryStats, 'nama_kategori'),
    'values' => array_map('intval', array_column($categoryStats, 'jumlah_buku')),
];
$loanChartData = [
    'labels' => ['Dipinjam', 'Terlambat', 'Dikembalikan', 'Dibatalkan'],
    'values' => [
        (int) $loanStats['dipinjam'],
        (int) $loanStats['terlambat'],
        (int) $loanStats['dikembalikan'],
        (int) $loanStats['dibatalkan'],
    ],
];
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Total buku</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['total_buku'], 0, ',', '.') ?></p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>">Kelola data buku</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Total anggota</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['total_anggota'], 0, ',', '.') ?></p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>">Kelola data anggota</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Buku sedang dipinjam</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['buku_dipinjam'], 0, ',', '.') ?></p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/peminjaman.php')) ?>">Lihat transaksi</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Total denda tercatat</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink">Rp<?= number_format((float) $stats['total_denda'], 0, ',', '.') ?></p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/denda.php')) ?>">Lihat rincian denda</a>
  </article>
  <article class="card-shadow rounded-2xl border border-amber-100 bg-amber-50 p-5">
    <p class="text-xs font-semibold text-amber-800">Stok rendah (1–3)</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-amber-900"><?= number_format((int) $stats['stok_rendah'], 0, ',', '.') ?></p>
    <p class="mt-3 text-xs text-amber-800">Judul buku aktif perlu diperiksa</p>
  </article>
</div>

<div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
    <div class="mb-5">
      <h2 class="display-font text-sm font-extrabold text-ink">Koleksi berdasarkan kategori</h2>
      <p class="mt-1 text-xs text-slate-500">Jumlah buku pada setiap kategori, diperbarui dari database.</p>
    </div>
    <div class="relative mx-auto h-72 max-w-lg">
      <canvas id="category-chart" aria-label="Grafik jumlah buku berdasarkan kategori" role="img"></canvas>
    </div>
  </section>
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
    <div class="mb-5">
      <h2 class="display-font text-sm font-extrabold text-ink">Statistik peminjaman</h2>
      <p class="mt-1 text-xs text-slate-500">Transaksi dikelompokkan menurut status; pinjaman aktif yang melewati jatuh tempo ditampilkan terpisah.</p>
    </div>
    <div class="relative mx-auto h-72 max-w-lg">
      <canvas id="loan-chart" aria-label="Grafik statistik status peminjaman" role="img"></canvas>
    </div>
  </section>
</div>

<section class="card-shadow mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div>
      <h2 class="display-font text-sm font-extrabold text-ink">Buku dengan stok rendah</h2>
      <p class="mt-1 text-xs text-slate-500">Buku aktif dengan stok maksimal <?= $lowStockLimit ?> eksemplar, termasuk stok habis.</p>
    </div>
    <a class="btn btn-soft no-underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>">Kelola buku</a>
  </div>
  <?php if ($lowStockBooks): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Judul buku</th><th>ISBN</th><th>Rak</th><th>Stok</th></tr></thead>
        <tbody>
          <?php foreach ($lowStockBooks as $book): ?>
            <tr>
              <td class="font-semibold text-ink"><?= digilibEscape($book['judul']) ?></td>
              <td><?= digilibEscape($book['isbn'] ?? '—') ?></td>
              <td><?= digilibEscape($book['rak']) ?></td>
              <td><span class="badge <?= (int) $book['stok'] === 0 ? 'badge-red' : 'badge-amber' ?>"><?= (int) $book['stok'] === 0 ? 'Habis' : number_format((int) $book['stok'], 0, ',', '.') . ' tersedia' ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada buku aktif dengan stok rendah.</p>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
  const categoryChartData = <?= json_encode($categoryChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
  const loanChartData = <?= json_encode($loanChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
  const chartColors = ['#0f766e', '#14b8a6', '#2dd4bf', '#99f6e4', '#f59e0b', '#f87171', '#60a5fa', '#a78bfa'];

  new Chart(document.getElementById('category-chart'), {
    type: 'doughnut',
    data: {
      labels: categoryChartData.labels,
      datasets: [{ data: categoryChartData.values, backgroundColor: chartColors, borderColor: '#fff', borderWidth: 3 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } } }
    }
  });

  new Chart(document.getElementById('loan-chart'), {
    type: 'bar',
    data: {
      labels: loanChartData.labels,
      datasets: [{ label: 'Jumlah transaksi', data: loanChartData.values, backgroundColor: ['#0f766e', '#f59e0b', '#14b8a6', '#94a3b8'], borderRadius: 8, maxBarThickness: 56 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { display: false } }
    }
  });
</script>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
