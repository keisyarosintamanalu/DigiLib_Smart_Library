<?php
declare(strict_types=1);

/**
 * Membuat koneksi PDO ke MySQL.
 *
 * Gunakan variabel DIGILIB_DB_* atau config/database.local.php untuk hosting.
 */
function digilibDatabaseConnection(): PDO
{
    $localConfigPath = __DIR__ . '/database.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
    if (!is_array($localConfig)) {
        throw new RuntimeException('Database local configuration must return an array.');
    }

    $setting = static function (string $environmentKey, string $configKey, string $default = '') use ($localConfig): string {
        $environmentValue = getenv($environmentKey);
        if ($environmentValue !== false && $environmentValue !== '') {
            return $environmentValue;
        }

        $configValue = $localConfig[$configKey] ?? null;
        return is_string($configValue) && $configValue !== '' ? $configValue : $default;
    };

    $host = $setting('DIGILIB_DB_HOST', 'host', '127.0.0.1');
    $port = $setting('DIGILIB_DB_PORT', 'port', '3306');
    $database = $setting('DIGILIB_DB_NAME', 'database', 'digilib_smart_library');
    $username = $setting('DIGILIB_DB_USER', 'username', 'root');
    $password = $setting('DIGILIB_DB_PASS', 'password');

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
