<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$database = digilibDatabaseRead();
$portalTitle = 'Pengembalian Buku';
$portalDescription = 'Proses buku kembali dan hitung keterlambatan berdasarkan tanggal database.';
$activePage = 'returns';
$errorMessage = '';
$successMessage = (string) ($_SESSION['returns_success'] ?? '');
unset($_SESSION['returns_success']);

function digilibReturnRedirect(): never
{
    header('Location: ' . digilibUrl('admin/pengembalian.php'), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $loanId = filter_var($_POST['peminjaman_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($loanId === false) {
            $errorMessage = 'Transaksi peminjaman yang dipilih tidak valid.';
        } else {
            try {
                digilibDatabaseTransaction(static function (array &$database) use ($loanId): void {
                    $loanIndex = null;
                    foreach ($database['peminjaman'] as $index => $record) {
                        if ((int) $record['peminjaman_id'] === (int) $loanId && $record['status'] === 'dipinjam') {
                            $loanIndex = $index;
                            break;
                        }
                    }
                    if ($loanIndex === null) {
                        throw new DomainException('loan_missing');
                    }

                    $bookIndex = null;
                    foreach ($database['buku'] as $index => $book) {
                        if ((int) $book['buku_id'] === (int) $database['peminjaman'][$loanIndex]['buku_id']) {
                            $bookIndex = $index;
                            break;
                        }
                    }
                    if ($bookIndex === null) {
                        throw new DomainException('book_missing');
                    }

                    $today = digilibDatabaseToday();
                    $dueDate = new DateTimeImmutable($database['peminjaman'][$loanIndex]['tanggal_jatuh_tempo']);
                    $returnDate = new DateTimeImmutable($today);
                    $lateDays = max(0, (int) $dueDate->diff($returnDate)->format('%r%a'));
                    $returnId = digilibDatabaseNextId($database, 'pengembalian', 'pengembalian_id');
                    $database['pengembalian'][] = [
                        'pengembalian_id' => $returnId,
                        'peminjaman_id' => (int) $loanId,
                        'tanggal_kembali' => $today,
                        'catatan' => null,
                        'created_at' => digilibDatabaseTimestamp(),
                    ];
                    $database['peminjaman'][$loanIndex]['status'] = 'dikembalikan';
                    $database['peminjaman'][$loanIndex]['updated_at'] = digilibDatabaseTimestamp();
                    $database['buku'][$bookIndex]['stok'] = (int) $database['buku'][$bookIndex]['stok'] + 1;
                    $database['denda'][] = [
                        'denda_id' => digilibDatabaseNextId($database, 'denda', 'denda_id'),
                        'pengembalian_id' => $returnId,
                        'jumlah_hari_terlambat' => $lateDays,
                        'tarif_per_hari' => 2000,
                        'jumlah_denda' => $lateDays * 2000,
                        'status_pembayaran' => $lateDays > 0 ? 'belum_dibayar' : 'lunas',
                        'tanggal_pembayaran' => null,
                        'created_at' => digilibDatabaseTimestamp(),
                    ];
                });
                $_SESSION['returns_success'] = 'Pengembalian berhasil diproses. Tanggal dan denda dihitung dari database JSON.';
                digilibReturnRedirect();
            } catch (DomainException $exception) {
                $errorMessage = $exception->getMessage() === 'book_missing'
                    ? 'Data buku untuk transaksi ini tidak ditemukan; stok tidak dapat diperbarui.'
                    : 'Transaksi tidak ditemukan atau buku sudah dikembalikan.';
            } catch (RuntimeException $exception) {
                error_log('DIGILIB book return JSON database error: ' . $exception->getMessage());
                $errorMessage = 'Pengembalian gagal diproses. Tidak ada perubahan stok atau transaksi yang disimpan.';
            }
        }
    }
}

$database = digilibDatabaseRead();
$activeLoans = [];
foreach ($database['peminjaman'] as $loan) {
    if ($loan['status'] !== 'dipinjam') {
        continue;
    }
    $joined = digilibDatabaseLoanRecord($database, (int) $loan['peminjaman_id']);
    if ($joined === null) {
        continue;
    }
    $loan['nomor_anggota'] = $joined['nomor_anggota'];
    $loan['nama_lengkap'] = $joined['nama_lengkap'];
    $loan['email'] = $joined['email'];
    $loan['judul'] = $joined['buku']['judul'];
    $loan['isbn'] = $joined['buku']['isbn'];
    $loan['selisih_hari'] = max(0, (int) (new DateTimeImmutable($loan['tanggal_jatuh_tempo']))
        ->diff(new DateTimeImmutable(digilibDatabaseToday()))->format('%r%a'));
    $activeLoans[] = $loan;
}
usort($activeLoans, static function (array $left, array $right): int {
    $dateOrder = strcmp($left['tanggal_jatuh_tempo'], $right['tanggal_jatuh_tempo']);
    return $dateOrder !== 0 ? $dateOrder : ((int) $left['peminjaman_id'] <=> (int) $right['peminjaman_id']);
});
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>

<?php if ($successMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status"><?= digilibEscape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Buku yang masih dipinjam</h2><p class="mt-1 text-xs text-slate-400">Tanggal terlambat dihitung dari tanggal jatuh tempo dan hari ini.</p></div>
    <span class="badge badge-amber"><?= count($activeLoans) ?> transaksi aktif</span>
  </div>
  <div class="table-wrap">
    <?php if ($activeLoans): ?>
      <table class="data-table">
        <thead><tr><th>Anggota</th><th>Buku</th><th>Tanggal pinjam</th><th>Jatuh tempo</th><th>Keterlambatan saat ini</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($activeLoans as $loan): ?>
            <?php $lateDays = max(0, (int) $loan['selisih_hari']); ?>
            <tr>
              <td><div class="font-bold text-ink"><?= digilibEscape($loan['nama_lengkap']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['email']) ?> · <?= digilibEscape($loan['nomor_anggota']) ?></div></td>
              <td><div class="font-semibold text-ink"><?= digilibEscape($loan['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($loan['isbn'] ?? '—') ?></div></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_pinjam']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($loan['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td>
                <?php if ($lateDays > 0): ?>
                  <span class="badge badge-red"><?= $lateDays ?> hari terlambat</span>
                <?php else: ?>
                  <span class="badge badge-green">Belum terlambat</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" action="<?= digilibEscape(digilibUrl('admin/pengembalian.php')) ?>" onsubmit="return confirm('Proses pengembalian buku ini menggunakan tanggal database hari ini?')">
                  <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                  <input type="hidden" name="peminjaman_id" value="<?= (int) $loan['peminjaman_id'] ?>">
                  <button class="btn btn-primary btn-small" type="submit">Proses kembali</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="px-5 py-12 text-center">
        <p class="text-sm font-semibold text-slate-600">Tidak ada buku yang menunggu pengembalian.</p>
        <a class="btn btn-soft mt-4 no-underline" href="<?= digilibEscape(digilibUrl('admin/denda.php')) ?>">Lihat riwayat pengembalian</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
