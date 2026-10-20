<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !digilibVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo 'Permintaan logout tidak valid.';
    exit;
}

$_SESSION = [];
$cookie = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: ' . digilibUrl('index.php') . '?logged_out=1', true, 303);
exit;
