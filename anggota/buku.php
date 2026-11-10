<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/supabase_storage.php';
$portalUser = digilibRequireRole(['anggota']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Katalog Buku';
$portalDescription = 'Cari buku pilihan dan pinjam langsung dari katalog.';
$activePage = 'books';

$memberStatement = $pdo->prepare(
    "SELECT `anggota_id` FROM `anggota`
     WHERE `user_id` = :user_id AND `status` = 'aktif'
     LIMIT 1"
);
$memberStatement->execute(['user_id' => $portalUser['user_id']]);
$memberId = $memberStatement->fetchColumn();
if ($memberId === false) {
    throw new RuntimeException('Active member profile could not be loaded.');
}

$activeCountStatement = $pdo->prepare(
    "SELECT COUNT(*) FROM `peminjaman`
     WHERE `anggota_id` = :anggota_id AND `status` = 'dipinjam'"
);
$activeCountStatement->execute(['anggota_id' => $memberId]);
$activeCount = (int) $activeCountStatement->fetchColumn();

$categories = $pdo->query(
    'SELECT DISTINCT k.`kategori_id`, k.`nama_kategori`
     FROM `kategori` k
     INNER JOIN `buku` b ON b.`kategori_id` = k.`kategori_id`
     WHERE b.`status` = \'aktif\'
     ORDER BY k.`nama_kategori`'
)->fetchAll();

$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = filter_var($_GET['kategori'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($categoryFilter === false) {
    $categoryFilter = null;
}

$conditions = ["b.`status` = 'aktif'"];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(b.`isbn` LIKE :isbn OR b.`judul` LIKE :judul OR b.`penulis` LIKE :penulis OR b.`penerbit` LIKE :penerbit OR k.`nama_kategori` LIKE :kategori_cari)';
    $term = '%' . $search . '%';
    $parameters['isbn'] = $term;
    $parameters['judul'] = $term;
    $parameters['penulis'] = $term;
    $parameters['penerbit'] = $term;
    $parameters['kategori_cari'] = $term;
}
if ($categoryFilter !== null) {
    $conditions[] = 'b.`kategori_id` = :kategori_id';
    $parameters['kategori_id'] = $categoryFilter;
}

$booksStatement = $pdo->prepare(
    'SELECT b.`buku_id`, b.`isbn`, b.`judul`, b.`penulis`, b.`penerbit`, b.`tahun_terbit`,
            b.`rak`, b.`stok`, b.`cover_path`, k.`nama_kategori`
     FROM `buku` b
     INNER JOIN `kategori` k ON k.`kategori_id` = b.`kategori_id`
     WHERE ' . implode(' AND ', $conditions) . '
     ORDER BY b.`judul` ASC, b.`buku_id` DESC'
);
$booksStatement->execute($parameters);
$books = $booksStatement->fetchAll();

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
            <?php
              $coverUrl = digilibBookCoverUrl(is_string($book['cover_path']) ? $book['cover_path'] : null);
            ?>
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
