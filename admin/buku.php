<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/supabase_storage.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$pdo = digilibDatabaseConnection();
$portalTitle = 'Data Buku';
$portalDescription = 'Kelola katalog buku perpustakaan.';
$activePage = 'books';
$errorMessage = '';
$successMessage = (string) ($_SESSION['books_success'] ?? '');
unset($_SESSION['books_success']);
$formValues = [];
$editingBook = null;

function digilibBookRedirect(): void
{
    header('Location: ' . digilibUrl('admin/buku.php'), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'delete') {
            $bookId = filter_var($_POST['buku_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($bookId === false) {
                $errorMessage = 'Data buku yang akan dihapus tidak valid.';
            } else {
                try {
                    $statement = $pdo->prepare('SELECT `cover_path` FROM `buku` WHERE `buku_id` = :id');
                    $statement->execute(['id' => $bookId]);
                    $bookToDelete = $statement->fetch();
                    if (!$bookToDelete) {
                        $errorMessage = 'Buku yang akan dihapus tidak ditemukan.';
                    } else {
                        $delete = $pdo->prepare('DELETE FROM `buku` WHERE `buku_id` = :id');
                        $delete->execute(['id' => $bookId]);
                        digilibRemoveBookCover($bookToDelete['cover_path']);
                        $_SESSION['books_success'] = 'Buku berhasil dihapus.';
                        digilibBookRedirect();
                    }
                } catch (PDOException $exception) {
                    error_log('DIGILIB book delete database error: ' . $exception->getMessage());
                    $errorMessage = $exception->getCode() === '23000'
                        ? 'Buku tidak dapat dihapus karena masih digunakan dalam transaksi peminjaman.'
                        : 'Buku gagal dihapus karena terjadi kesalahan database.';
                }
            }
        } elseif ($action === 'save') {
            $bookId = filter_var($_POST['buku_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $isEditing = $bookId !== false && $bookId !== null;
            $formValues = [
                'isbn' => trim((string) ($_POST['isbn'] ?? '')),
                'judul' => trim((string) ($_POST['judul'] ?? '')),
                'penulis' => trim((string) ($_POST['penulis'] ?? '')),
                'penerbit' => trim((string) ($_POST['penerbit'] ?? '')),
                'tahun_terbit' => trim((string) ($_POST['tahun_terbit'] ?? '')),
                'kategori_id' => trim((string) ($_POST['kategori_id'] ?? '')),
                'rak' => trim((string) ($_POST['rak'] ?? '')),
                'stok' => trim((string) ($_POST['stok'] ?? '')),
            ];
            if (array_key_exists('buku_id', $_POST) && !$isEditing) {
                $errorMessage = 'ID buku yang akan diedit tidak valid.';
            }

            $isbn = preg_replace('/[\s-]+/', '', $formValues['isbn']);
            $year = filter_var($formValues['tahun_terbit'], FILTER_VALIDATE_INT);
            $categoryId = filter_var($formValues['kategori_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $stock = filter_var($formValues['stok'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $maximumYear = (int) date('Y') + 1;

            if ($errorMessage === '' && $isEditing) {
                $editStatement = $pdo->prepare('SELECT * FROM `buku` WHERE `buku_id` = :id');
                $editStatement->execute(['id' => $bookId]);
                $editingBook = $editStatement->fetch() ?: null;
                if ($editingBook === null) {
                    http_response_code(404);
                    $errorMessage = 'Buku yang akan diedit tidak ditemukan.';
                }
            }

            if ($errorMessage === '') {
                if ($isbn === '' || strlen($isbn) > 20
                    || $formValues['judul'] === '' || mb_strlen($formValues['judul']) > 200
                    || $formValues['penulis'] === '' || mb_strlen($formValues['penulis']) > 150
                    || $formValues['penerbit'] === '' || mb_strlen($formValues['penerbit']) > 150
                    || $year === false || $year < 1000 || $year > $maximumYear
                    || $categoryId === false || $stock === false
                    || $formValues['rak'] === '' || mb_strlen($formValues['rak']) > 30) {
                    $errorMessage = 'Periksa kembali semua kolom. ISBN, judul, penulis, penerbit, tahun, kategori, rak, dan stok harus valid.';
                } else {
                    $categoryExists = $pdo->prepare('SELECT 1 FROM `kategori` WHERE `kategori_id` = :id');
                    $categoryExists->execute(['id' => $categoryId]);
                    if (!$categoryExists->fetchColumn()) {
                        $errorMessage = 'Kategori yang dipilih tidak tersedia.';
                    }
                }
            }

            $newCoverPath = null;
            $oldCoverFilename = $editingBook['cover_path'] ?? null;
            $coverUpload = $_FILES['cover'] ?? null;
            if ($errorMessage === '' && is_array($coverUpload) && $coverUpload['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($coverUpload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($coverUpload['tmp_name'])) {
                    $errorMessage = 'Cover gagal diunggah. Pilih ulang file gambar yang valid.';
                } elseif ($coverUpload['size'] > 3 * 1024 * 1024) {
                    $errorMessage = 'Ukuran cover maksimal 3 MB.';
                } else {
                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file($coverUpload['tmp_name']);
                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    $imageInfo = @getimagesize($coverUpload['tmp_name']);
                    if (!isset($allowedTypes[$mimeType]) || $imageInfo === false) {
                        $errorMessage = 'Format cover harus berupa gambar JPG, PNG, atau WebP.';
                    } else {
                        try {
                            $newCoverPath = digilibUploadBookCover(
                                $coverUpload['tmp_name'],
                                $mimeType,
                                $allowedTypes[$mimeType]
                            );
                        } catch (RuntimeException $exception) {
                            error_log('DIGILIB book cover upload failed: ' . $exception->getMessage());
                            $errorMessage = 'Cover gagal disimpan ke Supabase Storage. Periksa konfigurasi kunci dan bucket Supabase.';
                        }
                    }
                }
            }

            if ($errorMessage === '') {
                $coverPath = $newCoverPath ?? $oldCoverFilename;
                try {
                    if ($isEditing) {
                        $statement = $pdo->prepare(
                            'UPDATE `buku`
                             SET `isbn` = :isbn, `judul` = :judul, `penulis` = :penulis,
                                 `penerbit` = :penerbit, `tahun_terbit` = :tahun_terbit,
                                 `kategori_id` = :kategori_id, `rak` = :rak, `stok` = :stok,
                                 `cover_path` = :cover_path
                             WHERE `buku_id` = :buku_id'
                        );
                        $statement->execute([
                            'isbn' => $isbn,
                            'judul' => $formValues['judul'],
                            'penulis' => $formValues['penulis'],
                            'penerbit' => $formValues['penerbit'],
                            'tahun_terbit' => $year,
                            'kategori_id' => $categoryId,
                            'rak' => $formValues['rak'],
                            'stok' => $stock,
                            'cover_path' => $coverPath,
                            'buku_id' => $bookId,
                        ]);
                        if ($newCoverPath !== null) {
                            digilibRemoveBookCover($oldCoverFilename);
                        }
                        $_SESSION['books_success'] = 'Perubahan buku berhasil disimpan.';
                    } else {
                        $statement = $pdo->prepare(
                            'INSERT INTO `buku`
                                (`isbn`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `kategori_id`, `rak`, `stok`, `cover_path`)
                             VALUES
                                (:isbn, :judul, :penulis, :penerbit, :tahun_terbit, :kategori_id, :rak, :stok, :cover_path)'
                        );
                        $statement->execute([
                            'isbn' => $isbn,
                            'judul' => $formValues['judul'],
                            'penulis' => $formValues['penulis'],
                            'penerbit' => $formValues['penerbit'],
                            'tahun_terbit' => $year,
                            'kategori_id' => $categoryId,
                            'rak' => $formValues['rak'],
                            'stok' => $stock,
                            'cover_path' => $coverPath,
                        ]);
                        $_SESSION['books_success'] = 'Buku berhasil ditambahkan ke katalog.';
                    }
                    digilibBookRedirect();
                } catch (PDOException $exception) {
                    if ($newCoverPath !== null) {
                        digilibRemoveBookCover($newCoverPath);
                    }
                    error_log('DIGILIB book save database error: ' . $exception->getMessage());
                    $errorMessage = $exception->getCode() === '23000'
                        ? 'ISBN tersebut sudah digunakan oleh buku lain.'
                        : 'Data buku gagal disimpan karena terjadi kesalahan database.';
                }
            }
        } else {
            http_response_code(400);
            $errorMessage = 'Aksi buku tidak dikenali.';
        }
    }
}

$requestedEditId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $requestedEditId !== false && $requestedEditId !== null) {
    $editStatement = $pdo->prepare('SELECT * FROM `buku` WHERE `buku_id` = :id');
    $editStatement->execute(['id' => $requestedEditId]);
    $editingBook = $editStatement->fetch() ?: null;
    if ($editingBook === null) {
        http_response_code(404);
        $errorMessage = 'Buku yang akan diedit tidak ditemukan.';
    }
}

$categories = $pdo->query('SELECT `kategori_id`, `nama_kategori` FROM `kategori` ORDER BY `nama_kategori`')->fetchAll();
$search = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = filter_var($_GET['kategori'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($categoryFilter === false) {
    $categoryFilter = null;
}

$conditions = [];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(b.`isbn` LIKE :search_isbn OR b.`judul` LIKE :search_judul OR b.`penulis` LIKE :search_penulis OR b.`penerbit` LIKE :search_penerbit)';
    $term = '%' . $search . '%';
    $parameters['search_isbn'] = $term;
    $parameters['search_judul'] = $term;
    $parameters['search_penulis'] = $term;
    $parameters['search_penerbit'] = $term;
}
if ($categoryFilter !== null) {
    $conditions[] = 'b.`kategori_id` = :kategori_id';
    $parameters['kategori_id'] = $categoryFilter;
}
$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$listStatement = $pdo->prepare(
    'SELECT b.`buku_id`, b.`isbn`, b.`judul`, b.`penulis`, b.`penerbit`, b.`tahun_terbit`,
            b.`rak`, b.`stok`, b.`cover_path`, k.`nama_kategori`,
            (SELECT COUNT(*) FROM `peminjaman` p
             WHERE p.`buku_id` = b.`buku_id` AND p.`status` = \'dipinjam\') AS `pinjaman_aktif`
     FROM `buku` b
     INNER JOIN `kategori` k ON k.`kategori_id` = b.`kategori_id`
     ' . $whereSql . '
     ORDER BY b.`judul` ASC, b.`buku_id` DESC'
);
$listStatement->execute($parameters);
$books = $listStatement->fetchAll();

if ($editingBook !== null) {
    $formValues = array_merge($editingBook, $formValues);
}
$isEditing = $editingBook !== null || (isset($_POST['action'], $_POST['buku_id']) && $_POST['action'] === 'save');
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
  <p class="text-sm text-slate-500">Kelola ISBN, informasi bibliografi, kategori, stok, dan cover koleksi.</p>
  <a class="btn btn-primary no-underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>#form-buku">+ Tambah buku</a>
</div>

<?php if ($successMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status"><?= digilibEscape($successMessage) ?></div>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<section class="card-shadow mb-6 rounded-2xl border border-slate-100 bg-white p-4 sm:p-5">
  <form class="flex flex-col gap-3 sm:flex-row sm:items-end" action="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>" method="get">
    <div class="min-w-0 flex-1">
      <label class="field-label" for="search-books">Cari buku</label>
      <input class="field" id="search-books" type="search" name="q" value="<?= digilibEscape($search) ?>" placeholder="ISBN, judul, penulis, atau penerbit">
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
    <button class="btn btn-primary" type="submit">Cari / Filter</button>
    <?php if ($search !== '' || $categoryFilter !== null): ?>
      <a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>">Reset</a>
    <?php endif; ?>
  </form>
</section>

<section class="card-shadow mb-6 overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <div class="flex items-center justify-between px-5 py-4">
    <div><h2 class="display-font text-sm font-extrabold">Katalog buku</h2><p class="mt-1 text-xs text-slate-400"><?= count($books) ?> buku ditemukan</p></div>
  </div>
  <div class="table-wrap">
    <?php if ($books): ?>
      <table class="data-table">
        <thead><tr><th>Cover / ISBN</th><th>Judul dan penulis</th><th>Penerbit</th><th>Tahun</th><th>Kategori</th><th>Rak</th><th>Stok tersedia</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($books as $book): ?>
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <?php $coverUrl = digilibBookCoverUrl(is_string($book['cover_path']) ? $book['cover_path'] : null); ?>
                  <?php if ($coverUrl !== null): ?>
                    <img class="h-14 w-11 rounded-lg border border-slate-100 object-cover" src="<?= digilibEscape($coverUrl) ?>" alt="Cover <?= digilibEscape($book['judul']) ?>">
                  <?php else: ?>
                    <div class="grid h-14 w-11 place-items-center rounded-lg bg-teal-50 text-teal-700">B</div>
                  <?php endif; ?>
                  <span class="text-xs font-medium text-slate-600"><?= digilibEscape($book['isbn'] ?? '—') ?></span>
                </div>
              </td>
              <td><div class="font-bold text-ink"><?= digilibEscape($book['judul']) ?></div><div class="mt-1 text-xs text-slate-500"><?= digilibEscape($book['penulis']) ?></div></td>
              <td><?= digilibEscape($book['penerbit']) ?></td>
              <td><?= (int) $book['tahun_terbit'] ?></td>
              <td><span class="badge badge-blue"><?= digilibEscape($book['nama_kategori']) ?></span></td>
              <td><?= digilibEscape($book['rak']) ?></td>
              <td><span class="font-bold text-ink"><?= (int) $book['stok'] ?></span><span class="text-slate-400"> / <?= (int) $book['stok'] + (int) $book['pinjaman_aktif'] ?></span></td>
              <td><span class="badge <?= (int) $book['stok'] > 0 ? 'badge-green' : ((int) $book['pinjaman_aktif'] > 0 ? 'badge-amber' : 'badge-red') ?>"><?= (int) $book['stok'] > 0 ? 'Tersedia' : ((int) $book['pinjaman_aktif'] > 0 ? 'Dipinjam' : 'Stok habis') ?></span></td>
              <td>
                <div class="flex items-center gap-1">
                  <a class="icon-btn no-underline" href="<?= digilibEscape(digilibUrl('admin/buku.php?edit=' . (int) $book['buku_id'])) ?>#form-buku" title="Edit buku" aria-label="Edit <?= digilibEscape($book['judul']) ?>">✎</a>
                  <form method="post" action="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>" onsubmit="return confirm('Hapus buku ini dari katalog?')">
                    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="buku_id" value="<?= (int) $book['buku_id'] ?>">
                    <button class="icon-btn hover:!bg-red-50 hover:!text-red-600" type="submit" title="Hapus buku" aria-label="Hapus <?= digilibEscape($book['judul']) ?>">×</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="flex flex-col items-center justify-center px-5 py-12 text-center">
        <div class="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-slate-50 text-slate-400">▤</div>
        <p class="text-sm font-semibold text-slate-600"><?= $search !== '' || $categoryFilter !== null ? 'Tidak ada buku yang sesuai dengan pencarian atau filter.' : 'Belum ada buku di katalog. Tambahkan buku melalui formulir di bawah.' ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="form-buku" class="card-shadow rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
  <div class="mb-5">
    <h2 class="display-font text-sm font-extrabold"><?= $isEditing ? 'Edit buku' : 'Tambah buku' ?></h2>
    <p class="mt-1 text-xs text-slate-400"><?= $isEditing ? 'Perbarui informasi buku. Cover boleh dikosongkan jika tidak diganti.' : 'Data akan langsung disimpan ke database dan muncul di katalog.' ?></p>
  </div>
  <form action="<?= digilibEscape(digilibUrl('admin/buku.php')) ?><?= $isEditing && $editingBook ? '?edit=' . (int) $editingBook['buku_id'] . '#form-buku' : '#form-buku' ?>" method="post" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <input type="hidden" name="csrf_token" value="<?= digilibEscape(digilibCsrfToken()) ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($isEditing && $editingBook): ?><input type="hidden" name="buku_id" value="<?= (int) $editingBook['buku_id'] ?>"><?php endif; ?>
    <div><label class="field-label" for="isbn">ISBN *</label><input class="field" id="isbn" name="isbn" maxlength="20" value="<?= digilibEscape((string) ($formValues['isbn'] ?? '')) ?>" placeholder="Contoh: 9786020332956" required></div>
    <div><label class="field-label" for="judul">Judul buku *</label><input class="field" id="judul" name="judul" maxlength="200" value="<?= digilibEscape((string) ($formValues['judul'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="penulis">Penulis *</label><input class="field" id="penulis" name="penulis" maxlength="150" value="<?= digilibEscape((string) ($formValues['penulis'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="penerbit">Penerbit *</label><input class="field" id="penerbit" name="penerbit" maxlength="150" value="<?= digilibEscape((string) ($formValues['penerbit'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="tahun-terbit">Tahun terbit *</label><input class="field" id="tahun-terbit" name="tahun_terbit" type="number" min="1000" max="<?= (int) date('Y') + 1 ?>" value="<?= digilibEscape((string) ($formValues['tahun_terbit'] ?? '')) ?>" required></div>
    <div><label class="field-label" for="kategori">Kategori *</label><select class="field" id="kategori" name="kategori_id" required><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['kategori_id'] ?>" <?= (string) ($formValues['kategori_id'] ?? '') === (string) $category['kategori_id'] ? 'selected' : '' ?>><?= digilibEscape($category['nama_kategori']) ?></option><?php endforeach; ?></select></div>
    <div><label class="field-label" for="rak">Rak *</label><input class="field" id="rak" name="rak" maxlength="30" value="<?= digilibEscape((string) ($formValues['rak'] ?? '')) ?>" placeholder="Contoh: A-01" required></div>
    <div><label class="field-label" for="stok">Stok *</label><input class="field" id="stok" name="stok" type="number" min="0" step="1" value="<?= digilibEscape((string) ($formValues['stok'] ?? '0')) ?>" required></div>
    <div><label class="field-label" for="cover">Cover buku</label><input class="field" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"><span class="mt-1 block text-[11px] text-slate-400">JPG, PNG, atau WebP; maksimal 3 MB.</span>
      <?php $currentCoverUrl = $isEditing ? digilibBookCoverUrl(isset($formValues['cover_path']) ? (string) $formValues['cover_path'] : null) : null; ?>
      <?php if ($currentCoverUrl !== null): ?><img class="mt-2 h-20 rounded-lg object-cover" src="<?= digilibEscape($currentCoverUrl) ?>" alt="Cover saat ini"><?php endif; ?>
    </div>
    <div class="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-3">
      <button class="btn btn-primary" type="submit"><?= $isEditing ? 'Simpan perubahan' : 'Simpan buku' ?></button>
      <?php if ($isEditing): ?><a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/buku.php')) ?>">Batal edit</a><?php endif; ?>
    </div>
  </form>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
