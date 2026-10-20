<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
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
                $pdo->beginTransaction();
                $loanStatement = $pdo->prepare(
                    "SELECT `peminjaman_id`, `buku_id`, `status`
                     FROM `peminjaman`
                     WHERE `peminjaman_id` = :peminjaman_id
                     FOR UPDATE"
                );
                $loanStatement->execute(['peminjaman_id' => $loanId]);
                $loan = $loanStatement->fetch();

                if (!$loan || $loan['status'] !== 'dipinjam') {
                    $pdo->rollBack();
                    $errorMessage = 'Transaksi tidak ditemukan atau buku sudah dikembalikan.';
                } else {
                    $bookStatement = $pdo->prepare(
                        'SELECT `buku_id` FROM `buku` WHERE `buku_id` = :buku_id FOR UPDATE'
                    );
                    $bookStatement->execute(['buku_id' => $loan['buku_id']]);
                    if (!$bookStatement->fetch()) {
                        $pdo->rollBack();
                        $errorMessage = 'Data buku untuk transaksi ini tidak ditemukan; stok tidak dapat diperbarui.';
                    } else {
                        $returnStatement = $pdo->prepare(
                            'INSERT INTO `pengembalian` (`peminjaman_id`, `tanggal_kembali`)
                             VALUES (:peminjaman_id, CURRENT_DATE)'
                        );
                        $returnStatement->execute(['peminjaman_id' => $loanId]);
                        $returnId = (int) $pdo->lastInsertId();

                        $updateLoan = $pdo->prepare(
                            "UPDATE `peminjaman`
                             SET `status` = 'dikembalikan'
                             WHERE `peminjaman_id` = :peminjaman_id AND `status` = 'dipinjam'"
                        );
                        $updateLoan->execute(['peminjaman_id' => $loanId]);
                        if ($updateLoan->rowCount() !== 1) {
                            throw new RuntimeException('Loan status changed while processing the return.');
                        }

                        $updateStock = $pdo->prepare(
                            'UPDATE `buku` SET `stok` = `stok` + 1 WHERE `buku_id` = :buku_id'
                        );
                        $updateStock->execute(['buku_id' => $loan['buku_id']]);

                        $insertFine = $pdo->prepare(
                            "INSERT INTO `denda`
                                (`pengembalian_id`, `jumlah_hari_terlambat`, `tarif_per_hari`,
                                 `jumlah_denda`, `status_pembayaran`, `tanggal_pembayaran`)
                             SELECT
                                :pengembalian_id,
                                GREATEST(DATEDIFF(r.`tanggal_kembali`, p.`tanggal_jatuh_tempo`), 0),
                                2000.00,
                                GREATEST(DATEDIFF(r.`tanggal_kembali`, p.`tanggal_jatuh_tempo`), 0) * 2000.00,
                                CASE
                                    WHEN DATEDIFF(r.`tanggal_kembali`, p.`tanggal_jatuh_tempo`) > 0 THEN 'belum_dibayar'
                                    ELSE 'lunas'
                                END,
                                NULL
                             FROM `pengembalian` r
                             INNER JOIN `peminjaman` p ON p.`peminjaman_id` = r.`peminjaman_id`
                             WHERE r.`pengembalian_id` = :return_id"
                        );
                        $insertFine->execute([
                            'pengembalian_id' => $returnId,
                            'return_id' => $returnId,
                        ]);
                        if ($insertFine->rowCount() !== 1) {
                            throw new RuntimeException('Fine could not be calculated from the database dates.');
                        }

                        $pdo->commit();
                        $_SESSION['returns_success'] = 'Pengembalian berhasil diproses. Tanggal dan denda dihitung dari data database.';
                        digilibReturnRedirect();
                    }
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('DIGILIB book return error: ' . $exception->getMessage());
                $errorMessage = 'Pengembalian gagal diproses. Tidak ada perubahan stok atau transaksi yang disimpan.';
            }
        }
    }
}

$activeLoans = $pdo->query(
    "SELECT p.`peminjaman_id`, p.`tanggal_pinjam`, p.`tanggal_jatuh_tempo`,
            DATEDIFF(CURRENT_DATE, p.`tanggal_jatuh_tempo`) AS `selisih_hari`,
            a.`nomor_anggota`, u.`nama_lengkap`, u.`email`,
            b.`judul`, b.`isbn`
     FROM `peminjaman` p
     INNER JOIN `anggota` a ON a.`anggota_id` = p.`anggota_id`
     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
     INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id`
     WHERE p.`status` = 'dipinjam'
     ORDER BY p.`tanggal_jatuh_tempo` ASC, p.`peminjaman_id` ASC"
)->fetchAll();
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
    <div><h2 class="display-font text-sm font-extrabold">Buku yang masih dipinjam</h2><p class="mt-1 text-xs text-slate-400">Tanggal terlambat dihitung oleh MySQL dari jatuh tempo dan tanggal hari ini.</p></div>
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
