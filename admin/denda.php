<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$database = digilibDatabaseRead();
$portalTitle = 'Denda dan Pengembalian';
$portalDescription = 'Lihat tanggal kembali, detail keterlambatan, dan status pembayaran denda.';
$activePage = 'fines';
$errorMessage = '';
$successMessage = (string) ($_SESSION['fines_success'] ?? '');
unset($_SESSION['fines_success']);

function digilibFineRedirect(): never
{
    header('Location: ' . digilibUrl('admin/denda.php'), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $fineId = filter_var($_POST['denda_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($fineId === false) {
            $errorMessage = 'Data denda yang dipilih tidak valid.';
        } else {
            try {
                digilibDatabaseTransaction(static function (array &$database) use ($fineId): void {
                    foreach ($database['denda'] as &$fine) {
                        if ((int) $fine['denda_id'] !== (int) $fineId) {
                            continue;
                        }
                        if ($fine['status_pembayaran'] === 'lunas') {
                            throw new DomainException('already_paid');
                        }
                        if ((float) $fine['jumlah_denda'] <= 0) {
                            throw new DomainException('zero_amount');
                        }
                        $fine['status_pembayaran'] = 'lunas';
                        $fine['tanggal_pembayaran'] = digilibDatabaseToday();
                        break;
                    }
                    unset($fine);
                    if (!digilibDatabaseRecord($database, 'denda', 'denda_id', (int) $fineId)) {
                        throw new DomainException('fine_missing');
                    }
                });
                $_SESSION['fines_success'] = 'Pembayaran denda berhasil dicatat menggunakan tanggal database JSON.';
                digilibFineRedirect();
            } catch (DomainException $exception) {
                $errorMessage = [
                    'already_paid' => 'Denda tersebut sudah berstatus lunas.',
                    'zero_amount' => 'Denda dengan nominal Rp0 tidak memerlukan pembayaran.',
                    'fine_missing' => 'Data denda tidak ditemukan.',
                ][$exception->getMessage()] ?? 'Status pembayaran tidak dapat diperbarui.';
            } catch (RuntimeException $exception) {
                error_log('DIGILIB fine payment JSON database error: ' . $exception->getMessage());
                $errorMessage = 'Status pembayaran tidak dapat diperbarui. Tidak ada perubahan yang disimpan.';
            }
        }
    }
}

$database = digilibDatabaseRead();
$fines = [];
foreach ($database['denda'] as $fine) {
    $returnRecord = digilibDatabaseRecord($database, 'pengembalian', 'pengembalian_id', (int) $fine['pengembalian_id']);
    $loan = $returnRecord === null
        ? null
        : digilibDatabaseLoanRecord($database, (int) $returnRecord['peminjaman_id']);
    if ($returnRecord === null || $loan === null) {
        continue;
    }
    $fines[] = array_merge($fine, [
        'tanggal_kembali' => $returnRecord['tanggal_kembali'],
        'tanggal_pinjam' => $loan['tanggal_pinjam'],
        'tanggal_jatuh_tempo' => $loan['tanggal_jatuh_tempo'],
        'nama_lengkap' => $loan['nama_lengkap'],
        'email' => $loan['email'],
        'nomor_anggota' => $loan['nomor_anggota'],
        'judul' => $loan['buku']['judul'],
        'isbn' => $loan['buku']['isbn'],
    ]);
}
usort($fines, static function (array $left, array $right): int {
    $dateOrder = strcmp($right['tanggal_kembali'], $left['tanggal_kembali']);
    return $dateOrder !== 0 ? $dateOrder : ((int) $right['denda_id'] <=> (int) $left['denda_id']);
});

$unpaidTotal = 0.0;
$paidTotal = 0.0;
foreach ($fines as $fine) {
    if ($fine['status_pembayaran'] === 'belum_dibayar') {
        $unpaidTotal += (float) $fine['jumlah_denda'];
    } else {
        $paidTotal += (float) $fine['jumlah_denda'];
    }
}
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>

<?php if ($successMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status"><?= digilibEscape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Denda belum dibayar</p><p class="display-font mt-3 text-2xl font-extrabold text-ink"><?= digilibEscape(number_format($unpaidTotal, 0, ',', '.')) ?></p></article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Denda sudah dibayar</p><p class="display-font mt-3 text-2xl font-extrabold text-ink"><?= digilibEscape(number_format($paidTotal, 0, ',', '.')) ?></p></article>
  <article class="card-shadow rounded-2xl border border-slate-100 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Tarif keterlambatan</p><p class="display-font mt-3 text-2xl font-extrabold text-ink">Rp2.000 <span class="text-sm font-semibold text-slate-400">/ hari</span></p></article>
</div>

<section class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Detail pengembalian dan denda</h2><p class="mt-1 text-xs text-slate-400">Hari terlambat dan nominal denda dihitung dari tanggal database.</p></div>
    <a class="btn btn-soft no-underline" href="<?= digilibEscape(digilibUrl('admin/pengembalian.php')) ?>">Proses pengembalian</a>
  </div>
  <div class="table-wrap">
    <?php if ($fines): ?>
      <table class="data-table">
        <thead><tr><th>Anggota</th><th>Buku</th><th>Jatuh tempo</th><th>Tanggal kembali</th><th>Terlambat</th><th>Tarif / hari</th><th>Jumlah denda</th><th>Status pembayaran</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($fines as $fine): ?>
            <tr>
              <td><div class="font-bold text-ink"><?= digilibEscape($fine['nama_lengkap']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($fine['nomor_anggota']) ?></div></td>
              <td><div class="font-semibold text-ink"><?= digilibEscape($fine['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($fine['isbn'] ?? '—') ?></div></td>
              <td><?= digilibEscape((new DateTimeImmutable($fine['tanggal_jatuh_tempo']))->format('d-m-Y')) ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($fine['tanggal_kembali']))->format('d-m-Y')) ?></td>
              <td><?= (int) $fine['jumlah_hari_terlambat'] ?> hari</td>
              <td>Rp<?= digilibEscape(number_format((float) $fine['tarif_per_hari'], 0, ',', '.')) ?></td>
              <td class="font-bold">Rp<?= digilibEscape(number_format((float) $fine['jumlah_denda'], 0, ',', '.')) ?></td>
              <td>
                <?php if ($fine['status_pembayaran'] === 'lunas'): ?>
                  <span class="badge badge-green"><?= (float) $fine['jumlah_denda'] > 0 ? 'Lunas' : 'Tidak ada denda' ?></span>
                  <?php if ((float) $fine['jumlah_denda'] > 0 && $fine['tanggal_pembayaran']): ?><div class="mt-1 text-[11px] text-slate-400"><?= digilibEscape((new DateTimeImmutable($fine['tanggal_pembayaran']))->format('d-m-Y')) ?></div><?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-red">Belum dibayar</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($fine['status_pembayaran'] === 'belum_dibayar' && (float) $fine['jumlah_denda'] > 0): ?>
                  <form method="post" action="<?= digilibEscape(digilibUrl('admin/denda.php')) ?>" onsubmit="return confirm('Catat pembayaran denda ini sebagai lunas?')">
                    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                    <input type="hidden" name="denda_id" value="<?= (int) $fine['denda_id'] ?>">
                    <button class="btn btn-soft btn-small" type="submit">Tandai lunas</button>
                  </form>
                <?php else: ?>
                  <span class="text-xs text-slate-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="px-5 py-12 text-center">
        <p class="text-sm font-semibold text-slate-600">Belum ada data pengembalian atau denda.</p>
        <p class="mt-1 text-xs text-slate-400">Catatan pengembalian dan denda akan muncul setelah admin memproses buku kembali.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
