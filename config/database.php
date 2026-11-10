<?php
declare(strict_types=1);

function digilibDatabaseFile(): string
{
    return __DIR__ . '/../database/data.json';
}

function digilibDatabaseLockPath(): string
{
    return __DIR__ . '/../database/data.json.lock';
}

function digilibDatabaseSeed(): array
{
    $seedPath = __DIR__ . '/../database/data.example.json';
    $contents = file_get_contents($seedPath);
    if ($contents === false) {
        throw new RuntimeException('JSON database seed file could not be read.');
    }

    $database = json_decode($contents, true);
    if (!is_array($database)) {
        throw new RuntimeException('JSON database seed file is invalid.');
    }

    return $database;
}

function digilibDatabaseReadUnlocked(): array
{
    $path = digilibDatabaseFile();
    if (!is_file($path)) {
        return digilibDatabaseSeed();
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('JSON database file could not be read.');
    }

    $database = json_decode($contents, true);
    if (!is_array($database)) {
        throw new RuntimeException('JSON database file is invalid or corrupted.');
    }

    foreach (['users', 'kategori', 'anggota', 'buku', 'peminjaman', 'pengembalian', 'denda'] as $table) {
        if (!isset($database[$table]) || !is_array($database[$table])) {
            throw new RuntimeException('JSON database collection is missing or invalid: ' . $table);
        }
    }

    return $database;
}

function digilibDatabaseWriteUnlocked(array $database): void
{
    $directory = dirname(digilibDatabaseFile());
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Database folder is not writable by PHP.');
    }

    $json = json_encode($database, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('JSON database could not be encoded: ' . json_last_error_msg());
    }

    $temporaryPath = tempnam($directory, '.digilib-database-');
    if ($temporaryPath === false) {
        throw new RuntimeException('Temporary JSON database file could not be created.');
    }

    try {
        if (file_put_contents($temporaryPath, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Temporary JSON database file could not be written.');
        }
        if (DIRECTORY_SEPARATOR !== '\\') {
            chmod($temporaryPath, 0660);
        }
        if (!rename($temporaryPath, digilibDatabaseFile())) {
            throw new RuntimeException('JSON database file could not be updated.');
        }
    } finally {
        if (is_file($temporaryPath)) {
            unlink($temporaryPath);
        }
    }
}

function digilibDatabaseRead(): array
{
    if (!is_file(digilibDatabaseFile())) {
        return digilibDatabaseTransaction(static function (array &$database): array {
            return $database;
        });
    }

    $lock = fopen(digilibDatabaseLockPath(), 'c');
    if ($lock === false) {
        throw new RuntimeException('JSON database lock file could not be opened.');
    }

    try {
        if (!flock($lock, LOCK_SH)) {
            throw new RuntimeException('JSON database could not be locked for reading.');
        }
        return digilibDatabaseReadUnlocked();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function digilibDatabaseTransaction(callable $callback)
{
    $lock = fopen(digilibDatabaseLockPath(), 'c');
    if ($lock === false) {
        throw new RuntimeException('JSON database lock file could not be opened.');
    }

    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('JSON database could not be locked for writing.');
        }

        $database = digilibDatabaseReadUnlocked();
        $result = $callback($database);
        digilibDatabaseWriteUnlocked($database);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function digilibDatabaseNextId(array $database, string $table, string $idColumn): int
{
    $maximumId = 0;
    foreach ($database[$table] as $record) {
        $maximumId = max($maximumId, (int) ($record[$idColumn] ?? 0));
    }

    return $maximumId + 1;
}

function digilibDatabaseRecord(array $database, string $table, string $idColumn, int $id): ?array
{
    foreach ($database[$table] as $record) {
        if ((int) ($record[$idColumn] ?? 0) === $id) {
            return $record;
        }
    }

    return null;
}

function digilibDatabaseMemberRecord(array $database, int $memberId): ?array
{
    foreach ($database['anggota'] as $member) {
        if ((int) $member['anggota_id'] !== $memberId) {
            continue;
        }
        foreach ($database['users'] as $user) {
            if ((int) $user['user_id'] === (int) $member['user_id']) {
                return array_merge($member, [
                    'nama_lengkap' => $user['nama_lengkap'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'user_status' => $user['status'],
                    'password_hash' => $user['password_hash'],
                ]);
            }
        }
    }

    return null;
}

function digilibDatabaseBookRecord(array $database, int $bookId): ?array
{
    $book = digilibDatabaseRecord($database, 'buku', 'buku_id', $bookId);
    if ($book === null) {
        return null;
    }

    foreach ($database['kategori'] as $category) {
        if ((int) $category['kategori_id'] === (int) $book['kategori_id']) {
            $book['nama_kategori'] = $category['nama_kategori'];
            break;
        }
    }

    return $book;
}

function digilibDatabaseLoanRecord(array $database, int $loanId): ?array
{
    $loan = digilibDatabaseRecord($database, 'peminjaman', 'peminjaman_id', $loanId);
    if ($loan === null) {
        return null;
    }

    $member = digilibDatabaseMemberRecord($database, (int) $loan['anggota_id']);
    $book = digilibDatabaseBookRecord($database, (int) $loan['buku_id']);
    if ($member === null || $book === null) {
        return null;
    }

    return array_merge($loan, [
        'nomor_anggota' => $member['nomor_anggota'],
        'nama_lengkap' => $member['nama_lengkap'],
        'email' => $member['email'],
        'buku' => $book,
    ]);
}

function digilibDatabaseToday(): string
{
    return (new DateTimeImmutable('today'))->format('Y-m-d');
}

function digilibDatabaseTimestamp(): string
{
    return (new DateTimeImmutable())->format('Y-m-d H:i:s');
}
