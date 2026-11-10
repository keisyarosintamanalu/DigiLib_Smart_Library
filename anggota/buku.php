<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/book_covers.php';
$portalUser = digilibRequireRole(['anggota']);
$database = digilibDatabaseRead();
$portalTitle = 'Katalog Buku';
$portalDescription = 'Cari buku pilihan dan pinjam langsung dari katalog.';
$activePage = 'books';

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

$activeCount = count(array_filter($database['peminjaman'], static function (array $loan) use ($memberId): bool {
    return (int) $loan['anggota_id'] === $memberId && $loan['status'] === 'dipinjam';
}));
$categoryIds = [];
foreach ($database['buku'] as $book) {
    if ($book['status'] === 'aktif') {
        $categoryIds[(int) $book['kategori_id']] = true;
    }
}
$categories = array_values(array_filter($database['kategori'], static function (array $category) use ($categoryIds): bool {
    return isset($categoryIds[(int) $category['kategori_id']]);
}));
usort($categories, static function (array $left, array $right): int {
    return strcasecmp($left['nama_kategori'], $right['nama_kategori']);
});

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = filter_var($_GET['kategori'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($categoryFilter === false) {
    $categoryFilter = null;
}

$books = [];
foreach ($database['buku'] as $bookRecord) {
    if ($bookRecord['status'] !== 'aktif'
        || ($categoryFilter !== null && (int) $bookRecord['kategori_id'] !== (int) $categoryFilter)) {
        continue;
    }
    $book = digilibDatabaseBookRecord($database, (int) $bookRecord['buku_id']);
    $haystack = implode(' ', [
        $book['isbn'] ?? '',
        $book['judul'] ?? '',
        $book['penulis'] ?? '',
        $book['penerbit'] ?? '',
        $book['nama_kategori'] ?? '',
    ]);
    if ($search !== '' && stripos($haystack, $search) === false) {
        continue;
    }
    $books[] = $book;
}
usort($books, static function (array $left, array $right): int {
    $titleOrder = strcasecmp($left['judul'], $right['judul']);
    return $titleOrder !== 0 ? $titleOrder : ((int) $right['buku_id'] <=> (int) $left['buku_id']);
});
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<section class="card-shadow mb-6 rounded-2xl border border-slate-100 bg-white p-4 sm:p-5">
  <form class="flex flex-col gap-3 sm:flex-row sm:items-end" action="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>" method="get">
    <div class="min-w-0 flex-1">
      <label class="field-label" for="search-books">Cari buku</label>
      <input class="field" id="search-books" type="search" name="q" value="<?= digilibEscape($search) ?>" placeholder="Judul, penulis, penerbit, ISBN, atau kategori">
    </div>
    <div class="sm:w-64">
      <label class="field-label" for="filter-category">Kategori</label>
      <select class="field" id="filter-category" name="kategori">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['kategori_id'] ?>" <?= $categoryFilter === (int) $category['kategori_id'] ? 'selected' : '' ?>><?= digilibEscape($category['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Cari buku</button>
    <?php if ($search !== '' || $categoryFilter !== null): ?>
      <a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Reset</a>
    <?php endif; ?>
  </form>
</section>

<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Koleksi perpustakaan</h2><p class="mt-1 text-xs text-slate-500">Cari dan pinjam buku langsung dari katalog.</p></div>
    <span class="badge badge-green"><?= count($books) ?> buku ditemukan</span>
  </div>
  <div class="table-wrap">
    <?php if ($books): ?>
      <table class="data-table">
        <thead><tr><th>Informasi buku</th><th>Kategori</th><th>Tahun</th><th>Rak</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($books as $index => $book): ?>
            <?php $coverUrl = digilibBookCoverUrl(is_string($book['cover_path']) ? $book['cover_path'] : null); ?>
            <tr>
              <td>
                <div class="flex min-w-[220px] items-center gap-3">
                  <?php if ($coverUrl !== null): ?>
                    <img class="h-14 w-11 rounded-lg border border-slate-100 object-cover" src="<?= digilibEscape($coverUrl) ?>" alt="Cover <?= digilibEscape($book['judul']) ?>">
                  <?php else: ?>
                    <div class="book-cover-placeholder book-cover-tone-<?= $index % 4 ?>" aria-hidden="true"><span>▤</span></div>
                  <?php endif; ?>
                  <div class="min-w-0">
                    <div class="font-bold text-ink"><?= digilibEscape($book['judul']) ?></div>
                    <div class="mt-1 text-xs text-slate-500"><?= digilibEscape($book['penulis']) ?> · <?= digilibEscape($book['penerbit']) ?></div>
                    <div class="mt-1 text-[11px] text-slate-400">ISBN <?= digilibEscape($book['isbn'] ?? '—') ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-blue"><?= digilibEscape($book['nama_kategori']) ?></span></td>
              <td><?= (int) $book['tahun_terbit'] ?></td>
              <td><?= digilibEscape($book['rak']) ?></td>
              <td><span class="font-bold <?= (int) $book['stok'] > 0 ? 'text-ink' : 'text-red-600' ?>"><?= (int) $book['stok'] ?></span></td>
              <td><span class="badge <?= (int) $book['stok'] > 0 ? 'badge-green' : 'badge-red' ?>"><?= (int) $book['stok'] > 0 ? 'Tersedia' : 'Stok habis' ?></span></td>
              <td>
                <div class="flex flex-wrap items-center gap-2">
                  <a class="btn btn-soft btn-small no-underline" href="<?= digilibEscape(digilibUrl('anggota/detail_buku.php?id=' . (int) $book['buku_id'])) ?>">Detail</a>
                  <?php if ((int) $book['stok'] > 0 && $activeCount < 3): ?>
                    <form method="post" action="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">
                      <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                      <input type="hidden" name="buku_id" value="<?= (int) $book['buku_id'] ?>">
                      <button class="btn btn-primary btn-small" type="submit">Pinjam</button>
                    </form>
                  <?php elseif ($activeCount >= 3): ?>
                    <button class="btn btn-muted btn-small cursor-not-allowed" type="button" disabled title="Selesaikan atau kembalikan pinjaman aktif untuk meminjam buku lagi.">Pinjam (batas tercapai)</button>
                  <?php else: ?>
                    <button class="btn btn-muted btn-small cursor-not-allowed" type="button" disabled title="Stok buku sedang habis.">Stok habis</button>
                  <?php endif; ?>
                </div>
                <?php if ($activeCount >= 3): ?>
                  <a class="mt-2 inline-block text-[11px] font-semibold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">Lihat pinjaman aktif</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="px-5 py-12 text-center">
        <p class="text-sm font-semibold text-slate-600"><?= $search !== '' || $categoryFilter !== null ? 'Tidak ada buku yang sesuai dengan pencarian atau kategori.' : 'Belum ada buku di katalog.' ?></p>
        <p class="mt-1 text-xs text-slate-400">Buku yang ditambahkan pustakawan akan tampil di sini.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
