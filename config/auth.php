<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function digilibEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function digilibCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function digilibVerifyCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function digilibBasePath(): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (in_array(basename($scriptDirectory), ['admin', 'anggota'], true)) {
        $scriptDirectory = dirname($scriptDirectory);
    }

    return $scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/');
}

function digilibUrl(string $path): string
{
    return digilibBasePath() . '/' . ltrim($path, '/');
}

function digilibRedirectForRole(string $role): never
{
    $destination = digilibUrl($role === 'anggota' ? 'anggota/index.php' : 'admin/index.php');
    header('Location: ' . $destination, true, 303);
    exit;
}

/**
 * @param list<string> $allowedRoles
 * @return array{user_id: int, nama_lengkap: string, email: string, role: string}
 */
function digilibRequireRole(array $allowedRoles): array
{
    $user = $_SESSION['digilib_user'] ?? null;
    if (!is_array($user) || !isset($user['user_id'], $user['role']) || !in_array($user['role'], $allowedRoles, true)) {
        header('Location: ' . digilibUrl('index.php?login=1'), true, 303);
        exit;
    }

    if ($user['role'] === 'anggota') {
        $pdo = digilibDatabaseConnection();
        $statement = $pdo->prepare(
            "SELECT a.`status` AS `status_anggota`, u.`status` AS `status_user`
             FROM `anggota` a
             INNER JOIN `users` u ON u.`user_id` = a.`user_id`
             WHERE a.`user_id` = :user_id AND u.`role` = 'anggota'
             LIMIT 1"
        );
        $statement->execute(['user_id' => $user['user_id']]);
        $account = $statement->fetch();

        if (!$account || $account['status_anggota'] !== 'aktif' || $account['status_user'] !== 'aktif') {
            unset($_SESSION['digilib_user']);
            session_regenerate_id(true);
            header('Location: ' . digilibUrl('index.php?account=inactive'), true, 303);
            exit;
        }
    }

    return $user;
}
