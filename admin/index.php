<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$database = digilibDatabaseRead();
$portalTitle = 'Dashboard Admin';
$portalDescription = 'Ringkasan kondisi perpustakaan berdasarkan data terbaru.';
$activePage = 'dashboard';

$lowStockLimit = 3;
$activeBooks = array_values(array_filter($database['buku'], static function (array $book): bool {
    return $book['status'] === 'aktif';
}));
$today = digilibDatabaseToday();
$lateLoanCount = 0;
foreach ($database['peminjaman'] as $loan) {
    if ($loan['status'] === 'dipinjam' && $loan['tanggal_jatuh_tempo'] < $today) {
        $lateLoanCount++;
    }
}
$unpaidFines = array_values(array_filter($database['denda'], static function (array $fine): bool {
    return $fine['status_pembayaran'] === 'belum_dibayar';
}));
$stats = [
    'total_judul' => count($activeBooks),
    'total_eksemplar' => array_sum(array_map('intval', array_column($activeBooks, 'stok'))),
    'total_anggota' => count($database['anggota']),
    'anggota_aktif' => count(array_filter($database['anggota'], static function (array $member): bool {
        return $member['status'] === 'aktif';
    })),
    'buku_dipinjam' => count(array_filter($database['peminjaman'], static function (array $loan): bool {
        return $loan['status'] === 'dipinjam';
    })),
    'terlambat' => $lateLoanCount,
    'denda_belum_dibayar' => array_sum(array_map('floatval', array_column($unpaidFines, 'jumlah_denda'))),
    'transaksi_denda' => count($unpaidFines),
    'stok_rendah' => count(array_filter($activeBooks, static function (array $book) use ($lowStockLimit): bool {
        return (int) $book['stok'] <= $lowStockLimit;
    })),
];

$lowStockBooks = array_values(array_filter($activeBooks, static function (array $book) use ($lowStockLimit): bool {
    return (int) $book['stok'] <= $lowStockLimit;
}));
usort($lowStockBooks, static function (array $left, array $right): int {
    $stockOrder = (int) $left['stok'] <=> (int) $right['stok'];
    return $stockOrder !== 0 ? $stockOrder : strcasecmp($left['judul'], $right['judul']);
});
$lowStockBooks = array_slice($lowStockBooks, 0, 8);

$categoryStats = [];
foreach ($database['kategori'] as $category) {
    $count = count(array_filter($database['buku'], static function (array $book) use ($category): bool {
        return (int) $book['kategori_id'] === (int) $category['kategori_id'];
    }));
    if ($count > 0) {
        $categoryStats[] = ['nama_kategori' => $category['nama_kategori'], 'jumlah_buku' => $count];
    }
}
usort($categoryStats, static function (array $left, array $right): int {
    return strcasecmp($left['nama_kategori'], $right['nama_kategori']);
});

$loanStats = ['terlambat' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'dibatalkan' => 0];
foreach ($database['peminjaman'] as $loan) {
    if ($loan['status'] === 'dipinjam' && $loan['tanggal_jatuh_tempo'] < $today) {
        $loanStats['terlambat']++;
    } elseif (isset($loanStats[$loan['status']])) {
        $loanStats[$loan['status']]++;
    }
}

$recentLoans = [];
foreach ($database['peminjaman'] as $loan) {
    $joined = digilibDatabaseLoanRecord($database, (int) $loan['peminjaman_id']);
    if ($joined !== null) {
        $recentLoans[] = array_merge($loan, [
            'nama_lengkap' => $joined['nama_lengkap'],
            'judul' => $joined['buku']['judul'],
        ]);
    }
}
usort($recentLoans, static function (array $left, array $right): int {
    $dateOrder = strcmp($right['tanggal_pinjam'], $left['tanggal_pinjam']);
    return $dateOrder !== 0 ? $dateOrder : ((int) $right['peminjaman_id'] <=> (int) $left['peminjaman_id']);
});
$recentLoans = array_slice($recentLoans, 0, 5);

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
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Total koleksi buku</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['total_eksemplar'], 0, ',', '.') ?></p>
    <p class="mt-1 text-[11px] text-slate-400"><?= number_format((int) $stats['total_judul'], 0, ',', '.') ?> judul dalam katalog</p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>">Kelola koleksi buku</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Anggota terdaftar</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['total_anggota'], 0, ',', '.') ?></p>
    <p class="mt-1 text-[11px] text-slate-400"><?= number_format((int) $stats['anggota_aktif'], 0, ',', '.') ?> anggota aktif</p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>">Kelola data anggota</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Sedang dipinjam</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['buku_dipinjam'], 0, ',', '.') ?></p>
    <p class="mt-1 text-[11px] text-slate-400"><?= number_format((int) $stats['terlambat'], 0, ',', '.') ?> peminjaman terlambat</p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/peminjaman.php')) ?>">Pantau peminjaman</a>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Denda belum dibayar</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink">Rp<?= number_format((float) $stats['denda_belum_dibayar'], 0, ',', '.') ?></p>
    <p class="mt-1 text-[11px] text-slate-400"><?= number_format((int) $stats['transaksi_denda'], 0, ',', '.') ?> transaksi denda</p>
    <a class="mt-3 inline-block text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/denda.php')) ?>">Lihat denda</a>
  </article>
</div>

<div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
    <div class="mb-5">
      <h2 class="display-font text-sm font-extrabold text-ink">Koleksi berdasarkan kategori</h2>
      <p class="mt-1 text-xs text-slate-500">Jumlah buku pada setiap kategori, diperbarui dari database.</p>
    </div>
    <div class="relative mx-auto h-72 w-full max-w-lg">
      <canvas id="category-chart" aria-label="Grafik jumlah buku berdasarkan kategori" role="img"></canvas>
    </div>
  </section>
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
    <div class="mb-5">
      <div class="flex items-start justify-between gap-3">
        <div><h2 class="display-font text-sm font-extrabold text-ink">Akses cepat</h2><p class="mt-1 text-xs text-slate-500">Kelola kegiatan perpustakaan.</p></div>
        <span class="badge badge-green">DIGILIB</span>
      </div>
    </div>
    <div class="grid gap-3">
      <a class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 no-underline transition hover:border-teal-200 hover:bg-teal-50" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>"><span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-50 font-bold text-teal-700">▤</span><span><b class="block text-xs text-ink">Koleksi buku</b><small class="text-[11px] text-slate-400">Atur katalog buku</small></span></a>
      <a class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 no-underline transition hover:border-blue-200 hover:bg-blue-50" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 font-bold text-blue-700">♙</span><span><b class="block text-xs text-ink">Data anggota</b><small class="text-[11px] text-slate-400">Kelola keanggotaan</small></span></a>
      <a class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 no-underline transition hover:border-amber-200 hover:bg-amber-50" href="<?= digilibEscape(digilibUrl('admin/pengembalian.php')) ?>"><span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 font-bold text-amber-700">↩</span><span><b class="block text-xs text-ink">Proses pengembalian</b><small class="text-[11px] text-slate-400"><?= number_format((int) $stats['buku_dipinjam'], 0, ',', '.') ?> buku aktif dipinjam</small></span></a>
      <a class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 no-underline transition hover:border-rose-200 hover:bg-rose-50" href="<?= digilibEscape(digilibUrl('admin/laporan.php')) ?>"><span class="grid h-10 w-10 place-items-center rounded-xl bg-rose-50 font-bold text-rose-700">▥</span><span><b class="block text-xs text-ink">Lihat laporan</b><small class="text-[11px] text-slate-400">Cetak ringkasan data</small></span></a>
    </div>
  </section>
</div>

<section class="card-shadow mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold text-ink">Aktivitas peminjaman terbaru</h2><p class="mt-1 text-xs text-slate-500">Transaksi terbaru dari seluruh anggota.</p></div>
    <a class="text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('admin/peminjaman.php')) ?>">Lihat semua →</a>
  </div>
  <?php if ($recentLoans): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Buku</th><th>Anggota</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentLoans as $loan): ?>
            <?php $late = $loan['status'] === 'dipinjam' && $loan['tanggal_jatuh_tempo'] < date('Y-m-d'); ?>
            <tr>
              <td class="font-semibold text-ink"><?= digilibEscape($loan['judul']) ?></td>
              <td><?= digilibEscape($loan['nama_lengkap']) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_pinjam']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td><span class="badge <?= $late ? 'badge-red' : ($loan['status'] === 'dipinjam' ? 'badge-amber' : 'badge-green') ?>"><?= $late ? 'Terlambat' : digilibEscape(ucfirst($loan['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada aktivitas peminjaman.</p>
  <?php endif; ?>
</section>

<div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
    <div class="mb-5">
      <h2 class="display-font text-sm font-extrabold text-ink">Statistik peminjaman</h2>
      <p class="mt-1 text-xs text-slate-500">Transaksi dikelompokkan menurut status; pinjaman aktif yang melewati jatuh tempo ditampilkan terpisah.</p>
    </div>
    <div class="relative mx-auto h-72 max-w-lg">
      <canvas id="loan-chart" aria-label="Grafik statistik status peminjaman" role="img"></canvas>
    </div>
  </section>
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
</div>

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
      plugins: {
        legend: {
          position: 'right',
          align: 'center',
          labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 14 }
        }
      }
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
