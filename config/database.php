<?php
declare(strict_types=1);

/**
 * Membuat koneksi PDO ke MySQL.
 *
 * Variabel lingkungan DIGILIB_DB_* dapat digunakan untuk mengganti
 * pengaturan default Laragon tanpa mengubah file ini.
 */
function digilibDatabaseConnection(): PDO
{
    $host = getenv('DIGILIB_DB_HOST') ?: '127.0.0.1';
    $port = getenv('DIGILIB_DB_PORT') ?: '3306';
    $database = getenv('DIGILIB_DB_NAME') ?: 'digilib_smart_library';
    $username = getenv('DIGILIB_DB_USER') ?: 'root';
    $password = getenv('DIGILIB_DB_PASS');
    $password = $password === false ? '' : $password;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
