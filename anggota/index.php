<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/book_covers.php';
$portalUser = digilibRequireRole(['anggota']);
$database = digilibDatabaseRead();
$memberId = null;
foreach ($database['anggota'] as $member) {
    if ((int) $member['user_id'] === (int) $portalUser['user_id'] && $member['status'] === 'aktif') {
        $memberId = (int) $member['anggota_id'];
        break;
    }
}
if ($memberId === null) {
    throw new RuntimeException('Active member profile could not be loaded.');
}

$activeBooks = array_values(array_filter($database['buku'], static function (array $book): bool {
    return $book['status'] === 'aktif';
}));
$memberLoans = array_values(array_filter($database['peminjaman'], static function (array $loan) use ($memberId): bool {
    return (int) $loan['anggota_id'] === $memberId;
}));
$activeMemberLoans = array_values(array_filter($memberLoans, static function (array $loan): bool {
    return $loan['status'] === 'dipinjam';
}));
$unpaidFineTotal = 0.0;
foreach ($database['denda'] as $fine) {
    if ($fine['status_pembayaran'] !== 'belum_dibayar') {
        continue;
    }
    foreach ($database['pengembalian'] as $returnRecord) {
        if ((int) $returnRecord['pengembalian_id'] !== (int) $fine['pengembalian_id']) {
            continue;
        }
        foreach ($database['peminjaman'] as $loan) {
            if ((int) $loan['peminjaman_id'] === (int) $returnRecord['peminjaman_id']
                && (int) $loan['anggota_id'] === $memberId) {
                $unpaidFineTotal += (float) $fine['jumlah_denda'];
                break 2;
            }
        }
    }
}
$stats = [
    'total_judul' => count($activeBooks),
    'buku_tersedia' => array_sum(array_map('intval', array_column($activeBooks, 'stok'))),
    'pinjaman_aktif' => count($activeMemberLoans),
    'denda_belum_dibayar' => $unpaidFineTotal,
];

$activeLoans = [];
foreach ($activeMemberLoans as $loan) {
    $book = digilibDatabaseBookRecord($database, (int) $loan['buku_id']);
    if ($book === null) {
        continue;
    }
    $loan['judul'] = $book['judul'];
    $loan['penulis'] = $book['penulis'];
    $loan['selisih_hari'] = max(0, (int) (new DateTimeImmutable($loan['tanggal_jatuh_tempo']))
        ->diff(new DateTimeImmutable(digilibDatabaseToday()))->format('%r%a'));
    $activeLoans[] = $loan;
}
usort($activeLoans, static function (array $left, array $right): int {
    $dateOrder = strcmp($left['tanggal_jatuh_tempo'], $right['tanggal_jatuh_tempo']);
    return $dateOrder !== 0 ? $dateOrder : ((int) $right['peminjaman_id'] <=> (int) $left['peminjaman_id']);
});
$activeLoans = array_slice($activeLoans, 0, 5);

$recommendedBooks = array_values(array_filter($activeBooks, static function (array $book): bool {
    return (int) $book['stok'] > 0;
}));
usort($recommendedBooks, static function (array $left, array $right): int {
    return strcasecmp($left['judul'], $right['judul']);
});
$recommendedBooks = array_slice($recommendedBooks, 0, 4);

$portalTitle = 'Dashboard Anggota';
$portalDescription = 'Temukan bacaan berikutnya dan pantau peminjaman Anda.';
$activePage = 'dashboard';
require __DIR__ . '/../includes/portal_header.php';
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="text-xs font-bold uppercase tracking-[.15em] text-teal-700">Ruang baca Anda</p>
    <h2 class="display-font mt-2 text-2xl font-extrabold text-ink">Halo, <?= digilibEscape(explode(' ', trim($portalUser['nama_lengkap']))[0]) ?> 👋</h2>
    <p class="mt-1 text-sm text-slate-500">Temukan bacaan berikutnya dan pantau peminjaman Anda.</p>
  </div>
  <a class="btn btn-primary no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Jelajahi buku <span aria-hidden="true">→</span></a>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Pinjaman aktif</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['pinjaman_aktif'], 0, ',', '.') ?><span class="ml-1 text-sm font-semibold text-slate-400">/ 3 buku</span></p>
    <p class="mt-2 text-xs text-slate-500">Masa pinjam 7 hari</p>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Buku tersedia</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink"><?= number_format((int) $stats['buku_tersedia'], 0, ',', '.') ?></p>
    <p class="mt-2 text-xs text-slate-500"><?= number_format((int) $stats['total_judul'], 0, ',', '.') ?> judul dalam katalog</p>
  </article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5">
    <p class="text-xs font-semibold text-slate-500">Denda belum dibayar</p>
    <p class="display-font mt-3 text-3xl font-extrabold text-ink">Rp<?= number_format((float) $stats['denda_belum_dibayar'], 0, ',', '.') ?></p>
    <p class="mt-2 text-xs text-slate-500">Tarif keterlambatan Rp2.000 per hari</p>
  </article>
</div>

<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Buku yang sedang Anda pinjam</h2><p class="mt-1 text-xs text-slate-500">Kembalikan tepat waktu untuk menghindari denda.</p></div>
    <a class="btn btn-soft btn-small no-underline" href="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">Riwayat peminjaman</a>
  </div>
  <?php if ($activeLoans): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Buku</th><th>Tanggal pinjam</th><th>Batas kembali</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($activeLoans as $loan): ?>
            <?php $lateDays = max(0, (int) $loan['selisih_hari']); ?>
            <tr>
              <td><div class="font-bold text-ink"><?= digilibEscape($loan['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['penulis']) ?></div></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_pinjam']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td><span class="badge <?= $lateDays > 0 ? 'badge-red' : 'badge-amber' ?>"><?= $lateDays > 0 ? $lateDays . ' hari terlambat' : 'Sedang dipinjam' ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="px-5 py-9 text-center">
      <p class="text-sm font-semibold text-slate-600">Belum ada buku yang dipinjam.</p>
      <a class="btn btn-soft mt-3 no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Jelajahi katalog</a>
    </div>
  <?php endif; ?>
</section>

<section class="card-shadow mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Rekomendasi bacaan</h2><p class="mt-1 text-xs text-slate-500">Buku pilihan yang tersedia untuk dipinjam.</p></div>
    <a class="text-xs font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Lihat katalog →</a>
  </div>
  <?php if ($recommendedBooks): ?>
    <div class="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-4">
      <?php foreach ($recommendedBooks as $index => $book): ?>
        <article class="flex min-w-0 items-center gap-3 rounded-xl border border-slate-100 p-3">
          <?php $coverUrl = digilibBookCoverUrl(is_string($book['cover_path']) ? $book['cover_path'] : null); ?>
          <?php if ($coverUrl !== null): ?>
            <img class="h-14 w-11 flex-none rounded-lg border border-slate-100 object-cover" src="<?= digilibEscape($coverUrl) ?>" alt="Cover <?= digilibEscape($book['judul']) ?>">
          <?php else: ?>
            <div class="book-cover-placeholder book-cover-tone-<?= $index % 4 ?>" aria-hidden="true"><span>▤</span></div>
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-xs font-bold text-ink"><?= digilibEscape($book['judul']) ?></h3>
            <p class="truncate text-[11px] text-slate-500"><?= digilibEscape($book['penulis']) ?></p>
            <span class="mt-1 inline-block text-[10px] font-bold text-teal-700"><?= (int) $book['stok'] ?> tersedia</span>
          </div>
          <a class="icon-btn no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>" aria-label="Lihat katalog">→</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada buku tersedia saat ini.</p>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
