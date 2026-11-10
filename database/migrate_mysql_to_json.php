<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$localConfigPath = __DIR__ . '/../config/database.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('Legacy database configuration must return an array.');
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
$name = $setting('DIGILIB_DB_NAME', 'database', 'digilib_smart_library');
$username = $setting('DIGILIB_DB_USER', 'username', 'root');
$password = $setting('DIGILIB_DB_PASS', 'password');
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$legacyDatabase = [];
foreach (['users', 'kategori', 'anggota', 'buku', 'peminjaman', 'pengembalian', 'denda'] as $table) {
    $legacyDatabase[$table] = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
}

digilibDatabaseTransaction(static function (array &$database) use ($legacyDatabase): void {
    if (is_file(digilibDatabaseFile())) {
        throw new RuntimeException('JSON database already exists; migration stopped to avoid overwriting data.');
    }

    foreach ($legacyDatabase as $table => $records) {
        $database[$table] = $records;
    }
});

echo "MySQL records have been imported into database/data.json.\n";
foreach ($legacyDatabase as $table => $records) {
    echo $table . ': ' . count($records) . PHP_EOL;
}
