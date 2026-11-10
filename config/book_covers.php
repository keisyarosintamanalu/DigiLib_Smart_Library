<?php
declare(strict_types=1);

function digilibBookCoverUrl(?string $coverPath): ?string
{
    if ($coverPath === null || $coverPath === '' || basename($coverPath) !== $coverPath) {
        return null;
    }

    $coverDirectory = realpath(__DIR__ . '/../assets/uploads/covers');
    $coverFile = realpath(__DIR__ . '/../assets/uploads/covers/' . $coverPath);
    if ($coverDirectory === false
        || $coverFile === false
        || strpos($coverFile, $coverDirectory . DIRECTORY_SEPARATOR) !== 0
        || !is_file($coverFile)) {
        return null;
    }

    return digilibUrl('assets/uploads/covers/' . rawurlencode($coverPath));
}

function digilibUploadBookCover(string $temporaryPath, string $extension): string
{
    $directory = __DIR__ . '/../assets/uploads/covers';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan cover tidak dapat disiapkan.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Cover gagal disimpan. Periksa izin folder uploads/covers.');
    }

    return $filename;
}

function digilibRemoveBookCover(?string $coverPath): void
{
    if ($coverPath === null || $coverPath === '' || basename($coverPath) !== $coverPath) {
        return;
    }

    $coverDirectory = realpath(__DIR__ . '/../assets/uploads/covers');
    $coverFile = realpath(__DIR__ . '/../assets/uploads/covers/' . $coverPath);
    if ($coverDirectory !== false
        && $coverFile !== false
        && strpos($coverFile, $coverDirectory . DIRECTORY_SEPARATOR) === 0
        && is_file($coverFile)
        && !unlink($coverFile)) {
        error_log('DIGILIB could not remove book cover: ' . $coverFile);
    }
}
