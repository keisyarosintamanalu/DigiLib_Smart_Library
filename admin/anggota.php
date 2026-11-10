<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Data Anggota';
$portalDescription = 'Kelola profil dan status anggota perpustakaan.';
$activePage = 'members';
$errorMessage = '';
$successMessage = (string) ($_SESSION['members_success'] ?? '');
unset($_SESSION['members_success']);
$formValues = [];
$editingMember = null;

function digilibMemberRedirect(): never
{
    header('Location: ' . digilibUrl('admin/anggota.php'), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save') {
            $rawId = $_POST['anggota_id'] ?? null;
            $memberId = $rawId === null || $rawId === ''
                ? null
                : filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $isEditing = $memberId !== null && $memberId !== false;

            $formValues = [
                'nama_lengkap' => trim((string) ($_POST['nama_lengkap'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'nomor_telepon' => trim((string) ($_POST['nomor_telepon'] ?? '')),
                'alamat' => trim((string) ($_POST['alamat'] ?? '')),
                'tanggal_daftar' => trim((string) ($_POST['tanggal_daftar'] ?? '')),
                'status' => (string) ($_POST['status'] ?? 'aktif'),
            ];
            $password = (string) ($_POST['password'] ?? '');

            if ($rawId !== null && $rawId !== '' && !$isEditing) {
                $errorMessage = 'ID anggota yang akan diedit tidak valid.';
            }

            if ($errorMessage === '' && $isEditing) {
                $statement = $pdo->prepare(
                    "SELECT a.`anggota_id`, a.`user_id`, a.`nomor_anggota`, a.`nomor_telepon`,
                            a.`alamat`, a.`tanggal_daftar`, a.`status`,
                            u.`nama_lengkap`, u.`email`
                     FROM `anggota` a
                     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
                     WHERE a.`anggota_id` = :id AND u.`role` = 'anggota'
                     LIMIT 1"
                );
                $statement->execute(['id' => $memberId]);
                $editingMember = $statement->fetch() ?: null;
                if ($editingMember === null) {
                    http_response_code(404);
                    $errorMessage = 'Data anggota yang akan diedit tidak ditemukan.';
                }
            }

            $email = filter_var($formValues['email'], FILTER_VALIDATE_EMAIL);
            $registrationDate = DateTimeImmutable::createFromFormat('!Y-m-d', $formValues['tanggal_daftar']);
            $dateIsValid = $registrationDate !== false
                && $registrationDate->format('Y-m-d') === $formValues['tanggal_daftar'];

            if ($errorMessage === '' && (
                $formValues['nama_lengkap'] === ''
                || mb_strlen($formValues['nama_lengkap']) > 120
                || $email === false
                || mb_strlen($formValues['nomor_telepon']) > 30
                || mb_strlen($formValues['alamat']) > 5000
                || !$dateIsValid
                || !in_array($formValues['status'], ['aktif', 'nonaktif'], true)
                || (!$isEditing && strlen($password) < 8)
                || ($password !== '' && strlen($password) < 8)
            )) {
                $errorMessage = 'Periksa kembali data anggota. Email, nama, tanggal, status harus valid; kata sandi minimal 8 karakter.';
            }

            if ($errorMessage === '') {
                $emailCheck = $pdo->prepare(
                    'SELECT `user_id` FROM `users` WHERE `email` = :email LIMIT 1'
                );
                $emailCheck->execute(['email' => $email]);
                $existingEmailUserId = $emailCheck->fetchColumn();
                if ($existingEmailUserId !== false
                    && (!$isEditing || (int) $existingEmailUserId !== (int) $editingMember['user_id'])) {
                    $errorMessage = 'Email tersebut sudah digunakan oleh akun lain.';
                }
            }

            if ($errorMessage === '') {
                try {
                    $pdo->beginTransaction();
                    if ($isEditing) {
                        $updateUser = $pdo->prepare(
                            'UPDATE `users`
                             SET `nama_lengkap` = :nama, `email` = :email, `status` = :status
                             WHERE `user_id` = :user_id AND `role` = :role'
                        );
                        $updateUser->execute([
                            'nama' => $formValues['nama_lengkap'],
                            'email' => $email,
                            'status' => $formValues['status'],
                            'user_id' => $editingMember['user_id'],
                            'role' => 'anggota',
                        ]);

                        if ($password !== '') {
                            $updatePassword = $pdo->prepare(
                                'UPDATE `users` SET `password_hash` = :password_hash
                                 WHERE `user_id` = :user_id AND `role` = :role'
                            );
                            $updatePassword->execute([
                                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                                'user_id' => $editingMember['user_id'],
                                'role' => 'anggota',
                            ]);
                        }

                        $updateMember = $pdo->prepare(
                            'UPDATE `anggota`
                             SET `nomor_telepon` = :telepon, `alamat` = :alamat,
                                 `tanggal_daftar` = :tanggal_daftar, `status` = :status
                             WHERE `anggota_id` = :anggota_id'
                        );
                        $updateMember->execute([
                            'telepon' => $formValues['nomor_telepon'] !== '' ? $formValues['nomor_telepon'] : null,
                            'alamat' => $formValues['alamat'] !== '' ? $formValues['alamat'] : null,
                            'tanggal_daftar' => $formValues['tanggal_daftar'],
                            'status' => $formValues['status'],
                            'anggota_id' => $memberId,
                        ]);
                        $success = 'Data anggota berhasil diperbarui.';
                    } else {
                        $insertUser = $pdo->prepare(
                            'INSERT INTO `users` (`nama_lengkap`, `email`, `password_hash`, `role`, `status`)
                             VALUES (:nama, :email, :password_hash, :role, :status)'
                        );
                        $insertUser->execute([
                            'nama' => $formValues['nama_lengkap'],
                            'email' => $email,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'role' => 'anggota',
                            'status' => $formValues['status'],
                        ]);
                        $newUserId = (int) $pdo->lastInsertId();
                        $memberNumber = 'AGT-' . strtoupper(bin2hex(random_bytes(5)));

                        $insertMember = $pdo->prepare(
                            'INSERT INTO `anggota`
                                (`user_id`, `nomor_anggota`, `nomor_telepon`, `alamat`, `tanggal_daftar`, `status`)
                             VALUES
                                (:user_id, :nomor_anggota, :telepon, :alamat, :tanggal_daftar, :status)'
                        );
                        $insertMember->execute([
                            'user_id' => $newUserId,
                            'nomor_anggota' => $memberNumber,
                            'telepon' => $formValues['nomor_telepon'] !== '' ? $formValues['nomor_telepon'] : null,
                            'alamat' => $formValues['alamat'] !== '' ? $formValues['alamat'] : null,
                            'tanggal_daftar' => $formValues['tanggal_daftar'],
                            'status' => $formValues['status'],
                        ]);
                        $success = 'Anggota dan akun login berhasil ditambahkan.';
                    }

                    $pdo->commit();
                    $_SESSION['members_success'] = $success;
                    digilibMemberRedirect();
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('DIGILIB member save database error: ' . $exception->getMessage());
                    $errorMessage = $exception->getCode() === '23000'
                        ? 'Email atau nomor anggota sudah digunakan.'
                        : 'Data anggota gagal disimpan karena terjadi kesalahan database.';
                }
            }
        } elseif ($action === 'delete') {
            $memberId = filter_var($_POST['anggota_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($memberId === false) {
                $errorMessage = 'ID anggota yang akan dihapus tidak valid.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $statement = $pdo->prepare(
                        "SELECT a.`user_id`
                         FROM `anggota` a
                         INNER JOIN `users` u ON u.`user_id` = a.`user_id`
                         WHERE a.`anggota_id` = :id AND u.`role` = 'anggota'
                         FOR UPDATE"
                    );
                    $statement->execute(['id' => $memberId]);
                    $memberToDelete = $statement->fetch();
                    if (!$memberToDelete) {
                        $pdo->rollBack();
                        $errorMessage = 'Data anggota tidak ditemukan.';
                    } else {
                        $deleteMember = $pdo->prepare('DELETE FROM `anggota` WHERE `anggota_id` = :id');
                        $deleteMember->execute(['id' => $memberId]);
                        $deleteUser = $pdo->prepare(
                            "DELETE FROM `users` WHERE `user_id` = :user_id AND `role` = 'anggota'"
                        );
                        $deleteUser->execute(['user_id' => $memberToDelete['user_id']]);
                        $pdo->commit();
                        $_SESSION['members_success'] = 'Data anggota dan akun login berhasil dihapus.';
                        digilibMemberRedirect();
                    }
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('DIGILIB member delete database error: ' . $exception->getMessage());
                    $errorMessage = $exception->getCode() === '23000'
                        ? 'Anggota tidak dapat dihapus karena masih memiliki riwayat peminjaman.'
                        : 'Data anggota gagal dihapus karena terjadi kesalahan database.';
                }
            }
        } else {
            http_response_code(400);
            $errorMessage = 'Aksi anggota tidak dikenali.';
        }
    }
}

$requestedEditId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $requestedEditId !== false && $requestedEditId !== null) {
    $statement = $pdo->prepare(
        "SELECT a.`anggota_id`, a.`user_id`, a.`nomor_anggota`, a.`nomor_telepon`,
                a.`alamat`, a.`tanggal_daftar`, a.`status`,
                u.`nama_lengkap`, u.`email`
         FROM `anggota` a
         INNER JOIN `users` u ON u.`user_id` = a.`user_id`
         WHERE a.`anggota_id` = :id AND u.`role` = 'anggota'
         LIMIT 1"
    );
    $statement->execute(['id' => $requestedEditId]);
    $editingMember = $statement->fetch() ?: null;
    if ($editingMember === null) {
        http_response_code(404);
        $errorMessage = 'Data anggota yang akan diedit tidak ditemukan.';
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'aktif', 'nonaktif'], true)) {
    $statusFilter = '';
}
$conditions = ["u.`role` = 'anggota'"];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(u.`nama_lengkap` LIKE :nama OR u.`email` LIKE :email OR a.`nomor_anggota` LIKE :nomor OR a.`nomor_telepon` LIKE :telepon OR a.`alamat` LIKE :alamat)';
    $term = '%' . $search . '%';
    $parameters['nama'] = $term;
    $parameters['email'] = $term;
    $parameters['nomor'] = $term;
    $parameters['telepon'] = $term;
    $parameters['alamat'] = $term;
}
if ($statusFilter !== '') {
    $conditions[] = 'a.`status` = :status';
    $parameters['status'] = $statusFilter;
}
$statement = $pdo->prepare(
    'SELECT a.`anggota_id`, a.`nomor_anggota`, a.`nomor_telepon`, a.`alamat`,
            a.`tanggal_daftar`, a.`status`, u.`nama_lengkap`, u.`email`,
            (SELECT COUNT(*) FROM `peminjaman` p
             WHERE p.`anggota_id` = a.`anggota_id` AND p.`status` = \'dipinjam\') AS `pinjaman_aktif`
     FROM `anggota` a
     INNER JOIN `users` u ON u.`user_id` = a.`user_id`
     WHERE ' . implode(' AND ', $conditions) . '
     ORDER BY a.`tanggal_daftar` DESC, u.`nama_lengkap` ASC'
);
$statement->execute($parameters);
$members = $statement->fetchAll();

if ($editingMember !== null) {
    $formValues = array_merge($editingMember, $formValues);
}
$isEditing = $editingMember !== null;
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <p class="text-sm text-slate-500">Kelola profil anggota dan status akun untuk mengatur hak akses peminjaman.</p>
  <a class="btn btn-primary no-underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>#form-anggota">+ Tambah anggota</a>
</div>

<?php if ($successMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status"><?= digilibEscape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<section class="card-shadow mb-6 rounded-2xl border border-slate-100 bg-white p-4 sm:p-5">
  <form class="flex flex-col gap-3 sm:flex-row sm:items-end" action="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>" method="get">
    <div class="min-w-0 flex-1"><label class="field-label" for="search-members">Cari anggota</label><input class="field" id="search-members" type="search" name="q" value="<?= digilibEscape($search) ?>" placeholder="Nama, email, nomor anggota, atau telepon"></div>
    <div class="sm:w-56"><label class="field-label" for="filter-status">Status anggota</label><select class="field" id="filter-status" name="status"><option value="">Semua status</option><option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= $statusFilter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div>
    <button class="btn btn-primary" type="submit">Cari / Filter</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?><a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>">Reset</a><?php endif; ?>
  </form>
</section>

<section class="card-shadow mb-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex items-center justify-between px-5 py-4"><div><h2 class="display-font text-sm font-extrabold">Daftar anggota</h2><p class="mt-1 text-xs text-slate-400"><?= count($members) ?> anggota ditemukan</p></div></div>
  <div class="table-wrap">
    <?php if ($members): ?>
      <table class="data-table">
        <thead><tr><th>Anggota</th><th>Nomor anggota</th><th>Kontak</th><th>Alamat</th><th>Tanggal daftar</th><th>Pinjaman aktif</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><div class="font-bold text-ink"><?= digilibEscape($member['nama_lengkap']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($member['email']) ?></div></td>
              <td><span class="badge badge-blue"><?= digilibEscape($member['nomor_anggota']) ?></span></td>
              <td><?= digilibEscape($member['nomor_telepon'] ?: '—') ?></td>
              <td class="max-w-xs truncate"><?= digilibEscape($member['alamat'] ?: '—') ?></td>
              <td><?= digilibEscape((new DateTimeImmutable($member['tanggal_daftar']))->format('d-m-Y')) ?></td>
              <td><span class="badge <?= (int) $member['pinjaman_aktif'] > 0 ? 'badge-blue' : 'badge-gray' ?>"><?= (int) $member['pinjaman_aktif'] ?> buku</span></td>
              <td><span class="badge <?= $member['status'] === 'aktif' ? 'badge-green' : 'badge-gray' ?>"><?= $member['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td>
                <div class="flex items-center gap-1">
                  <a class="icon-btn no-underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php?edit=' . (int) $member['anggota_id'])) ?>#form-anggota" title="Edit anggota" aria-label="Edit <?= digilibEscape($member['nama_lengkap']) ?>">✎</a>
                  <form method="post" action="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>" onsubmit="return confirm('Hapus anggota dan akun login ini? Riwayat peminjaman terkait akan mencegah penghapusan.')">
                    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="anggota_id" value="<?= (int) $member['anggota_id'] ?>">
                    <button class="icon-btn hover:!bg-red-50 hover:!text-red-600" type="submit" title="Hapus anggota" aria-label="Hapus <?= digilibEscape($member['nama_lengkap']) ?>">×</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="flex flex-col items-center justify-center px-5 py-12 text-center"><div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-50 text-slate-400">♙</div><p class="text-sm font-semibold text-slate-600"><?= $search !== '' || $statusFilter !== '' ? 'Tidak ada anggota yang sesuai dengan pencarian atau filter.' : 'Belum ada data anggota. Tambahkan anggota melalui formulir di bawah.' ?></p></div>
    <?php endif; ?>
  </div>
</section>

<section id="form-anggota" class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
  <div class="mb-5"><h2 class="display-font text-sm font-extrabold"><?= $isEditing ? 'Edit anggota' : 'Tambah anggota' ?></h2><p class="mt-1 text-xs text-slate-400"><?= $isEditing ? 'Email, nama, dan status akun anggota ikut diperbarui.' : 'Satu akun dengan role anggota akan dibuat bersama profilnya.' ?></p></div>
  <form action="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?><?= $isEditing ? '?edit=' . (int) $editingMember['anggota_id'] . '#form-anggota' : '#form-anggota' ?>" method="post" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($isEditing): ?><input type="hidden" name="anggota_id" value="<?= (int) $editingMember['anggota_id'] ?>"><?php endif; ?>
    <div><label class="field-label" for="nama-anggota">Nama lengkap *</label><input class="field" id="nama-anggota" name="nama_lengkap" maxlength="120" value="<?= digilibEscape((string) ($formValues['nama_lengkap'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="email-anggota">Email login *</label><input class="field" id="email-anggota" name="email" type="email" maxlength="190" value="<?= digilibEscape((string) ($formValues['email'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="telepon-anggota">Nomor telepon</label><input class="field" id="telepon-anggota" name="nomor_telepon" maxlength="30" value="<?= digilibEscape((string) ($formValues['nomor_telepon'] ?? '')) ?>"></div>
    <div><label class="field-label" for="tanggal-daftar">Tanggal daftar *</label><input class="field" id="tanggal-daftar" name="tanggal_daftar" type="date" value="<?= digilibEscape((string) ($formValues['tanggal_daftar'] ?? date('Y-m-d'))) ?>" required></div>
    <div><label class="field-label" for="status-anggota">Status anggota *</label><select class="field" id="status-anggota" name="status" required><option value="aktif" <?= ($formValues['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= ($formValues['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div>
    <div><label class="field-label" for="password-anggota">Kata sandi <?= $isEditing ? '(opsional)' : '*' ?></label><input class="field" id="password-anggota" name="password" type="password" minlength="8" autocomplete="new-password" <?= $isEditing ? '' : 'required' ?> placeholder="<?= $isEditing ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' ?>"></div>
    <div class="sm:col-span-2 xl:col-span-3"><label class="field-label" for="alamat-anggota">Alamat</label><textarea class="field" id="alamat-anggota" name="alamat" rows="3" maxlength="5000"><?= digilibEscape((string) ($formValues['alamat'] ?? '')) ?></textarea></div>
    <div class="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-3">
      <button class="btn btn-primary" type="submit"><?= $isEditing ? 'Simpan perubahan' : 'Simpan anggota' ?></button>
      <?php if ($isEditing): ?><a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/anggota.php')) ?>">Batal edit</a><?php endif; ?>
    </div>
  </form>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
