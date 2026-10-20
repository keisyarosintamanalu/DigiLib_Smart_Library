<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['anggota']);
$pdo = digilibDatabaseConnection();
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
                $pdo->beginTransaction();

                $memberStatement = $pdo->prepare(
                    "SELECT a.`anggota_id`, a.`status` AS `status_anggota`, u.`status` AS `status_user`
                     FROM `anggota` a
                     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
                     WHERE a.`user_id` = :user_id AND u.`role` = 'anggota'
                     FOR UPDATE"
                );
                $memberStatement->execute(['user_id' => $portalUser['user_id']]);
                $member = $memberStatement->fetch();

                if (!$member || $member['status_anggota'] !== 'aktif' || $member['status_user'] !== 'aktif') {
                    $pdo->rollBack();
                    unset($_SESSION['digilib_user']);
                    session_regenerate_id(true);
                    header('Location: ' . digilibUrl('index.php?account=inactive'), true, 303);
                    exit;
                }

                $activeCountStatement = $pdo->prepare(
                    "SELECT COUNT(*) FROM `peminjaman`
                     WHERE `anggota_id` = :anggota_id AND `status` = 'dipinjam'"
                );
                $activeCountStatement->execute(['anggota_id' => $member['anggota_id']]);
                $activeCount = (int) $activeCountStatement->fetchColumn();

                if ($activeCount >= 3) {
                    $pdo->rollBack();
                    $errorMessage = 'Batas maksimal peminjaman adalah 3 buku yang sedang dipinjam.';
                } else {
                    $bookStatement = $pdo->prepare(
                        "SELECT `buku_id`, `judul`, `stok`
                         FROM `buku`
                         WHERE `buku_id` = :buku_id AND `status` = 'aktif'
                         FOR UPDATE"
                    );
                    $bookStatement->execute(['buku_id' => $bookId]);
                    $book = $bookStatement->fetch();

                    if (!$book) {
                        $pdo->rollBack();
                        $errorMessage = 'Buku tidak ditemukan atau tidak aktif.';
                    } elseif ((int) $book['stok'] < 1) {
                        $pdo->rollBack();
                        $errorMessage = 'Stok buku habis. Buku ini tidak dapat dipinjam saat ini.';
                    } else {
                        $updateStock = $pdo->prepare(
                            "UPDATE `buku`
                             SET `stok` = `stok` - 1
                             WHERE `buku_id` = :buku_id AND `status` = 'aktif' AND `stok` > 0"
                        );
                        $updateStock->execute(['buku_id' => $bookId]);

                        if ($updateStock->rowCount() !== 1) {
                            $pdo->rollBack();
                            $errorMessage = 'Stok buku baru saja berubah. Muat ulang halaman dan coba lagi.';
                        } else {
                            $insertLoan = $pdo->prepare(
                                "INSERT INTO `peminjaman`
                                    (`anggota_id`, `buku_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `status`)
                                 VALUES
                                    (:anggota_id, :buku_id, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'dipinjam')"
                            );
                            $insertLoan->execute([
                                'anggota_id' => $member['anggota_id'],
                                'buku_id' => $bookId,
                            ]);
                            $pdo->commit();
                            $_SESSION['loans_success'] = 'Peminjaman "' . $book['judul'] . '" berhasil. Batas pengembalian 7 hari setelah tanggal peminjaman.';
                            header('Location: ' . digilibUrl('anggota/peminjaman.php'), true, 303);
                            exit;
                        }
                    }
                }
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('DIGILIB loan create database error: ' . $exception->getMessage());
                $errorMessage = 'Peminjaman gagal disimpan karena terjadi kesalahan database. Silakan coba lagi.';
            }
        }
    }
}

$memberStatement = $pdo->prepare(
    "SELECT `anggota_id` FROM `anggota`
     WHERE `user_id` = :user_id AND `status` = 'aktif'
     LIMIT 1"
);
$memberStatement->execute(['user_id' => $portalUser['user_id']]);
$memberId = $memberStatement->fetchColumn();
if ($memberId === false) {
    unset($_SESSION['digilib_user']);
    session_regenerate_id(true);
    header('Location: ' . digilibUrl('index.php?account=inactive'), true, 303);
    exit;
}

$activeCountStatement = $pdo->prepare(
    "SELECT COUNT(*) FROM `peminjaman`
     WHERE `anggota_id` = :anggota_id AND `status` = 'dipinjam'"
);
$activeCountStatement->execute(['anggota_id' => $memberId]);
$activeCount = (int) $activeCountStatement->fetchColumn();

$availableBooks = $pdo->query(
    "SELECT `buku_id`, `isbn`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `rak`, `stok`
     FROM `buku`
     WHERE `status` = 'aktif' AND `stok` > 0
     ORDER BY `judul` ASC"
)->fetchAll();

$historyStatement = $pdo->prepare(
    'SELECT p.`peminjaman_id`, p.`tanggal_pinjam`, p.`tanggal_jatuh_tempo`, p.`status`,
            b.`judul`, b.`penulis`, b.`isbn`
     FROM `peminjaman` p
     INNER JOIN `buku` b ON b.`buku_id` = p.`buku_id`
     WHERE p.`anggota_id` = :anggota_id
     ORDER BY p.`tanggal_pinjam` DESC, p.`peminjaman_id` DESC'
);
$historyStatement->execute(['anggota_id' => $memberId]);
$loans = $historyStatement->fetchAll();
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
