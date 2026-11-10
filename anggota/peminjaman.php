<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['anggota']);
$database = digilibDatabaseRead();
$portalTitle = 'Peminjaman Buku';
$portalDescription = 'Pinjam buku yang tersedia dan pantau tanggal pengembaliannya.';
$activePage = 'loans';
$errorMessage = '';
$successMessage = (string) ($_SESSION['loans_success'] ?? '');
unset($_SESSION['loans_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $bookId = filter_var($_POST['buku_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($bookId === false) {
            $errorMessage = 'Pilih buku yang valid untuk dipinjam.';
        } else {
            try {
                $loan = digilibDatabaseTransaction(static function (array &$database) use ($portalUser, $bookId): array {
                    $member = null;
                    foreach ($database['anggota'] as $memberRecord) {
                        if ((int) $memberRecord['user_id'] !== (int) $portalUser['user_id']) {
                            continue;
                        }
                        foreach ($database['users'] as $user) {
                            if ((int) $user['user_id'] === (int) $portalUser['user_id']
                                && $user['role'] === 'anggota') {
                                $member = ['profile' => $memberRecord, 'user' => $user];
                                break 2;
                            }
                        }
                    }
                    if ($member === null
                        || $member['profile']['status'] !== 'aktif'
                        || $member['user']['status'] !== 'aktif') {
                        throw new DomainException('inactive_member');
                    }

                    $memberId = (int) $member['profile']['anggota_id'];
                    $activeCount = count(array_filter($database['peminjaman'], static function (array $record) use ($memberId): bool {
                        return (int) $record['anggota_id'] === $memberId && $record['status'] === 'dipinjam';
                    }));
                    if ($activeCount >= 3) {
                        throw new DomainException('loan_limit');
                    }

                    $bookIndex = null;
                    foreach ($database['buku'] as $index => $bookRecord) {
                        if ((int) $bookRecord['buku_id'] === (int) $bookId && $bookRecord['status'] === 'aktif') {
                            $bookIndex = $index;
                            break;
                        }
                    }
                    if ($bookIndex === null) {
                        throw new DomainException('book_missing');
                    }
                    if ((int) $database['buku'][$bookIndex]['stok'] < 1) {
                        throw new DomainException('book_out_of_stock');
                    }

                    $database['buku'][$bookIndex]['stok'] = (int) $database['buku'][$bookIndex]['stok'] - 1;
                    $timestamp = digilibDatabaseTimestamp();
                    $loan = [
                        'peminjaman_id' => digilibDatabaseNextId($database, 'peminjaman', 'peminjaman_id'),
                        'anggota_id' => $memberId,
                        'buku_id' => (int) $bookId,
                        'tanggal_pinjam' => digilibDatabaseToday(),
                        'tanggal_jatuh_tempo' => (new DateTimeImmutable('today +7 days'))->format('Y-m-d'),
                        'status' => 'dipinjam',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                    $database['peminjaman'][] = $loan;
                    return ['loan' => $loan, 'judul' => $database['buku'][$bookIndex]['judul']];
                });
                $_SESSION['loans_success'] = 'Peminjaman "' . $loan['judul'] . '" berhasil. Batas pengembalian 7 hari setelah tanggal peminjaman.';
                header('Location: ' . digilibUrl('anggota/peminjaman.php'), true, 303);
                exit;
            } catch (DomainException $exception) {
                if ($exception->getMessage() === 'inactive_member') {
                    unset($_SESSION['digilib_user']);
                    session_regenerate_id(true);
                    header('Location: ' . digilibUrl('index.php?account=inactive'), true, 303);
                    exit;
                }
                $errorMessage = [
                    'loan_limit' => 'Batas maksimal peminjaman adalah 3 buku yang sedang dipinjam.',
                    'book_missing' => 'Buku tidak ditemukan atau tidak aktif.',
                    'book_out_of_stock' => 'Stok buku habis. Buku ini tidak dapat dipinjam saat ini.',
                ][$exception->getMessage()] ?? 'Peminjaman tidak dapat diproses.';
            } catch (RuntimeException $exception) {
                error_log('DIGILIB loan create JSON database error: ' . $exception->getMessage());
                $errorMessage = 'Peminjaman gagal disimpan karena terjadi kesalahan penyimpanan. Silakan coba lagi.';
            }
        }
    }
}

$database = digilibDatabaseRead();
$memberId = null;
foreach ($database['anggota'] as $member) {
    if ((int) $member['user_id'] === (int) $portalUser['user_id'] && $member['status'] === 'aktif') {
        $memberId = (int) $member['anggota_id'];
        break;
    }
}
if ($memberId === null) {
    unset($_SESSION['digilib_user']);
    session_regenerate_id(true);
    header('Location: ' . digilibUrl('index.php?account=inactive'), true, 303);
    exit;
}

$activeCount = count(array_filter($database['peminjaman'], static function (array $loan) use ($memberId): bool {
    return (int) $loan['anggota_id'] === $memberId && $loan['status'] === 'dipinjam';
}));
$availableBooks = array_values(array_filter($database['buku'], static function (array $book): bool {
    return $book['status'] === 'aktif' && (int) $book['stok'] > 0;
}));
usort($availableBooks, static function (array $left, array $right): int {
    return strcasecmp($left['judul'], $right['judul']);
});

$loans = [];
foreach ($database['peminjaman'] as $loan) {
    if ((int) $loan['anggota_id'] !== $memberId) {
        continue;
    }
    $book = digilibDatabaseBookRecord($database, (int) $loan['buku_id']);
    if ($book !== null) {
        $loans[] = array_merge($loan, [
            'judul' => $book['judul'],
            'penulis' => $book['penulis'],
            'isbn' => $book['isbn'],
        ]);
    }
}
usort($loans, static function (array $left, array $right): int {
    $dateOrder = strcmp($right['tanggal_pinjam'], $left['tanggal_pinjam']);
    return $dateOrder !== 0 ? $dateOrder : ((int) $right['peminjaman_id'] <=> (int) $left['peminjaman_id']);
});
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>

<?php if ($successMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status"><?= digilibEscape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<section class="card-shadow mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-white p-5">
  <div><p class="text-xs font-semibold text-slate-500">Buku yang sedang Anda pinjam</p><p class="display-font mt-2 text-2xl font-extrabold text-ink"><?= $activeCount ?> <span class="text-sm font-semibold text-slate-400">/ 3 buku</span></p></div>
  <p class="text-sm text-slate-500">Masa pinjam <b class="text-ink">7 hari</b>. Tanggal jatuh tempo dicatat otomatis saat transaksi berhasil.</p>
</section>

<section class="card-shadow mb-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="border-b border-slate-100 px-5 py-4">
    <h2 class="display-font text-sm font-extrabold">Buku tersedia untuk dipinjam</h2>
    <p class="mt-1 text-xs text-slate-400"><?= count($availableBooks) ?> buku tersedia saat ini</p>
  </div>
  <div class="table-wrap">
    <?php if ($availableBooks): ?>
      <table class="data-table">
        <thead><tr><th>ISBN</th><th>Judul buku</th><th>Penulis</th><th>Penerbit</th><th>Tahun</th><th>Rak</th><th>Stok</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($availableBooks as $book): ?>
            <tr>
              <td class="text-xs"><?= digilibEscape($book['isbn'] ?? '—') ?></td>
              <td><div class="font-bold text-ink"><?= digilibEscape($book['judul']) ?></div></td>
              <td><?= digilibEscape($book['penulis']) ?></td>
              <td><?= digilibEscape($book['penerbit']) ?></td>
              <td><?= (int) $book['tahun_terbit'] ?></td>
              <td><?= digilibEscape($book['rak']) ?></td>
              <td><span class="badge badge-green"><?= (int) $book['stok'] ?> tersedia</span></td>
              <td>
                <?php if ($activeCount < 3): ?>
                  <form method="post" action="<?= digilibEscape(digilibUrl('anggota/peminjaman.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                    <input type="hidden" name="buku_id" value="<?= (int) $book['buku_id'] ?>">
                    <button class="btn btn-primary btn-small" type="submit">Pinjam</button>
                  </form>
                <?php else: ?>
                  <span class="badge badge-gray">Batas tercapai</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="flex flex-col items-center justify-center px-5 py-12 text-center">
        <div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-50 text-slate-400">▤</div>
        <p class="text-sm font-semibold text-slate-600">Belum ada buku dengan stok tersedia.</p>
        <p class="mt-1 text-xs text-slate-400">Silakan kembali lagi setelah buku ditambahkan atau stok tersedia.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="display-font text-sm font-extrabold">Transaksi peminjaman saya</h2><p class="mt-1 text-xs text-slate-400">Riwayat peminjaman dan batas pengembalian buku.</p></div>
  <div class="table-wrap">
    <?php if ($loans): ?>
      <table class="data-table">
        <thead><tr><th>ISBN</th><th>Buku</th><th>Tanggal pinjam</th><th>Tanggal jatuh tempo</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($loans as $loan): ?>
            <tr>
              <td class="text-xs"><?= digilibEscape($loan['isbn'] ?? '—') ?></td>
              <td><div class="font-bold text-ink"><?= digilibEscape($loan['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['penulis']) ?></div></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_pinjam']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td><span class="badge <?= $loan['status'] === 'dipinjam' ? 'badge-amber' : ($loan['status'] === 'dikembalikan' ? 'badge-green' : 'badge-gray') ?>"><?= digilibEscape(ucfirst($loan['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="px-5 py-10 text-center text-sm text-slate-500">Anda belum memiliki transaksi peminjaman.</div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
