<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
$portalUser = digilibRequireRole(['admin', 'pustakawan']);
$database = digilibDatabaseRead();
$portalTitle = 'Laporan Perpustakaan';
$portalDescription = 'Pilih jenis laporan, atur rentang tanggal, lalu cetak hasilnya.';
$activePage = 'reports';

$reportDefinitions = [
    'buku' => [
        'title' => 'Laporan Data Buku',
        'date_label' => 'Tanggal data buku (ditambahkan)',
        'date_key' => 'created_at',
        'source' => 'buku',
        'columns' => [
            'isbn' => 'ISBN', 'judul' => 'Judul', 'penulis' => 'Penulis', 'penerbit' => 'Penerbit',
            'tahun_terbit' => 'Tahun', 'nama_kategori' => 'Kategori', 'rak' => 'Rak', 'stok' => 'Stok', 'status' => 'Status',
        ],
    ],
    'anggota' => [
        'title' => 'Laporan Data Anggota',
        'date_label' => 'Tanggal daftar',
        'date_key' => 'tanggal_daftar',
        'source' => 'anggota',
        'columns' => [
            'nama_lengkap' => 'Nama', 'email' => 'Email', 'nomor_anggota' => 'Nomor anggota',
            'nomor_telepon' => 'Telepon', 'alamat' => 'Alamat', 'tanggal_daftar' => 'Tanggal daftar', 'status' => 'Status',
        ],
    ],
    'peminjaman' => [
        'title' => 'Laporan Peminjaman',
        'date_label' => 'Tanggal peminjaman',
        'date_key' => 'tanggal_pinjam',
        'source' => 'peminjaman',
        'columns' => [
            'peminjaman_id' => 'ID', 'nama_lengkap' => 'Anggota', 'nomor_anggota' => 'Nomor anggota',
            'judul' => 'Buku', 'isbn' => 'ISBN', 'tanggal_pinjam' => 'Tanggal pinjam',
            'tanggal_jatuh_tempo' => 'Jatuh tempo', 'status' => 'Status',
        ],
    ],
    'pengembalian' => [
        'title' => 'Laporan Pengembalian',
        'date_label' => 'Tanggal pengembalian',
        'date_key' => 'tanggal_kembali',
        'source' => 'pengembalian',
        'columns' => [
            'pengembalian_id' => 'ID', 'nama_lengkap' => 'Anggota', 'nomor_anggota' => 'Nomor anggota',
            'judul' => 'Buku', 'isbn' => 'ISBN', 'tanggal_pinjam' => 'Tanggal pinjam',
            'tanggal_jatuh_tempo' => 'Jatuh tempo', 'tanggal_kembali' => 'Tanggal kembali',
            'jumlah_hari_terlambat' => 'Terlambat (hari)', 'jumlah_denda' => 'Denda',
        ],
    ],
    'denda' => [
        'title' => 'Laporan Denda',
        'date_label' => 'Tanggal pengembalian',
        'date_key' => 'tanggal_kembali',
        'source' => 'denda',
        'columns' => [
            'nama_lengkap' => 'Anggota', 'nomor_anggota' => 'Nomor anggota', 'judul' => 'Buku',
            'tanggal_jatuh_tempo' => 'Jatuh tempo', 'tanggal_kembali' => 'Tanggal kembali',
            'jumlah_hari_terlambat' => 'Terlambat (hari)', 'tarif_per_hari' => 'Tarif / hari',
            'jumlah_denda' => 'Jumlah denda', 'status_pembayaran' => 'Status pembayaran',
            'tanggal_pembayaran' => 'Tanggal pembayaran',
        ],
    ],
];

$selectedReport = (string) ($_GET['jenis'] ?? 'buku');
$errorMessage = '';
if (!array_key_exists($selectedReport, $reportDefinitions)) {
    $errorMessage = 'Jenis laporan yang dipilih tidak tersedia.';
    $selectedReport = 'buku';
}
$report = $reportDefinitions[$selectedReport];

$dateFrom = trim((string) ($_GET['dari'] ?? ''));
$dateTo = trim((string) ($_GET['sampai'] ?? ''));
$validateDate = static function (string $value): bool {
    if ($value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if (!$validateDate($dateFrom) || !$validateDate($dateTo)) {
    $errorMessage = 'Format tanggal tidak valid. Pilih tanggal melalui pemilih tanggal.';
} elseif ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $errorMessage = 'Tanggal dari tidak boleh melewati tanggal sampai.';
}

$records = [];
if ($errorMessage === '') {
    switch ($selectedReport) {
        case 'buku':
            foreach ($database['buku'] as $book) {
                $joined = digilibDatabaseBookRecord($database, (int) $book['buku_id']);
                if ($joined !== null) {
                    $records[] = [
                        'isbn' => $book['isbn'],
                        'judul' => $book['judul'],
                        'penulis' => $book['penulis'],
                        'penerbit' => $book['penerbit'],
                        'tahun_terbit' => $book['tahun_terbit'],
                        'nama_kategori' => $joined['nama_kategori'] ?? '',
                        'rak' => $book['rak'],
                        'stok' => $book['stok'],
                        'status' => $book['status'],
                        'created_at' => $book['created_at'] ?? '',
                    ];
                }
            }
            usort($records, static function (array $left, array $right): int {
                return strcasecmp($left['judul'], $right['judul']);
            });
            break;
        case 'anggota':
            foreach ($database['anggota'] as $member) {
                $joined = digilibDatabaseMemberRecord($database, (int) $member['anggota_id']);
                if ($joined !== null && $joined['role'] === 'anggota') {
                    $records[] = $joined;
                }
            }
            usort($records, static function (array $left, array $right): int {
                return strcasecmp($left['nama_lengkap'], $right['nama_lengkap']);
            });
            break;
        case 'peminjaman':
            foreach ($database['peminjaman'] as $loan) {
                $joined = digilibDatabaseLoanRecord($database, (int) $loan['peminjaman_id']);
                if ($joined !== null) {
                    $records[] = array_merge($loan, [
                        'nama_lengkap' => $joined['nama_lengkap'],
                        'nomor_anggota' => $joined['nomor_anggota'],
                        'judul' => $joined['buku']['judul'],
                        'isbn' => $joined['buku']['isbn'],
                    ]);
                }
            }
            usort($records, static function (array $left, array $right): int {
                $dateOrder = strcmp($right['tanggal_pinjam'], $left['tanggal_pinjam']);
                return $dateOrder !== 0 ? $dateOrder : ((int) $right['peminjaman_id'] <=> (int) $left['peminjaman_id']);
            });
            break;
        case 'pengembalian':
            foreach ($database['pengembalian'] as $returnRecord) {
                $joined = digilibDatabaseLoanRecord($database, (int) $returnRecord['peminjaman_id']);
                if ($joined === null) {
                    continue;
                }
                $fine = null;
                foreach ($database['denda'] as $fineRecord) {
                    if ((int) $fineRecord['pengembalian_id'] === (int) $returnRecord['pengembalian_id']) {
                        $fine = $fineRecord;
                        break;
                    }
                }
                $records[] = [
                    'pengembalian_id' => $returnRecord['pengembalian_id'],
                    'nama_lengkap' => $joined['nama_lengkap'],
                    'nomor_anggota' => $joined['nomor_anggota'],
                    'judul' => $joined['buku']['judul'],
                    'isbn' => $joined['buku']['isbn'],
                    'tanggal_pinjam' => $joined['tanggal_pinjam'],
                    'tanggal_jatuh_tempo' => $joined['tanggal_jatuh_tempo'],
                    'tanggal_kembali' => $returnRecord['tanggal_kembali'],
                    'jumlah_hari_terlambat' => $fine['jumlah_hari_terlambat'] ?? 0,
                    'jumlah_denda' => $fine['jumlah_denda'] ?? 0,
                ];
            }
            usort($records, static function (array $left, array $right): int {
                $dateOrder = strcmp($right['tanggal_kembali'], $left['tanggal_kembali']);
                return $dateOrder !== 0 ? $dateOrder : ((int) $right['pengembalian_id'] <=> (int) $left['pengembalian_id']);
            });
            break;
        case 'denda':
            foreach ($database['denda'] as $fine) {
                $returnRecord = digilibDatabaseRecord($database, 'pengembalian', 'pengembalian_id', (int) $fine['pengembalian_id']);
                $joined = $returnRecord === null
                    ? null
                    : digilibDatabaseLoanRecord($database, (int) $returnRecord['peminjaman_id']);
                if ($joined !== null) {
                    $records[] = array_merge($fine, [
                        'nama_lengkap' => $joined['nama_lengkap'],
                        'nomor_anggota' => $joined['nomor_anggota'],
                        'judul' => $joined['buku']['judul'],
                        'tanggal_jatuh_tempo' => $joined['tanggal_jatuh_tempo'],
                        'tanggal_kembali' => $returnRecord['tanggal_kembali'],
                    ]);
                }
            }
            usort($records, static function (array $left, array $right): int {
                $dateOrder = strcmp($right['tanggal_kembali'], $left['tanggal_kembali']);
                return $dateOrder !== 0 ? $dateOrder : ((int) $right['denda_id'] <=> (int) $left['denda_id']);
            });
            break;
    }

    $records = array_values(array_filter($records, static function (array $record) use ($report, $dateFrom, $dateTo): bool {
        $date = substr((string) ($record[$report['date_key']] ?? ''), 0, 10);
        return ($dateFrom === '' || $date >= $dateFrom) && ($dateTo === '' || $date <= $dateTo);
    }));
}

$formatCell = static function (string $key, $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    if (strpos($key, 'tanggal_') === 0) {
        return (new DateTimeImmutable((string) $value))->format('d-m-Y');
    }
    if (in_array($key, ['jumlah_denda', 'tarif_per_hari'], true)) {
        return 'Rp' . number_format((float) $value, 0, ',', '.');
    }
    if (in_array($key, ['status', 'status_pembayaran'], true)) {
        return ucwords(str_replace('_', ' ', (string) $value));
    }
    return (string) $value;
};
$dateFromDisplay = $dateFrom !== '' && $validateDate($dateFrom)
    ? (new DateTimeImmutable($dateFrom))->format('d-m-Y')
    : 'awal data';
$dateToDisplay = $dateTo !== '' && $validateDate($dateTo)
    ? (new DateTimeImmutable($dateTo))->format('d-m-Y')
    : 'data terbaru';
?>
<?php require __DIR__ . '/../includes/portal_header.php'; ?>
<section class="no-print card-shadow mb-5 rounded-2xl border border-slate-100 bg-white p-5 sm:p-6">
  <form method="get" action="<?= digilibEscape(digilibUrl('admin/laporan.php')) ?>" class="grid grid-cols-1 items-end gap-4 md:grid-cols-4">
    <label>
      <span class="field-label">Jenis laporan</span>
      <select class="field" name="jenis">
        <?php foreach ($reportDefinitions as $key => $definition): ?>
          <option value="<?= digilibEscape($key) ?>" <?= $selectedReport === $key ? 'selected' : '' ?>><?= digilibEscape(str_replace('Laporan ', '', $definition['title'])) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      <span class="field-label">Tanggal dari</span>
      <input class="field" type="date" name="dari" value="<?= digilibEscape($dateFrom) ?>">
    </label>
    <label>
      <span class="field-label">Tanggal sampai</span>
      <input class="field" type="date" name="sampai" value="<?= digilibEscape($dateTo) ?>">
    </label>
    <div class="flex flex-wrap gap-2">
      <button class="btn btn-primary" type="submit">Tampilkan</button>
      <a class="btn btn-muted no-underline" href="<?= digilibEscape(digilibUrl('admin/laporan.php')) ?>">Reset</a>
    </div>
  </form>
  <p class="mt-3 text-xs text-slate-500">Rentang tanggal memfilter <?= digilibEscape(mb_strtolower($report['date_label'], 'UTF-8')) ?>. Kosongkan tanggal untuk melihat seluruh data.</p>
</section>

<?php if ($errorMessage !== ''): ?>
  <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert"><?= digilibEscape($errorMessage) ?></div>
<?php endif; ?>

<section id="report-print-area" class="card-shadow overflow-hidden rounded-2xl border border-slate-100 bg-white">
  <header class="border-b border-slate-100 px-5 py-5 sm:px-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-teal-700">DIGILIB SMART LIBRARY</p>
        <h2 class="display-font mt-1 text-lg font-extrabold text-ink"><?= digilibEscape($report['title']) ?></h2>
        <p class="mt-1 text-xs text-slate-500">
          Periode <?= digilibEscape($dateFromDisplay) ?> sampai <?= digilibEscape($dateToDisplay) ?>
        </p>
      </div>
      <div class="no-print">
        <button class="btn btn-primary" type="button" onclick="window.print()">Cetak laporan</button>
      </div>
    </div>
    <p class="mt-3 text-xs text-slate-500">Jumlah data: <?= number_format(count($records), 0, ',', '.') ?> · Dicetak pada <?= digilibEscape((new DateTimeImmutable())->format('d-m-Y H:i')) ?></p>
  </header>
  <?php if ($errorMessage === '' && $records !== []): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><?php foreach ($report['columns'] as $label): ?><th><?= digilibEscape($label) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody>
          <?php foreach ($records as $record): ?>
            <tr>
              <?php foreach ($report['columns'] as $key => $label): ?>
                <td><?= digilibEscape($formatCell($key, $record[$key] ?? null)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif ($errorMessage === ''): ?>
    <p class="px-5 py-12 text-center text-sm text-slate-500">Tidak ada data untuk laporan ini pada rentang tanggal yang dipilih.</p>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
