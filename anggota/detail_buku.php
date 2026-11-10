<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/book_covers.php';
$portalUser = digilibRequireRole(['anggota']);
$database = digilibDatabaseRead();
$portalTitle = 'Detail Buku';
$portalDescription = 'Kenali informasi buku dan ketentuan peminjaman sebelum meminjam.';
$activePage = 'books';

$bookId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$book = false;
if ($bookId !== false) {
    $book = digilibDatabaseBookRecord($database, (int) $bookId);
    if ($book !== null && $book['status'] !== 'aktif') {
        $book = null;
    }
}

if (!$book) {
    http_response_code(404);
}

$activeCount = 3;
if ($book) {
    $member = null;
    foreach ($database['anggota'] as $memberRecord) {
        if ((int) $memberRecord['user_id'] === (int) $portalUser['user_id'] && $memberRecord['status'] === 'aktif') {
            $member = $memberRecord;
            break;
        }
    }
    if (!$member) {
        throw new RuntimeException('Active member profile could not be loaded.');
    }
    $activeCount = count(array_filter($database['peminjaman'], static function (array $loan) use ($member): bool {
        return (int) $loan['anggota_id'] === (int) $member['anggota_id'] && $loan['status'] === 'dipinjam';
    }));
}

$coverUrl = $book ? digilibBookCoverUrl(is_string($book['cover_path'] ?? null) ? $book['cover_path'] : null) : null;
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<div class="mb-5">
  <a class="text-sm font-bold text-teal-700 no-underline hover:underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">← Kembali ke katalog</a>
</div>

<?php if (!$book): ?>
  <section class="card-shadow rounded-2xl border border-slate-100 bg-white px-5 py-12 text-center">
    <h2 class="display-font text-lg font-extrabold text-ink">Buku tidak ditemukan</h2>
    <p class="mt-2 text-sm text-slate-500">Buku ini mungkin sudah tidak tersedia di katalog.</p>
    <a class="btn btn-primary mt-5 no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Jelajahi katalog</a>
  </section>
<?php else: ?>
  <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.65fr)]">
    <section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
      <div class="grid gap-6 p-5 sm:p-7 md:grid-cols-[220px_minmax(0,1fr)]">
        <div class="flex min-h-[280px] items-center justify-center rounded-2xl bg-sand p-5">
          <?php if ($coverUrl !== null): ?>
            <img class="max-h-[320px] w-auto max-w-full rounded-xl object-contain shadow-lg" src="<?= digilibEscape($coverUrl) ?>" alt="Cover <?= digilibEscape($book['judul']) ?>">
          <?php else: ?>
            <div class="book-cover-placeholder book-cover-tone-0 !h-64 !w-44 !flex-none !rounded-xl text-5xl" aria-hidden="true"><span>▤</span></div>
          <?php endif; ?>
        </div>
        <div class="flex flex-col">
          <div class="mb-3"><span class="badge badge-blue"><?= digilibEscape($book['nama_kategori']) ?></span></div>
          <h2 class="display-font text-2xl font-extrabold leading-tight text-ink sm:text-3xl"><?= digilibEscape($book['judul']) ?></h2>
          <p class="mt-2 text-sm text-slate-500">Ditulis oleh <span class="font-semibold text-ink"><?= digilibEscape($book['penulis']) ?></span></p>

          <dl class="mt-6 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold text-slate-400">Penerbit</dt><dd class="mt-1 text-sm font-semibold text-ink"><?= digilibEscape($book['penerbit']) ?></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400">Tahun terbit</dt><dd class="mt-1 text-sm font-semibold text-ink"><?= (int) $book['tahun_terbit'] ?></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400">ISBN</dt><dd class="mt-1 text-sm font-semibold text-ink"><?= digilibEscape($book['isbn'] ?? 'Belum tersedia') ?></dd></div>
            <div><dt class="text-xs font-semibold text-slate-400">Lokasi rak</dt><dd class="mt-1 text-sm font-semibold text-ink"><?= digilibEscape($book['rak']) ?></dd></div>
            <div>
              <dt class="text-xs font-semibold text-slate-400">Ketersediaan</dt>
              <dd class="mt-1"><span class="badge <?= (int) $book['stok'] > 0 ? 'badge-green' : 'badge-red' ?>"><?= (int) $book['stok'] > 0 ? (int) $book['stok'] . ' eksemplar tersedia' : 'Stok habis' ?></span></dd>
            </div>
          </dl>

          <div class="mt-auto flex flex-wrap gap-2 pt-7">
            <?php if ((int) $book['stok'] > 0 && $activeCount < 3): ?>
              <form method="post" action="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                <input type="hidden" name="buku_id" value="<?= (int) $book['buku_id'] ?>">
                <button class="btn btn-primary" type="submit">Pinjam buku ini</button>
              </form>
            <?php elseif ($activeCount >= 3): ?>
              <button class="btn btn-muted cursor-not-allowed" type="button" disabled title="Selesaikan atau kembalikan pinjaman aktif untuk meminjam buku lagi.">Pinjam (batas tercapai)</button>
              <a class="btn btn-soft no-underline" href="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">Lihat pinjaman aktif</a>
            <?php else: ?>
              <button class="btn btn-muted cursor-not-allowed" type="button" disabled title="Stok buku sedang habis.">Stok habis</button>
            <?php endif; ?>
            <a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('anggota/buku.php')) ?>">Lihat buku lainnya</a>
          </div>
        </div>
      </div>
    </section>

    <aside class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
      <h2 class="display-font text-base font-extrabold text-ink">Ketentuan peminjaman</h2>
      <p class="mt-1 text-xs text-slate-500">Perhatikan ketentuan berikut sebelum mengajukan pinjaman.</p>
      <ul class="mt-5 space-y-4">
        <li class="flex gap-3"><span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-teal-50 text-sm font-extrabold text-teal-700">1</span><span class="pt-1 text-sm text-slate-600">Masa peminjaman adalah <b class="text-ink">7 hari</b> sejak tanggal peminjaman.</span></li>
        <li class="flex gap-3"><span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-teal-50 text-sm font-extrabold text-teal-700">2</span><span class="pt-1 text-sm text-slate-600">Setiap anggota dapat meminjam maksimal <b class="text-ink">3 buku aktif</b> pada saat yang sama.</span></li>
        <li class="flex gap-3"><span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-amber-50 text-sm font-extrabold text-amber-700">3</span><span class="pt-1 text-sm text-slate-600">Keterlambatan dikenakan denda <b class="text-ink">Rp2.000 per hari</b>.</span></li>
        <li class="flex gap-3"><span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-blue-50 text-sm font-extrabold text-blue-700">4</span><span class="pt-1 text-sm text-slate-600">Peminjaman hanya dapat dilakukan jika stok buku tersedia dan batas pinjaman belum tercapai.</span></li>
      </ul>
      <div class="mt-6 rounded-xl bg-sand p-4">
        <p class="text-xs font-bold text-ink">Pinjaman aktif Anda</p>
        <p class="mt-1 text-sm text-slate-500"><b class="text-teal-700"><?= $activeCount ?></b> dari 3 buku</p>
      </div>
    </aside>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
